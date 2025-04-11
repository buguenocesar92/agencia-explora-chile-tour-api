<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    // Configuración para WhatsApp Business API
    private $phoneNumberId = '622368397626491';
    private $token = 'EAAz2avvOZAHsBO3A3XDLSLp7yGphsqZC9nrFyTgwrDnYYXXVul8xRROS0txSVnvZA86HVfsRdFqaKKH2PqWV9n73KhcCaGoOmkGk6uFyMPOjIYzBg2Ww6n3dpd1s6GiHSRERDZAKpEbhgcPEAtSq7aXOW39exx9WEcVZCcAqF331qeQ4MjalI3RZAkXkpFdbpBxYKPe1pbMkxssU9glF70t3XlSKbckhB8i38ZD';

    /**
     * Envía un mensaje de WhatsApp al cliente cuando se marca una reserva como pagada
     *
     * @param string $phone Número de teléfono del cliente (ej: 56944964919)
     * @param array $data Datos de la reserva
     * @return bool True si se envió correctamente, False en caso contrario
     */
    public function sendPaymentConfirmation(string $phone, array $data): bool
    {
        try {
            // Obtener el número formateado (asegurarse de que no tenga el prefijo '+')
            $formattedPhone = $this->formatPhoneNumber($phone);

            Log::info('WhatsAppService::sendPaymentConfirmation - Iniciando envío', [
                'phone_original' => $phone,
                'phone_formatted' => $formattedPhone
            ]);

            // Intentar primero con plantilla hello_world (que es la plantilla por defecto aprobada)
            $response = $this->sendTemplateMessage($formattedPhone, 'hello_world');

            if ($response) {
                Log::info('WhatsAppService::sendPaymentConfirmation - Mensaje de plantilla enviado correctamente');
                return true;
            }

            // Si falla la plantilla, intentar con mensaje de texto simple
            Log::info('WhatsAppService::sendPaymentConfirmation - Intentando con mensaje de texto directo');
            return $this->sendTextMessage($formattedPhone, $data);

        } catch (\Exception $e) {
            Log::error('WhatsAppService::sendPaymentConfirmation - Excepción', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Envía un mensaje usando una plantilla predefinida
     */
    private function sendTemplateMessage(string $phone, string $templateName): bool
    {
        try {
            // URL de la API
            $url = "https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages";

            // Payload para plantilla
            $postData = json_encode([
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'template',
                'template' => [
                    'name' => $templateName,
                    'language' => [
                        'code' => 'en_US'
                    ]
                ]
            ]);

            Log::info('WhatsAppService::sendTemplateMessage - Enviando plantilla', [
                'template' => $templateName,
                'phone' => $phone
            ]);

            return $this->executeApiCall($url, $postData);
        } catch (\Exception $e) {
            Log::error('WhatsAppService::sendTemplateMessage - Error', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Envía un mensaje de texto simple
     */
    private function sendTextMessage(string $phone, array $data): bool
    {
        try {
            // URL de la API
            $url = "https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages";

            // Crear mensaje simple
            $message = "Hola " . ($data['nombre'] ?? 'Cliente') . "! Tu reserva para " .
                      ($data['destino'] ?? 'tu viaje') . " con fecha " .
                      ($data['fecha'] ?? 'programada') . " ha sido confirmada. Gracias por elegir Explora Chile Tour!";

            // Payload para mensaje de texto
            $postData = json_encode([
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $phone,
                'type' => 'text',
                'text' => [
                    'preview_url' => false,
                    'body' => $message
                ]
            ]);

            Log::info('WhatsAppService::sendTextMessage - Enviando texto', [
                'message' => $message
            ]);

            return $this->executeApiCall($url, $postData);
        } catch (\Exception $e) {
            Log::error('WhatsAppService::sendTextMessage - Error', [
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Ejecuta la llamada a la API de WhatsApp
     */
    private function executeApiCall(string $url, string $postData): bool
    {
        // Configuración de CURL
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_SSL_VERIFYHOST => 0,
            CURLOPT_SSL_VERIFYPEER => 0,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->token,
                'Content-Type: application/json',
            ],
        ]);

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $info = curl_getinfo($curl);

        curl_close($curl);

        // Registrar respuesta completa para debugging
        Log::info('WhatsAppService::executeApiCall - Respuesta API', [
            'response' => $response,
            'error' => $error,
            'http_code' => $info['http_code'] ?? 0
        ]);

        if ($error) {
            Log::error('WhatsAppService::executeApiCall - Error CURL', [
                'error_message' => $error
            ]);
            return false;
        }

        $responseData = json_decode($response, true);

        // Verificar que la respuesta contiene un mensaje ID (éxito)
        if (isset($responseData['messages']) && is_array($responseData['messages']) && count($responseData['messages']) > 0) {
            Log::info('WhatsAppService::executeApiCall - Mensaje enviado correctamente', [
                'message_id' => $responseData['messages'][0]['id'] ?? 'unknown'
            ]);
            return true;
        } else if (isset($responseData['error'])) {
            Log::error('WhatsAppService::executeApiCall - Error de la API', [
                'error_code' => $responseData['error']['code'] ?? 'unknown',
                'error_message' => $responseData['error']['message'] ?? 'unknown'
            ]);
        }

        return false;
    }

    /**
     * Crea un mensaje de texto para WhatsApp similar al correo electrónico
     */
    private function createTextMessageFromEmail(array $data): string
    {
        $nombre = $data['nombre'] ?? 'Cliente';
        $destino = $data['destino'] ?? 'su viaje';
        $fecha = $data['fecha'] ?? 'la fecha programada';

        return "¡Hola {$nombre}! 👋\n\n" .
               "✅ *¡Tu reserva ha sido confirmada!*\n" .
               "Prepárate para vivir una experiencia inolvidable.\n\n" .
               "Gracias por elegir *Explora Chile Tour* para tu próxima aventura. Estamos emocionados de acompañarte en este viaje.\n\n" .
               "*📋 DETALLES DEL VIAJE:*\n" .
               "• *Destino:* {$destino}\n" .
               "• *Fecha de viaje:* {$fecha}\n" .
               "• *Estado:* Confirmado ✅\n\n" .
               "*🌟 INCLUYE:*\n" .
               "• Asistencia 24/7\n" .
               "• 20kg Equipaje\n" .
               "• Seguro Incluido\n\n" .
               "Si tienes alguna pregunta sobre tu reserva, no dudes en contactarnos.\n\n" .
               "¡Esperamos que disfrutes de tu viaje! 🌎✈️";
    }

    /**
     * Formatea el número de teléfono para la API de WhatsApp
     * Asegura que tenga el formato correcto sin el símbolo '+'
     */
    private function formatPhoneNumber(string $phone): string
    {
        // Eliminar espacios, guiones y paréntesis
        $phone = preg_replace('/\s+|\(|\)|-/', '', $phone);

        // Si comienza con +, quitarlo
        if (str_starts_with($phone, '+')) {
            $phone = substr($phone, 1);
        }

        // Si no comienza con código de país (56 para Chile), añadirlo
        if (!str_starts_with($phone, '56')) {
            // Si comienza con 9, es un celular chileno
            if (str_starts_with($phone, '9')) {
                $phone = '56' . $phone;
            }
        }

        return $phone;
    }
}
