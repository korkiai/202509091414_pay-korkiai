<?php
// Uproszczony webhook bez systemu afiliacji i magic login
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: https://platforma.korkiai.pl');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Weryfikuj webhook Stripe
$stripe_webhook_secret = 'whsec_NXYb5Rm2Ar7v4VrA0etbQo90LG9gew61';

$payload = file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'];

// Prosta weryfikacja webhook bez biblioteki Stripe
function verifyStripeWebhook($payload, $sig_header, $secret) {
    $elements = explode(',', $sig_header);
    $sig_data = [];
    
    foreach ($elements as $element) {
        $parts = explode('=', $element, 2);
        if (count($parts) === 2) {
            $sig_data[$parts[0]] = $parts[1];
        }
    }
    
    if (!isset($sig_data['v1'])) {
        return false;
    }
    
    $expected_sig = hash_hmac('sha256', $payload, $secret);
    return hash_equals($expected_sig, $sig_data['v1']);
}

if (!verifyStripeWebhook($payload, $sig_header, $stripe_webhook_secret)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid signature']);
    exit;
}

$event = json_decode($payload, true);

// Obsłuż tylko udane płatności
if ($event['type'] === 'payment_intent.succeeded') {
    $payment_intent = $event['data']['object'];
    
    // Wyciągnij dane z płatności
    $customer_email = $payment_intent['receipt_email'];
    $customer_name = $payment_intent['metadata']['full_name'] ?? '';
    $plan = $payment_intent['metadata']['plan'] ?? 'standard';
    $company_name = $payment_intent['metadata']['company_name'] ?? '';
    $nip = $payment_intent['metadata']['nip'] ?? '';
    
    // Utwórz użytkownika w WordPress (bez afiliacji)
    $user_result = createWordPressUser($customer_email, $customer_name, $plan, $company_name, $nip);
    
    if ($user_result['success']) {
        echo json_encode([
            'status' => 'success',
            'message' => 'User created successfully',
            'user_id' => $user_result['user_id']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Failed to create user: ' . $user_result['error']
        ]);
    }
}

/**
 * Tworzy użytkownika w WordPress (uproszczona wersja)
 */
function createWordPressUser($email, $name, $plan, $company_name, $nip) {
    // URL do WordPress AJAX endpoint
    $wp_ajax_url = 'https://platforma.korkiai.pl/wp-admin/admin-ajax.php';
    
    // Przygotuj dane użytkownika
    $user_data = [
        'action' => 'create_user_from_payment',
        'email' => $email,
        'name' => $name,
        'plan' => $plan,
        'company_name' => $company_name,
        'nip' => $nip,
        'source' => 'stripe_payment',
        'verification_required' => false
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $wp_ajax_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($user_data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/x-www-form-urlencoded'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);
    
    if ($curl_error) {
        return [
            'success' => false,
            'error' => 'cURL Error: ' . $curl_error
        ];
    }
    
    $response_data = json_decode($response, true);
    
    return [
        'success' => $http_code === 200 && isset($response_data['success']) && $response_data['success'],
        'user_id' => $response_data['data']['user_id'] ?? null,
        'error' => $response_data['data']['message'] ?? 'Unknown error',
        'response' => $response_data
    ];
}
?>