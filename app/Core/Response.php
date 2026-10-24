<?php
declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function securityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header_remove('X-Powered-By');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=(), payment=()');
        header("Content-Security-Policy: default-src 'self'; script-src 'self'; style-src 'self' 'unsafe-inline'; "
            . "img-src 'self' data: blob: https://img.youtube.com https://i.vimeocdn.com; font-src 'self' data:; connect-src 'self'; "
            . "frame-src https://www.youtube.com https://www.youtube-nocookie.com https://player.vimeo.com; "
            . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'self'");
        if (Request::isHttps()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function redirect(string $to, int $status = 302): never
    {
        $url = preg_match('#^https?://#i', $to) ? $to : url($to);
        header('Location: ' . $url, true, $status);
        exit;
    }

    /** Redirect to previous page (same-origin only) or fallback. */
    public static function back(string $fallback = '/'): never
    {
        $ref = $_SERVER['HTTP_REFERER'] ?? '';
        $base = app_url();
        if ($ref !== '' && $base !== '' && str_starts_with($ref, $base . '/')) {
            header('Location: ' . $ref, true, 302);
            exit;
        }
        self::redirect($fallback);
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }

    public static function html(string $html, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: text/html; charset=utf-8');
        echo $html;
        exit;
    }

    /** Stream a CSV download from an iterable of rows. */
    public static function csv(string $filename, array $headers, iterable $rows): never
    {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . safe_filename($filename) . '"');
        header('Cache-Control: no-store');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
        fputcsv($out, $headers);
        foreach ($rows as $row) {
            // Neutralise spreadsheet formula injection.
            $row = array_map(static function ($v) {
                $v = (string) $v;
                return $v !== '' && in_array($v[0], ['=', '+', '-', '@', "\t", "\r"], true) && !is_numeric($v) ? "'" . $v : $v;
            }, array_values($row));
            fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }

    /** Stream a private file with safe headers. */
    public static function file(string $path, string $downloadName, string $mime, bool $inline = false): never
    {
        if (!is_file($path)) {
            throw new HttpException(404);
        }
        $name = safe_filename($downloadName);
        $disposition = $inline ? 'inline' : 'attachment';
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header(sprintf('Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s', $disposition, $name, rawurlencode($name)));
        header('X-Content-Type-Options: nosniff');
        header("Content-Security-Policy: default-src 'none'; img-src 'self'; style-src 'unsafe-inline'; sandbox");
        header('Cache-Control: private, max-age=300');
        readfile($path);
        exit;
    }
}
