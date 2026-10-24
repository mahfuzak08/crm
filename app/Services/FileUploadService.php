<?php
declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use App\Core\ValidationException;

/**
 * Secure uploads:
 *  - extension whitelist + server-side MIME sniffing (client MIME ignored)
 *  - executable / double extensions rejected
 *  - images re-encoded with GD (strips metadata and polyglot payloads)
 *  - random storage names, files kept under /storage (outside web root)
 */
final class FileUploadService
{
    private const ALLOWED = [
        'image' => [
            'jpg'  => ['image/jpeg'],
            'jpeg' => ['image/jpeg'],
            'png'  => ['image/png'],
            'webp' => ['image/webp'],
        ],
        'document' => [
            'pdf'  => ['application/pdf'],
            'doc'  => ['application/msword', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'],
            'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip', 'application/octet-stream'],
            'xls'  => ['application/vnd.ms-excel', 'application/vnd.ms-office', 'application/x-ole-storage', 'application/CDFV2'],
            'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/zip', 'application/octet-stream'],
        ],
        'video' => [
            'mp4'  => ['video/mp4'],
            'webm' => ['video/webm'],
        ],
    ];

    private const DANGEROUS = ['php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'phar', 'pht', 'phps', 'cgi', 'pl', 'py', 'sh', 'asp', 'aspx', 'jsp', 'exe', 'bat', 'cmd', 'com', 'js', 'html', 'htm', 'svg', 'shtml', 'htaccess', 'ini'];

    public const MIME_BY_EXT = [
        'jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp',
        'pdf' => 'application/pdf', 'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel', 'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'mp4' => 'video/mp4', 'webm' => 'video/webm',
    ];

    /**
     * @param string[] $categories e.g. ['image'] or ['image','document']
     * @return array{stored_name:string, original_name:string, mime:string, size:int, ext:string, category:string}
     */
    public static function store(array $file, array $categories, string $field = 'file', ?int $maxMb = null): array
    {
        $maxMb ??= (int) config('upload.max_size_mb', 10);
        $label = ucfirst(str_replace('_', ' ', $field));

        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            $msg = match ($file['error'] ?? 0) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => "$label is too large.",
                UPLOAD_ERR_PARTIAL => "$label was only partially uploaded.",
                default => "$label could not be uploaded.",
            };
            throw new ValidationException([$field => $msg]);
        }
        if (!is_uploaded_file($file['tmp_name'])) {
            throw new ValidationException([$field => "$label is invalid."]);
        }
        $size = (int) $file['size'];
        if ($size <= 0 || $size > $maxMb * 1024 * 1024) {
            throw new ValidationException([$field => "$label must be smaller than {$maxMb} MB."]);
        }

        $original = safe_filename((string) $file['name']);
        $parts = explode('.', strtolower($original));
        $ext = count($parts) > 1 ? array_pop($parts) : '';
        foreach ($parts as $p) { // double extension e.g. shell.php.jpg
            if (in_array($p, self::DANGEROUS, true)) {
                Logger::security('Upload rejected: dangerous double extension', ['name' => $original]);
                throw new ValidationException([$field => "$label has an invalid file name."]);
            }
        }

        $category = null;
        foreach ($categories as $cat) {
            if (isset(self::ALLOWED[$cat][$ext])) {
                $category = $cat;
                break;
            }
        }
        if ($category === null) {
            $allowed = [];
            foreach ($categories as $cat) {
                $allowed = [...$allowed, ...array_keys(self::ALLOWED[$cat] ?? [])];
            }
            throw new ValidationException([$field => "$label must be one of: " . implode(', ', $allowed) . '.']);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']) ?: 'application/octet-stream';
        if (!in_array($mime, self::ALLOWED[$category][$ext], true)) {
            Logger::security('Upload rejected: MIME mismatch', ['name' => $original, 'mime' => $mime]);
            throw new ValidationException([$field => "$label content does not match its file type."]);
        }
        // Office Open XML files are zips: make sure it really is an OOXML document.
        if (in_array($ext, ['docx', 'xlsx'], true) && !self::isOoxml($file['tmp_name'])) {
            throw new ValidationException([$field => "$label is not a valid Office document."]);
        }

        $rel = self::relativePath($ext);
        $dest = self::absolute($rel);
        if (!is_dir(dirname($dest)) && !mkdir(dirname($dest), 0750, true) && !is_dir(dirname($dest))) {
            throw new \RuntimeException('Upload directory not writable');
        }

        if ($category === 'image') {
            self::reencodeImage($file['tmp_name'], $dest, $ext, $field, $label);
            $size = (int) filesize($dest);
        } elseif (!move_uploaded_file($file['tmp_name'], $dest)) {
            throw new \RuntimeException('Could not move uploaded file');
        }
        @chmod($dest, 0640);

        return [
            'stored_name' => $rel,
            'original_name' => $original,
            'mime' => self::MIME_BY_EXT[$ext],
            'size' => $size,
            'ext' => $ext,
            'category' => $category,
        ];
    }

    /** Absolute path of a stored file; rejects anything not produced by store(). */
    public static function absolute(string $stored): string
    {
        if (!preg_match('#^\d{1,10}/\d{4}/\d{2}/[a-f0-9]{32}\.[a-z0-9]{2,5}$#', $stored)) {
            throw new \App\Core\HttpException(404);
        }
        return STORAGE_PATH . '/private/' . $stored;
    }

    public static function delete(?string $stored): void
    {
        if ($stored) {
            try {
                $p = self::absolute($stored);
                if (is_file($p)) {
                    @unlink($p);
                }
            } catch (\Throwable) {
            }
        }
    }

    private static function relativePath(string $ext): string
    {
        $cid = (int) ($_SESSION['cid'] ?? 0);
        return sprintf('%d/%s/%s/%s.%s', $cid, date('Y'), date('m'), bin2hex(random_bytes(16)), $ext);
    }

    private static function reencodeImage(string $src, string $dest, string $ext, string $field, string $label): void
    {
        $info = @getimagesize($src);
        if ($info === false || $info[0] < 1 || $info[1] < 1 || $info[0] * $info[1] > 40_000_000) {
            throw new ValidationException([$field => "$label is not a valid image."]);
        }
        $img = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($src),
            IMAGETYPE_PNG  => @imagecreatefrompng($src),
            IMAGETYPE_WEBP => @imagecreatefromwebp($src),
            default => false,
        };
        if (!$img) {
            throw new ValidationException([$field => "$label is not a valid image."]);
        }
        // Downscale very large images to keep things light.
        $w = imagesx($img);
        $h = imagesy($img);
        $max = 2000;
        if ($w > $max || $h > $max) {
            $ratio = min($max / $w, $max / $h);
            $resized = imagescale($img, (int) round($w * $ratio), (int) round($h * $ratio));
            if ($resized) {
                imagedestroy($img);
                $img = $resized;
            }
        }
        $ok = match ($ext) {
            'png'  => (imagesavealpha($img, true) && imagepng($img, $dest, 6)),
            'webp' => imagewebp($img, $dest, 85),
            default => imagejpeg($img, $dest, 85),
        };
        imagedestroy($img);
        if (!$ok) {
            throw new \RuntimeException('Image processing failed');
        }
    }

    private static function isOoxml(string $path): bool
    {
        if (!class_exists(\ZipArchive::class)) {
            return true;
        }
        $zip = new \ZipArchive();
        if ($zip->open($path) !== true) {
            return false;
        }
        $ok = $zip->locateName('[Content_Types].xml') !== false;
        $zip->close();
        return $ok;
    }
}
