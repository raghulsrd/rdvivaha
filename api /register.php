<?php
declare(strict_types=1);

require __DIR__ . '/helpers.php';
require __DIR__ . '/db.php';

$config = require __DIR__ . '/config.php';

applyCors($config['allowed_origin']);

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(405, ['success' => false, 'message' => 'Method not allowed.']);
}

$ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
if (!throttle('register:' . $ip)) {
    jsonResponse(429, ['success' => false, 'message' => 'Too many attempts. Try again later.']);
}

$raw = file_get_contents('php://input');
if ($raw === false || strlen($raw) > 10000) {
    jsonResponse(400, ['success' => false, 'message' => 'Invalid request.']);
}

$body = json_decode($raw, true);
if (!is_array($body)) {
    jsonResponse(400, ['success' => false, 'message' => 'Invalid JSON payload.']);
}

['errors' => $errors, 'data' => $data] = validateRegistration($body);

if ($errors !== []) {
    jsonResponse(422, [
        'success' => false,
        'message' => 'Please fix the errors below.',
        'errors'  => $errors,
    ]);
}

try {
    $pdo = db();

    // Email-க்கு UNIQUE index illa-na duplicate ஏறிடும், அதனால manual check
    $check = $pdo->prepare(
        'SELECT unique_member_id
           FROM com_customer_phno_address_book
          WHERE mailid = :mailid
          LIMIT 1'
    );
    $check->execute([':mailid' => $data['email']]);

    if ($check->fetch()) {
        jsonResponse(409, [
            'success' => false,
            'message' => 'An account with this email already exists.',
            'errors'  => ['email' => 'This email is already registered'],
        ]);
    }

    $stmt = $pdo->prepare(
        'INSERT INTO com_customer_phno_address_book
            (customername, phno_1, phno_2, mailid, address)
         VALUES (:customername, :phno_1, :phno_2, :mailid, :address)'
    );

    $stmt->execute([
        ':customername' => $data['customerName'],
        ':phno_1'       => $data['phone1'],
        ':phno_2'       => $data['phone2'],
        ':mailid'       => $data['email'],
        ':address'      => $data['address'],
    ]);

    jsonResponse(201, [
        'success'  => true,
        'message'  => 'Thank you! Your details have been registered.',
        'customer' => [
            'id'    => (int) $pdo->lastInsertId(),
            'name'  => $data['customerName'],
            'email' => $data['email'],
        ],
    ]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        jsonResponse(409, [
            'success' => false,
            'message' => 'An account with this email already exists.',
            'errors'  => ['email' => 'This email is already registered'],
        ]);
    }

    error_log('register error: ' . $e->getMessage());
    jsonResponse(500, ['success' => false, 'message' => 'Something went wrong. Please try again.']);
}
