<?php
declare(strict_types=1);

namespace App\Services\Notifications;

use RuntimeException;

/** Email via PHP mail() or a minimal SMTP client (STARTTLS/SSL + AUTH LOGIN). No dependencies. */
final class EmailChannel implements Channel
{
    public function name(): string
    {
        return 'email';
    }

    public function isConfigured(): bool
    {
        $driver = (string) config('mail.driver', 'none');
        return ($driver === 'mail' || ($driver === 'smtp' && config('mail.host'))) && filter_var(config('mail.from'), FILTER_VALIDATE_EMAIL);
    }

    public function send(string $recipient, string $subject, string $body, array $options = []): bool
    {
        if (!$this->isConfigured()) {
            throw new RuntimeException('Email is not configured');
        }
        if (!filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            throw new RuntimeException('Invalid recipient');
        }
        $subject = str_replace(["\r", "\n"], ' ', $subject);
        $from = (string) config('mail.from');
        $fromName = str_replace(["\r", "\n", '"'], '', (string) ($options['from_name'] ?? config('mail.from_name', '')));
        $html = (bool) ($options['html'] ?? true);

        $headers = [
            'From' => $fromName !== '' ? sprintf('"%s" <%s>', $fromName, $from) : $from,
            'MIME-Version' => '1.0',
            'Content-Type' => ($html ? 'text/html' : 'text/plain') . '; charset=UTF-8',
            'Content-Transfer-Encoding' => 'base64',
            'Date' => date('r'),
            'Message-ID' => sprintf('<%s@%s>', bin2hex(random_bytes(12)), substr(strrchr($from, '@') ?: '@localhost', 1)),
        ];
        if (!empty($options['reply_to']) && filter_var($options['reply_to'], FILTER_VALIDATE_EMAIL)) {
            $headers['Reply-To'] = $options['reply_to'];
        }
        $encodedSubject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $encodedBody = chunk_split(base64_encode($body));

        if (config('mail.driver') === 'mail') {
            $h = '';
            foreach ($headers as $k => $v) {
                $h .= "$k: $v\r\n";
            }
            if (!mail($recipient, $encodedSubject, $encodedBody, $h)) {
                throw new RuntimeException('mail() failed');
            }
            return true;
        }
        return $this->smtp($from, $recipient, $encodedSubject, $headers, $encodedBody);
    }

    private function smtp(string $from, string $to, string $subject, array $headers, string $body): bool
    {
        $host = (string) config('mail.host');
        $port = (int) config('mail.port', 587);
        $enc = (string) config('mail.encryption', 'tls');
        $remote = ($enc === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $ctx = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $fp = @stream_socket_client($remote, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            throw new RuntimeException("SMTP connect failed: $errstr");
        }
        stream_set_timeout($fp, 15);
        $read = static function () use ($fp): string {
            $data = '';
            while (($line = fgets($fp, 515)) !== false) {
                $data .= $line;
                if (isset($line[3]) && $line[3] === ' ') {
                    break;
                }
            }
            return $data;
        };
        $cmd = static function (string $c, array $ok) use ($fp, $read): string {
            fwrite($fp, $c . "\r\n");
            $r = $read();
            if (!in_array((int) substr($r, 0, 3), $ok, true)) {
                throw new RuntimeException('SMTP error: ' . trim($r));
            }
            return $r;
        };
        try {
            $read();
            $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');
            $cmd($ehlo, [250]);
            if ($enc === 'tls') {
                $cmd('STARTTLS', [220]);
                if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new RuntimeException('STARTTLS failed');
                }
                $cmd($ehlo, [250]);
            }
            if ((string) config('mail.username') !== '') {
                $cmd('AUTH LOGIN', [334]);
                $cmd(base64_encode((string) config('mail.username')), [334]);
                $cmd(base64_encode((string) config('mail.password')), [235]);
            }
            $cmd("MAIL FROM:<$from>", [250]);
            $cmd("RCPT TO:<$to>", [250, 251]);
            $cmd('DATA', [354]);
            $msg = "To: <$to>\r\nSubject: $subject\r\n";
            foreach ($headers as $k => $v) {
                $msg .= "$k: $v\r\n";
            }
            $msg .= "\r\n" . str_replace("\n.", "\n..", $body) . "\r\n.";
            $cmd($msg, [250]);
            $cmd('QUIT', [221]);
        } finally {
            fclose($fp);
        }
        return true;
    }
}
