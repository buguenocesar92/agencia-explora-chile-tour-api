<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class WhatsAppTokenService
{
    // Configuraciones para la API de Facebook
    private $appId;
    private $appSecret;
    private $systemUserId;
    private $systemUserToken;

    public function __construct()
    {
        $this->appId = env('FACEBOOK_APP_ID');
        $this->appSecret = env('FACEBOOK_APP_SECRET');
        $this->systemUserId = env('FACEBOOK_SYSTEM_USER_ID');
        $this->systemUserToken = env('FACEBOOK_SYSTEM_USER_TOKEN');
    }

    /**
     * Obtiene un token de larga duración usando un token de corta duración
     *
     * @param string $shortLivedToken Token de acceso de corta duración
     * @return string|null Token de larga duración o null en caso de error
     */
    public function getLongLivedToken(string $shortLivedToken): ?string
    {
        try {
            $response = Http::get('https://graph.facebook.com/v17.0/oauth/access_token', [
                'grant_type' => 'fb_exchange_token',
                'client_id' => $this->appId,
                'client_secret' => $this->appSecret,
                'fb_exchange_token' => $shortLivedToken
            ]);

            if ($response->successful() && isset($response['access_token'])) {
                return $response['access_token'];
            }

            Log::error('WhatsAppTokenService::getLongLivedToken - Error al obtener token de larga duración', [
                'response' => $response->json()
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('WhatsAppTokenService::getLongLivedToken - Excepción', [
                'message' => $e->getMessage()
            ]);

            return null;
        }
    }

    /**
     * Renueva automáticamente el token de WhatsApp usando un System User Access Token
     * Este método requiere configurar un System User en Facebook Business Manager
     *
     * @return bool True si se renovó correctamente, False en caso contrario
     */
    public function renewTokenAutomatically(): bool
    {
        try {
            // Verificar que tenemos la configuración necesaria
            if (empty($this->systemUserId) || empty($this->systemUserToken)) {
                Log::error('WhatsAppTokenService::renewTokenAutomatically - Falta configuración de System User');
                return false;
            }

            // Obtener un token nuevo usando el System User Token
            $response = Http::withToken($this->systemUserToken)
                ->get("https://graph.facebook.com/v17.0/{$this->systemUserId}/accounts");

            if (!$response->successful()) {
                Log::error('WhatsAppTokenService::renewTokenAutomatically - Error al obtener cuentas', [
                    'response' => $response->json()
                ]);
                return false;
            }

            $accounts = $response->json('data', []);

            // Si no hay cuentas, no podemos continuar
            if (empty($accounts)) {
                Log::error('WhatsAppTokenService::renewTokenAutomatically - No se encontraron cuentas');
                return false;
            }

            // Buscar la cuenta de WhatsApp Business (la primera debería servir)
            $account = $accounts[0];
            $newToken = $account['access_token'] ?? null;

            if (empty($newToken)) {
                Log::error('WhatsAppTokenService::renewTokenAutomatically - No se encontró token en la cuenta');
                return false;
            }

            // Actualizar el archivo .env con el nuevo token
            $this->updateTokenInEnv($newToken);

            Log::info('WhatsAppTokenService::renewTokenAutomatically - Token renovado correctamente');
            return true;
        } catch (\Exception $e) {
            Log::error('WhatsAppTokenService::renewTokenAutomatically - Excepción', [
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    /**
     * Actualiza el token en el archivo .env
     *
     * @param string $token Nuevo token a guardar
     * @return bool True si se actualizó correctamente, False en caso contrario
     */
    private function updateTokenInEnv(string $token): bool
    {
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
            \Illuminate\Support\Facades\Artisan::call('config:clear');

            return true;
        } catch (\Exception $e) {
            Log::error('WhatsAppTokenService::updateTokenInEnv - Error al actualizar token', [
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }
}
