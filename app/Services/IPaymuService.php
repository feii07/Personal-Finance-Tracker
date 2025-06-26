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
                'notifyUrl' => $data['notifyUrl'],
                'referenceId' => $data['referenceId'],
            ];

            $jsonBody = json_encode($body, JSON_UNESCAPED_SLASHES);
            $timestamp = date('YmdHis'); // Format: YYYYMMDDHHMMSS

            // Generate signature untuk payment creation
            $signature = $this->generateSignature('POST', 'payment', $jsonBody, $timestamp);

            Log::info('iPaymu payment request', [
                'body' => $jsonBody,
                'signature' => $signature,
                'timestamp' => $timestamp
            ]);

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

    public function verifyWebhook(array $data)
    {
        try {
            // Get signature dan timestamp dari data
            $receivedSignature = $data['signature'] ?? '';
            $timestamp = $data['timestamp'] ?? '';
            
            if (empty($receivedSignature) || empty($timestamp)) {
                Log::warning('Missing signature or timestamp in webhook data');
                return false;
            }
            
            // Remove signature dan timestamp dari data untuk verification
            $verificationData = $data;
            unset($verificationData['signature'], $verificationData['timestamp']);
            
            $jsonBody = json_encode($verificationData, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            
            // Generate expected signature
            $expectedSignature = $this->generateWebhookSignature($jsonBody, $timestamp);
            
            Log::info('Webhook signature verification', [
                'received_signature' => $receivedSignature,
                'expected_signature' => $expectedSignature,
                'timestamp' => $timestamp,
                'body' => $jsonBody
            ]);
            
            return hash_equals($expectedSignature, $receivedSignature);

        } catch (\Exception $e) {
            Log::error('Webhook verification error: ' . $e->getMessage());
            return false;
        }
    }

    protected function generateSignature($method, $endpoint, $jsonBody, $timestamp)
    {
        // Hash request body
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        
        // String to sign: METHOD:VA:HASHED_BODY:SECRET
        $stringToSign = strtoupper($method) . ':' . $this->va . ':' . $hashedBody . ':' . $this->secret;
        
        Log::info('Signature generation', [
            'method' => $method,
            'endpoint' => $endpoint,
            'body' => $jsonBody,
            'hashed_body' => $hashedBody,
            'string_to_sign' => $stringToSign,
            'timestamp' => $timestamp
        ]);
        
        // Generate signature
        $signature = hash_hmac('sha256', $stringToSign, $this->secret);
        
        return $signature;
    }

    protected function generateWebhookSignature($jsonBody, $timestamp)
    {
        // Untuk webhook, signature biasanya berbeda formatnya
        // Sesuaikan dengan dokumentasi iPaymu yang terbaru
        $hashedBody = strtolower(hash('sha256', $jsonBody));
        $stringToSign = 'POST:' . $this->va . ':' . $hashedBody . ':' . $this->secret;
        
        return hash_hmac('sha256', $stringToSign, $this->secret);
    }
}