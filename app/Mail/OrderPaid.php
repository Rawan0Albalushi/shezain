<?php

namespace App\Mail;

use App\Models\Order;
use App\Support\Money;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class OrderPaid extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Order $order) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'شيزين — تم استلام طلب '.$this->order->number,
        );
    }

    public function content(): Content
    {
        return new Content(
            htmlString: '<div style="font-family:Tahoma,sans-serif;color:#301621">'
                .'<h1 style="font-weight:500">تم تأكيد الدفع</h1>'
                .'<p>رقم الطلب: '.e($this->order->number).'</p>'
                .'<p>المبلغ: '.e(Money::format($this->order->total)).' ر.ع.</p>'
                .'<p>سنجهز العباية للشحن داخل سلطنة عُمان.</p>'
                .'</div>',
        );
    }
}
