<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ChileanRut implements Rule
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Eliminar puntos y guiones
        $rut = preg_replace('/[^0-9kK]/', '', $value);

        // Verificar largo mínimo
        if (strlen($rut) < 8) {
            return false;
        }

        // Separar cuerpo y dígito verificador
        $body = substr($rut, 0, -1);
        $dv = strtoupper(substr($rut, -1));

        // Calcular dígito verificador
        $sum = 0;
        $factor = 2;

        for ($i = strlen($body) - 1; $i >= 0; $i--) {
            $sum += $factor * intval($body[$i]);
            $factor = $factor == 7 ? 2 : $factor + 1;
        }

        $expectedDV = 11 - ($sum % 11);

        if ($expectedDV == 11) {
            $expectedDV = '0';
        } elseif ($expectedDV == 10) {
            $expectedDV = 'K';
        } else {
            $expectedDV = (string)$expectedDV;
        }

        return $dv == $expectedDV;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'El :attribute no es un RUT chileno válido.';
    }
}
