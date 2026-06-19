<?php
require_once __DIR__ . '/../config/db.php';

$input = file_get_contents('php://input');
$decoded = json_decode($input, true);

if (!is_array($decoded)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Invalid callback payload']);
    exit;
}

$body = $decoded['Body']['stkCallback'] ?? null;
if (!$body) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'Missing stkCallback']);
    exit;
}

$merchantRequestId = $body['MerchantRequestID'] ?? '';
$checkoutRequestId = $body['CheckoutRequestID'] ?? '';
$resultCode = (string) ($body['ResultCode'] ?? '');
$resultDesc = (string) ($body['ResultDesc'] ?? '');
$callbackMetadata = $body['CallbackMetadata']['Item'] ?? [];

$amount = '';
$mpesaReceiptNumber = '';
$phoneNumber = '';

foreach ($callbackMetadata as $item) {
    if (($item['Name'] ?? '') === 'Amount') {
        $amount = (string) $item['Value'];
    }
    if (($item['Name'] ?? '') === 'MpesaReceiptNumber') {
        $mpesaReceiptNumber = (string) $item['Value'];
    }
    if (($item['Name'] ?? '') === 'PhoneNumber') {
        $phoneNumber = (string) $item['Value'];
    }
}

$stmt = $pdo->prepare('INSERT INTO mpesa_payments (merchant_request_id, checkout_request_id, result_code, result_desc, amount, mpesa_receipt_number, phone_number, created_at) VALUES (:merchant_request_id, :checkout_request_id, :result_code, :result_desc, :amount, :mpesa_receipt_number, :phone_number, NOW())');
$stmt->execute([
    ':merchant_request_id' => $merchantRequestId,
    ':checkout_request_id' => $checkoutRequestId,
    ':result_code' => $resultCode,
    ':result_desc' => $resultDesc,
    ':amount' => $amount,
    ':mpesa_receipt_number' => $mpesaReceiptNumber,
    ':phone_number' => $phoneNumber,
]);

$appointmentStatus = 'failed';
if ($resultCode === '0') {
    $appointmentStatus = 'paid';
}

if (!empty($checkoutRequestId)) {
    $appointmentUpdate = $pdo->prepare('UPDATE appointments SET payment_status = :payment_status, mpesa_receipt_number = :mpesa_receipt_number, amount_paid = COALESCE(NULLIF(amount_paid, 0), :amount) WHERE mpesa_checkout_request_id = :checkout_request_id');
    $appointmentUpdate->execute([
        ':payment_status' => $appointmentStatus,
        ':mpesa_receipt_number' => $mpesaReceiptNumber,
        ':amount' => (int) $amount,
        ':checkout_request_id' => $checkoutRequestId,
    ]);
}

http_response_code(200);
echo json_encode(['ok' => true, 'message' => 'Callback received']);
