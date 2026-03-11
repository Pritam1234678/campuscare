<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

requireMethod('POST');

$input = getJsonInput();
requireFields($input, ['email', 'password']);

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    errorResponse('A valid email address is required.', 422);
}

$pdo = getDbConnection();
$statement = $pdo->prepare(
    'SELECT id, name, email, password, role, roll_number, gender, phone, hostel_id, status, created_at
     FROM users
     WHERE email = :email
     LIMIT 1'
);
$statement->execute([
    'email' => strtolower(trim($input['email'])),
]);
$user = $statement->fetch();

if (!$user || !password_verify((string) $input['password'], $user['password'])) {
    errorResponse('Invalid email or password.', 401);
}

if (($user['status'] ?? 'active') !== 'active') {
    errorResponse('This account is disabled.', 403);
}

$token = createToken([
    'user_id' => (int) $user['id'],
    'role' => $user['role'],
    'email' => $user['email'],
]);

unset($user['password']);

successResponse([
    'user_id' => (int) $user['id'],
    'role' => $user['role'],
    'token' => $token,
    'user' => $user,
], 'Login successful.');
