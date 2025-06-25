<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class IPaymuService
{
    protected $va;
    protected $secret;
    protected $baseUrl;

    public function __construct()
    {
        $this->va = config('services.ipaymu.va');
        $this->secret = config('services.ipaymu.secret');
        $this->baseUrl = config('services.ipaymu.base_url', 'https://my.ipaymu.com/api/v2');
    }

    public function createPayment(array $data)
    {

        try {
            $body = [
                'product' => $data['product'],
                'qty' => $data['qty'],
                'price' => $data['price'],
                'returnUrl' => $data['returnUrl'],
                'notifyUrl' => $data['notifyUrl'],
                'cancelUrl' => $data['cancelUrl'],
                'referenceId' => $data['referenceId'],
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);

            $signature = $this->generateSignature('POST', '', $jsonBody);

            $timestamp = now()->format('YmdHis');

            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'va' => $this->va,
                'signature' => $signature,
                'timestamp' => $timestamp,
            ])->withBody($jsonBody, 'application/json')
            ->post($this->baseUrl . '/payment');

            if ($response->successful()) {
                $responseData = $response->json();
                Log::info('iPaymu payment created', $responseData);

                return [
                    'success' => true,
                    'data' => $responseData['Data'] ?? $responseData,
                    'message' => 'Payment created successfully'
                ];
            }

            Log::error('iPaymu payment creation failed', [
                'status' => $response->status(),
                'response' => $response->json()
            ]);

            return [
                'success' => false,
                'message' => 'Payment creation failed: ' . $response->body()
            ];

        } catch (\Exception $e) {
            Log::error('iPaymu service error: ' . $e->getMessage());

            return [
                'success' => false,
                'message' => 'Service error: ' . $e->getMessage()
            ];
        }
    }

    public function checkPaymentStatus($transactionId)
    {
        try {
            
            $signature = $this->generateSignature('POST');

            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'va' => $this->va,
                'signature' => $signature,
                'timestamp' => time()
            ])->post($this->baseUrl . '/payment/status', [
                'transactionId' => $transactionId
            ]);


            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to check payment status'
            ];

        } catch (\Exception $e) {
            Log::error('iPaymu check status error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Service error: ' . $e->getMessage()
            ];
        }
    }

    public function verifyWebhook(array $data)
    {
        try {
            // iPaymu webhook verification logic
            $signature = $data['signature'] ?? '';
            $timestamp = $data['timestamp'] ?? '';
            
            // Remove signature and timestamp from data for verification
            unset($data['signature'], $data['timestamp']);
            
            $jsonBody = json_encode($data, JSON_UNESCAPED_SLASHES);
            $expectedSignature = $this->generateSignature('POST', '', $jsonBody);
            
            return hash_equals($expectedSignature, $signature);

        } catch (\Exception $e) {
            Log::error('Webhook verification error: ' . $e->getMessage());
            return false;
        }
    }

    protected function generateSignature($method, $endpoint = null, $jsonBody = null)
    {

        $requestBody = strtolower(hash('sha256', $jsonBody));

        $stringToSign = $method . ':' . $this->va . ':' . $requestBody . ':' . $this->secret;

        $signature = hash_hmac('sha256', $stringToSign, $this->secret);
        
        return $signature;
    }

    public function getPaymentMethods()
    {
        try {
            $signature = $this->generateSignature('GET', 'payment-method', '');

            $response = Http::withHeaders([
                'va' => $this->va,
                'signature' => $signature,
                'timestamp' => time()
            ])->get($this->baseUrl . '/payment-method');

            if ($response->successful()) {
                return [
                    'success' => true,
                    'data' => $response->json()
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to get payment methods'
            ];

        } catch (\Exception $e) {
            Log::error('Get payment methods error: ' . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'Service error: ' . $e->getMessage()
            ];
        }
    }
}