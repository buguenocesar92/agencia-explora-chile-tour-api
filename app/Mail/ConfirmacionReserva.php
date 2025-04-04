<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ConfirmacionReserva extends Mailable
{
    use Queueable, SerializesModels;

    public $datos;

    public function __construct($datos)
    {
        $this->datos = $datos;
    }

    public function build()
    {
        return $this->view('emails.confirmacion')
                    ->subject('Confirmación de Reserva')
                    ->with([
                        'nombre' => $this->datos['nombre'],
                        'destino' => $this->datos['destino'],
                        'fecha' => $this->datos['fecha'],
                    ]);
    }
}
