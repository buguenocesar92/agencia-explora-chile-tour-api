<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class WhatsAppTokenCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'whatsapp:token {token? : El nuevo token de WhatsApp}';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Actualiza el token de WhatsApp en el archivo .env';

    /**
     * Ejecutar el comando.
     */
    public function handle()
    {
        // Si no se proporciona un token, solicitar al usuario
        $token = $this->argument('token');
        if (!$token) {
            $this->info('Para obtener un nuevo token, sigue estos pasos:');
            $this->info('1. Ve a https://developers.facebook.com/');
            $this->info('2. Selecciona tu aplicación');
            $this->info('3. Ve a "WhatsApp" > "API Setup"');
            $this->info('4. En "Temporary access token", haz clic en "Generate new token"');
            $this->info('5. Copia el token generado');
            $this->newLine();

            $token = $this->secret('Ingresa el nuevo token de WhatsApp:');
            if (empty($token)) {
                $this->error('Token no proporcionado. Operación cancelada.');
                return 1;
            }
        }

        // Actualizar el archivo .env
        try {
            $envFile = base_path('.env');
            $envContent = File::get($envFile);

            // Reemplazar la variable WHATSAPP_API_TOKEN
            $pattern = '/^WHATSAPP_API_TOKEN=.*/m';
            $replacement = "WHATSAPP_API_TOKEN=\"{$token}\"";

            if (preg_match($pattern, $envContent)) {
                // Si la variable ya existe, reemplazarla
                $newEnvContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                // Si no existe, añadirla al final
                $newEnvContent = $envContent . PHP_EOL . $replacement;
            }

            // Guardar el archivo
            File::put($envFile, $newEnvContent);

            // Limpiar caché de configuración
            $this->call('config:clear');

            $this->info('Token de WhatsApp actualizado correctamente.');
            return 0;
        } catch (\Exception $e) {
            $this->error('Error al actualizar el token: ' . $e->getMessage());
            return 1;
        }
    }
}
