<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class WebPushGenerateKeysCommand extends Command
{
    protected $signature = 'webpush:generate-keys';

    protected $description = 'Generar claves VAPID para Web Push y escribirlas en .env';

    public function handle(): int
    {
        $pkey = openssl_pkey_new([
            'private_key_type' => OPENSSL_KEYTYPE_EC,
            'curve_name' => 'prime256v1',
        ]);

        if (! $pkey) {
            $this->error('No se pudo generar el par de claves VAPID.');

            return self::FAILURE;
        }

        $details = openssl_pkey_get_details($pkey);

        if (! isset($details['ec']['x'], $details['ec']['y'], $details['ec']['d'])) {
            $this->error('No se pudieron extraer los detalles de la clave EC.');

            return self::FAILURE;
        }

        $x = hex2bin($details['ec']['x']);
        $y = hex2bin($details['ec']['y']);
        $d = hex2bin($details['ec']['d']);

        $publicKey = $this->base64UrlEncode("\x04".$x.$y);
        $privateKey = $this->base64UrlEncode($d);

        $this->writeToEnv('VAPID_PUBLIC_KEY', $publicKey);
        $this->writeToEnv('VAPID_PRIVATE_KEY', $privateKey);

        $this->info('Claves VAPID generadas:');
        $this->line('VAPID_PUBLIC_KEY='.$publicKey);
        $this->line('VAPID_PRIVATE_KEY='.$privateKey);
        $this->newLine();
        $this->info('Se han añadido a tu archivo .env. Asegúrate de reiniciar el contenedor para recargar variables.');

        return self::SUCCESS;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function writeToEnv(string $key, string $value): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);
        $pattern = '/^'.$key.'=.*$/m';

        if (preg_match($pattern, $content)) {
            $content = preg_replace($pattern, $key.'='.$value, $content);
        } else {
            $content .= "\n{$key}={$value}\n";
        }

        file_put_contents($envPath, $content);
    }
}
