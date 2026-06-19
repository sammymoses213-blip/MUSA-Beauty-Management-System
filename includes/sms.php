<?php
function sendSMS(string $phone, string $message): bool {
    $phone = preg_replace('/[^0-9+]/', '', $phone);
    $message = trim($message);

    if ($phone === '' || $message === '') {
        return false;
    }

    // Optional real SMS provider integration when environment variables are configured.
    if (!empty($_ENV['SMS_API_URL']) && !empty($_ENV['SMS_API_KEY'])) {
        $payload = json_encode([
            'to' => $phone,
            'message' => $message,
            'sender' => $_ENV['SMS_SENDER'] ?? 'MUSA Beauty',
        ]);

        $ch = curl_init($_ENV['SMS_API_URL']);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $_ENV['SMS_API_KEY'],
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response !== false && $status >= 200 && $status < 300) {
            return true;
        }
    }

    $logDir = __DIR__ . '/../logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $logFile = $logDir . '/sms.log';
    $entry = sprintf("[%s] TO: %s | MESSAGE: %s\n", date('Y-m-d H:i:s'), $phone, $message);
    return file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX) !== false;
}
