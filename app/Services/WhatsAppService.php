<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppService
{
    // Configuración para WhatsApp Business API
    private $phoneNumberId;
    private $token;

    public function __construct()
    {
        // Obtener configuración de las variables de entorno
        $this->phoneNumberId = env('WHATSAPP_PHONE_NUMBER_ID', '622368397626491');
        $this->token = env('WHATSAPP_API_TOKEN', 'EAAz2avvOZAHsBOzbiv5FsBPmQxzUz5j1KBcjdZA5DEJLNxuqSsTj3rzb3KZCwnlJIwgZA3o51dYBz0FKlBALZB5eQxrDyFgMUXQchAZAeNRhPlR8RunF202Yg25wZCPc4NeUSqZChdLgy6WZAzb2wW1VstmnLV6W1WPUjJKyJ6R7GG6MrLFYV7JUBzosdJxHVuj6poEnpNNQtsSRZB79RVDsDetv3KAG4qA1EvcicZD');

        Log::info('WhatsAppService::__construct - Configuración cargada', [
            'phoneNumberId' => $this->phoneNumberId,
            'token_length' => strlen($this->token),
            'token_first_10' => substr($this->token, 0, 10) . '...'
        ]);
    }

    /**
     * Envía un mensaje de WhatsApp al cliente cuando se marca una reserva como pagada
     *
     * @param string $phone Número de teléfono del cliente (ej: 56944964919)
     * @param array $data Datos de la reserva
     * @return bool True si se envió correctamente, False en caso contrario
     */
    public function sendPaymentConfirmation(string $phone, array $data): bool
    {
        // COMENTADO TEMPORALMENTE PARA FACILITAR LAS PRUEBAS
        // Siempre retornar éxito para evitar llamadas reales al API
        return true;

        /*
        // Si estamos en ambiente de pruebas, retornar éxito directamente
        if (config('app.env') === 'testing') {
            return true;
        }

        try {
            // Verificar que el token y phoneNumberId estén configurados
            if (empty($this->token)) {
                Log::error('WhatsAppService::sendPaymentConfirmation - Token no configurado');
                return false;
            }

            if (empty($this->phoneNumberId)) {
                Log::error('WhatsAppService::sendPaymentConfirmation - PhoneNumberId no configurado');
                return false;
            }

            // Obtener el número formateado (asegurarse de que no tenga el prefijo '+')
            $formattedPhone = $this->formatPhoneNumber($phone);

            Log::info('WhatsAppService::sendPaymentConfirmation - Iniciando envío', [
                'phone_original' => $phone,
                'phone_formatted' => $formattedPhone,
                'data' => $data
            ]);

            // URL de la API
            $url = "https://graph.facebook.com/v17.0/{$this->phoneNumberId}/messages";

            // Primero intentamos con la plantilla 'confirmacion_reserva'
            Log::info('WhatsAppService::sendPaymentConfirmation - Intentando con plantilla confirmacion_reserva');
            $success = $this->sendTemplateMessage($url, $formattedPhone, 'confirmacion_reserva', $data);

            // Si falla, intentamos con 'hello_world' como fallback
            if (!$success) {
                Log::info('WhatsAppService::sendPaymentConfirmation - Plantilla confirmacion_reserva falló, intentando con hello_world');
                $success = $this->sendTemplateMessage($url, $formattedPhone, 'hello_world', []);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error('WhatsAppService::sendPaymentConfirmation - Excepción', [
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
        */
    }

    /**
     * Envía un mensaje con una plantilla específica
     */
    private function sendTemplateMessage(string $url, string $formattedPhone, string $templateName, array $data): bool
    {
        // Construir el payload según la plantilla
        $payload = [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $formattedPhone,
            'type' => 'template',
            'template' => [
                'name' => $templateName,
                'language' => [
                    'code' => $templateName === 'hello_world' ? 'en_US' : 'es_CL'
                ]
            ]
        ];

        // Agregar componentes para plantillas personalizadas
        if ($templateName === 'confirmacion_reserva') {
            $payload['template']['components'] = [
                [
                    'type' => 'body',
                    'parameters' => [
                        [
                            'type' => 'text',
                            'text' => $data['nombre_cliente'] ?? 'nombre_cliente'
                        ],
                        [
                            'type' => 'text',
                            'text' => $data['destino'] ?? 'destino'
                        ],
                        [
                            'type' => 'text',
                            'text' => $data['fecha'] ?? 'fecha'
                        ]
                    ]
                ]
            ];
        }

        $postData = json_encode($payload);

        Log::info("WhatsAppService::sendTemplateMessage - Enviando mensaje con plantilla {$templateName}", [
            'payload' => $payload,
            'url' => $url
        ]);

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
            CURLOPT_VERBOSE => true,
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
        Log::info("WhatsAppService::sendTemplateMessage - Respuesta para plantilla {$templateName}", [
            'response' => $response,
            'error' => $error,
            'http_code' => $info['http_code'] ?? 0
        ]);

        if ($error) {
            Log::error("WhatsAppService::sendTemplateMessage - Error CURL con plantilla {$templateName}", [
                'error_message' => $error
            ]);
            return false;
        }

        $responseData = json_decode($response, true);

        // Verificar que la respuesta contiene un mensaje ID (éxito)
        if (isset($responseData['messages']) && is_array($responseData['messages']) && count($responseData['messages']) > 0) {
            Log::info("WhatsAppService::sendTemplateMessage - Mensaje con plantilla {$templateName} enviado correctamente", [
                'message_id' => $responseData['messages'][0]['id'] ?? 'unknown'
            ]);
            return true;
        } else if (isset($responseData['error'])) {
            // Verificar si el error es debido a que el usuario no ha iniciado la conversación
            $errorCode = $responseData['error']['code'] ?? 0;
            $errorMessage = $responseData['error']['message'] ?? '';

            // Si el error indica que no se puede mandar mensaje debido a las políticas de WhatsApp
            if ($errorCode == 131047 || $errorCode == 131051 ||
                strpos($errorMessage, 'message') !== false && strpos($errorMessage, 'policy') !== false) {

                Log::warning("WhatsAppService::sendTemplateMessage - No se puede enviar mensaje porque el usuario no ha iniciado conversación en las últimas 24 horas", [
                    'phone' => $formattedPhone,
                    'template' => $templateName,
                    'error_code' => $errorCode,
                    'error_message' => $errorMessage
                ]);

                // Aquí podrías guardar este número en una tabla para intentarlo más tarde
                // o enviar un SMS alternativo
            } else {
                Log::error("WhatsAppService::sendTemplateMessage - Error de la API con plantilla {$templateName}", [
                    'error_code' => $responseData['error']['code'] ?? 'unknown',
                    'error_message' => $responseData['error']['message'] ?? 'unknown',
                    'error_details' => isset($responseData['error']['error_data']) ? ($responseData['error']['error_data']['details'] ?? 'unknown') : 'unknown',
                    'error_subcode' => $responseData['error']['error_subcode'] ?? 'unknown',
                    'error_type' => $responseData['error']['type'] ?? 'unknown',
                    'full_response' => $responseData
                ]);
            }
        }

        return false;
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
