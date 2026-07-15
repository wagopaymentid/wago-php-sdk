<?php

namespace Wago;

use Exception;

class Wago {
    private $appId;
    private $apiKey;
    private $webhookSecret;
    private $baseUrl;

    public function __construct(array $options) {
        if (empty($options['appId']) || empty($options['apiKey'])) {
            throw new Exception("appId and apiKey are required");
        }

        $this->appId = $options['appId'];
        $this->apiKey = $options['apiKey'];
        $this->webhookSecret = $options['webhookSecret'] ?? '';
        
        $isProduction = $options['isProduction'] ?? true;
        $this->baseUrl = $isProduction ? 'https://api.wago-id.web.id/api' : 'https://api.wago-id.web.id/api';
    }

    private function request(string $method, string $endpoint, array $data = null) {
        $ch = curl_init($this->baseUrl . $endpoint);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        
        $headers = [
            'Content-Type: application/json',
            'x-api-key: ' . $this->apiKey
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }

        $decoded = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $msg = $decoded['message'] ?? $decoded['error'] ?? 'Unknown Error';
            throw new Exception("WAGO API Error ($httpCode): $msg");
        }

        return $decoded;
    }

    public function createTransaction(array $params) {
        $payload = array_merge(['app_id' => $this->appId], $params);
        return $this->request('POST', '/order', $payload);
    }

    public function getTransactionStatus(string $orderId) {
        return $this->request('GET', '/order?id=' . urlencode($orderId));
    }

    public function verifyWebhook(array $body, string $signature, string $timestamp): bool {
        if (empty($this->webhookSecret)) {
            throw new Exception("webhookSecret is required to verify webhooks.");
        }

        $order_id = $body['order_id'] ?? '';
        $status = $body['status'] ?? '';
        $nominal_unik = $body['nominal_unik'] ?? '';
        $sn = $body['sn'] ?? '';

        $rawPayload = "{$order_id}:{$status}:{$nominal_unik}:{$sn}:{$timestamp}";
        $expectedSignature = hash_hmac('sha256', $rawPayload, $this->webhookSecret);

        return hash_equals($expectedSignature, $signature);
    }
}
