# WAGO PHP SDK

Official PHP SDK untuk layanan pembayaran [WAGO Payment ID](https://wago-id.web.id).

## Instalasi

Gunakan Composer:
```bash
composer require wago-id/php-sdk
```

## Penggunaan

### Inisialisasi

```php
require 'vendor/autoload.php';

use Wago\Wago;

$wago = new Wago([
    'appId' => 'APP_ID_ANDA',
    'apiKey' => 'API_KEY_ANDA',
    'webhookSecret' => 'WEBHOOK_SECRET_ANDA' // Opsional, diperlukan untuk validasi webhook
]);
```

### Membuat Transaksi

```php
try {
    $trx = $wago->createTransaction([
        'order_id' => 'INV-' . time(),
        'nominal' => 150000,
        'customer_name' => 'Budi Santoso',
        'payment_method' => 'QRIS',
        'is_sandbox' => true
    ]);
    
    print_r($trx);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
```

### Memvalidasi Webhook

```php
$headers = getallheaders();
$signature = $headers['X-WAGO-Signature'] ?? '';
$timestamp = $headers['X-WAGO-Timestamp'] ?? '';

$rawBody = file_get_contents('php://input');
$body = json_decode($rawBody, true);

if ($wago->verifyWebhook($body, $signature, $timestamp)) {
    http_response_code(200);
    echo json_encode(["status" => "ok"]);
} else {
    http_response_code(403);
    echo json_encode(["error" => "Invalid signature"]);
}
```
