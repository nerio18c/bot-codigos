<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Webklex\IMAP\Facades\Client;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ProcesarStreaming extends Command
{
    protected $signature = 'procesar:streaming';
    protected $description = 'Extrae pines de streaming del Gmail central';

    public function handle()
    {
        $centralEmail = (string) env('IMAP_USERNAME', '');
        if ($centralEmail === '') {
            $this->error('IMAP_USERNAME no esta configurado en el .env');
            return Command::FAILURE;
        }
        $imapPassword = (string) env('IMAP_PASSWORD', '');
        if ($imapPassword === '') {
            $this->error('IMAP_PASSWORD no esta configurado en el .env');
            return Command::FAILURE;
        }

        $client = Client::make([
            'host'          => env('IMAP_HOST', 'imap.gmail.com'),
            'port'          => (int) env('IMAP_PORT', 993),
            'encryption'    => env('IMAP_ENCRYPTION', 'ssl'),
            'validate_cert' => (bool) env('IMAP_VALIDATE_CERT', true),
            'username'      => $centralEmail,
            'password'      => $imapPassword,
            'protocol'      => env('IMAP_PROTOCOL', 'imap'),
        ]);

        $client->connect();
        // Procesamos bandejas configuradas (por defecto INBOX + Spam Gmail)
        $foldersEnv = (string) env('IMAP_FOLDERS', 'INBOX,[Gmail]/Spam');
        $folders = array_values(array_filter(array_map('trim', explode(',', $foldersEnv))));
        if (empty($folders)) {
            $folders = ['INBOX', '[Gmail]/Spam'];
        }

        foreach ($folders as $folderName) {
            $folder = $client->getFolder($folderName);
            $messages = $folder->query()
                ->unseen()
                ->since(Carbon::now()->subMinutes(30))
                ->get();

            $messageList = [];
            foreach ($messages as $message) {
                $messageList[] = $message;
            }
            usort($messageList, function ($a, $b) {
                $dateA = $this->obtenerFechaMensajeUtc($a);
                $dateB = $this->obtenerFechaMensajeUtc($b);
                return $dateA <=> $dateB;
            });

            foreach ($messageList as $message) {
                $cuerpo = $message->getTextBody() ?: $message->getHtmlBody();

                $subject = strtolower((string) $message->getSubject());
                $from = '';
                $fromList = $message->getFrom();
                if (!empty($fromList) && isset($fromList[0]->mail)) {
                    $from = strtolower((string) $fromList[0]->mail);
                }

                $bodyHtml = (string) ($message->getHtmlBody() ?: '');
                $bodyText = strtolower(strip_tags((string) $cuerpo . ' ' . $bodyHtml));
                $plataforma = $this->detectarPlataforma($subject, $from, $bodyText);

                // Netflix suele usar 4 digitos, otros pueden usar 6
                $pin = $this->extraerPin($bodyText, $plataforma);
                $email = $this->extraerEmailDestino($message, $bodyText, $from, $centralEmail);

                if ($plataforma && $pin && $email) {
                    // Evitar guardar correos de plataforma como email_cuenta
                    if ($this->esRemitentePlataforma($email)) {
                        continue;
                    }
                    $existing = DB::table('codigos_streaming')
                        ->where('email_cuenta', trim($email))
                        ->where('plataforma', $plataforma)
                        ->first();

                    $receivedAtUtc = $this->obtenerFechaMensajeUtc($message);

                    // Solo reemplazar si el mensaje es mas nuevo que el existente
                    if (!$existing || Carbon::parse($existing->created_at, 'UTC')->lt($receivedAtUtc)) {
                        DB::table('codigos_streaming')->updateOrInsert(
                            [
                                'email_cuenta' => trim($email),
                                'plataforma' => $plataforma,
                            ],
                            [
                                'pin' => $pin,
                                'updated_at' => Carbon::now('UTC'),
                                'created_at' => $receivedAtUtc
                            ]
                        );
                    }

                    $message->setFlag('Seen'); // Marcar como leido
                    $this->info('Codigo guardado para: ' . $email . ' (' . $folderName . ')');
                }
            }
        }
    }

    private function detectarPlataforma(string $subject, string $from, string $bodyText): ?string
    {
        $hayNetflix = str_contains($subject, 'netflix') || str_contains($from, 'netflix') || str_contains($bodyText, 'netflix');
        if ($hayNetflix) {
            return 'netflix';
        }

        $hayPrime = str_contains($subject, 'prime video')
            || str_contains($subject, 'amazon')
            || str_contains($from, 'amazon')
            || str_contains($bodyText, 'prime video')
            || str_contains($bodyText, 'amazon');
        if ($hayPrime) {
            return 'prime';
        }

        $hayDisney = str_contains($subject, 'disney') || str_contains($from, 'disney') || str_contains($bodyText, 'disney');
        if ($hayDisney) {
            return 'disney';
        }

        return null;
    }

    private function obtenerFechaMensajeUtc($message): Carbon
    {
        $receivedAtRaw = $message->getDate();
        if ($receivedAtRaw instanceof \Carbon\CarbonInterface) {
            $receivedAt = Carbon::instance($receivedAtRaw);
        } elseif ($receivedAtRaw instanceof \DateTimeInterface) {
            $receivedAt = Carbon::instance($receivedAtRaw);
        } elseif ($receivedAtRaw) {
            $receivedAt = Carbon::parse((string) $receivedAtRaw, 'UTC');
        } else {
            $receivedAt = Carbon::now('UTC');
        }

        return $receivedAt->copy()->setTimezone('UTC');
    }

    private function extraerPin(string $bodyText, ?string $plataforma): ?string
    {
        $textKeywords = $this->normalizarTexto($bodyText);

        if ($plataforma === 'netflix') {
            if (preg_match('/(code|codigo)[^\d]{0,30}((\d\s?){4,8})/i', $textKeywords, $match)) {
                $pin = preg_replace('/\s+/', '', $match[2]);
                if ($this->esPinValido($pin, $plataforma)) {
                    return $pin;
                }
            }
        }

        // Prioritize keywords near the code to avoid picking unrelated numbers
        if ($plataforma === 'prime') {
            $primePatterns = [
                '/si eras tu[^0-9]{0,60}codigo de verificacion es[^0-9]{0,20}(\d{6})/i',
                '/(codigo de verificacion|verification code|your code is|code is)[^0-9]{0,30}(\d{6})/i',
            ];
            $primePin = $this->extraerUltimoMatch($textKeywords, $primePatterns, 6);
            if ($primePin !== null) {
                return $primePin;
            }
        }

        $keywordPatterns = [
            '/(codigo de verificacion|verification code|your code is|code is|codigo es|one-time password|otp)[^0-9]{0,30}(\d{4,8})/i',
            '/\b(\d{4,8})\b[^a-z0-9]{0,10}(codigo|code|otp)/i',
            '/si eras tu[^0-9]{0,60}codigo de verificacion es[^0-9]{0,20}(\d{4,8})/i',
        ];
        $keywordPin = $this->extraerUltimoMatch($textKeywords, $keywordPatterns, null);
        if ($keywordPin !== null) {
            return $keywordPin;
        }

        if (preg_match('/(\d\s){3,7}\d/', $bodyText, $match)) {
            $pin = preg_replace('/\s+/', '', $match[0]);
            if ($this->esPinValido($pin, $plataforma)) {
                return $pin;
            }
        }

        if (preg_match_all('/\b\d{4,8}\b/', $bodyText, $matches)) {
            $lastValid = null;
            foreach ($matches[0] as $candidate) {
                if ($this->esPinValido($candidate, $plataforma)) {
                    $lastValid = $candidate;
                }
            }
            if ($lastValid !== null) {
                return $lastValid;
            }
        }

        return null;
    }

    private function extraerUltimoMatch(string $text, array $patterns, ?int $exactLen): ?string
    {
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches, PREG_SET_ORDER)) {
                $last = end($matches);
                if (!$last) {
                    continue;
                }
                $pin = $last[2] ?? $last[1] ?? null;
                if ($pin === null) {
                    continue;
                }
                if ($exactLen !== null && strlen($pin) !== $exactLen) {
                    continue;
                }
                if ($this->esPinValido($pin, null)) {
                    return $pin;
                }
            }
        }

        return null;
    }

    private function extraerEmailDestino($message, string $bodyText, string $from, string $centralEmail): ?string
    {
        $forwardedEmail = $this->extraerEmailDesdeHeaders($message, $centralEmail);
        if ($forwardedEmail) {
            return $forwardedEmail;
        }

        if ($from && $from !== $centralEmail && !$this->esRemitentePlataforma($from)) {
            return $from;
        }

        if (preg_match('/(para|to|original recipient|destinatario):\s*([\w._%+-]+@[\w.-]+\.[a-z]{2,})/i', $bodyText, $match)) {
            $candidate = strtolower($match[2]);
            if ($candidate !== $centralEmail) {
                return $candidate;
            }
        }

        if (preg_match_all('/[\w._%+-]+@[\w.-]+\.[a-z]{2,}/i', $bodyText, $matches)) {
            foreach ($matches[0] as $email) {
                $email = strtolower($email);
                if ($email !== $from && $email !== $centralEmail && !$this->esRemitentePlataforma($email)) {
                    return $email;
                }
            }
        }

        $toList = $message->getTo();
        if (!empty($toList) && isset($toList[0]->mail)) {
            $email = strtolower((string) $toList[0]->mail);
            if ($email !== $centralEmail) {
                return $email;
            }
        }

        return null;
    }

    private function extraerEmailDesdeHeaders($message, string $centralEmail): ?string
    {
        $header = $message->getHeader();
        if (!$header) {
            return null;
        }

        $headerKeys = [
            'x_original_to',
            'x_forwarded_to',
            'x_envelope_to',
            'delivered_to',
            'envelope_to',
            'original_to',
            'to',
        ];

        foreach ($headerKeys as $key) {
            $attr = $header->get($key);
            if (!$attr) {
                continue;
            }
            $value = strtolower((string) $attr);
            if (preg_match('/[\w._%+-]+@[\w.-]+\.[a-z]{2,}/i', $value, $match)) {
                $email = strtolower($match[0]);
                if ($email !== $centralEmail && !$this->esRemitentePlataforma($email)) {
                    return $email;
                }
            }
        }

        return null;
    }

    private function esRemitentePlataforma(string $email): bool
    {
        $email = strtolower($email);
        $domains = [
            '@amazon.com',
            '@primevideo.com',
            '@netflix.com',
            '@disney.com',
            '@disneyplus.com',
            '@disneyplus.com.br',
            '@disneyplus.es',
        ];
        foreach ($domains as $domain) {
            if (str_contains($email, $domain)) {
                return true;
            }
        }
        if (str_contains($email, 'amazon.') || str_contains($email, 'primevideo.')) {
            return true;
        }

        return false;
    }

    private function normalizarTexto(string $text): string
    {
        $text = strtolower($text);
        if (function_exists('iconv')) {
            $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
            if ($converted !== false && $converted !== '') {
                return $converted;
            }
        }

        return $text;
    }

    private function esPinValido(string $pin, ?string $plataforma): bool
    {
        $pin = trim($pin);
        if (!ctype_digit($pin)) {
            return false;
        }

        $len = strlen($pin);
        if ($len < 4 || $len > 8) {
            return false;
        }

        // Evitar anos tipo 2026
        $num = (int) $pin;
        if ($len === 4 && $num >= 2000 && $num <= 2099) {
            return false;
        }

        if ($plataforma === 'netflix') {
            return $len === 4;
        }

        return true;
    }
}
