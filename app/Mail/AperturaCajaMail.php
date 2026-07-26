<?php

namespace App\Mail;

use App\Models\CorteCaja;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class AperturaCajaMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public CorteCaja $corte) {}

    public function envelope(): Envelope
    {
        $cajero = $this->corte->user->name;
        return new Envelope(
            subject: "🔓 Apertura de Caja — {$cajero} — " . $this->corte->fecha_corte->format('d/m/Y'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.apertura_caja',
        );
    }
}
