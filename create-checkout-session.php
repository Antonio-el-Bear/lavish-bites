<?php
// Stripe Payment Processing for Lavish Bites
// Replace 'sk_test_...' with your actual Stripe Secret Key

require 'vendor/autoload.php';

\Stripe\Stripe::setApiKey('sk_test_your_secret_key_here');

// Get form data
$name = htmlspecialchars($_POST['name']);
$email = htmlspecialchars($_POST['email']);
$datetime = htmlspecialchars($_POST['datetime']);
$people = intval($_POST['people']);
$message = htmlspecialchars($_POST['message']);

// Calculate price based on platter size
$pricePerPerson = 150; // R150 per person
$totalAmount = $people * $pricePerPerson * 100; // Convert to cents for Stripe

try {
    $session = \Stripe\Checkout\Session::create([
        'payment_method_types' => ['card', 'eft', 'boleto'], // Cards, EFT, Mobile payments
        'line_items' => [[
            'price_data' => [
                'currency' => 'zar', // South African Rand
                'product_data' => [
                    'name' => 'Lavish Bites - Braai Platter for ' . $people . ' People',
                    'description' => 'Date: ' . $datetime . '\nSpecial Requests: ' . $message,
                    'images' => ['https://yoursite.com/img/logo-tag.jpg'],
                ],
                'unit_amount' => $totalAmount,
            ],
            'quantity' => 1,
        ]],
        'mode' => 'payment',
        'success_url' => 'https://yoursite.com/success.php?session_id={CHECKOUT_SESSION_ID}',
        'cancel_url' => 'https://yoursite.com/index.html',
        'customer_email' => $email,
        'metadata' => [
            'customer_name' => $name,
            'people_count' => $people,
            'order_date' => $datetime,
            'special_requests' => $message,
        ],
    ]);

    echo json_encode(['sessionId' => $session->id]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
