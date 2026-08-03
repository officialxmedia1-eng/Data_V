<?php
function verifyPaystackTransaction($reference, $secretKey)
{
    $ch = curl_init('https://api.paystack.co/transaction/verify/' . rawurlencode($reference));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $secretKey,
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode >= 200 && $httpCode < 300) {
        $result = json_decode($response, true);
        if (!empty($result['status']) && $result['status'] === true && !empty($result['data']['status']) && $result['data']['status'] === 'success') {
            return true;
        }
    }

    return false;
}

$secretKey = getenv('PAYSTACK_SECRET_KEY') ?: 'sk_test_eaa430b7dcbeff4dfb3222e97e69de5591396129';

$provider = isset($_GET['provider']) ? strtolower(trim($_GET['provider'])) : 'mtn';
$bundle = isset($_GET['bundle']) ? trim($_GET['bundle']) : '1GB';
$amount = isset($_GET['amount']) ? (int)$_GET['amount'] : 450;
$email = isset($_GET['email']) && filter_var($_GET['email'], FILTER_VALIDATE_EMAIL)
    ? $_GET['email']
    : 'customer@example.com';
$reference = strtoupper($provider . '-' . preg_replace('/[^A-Za-z0-9]/', '', $bundle) . '-' . time());

$baseUrl = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://') . ($_SERVER['HTTP_HOST'] ?? 'localhost');
$scriptDir = dirname($_SERVER['SCRIPT_NAME']);
$basePath = rtrim($baseUrl . $scriptDir, '/');
$successUrl = $basePath . '/success.html?reference=' . rawurlencode($reference);
$failureUrl = $basePath . '/failure.html?reference=' . rawurlencode($reference);

$callbackReference = isset($_GET['reference']) ? trim($_GET['reference']) : '';
$callbackReference = $callbackReference !== '' ? $callbackReference : (isset($_GET['trxref']) ? trim($_GET['trxref']) : '');
$shouldVerify = $callbackReference !== '' && !isset($_GET['provider']) && !isset($_GET['bundle']) && !isset($_GET['email']) && !isset($_GET['amount']);

if ($shouldVerify) {
    $verified = verifyPaystackTransaction($callbackReference, $secretKey);
    $redirectUrl = $verified ? $successUrl . '&status=success' : $failureUrl . '&status=failed';
    header('Location: ' . $redirectUrl);
    exit;
}

$payload = json_encode([
    'email' => $email,
    'amount' => $amount,
    'currency' => 'GHS',
    'reference' => $reference,
    'callback_url' => $basePath . '/pay.php?reference=' . rawurlencode($reference),
    'metadata' => [
        'provider' => $provider,
        'bundle' => $bundle,
        'email' => $email
    ],
    'channels' => ['card', 'bank', 'ussd', 'qr', 'mobile_money']
]);

$ch = curl_init('https://api.paystack.co/transaction/initialize');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Authorization: Bearer ' . $secretKey,
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode >= 200 && $httpCode < 300) {
    $result = json_decode($response, true);
    if (!empty($result['data']['authorization_url'])) {
        header('Location: ' . $result['data']['authorization_url']);
        exit;
    }
}

http_response_code(500);
echo json_encode([
    'success' => false,
    'message' => 'Unable to initialize payment right now.'
]);
