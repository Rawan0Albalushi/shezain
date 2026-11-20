<?php

namespace App\Http\Controllers\Store;

use App\Actions\SettleThawaniPayment;
use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Services\Thawani\ThawaniException;
use App\Services\Thawani\WebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, SettleThawaniPayment $settle): Response
    {
        $raw = $request->getContent();

        if (! WebhookSignature::valid(
            $raw,
            (string) $request->header('thawani-timestamp'),
            (string) $request->header('thawani-signature'),
            config('services.thawani.webhook_secret'),
        )) {
            abort(401);
        }

        $payload = json_decode($raw, true);
        $sessionId = is_array($payload) ? ($payload['data']['session_id'] ?? null) : null;

        if (is_string($sessionId)) {
            $payment = Payment::query()->where('session_id', $sessionId)->first();

            if ($payment) {
                try {
                    $settle->handle($payment);
                } catch (ThawaniException) {
                    abort(503);
                }
            }
        }

        return response()->noContent();
    }
}
