<?php

function mpesaNormalizePhone(string $phone): string
{
    $phone = preg_replace('/[^0-9]/', '', $phone) ?? '';

    if (strpos($phone, '254') === 0) {
        return $phone;
    }

    if (strpos($phone, '0') === 0) {
        return '254' . substr($phone, 1);
    }

    return $phone;
}

function mpesaConfig(): array
{
    return [
        'consumer_key' => getenv('MPESA_CONSUMER_KEY') ?: '',
        'consumer_secret' => getenv('MPESA_CONSUMER_SECRET') ?: '',
        'shortcode' => getenv('MPESA_SHORTCODE') ?: '',
        'passkey' => getenv('MPESA_PASSKEY') ?: '',
        'callback_url' => getenv('MPESA_CALLBACK_URL') ?: '',
        'environment' => getenv('MPESA_ENVIRONMENT') ?: 'sandbox',
    ];
}

function mpesaInitiateStkPush(int $amount, string $phone, string $accountReference, string $transactionDesc): array
{
    $config = mpesaConfig();

    if (empty($config['consumer_key']) || empty($config['consumer_secret']) || empty($config['shortcode']) || empty($config['passkey'])) {
        return [
            'ok' => false,
            'message' => 'MPesa credentials are not configured yet. Set MPESA_CONSUMER_KEY, MPESA_CONSUMER_SECRET, MPESA_SHORTCODE, and MPESA_PASSKEY in your environment.',
        ];
    }

    $phone = mpesaNormalizePhone($phone);
    if (strlen($phone) !== 12 || strpos($phone, '254') !== 0) {
        return [
            'ok' => false,
            'message' => 'Please enter a valid Kenyan phone number for MPesa (e.g. 254712345678).',
        ];
    }

    if (!function_exists('curl_init')) {
        return [
            'ok' => false,
            'message' => 'cURL is not available on this server. Enable PHP cURL for MPesa integration.',
        ];
    }

    $tokenUrl = $config['environment'] === 'production'
        ? 'https://api.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials'
        : 'https://sandbox.safaricom.co.ke/oauth/v1/generate?grant_type=client_credentials';

    $tokenHeaders = [
        'Authorization: Basic ' . base64_encode($config['consumer_key'] . ':' . $config['consumer_secret']),
    ];

    $tokenCurl = curl_init();
    curl_setopt_array($tokenCurl, [
        CURLOPT_URL => $tokenUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $tokenHeaders,
        CURLOPT_TIMEOUT => 30,
    ]);

    $tokenResponse = curl_exec($tokenCurl);
    $tokenError = curl_error($tokenCurl);
    curl_close($tokenCurl);

    if ($tokenError || !$tokenResponse) {
        return [
            'ok' => false,
            'message' => 'Could not obtain an MPesa access token: ' . $tokenError,
        ];
    }

    $tokenData = json_decode($tokenResponse, true);
    $accessToken = $tokenData['access_token'] ?? '';
    if (!$accessToken) {
        return [
            'ok' => false,
            'message' => 'MPesa token generation failed: ' . $tokenResponse,
        ];
    }

    $timestamp = gmdate('YmdHis');
    $password = base64_encode($config['shortcode'] . $config['passkey'] . $timestamp);
    $callbackUrl = $config['callback_url'] ?: (isset($_SERVER['HTTP_HOST']) ? 'http://' . $_SERVER['HTTP_HOST'] . '/client/mpesa_callback.php' : '');
    $body = [
        'BusinessShortCode' => (int) $config['shortcode'],
        'Password' => $password,
        'Timestamp' => $timestamp,
        'TransactionType' => 'CustomerPayBillOnline',
        'Amount' => (int) $amount,
        'PartyA' => (int) $phone,
        'PartyB' => (int) $config['shortcode'],
        'PhoneNumber' => (int) $phone,
        'CallBackURL' => $callbackUrl,
        'AccountReference' => $accountReference,
        'TransactionDesc' => $transactionDesc,
    ];

    $stkCurl = curl_init();
    curl_setopt_array($stkCurl, [
        CURLOPT_URL => ($config['environment'] === 'production'
            ? 'https://api.safaricom.co.ke/mpesa/stkpush/v1/processrequest'
            : 'https://sandbox.safaricom.co.ke/mpesa/stkpush/v1/processrequest'),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $accessToken,
            'Content-Type: application/json',
        ],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($body),
        CURLOPT_TIMEOUT => 60,
    ]);

    $stkResponse = curl_exec($stkCurl);
    $stkError = curl_error($stkCurl);
    curl_close($stkCurl);

    if ($stkError || !$stkResponse) {
        return [
            'ok' => false,
            'message' => 'MPesa STK push failed: ' . $stkError,
        ];
    }

    $response = json_decode($stkResponse, true);
    $responseCode = $response['ResponseCode'] ?? null;
    $success = $responseCode === '0' || $responseCode === 0;

    return [
        'ok' => $success,
        'message' => $response['ResponseDescription'] ?? $response['CustomerMessage'] ?? 'MPesa request submitted.',
        'response' => $response,
        'phone' => $phone,
        'amount' => (int) $amount,
    ];
}
