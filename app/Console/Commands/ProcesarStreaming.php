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
        $centralEmail = 'margaret62pr@gmail.com';
        $client = Client::make([
            'host'          => 'imap.gmail.com',
            'port'          => 993,
            'encryption'    => 'ssl',
            'validate_cert' => true,
            'username'      => $centralEmail,
            'password'      => 'yyinrherenaqgaas',
            'protocol'      => 'imap'
        ]);

        $client->connect();
        // Procesamos bandeja de entrada y Spam
        $folders = [
            'INBOX',
            '[Gmail]/Spam',
        ];

        foreach ($folders as $folderName) {
            $folder = $client->getFolder($folderName);
            $messages = $folder->query()
                ->since(Carbon::now()->subMinutes(30))
                ->get();

            foreach ($messages as $message) {
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
                    $receivedAtRaw = $message->getDate();
                    if ($receivedAtRaw instanceof \Carbon\CarbonInterface) {
                        $receivedAt = $receivedAtRaw;
                    } elseif ($receivedAtRaw instanceof \DateTimeInterface) {
                        $receivedAt = Carbon::instance($receivedAtRaw);
                    } elseif ($receivedAtRaw) {
                        $receivedAt = Carbon::parse((string) $receivedAtRaw, 'UTC');
                    } else {
                        $receivedAt = Carbon::now();
                    }

                    $existing = DB::table('codigos_streaming')
                        ->where('email_cuenta', trim($email))
                        ->where('plataforma', $plataforma)
                        ->first();

                    $receivedAtUtc = $receivedAt->copy()->setTimezone('UTC');

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

    private function extraerPin(string $bodyText, ?string $plataforma): ?string
    {
        if ($plataforma === 'netflix') {
            if (preg_match('/(code|codigo|c[oó]digo)[^\\d]{0,30}((\\d\\s?){4,8})/i', $bodyText, $match)) {
                $pin = preg_replace('/\\s+/', '', $match[2]);
                if ($this->esPinValido($pin, $plataforma)) {
                    return $pin;
                }
            }
        }

        if (preg_match('/(\d\s){3,7}\d/', $bodyText, $match)) {
            $pin = preg_replace('/\s+/', '', $match[0]);
            if ($this->esPinValido($pin, $plataforma)) {
                return $pin;
            }
        }

        if (preg_match_all('/\b\d{4,8}\b/', $bodyText, $matches)) {
            foreach ($matches[0] as $candidate) {
                if ($this->esPinValido($candidate, $plataforma)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function extraerEmailDestino($message, string $bodyText, string $from, string $centralEmail): ?string
    {
        if ($from && $from !== $centralEmail) {
            if (!str_contains($from, 'netflix.com') && !str_contains($from, 'amazon.') && !str_contains($from, 'disney')) {
                return $from;
            }
        }

        if (preg_match('/(para|to):\s*([\w._%+-]+@[\w.-]+\.[a-z]{2,})/i', $bodyText, $match)) {
            $candidate = strtolower($match[2]);
            if ($candidate !== $centralEmail) {
                return $candidate;
            }
        }

        if (preg_match_all('/[\w._%+-]+@[\w.-]+\.[a-z]{2,}/i', $bodyText, $matches)) {
            foreach ($matches[0] as $email) {
                $email = strtolower($email);
                if ($email !== $from && $email !== $centralEmail && !str_contains($email, 'netflix.com')) {
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

        return $centralEmail;
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
