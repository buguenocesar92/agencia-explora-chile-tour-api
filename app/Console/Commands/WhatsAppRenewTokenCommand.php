<?php

namespace App\Console\Commands;

use App\Services\WhatsAppTokenService;
use Illuminate\Console\Command;

class WhatsAppRenewTokenCommand extends Command
{
    /**
     * El nombre y la firma del comando.
     *
     * @var string
     */
    protected $signature = 'whatsapp:renew-token';

    /**
     * La descripción del comando.
     *
     * @var string
     */
    protected $description = 'Renueva automáticamente el token de WhatsApp Business API';

    /**
     * Servicio para gestionar los tokens de WhatsApp
     */
    protected WhatsAppTokenService $tokenService;

    /**
     * Crear una nueva instancia del comando.
     */
    public function __construct(WhatsAppTokenService $tokenService)
    {
        parent::__construct();
        $this->tokenService = $tokenService;
    }

    /**
     * Ejecutar el comando.
     */
    public function handle()
    {
        $this->info('Iniciando renovación automática del token de WhatsApp...');

        $success = $this->tokenService->renewTokenAutomatically();

        if ($success) {
            $this->info('Token de WhatsApp renovado correctamente.');
            return 0;
        } else {
            $this->error('Error al renovar el token de WhatsApp. Revisa los logs para más información.');

            // Mostrar instrucciones para renovación manual
            $this->newLine();
            $this->info('Para renovar el token manualmente, sigue estos pasos:');
            $this->info('1. Ve a https://developers.facebook.com/');
            $this->info('2. Selecciona tu aplicación');
            $this->info('3. Ve a "WhatsApp" > "API Setup"');
            $this->info('4. En "Temporary access token", haz clic en "Generate new token"');
            $this->info('5. Copia el token generado');
            $this->info('6. Ejecuta: php artisan whatsapp:token [token]');

            return 1;
        }
    }
}
