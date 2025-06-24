<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\DonationPaymentRequest;
use App\Http\Requests\StorePaymentRequest;
use App\Models\Donation;
use App\Models\Payment;
use Auth;
use DB;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $ipaymuService;

    public function __construct(IPaymuService $ipaymuService)
    {
        $this->ipaymuService = $ipaymuService;
    }

    public function upgrade(StorePaymentRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'user_id' => auth()->id(),
                'type' => 'upgrade',
                'amount' => $request->amount,
                'status' => 'pending',
                'gateway' => 'ipaymu',
                'payment_reference' => 'UPG-' . uniqid()
            ]);

            // Create payment with iPaymu
            $paymentData = $this->ipaymuService->createPayment([
                'product' => ['Premium Upgrade'],
                'qty' => [1],
                'price' => [$request->amount],
                'description' => 'Premium subscription upgrade',
                'returnUrl' => config('app.url') . '/payment/success',
                'notifyUrl' => config('app.url') . '/api/payments/webhook',
                'cancelUrl' => config('app.url') . '/payment/cancel',
                'referenceId' => $payment->payment_reference
            ]);

            if (!$paymentData['success']) {
                throw new \Exception('Failed to create payment: ' . $paymentData['message']);
            }

            // Update payment with gateway response
            $payment->update([
                'gateway_response' => $paymentData
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Payment created successfully',
                'data' => new PaymentResource($payment),
                'payment_url' => $paymentData['data']['url'] ?? null
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Payment creation failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function donate(DonationPaymentRequest $request)
    {
        try {
            DB::beginTransaction();

            // Create payment record
            $payment = Payment::create([
                'user_id' => auth()->id(),
                'type' => 'donation',
                'amount' => $request->amount,
                'status' => 'pending',
                'gateway' => 'ipaymu',
                'payment_reference' => 'DON-' . uniqid()
            ]);

            // Create donation record
            $donation = Donation::create([
                'payment_id' => $payment->id,
                'donor_name' => $request->donor_name,
                'message' => $request->message,
                'is_anonymous' => $request->is_anonymous ?? false,
                'show_in_list' => $request->show_in_list ?? true
            ]);

            // Create payment with iPaymu
            $paymentData = $this->ipaymuService->createPayment([
                'product' => ['Donation'],
                'qty' => [1],
                'price' => [$request->amount],
                'description' => 'Donation from ' . ($request->is_anonymous ? 'Anonymous' : $request->donor_name),
                'returnUrl' => config('app.url') . '/donation/success',
                'notifyUrl' => config('app.url') . '/api/payments/webhook',
                'cancelUrl' => config('app.url') . '/donation/cancel',
                'referenceId' => $payment->payment_reference
            ]);

            if (!$paymentData['success']) {
                throw new \Exception('Failed to create payment: ' . $paymentData['message']);
            }

            // Update payment with gateway response
            $payment->update([
                'gateway_response' => $paymentData
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Donation payment created successfully',
                'data' => new PaymentResource($payment->load('donation')),
                'payment_url' => $paymentData['data']['url'] ?? null
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Donation payment creation failed: ' . $e->getMessage());
            
            return response()->json([
                'message' => 'Failed to create donation payment',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function webhook(Request $request)
    {
        try {
            Log::info('iPaymu webhook received', $request->all());

            // Verify webhook signature
            if (!$this->ipaymuService->verifyWebhook($request->all())) {
                Log::warning('Invalid webhook signature');
                return response()->json(['message' => 'Invalid signature'], 400);
            }

            $referenceId = $request->input('reference_id');
            $status = $request->input('status');
            $transactionId = $request->input('transaction_id');

            $payment = Payment::where('payment_reference', $referenceId)->first();

            if (!$payment) {
                Log::warning('Payment not found for reference: ' . $referenceId);
                return response()->json(['message' => 'Payment not found'], 404);
            }

            // Update payment status based on webhook data
            switch ($status) {
                case 'berhasil':
                case 'success':
                    $payment->markAsSuccess();
                    $this->processSuccessfulPayment($payment);
                    break;
                case 'expired':
                case 'failed':
                case 'batal':
                    $payment->markAsFailed();
                    break;
                default:
                    Log::info('Unhandled payment status: ' . $status);
            }

            // Update gateway response with webhook data
            $gatewayResponse = $payment->gateway_response ?? [];
            $gatewayResponse['webhook'] = $request->all();
            $payment->update(['gateway_response' => $gatewayResponse]);

            return response()->json(['message' => 'Webhook processed successfully']);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed: ' . $e->getMessage());
            return response()->json(['message' => 'Webhook processing failed'], 500);
        }
    }

    protected function processSuccessfulPayment(Payment $payment)
    {
        if ($payment->type === 'upgrade' && $payment->user) {
            // Upgrade user to premium
            $payment->user->update(['role' => 'premium']);
            Log::info('User upgraded to premium: ' . $payment->user->id);
        }

        // Additional processing for successful donations can be added here
        if ($payment->type === 'donation') {
            Log::info('Donation payment successful: ' . $payment->id);
        }
    }
}
