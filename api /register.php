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
    $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = db()->prepare(
        'INSERT INTO com_customer_phno_address_book
            (customername, phno_1, phno_2, mailid, address, password_hash)
         VALUES (:customername, :phno_1, :phno_2, :mailid, :address, :password_hash)'
    );

    $stmt->execute([
        ':customername'  => $data['customerName'],
        ':phno_1'        => $data['phone1'],
        ':phno_2'        => $data['phone2'],
        ':mailid'        => $data['email'],
        ':address'       => $data['address'],
        ':password_hash' => $passwordHash,
    ]);

    jsonResponse(201, [
        'success'  => true,
        'message'  => 'Account created successfully.',
        'customer' => [
            'id'    => (int) db()->lastInsertId(),
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
