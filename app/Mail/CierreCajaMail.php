<?php

namespace App\Mail;

use App\Models\CorteCaja;
use App\Models\Venta;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CierreCajaMail extends Mailable
{
    use Queueable, SerializesModels;

    public array $ventas;

    public function __construct(public CorteCaja $corte)
    {
        $this->ventas = Venta::with('detalles.producto')
            ->where('user_id', $corte->user_id)
            ->whereDate('created_at', $corte->fecha_corte)
            ->where('created_at', '>=', $corte->created_at)
            ->where('estado', 'completada')
            ->orderBy('created_at')
            ->get()
            ->toArray();
    }

    public function envelope(): Envelope
    {
        $cajero = $this->corte->user->name;
        return new Envelope(
            subject: "🔒 Cierre de Caja — {$cajero} — " . $this->corte->fecha_corte->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.cierre_caja',
        );
    }
}
