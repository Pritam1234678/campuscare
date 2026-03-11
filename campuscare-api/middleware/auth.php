<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

function requireAuth(): array
{
    $token = getBearerToken();

    if ($token === null) {
        errorResponse('Authorization token is required.', 401);
    }

    $payload = verifyToken($token);

    if ($payload === null || !isset($payload['user_id'])) {
        errorResponse('Invalid or expired token.', 401);
    }

    $pdo = getDbConnection();
    $statement = $pdo->prepare(
        'SELECT id, name, email, role, roll_number, gender, phone, hostel_id, status, created_at
         FROM users
         WHERE id = :id
         LIMIT 1'
    );
    $statement->execute(['id' => (int) $payload['user_id']]);
    $user = $statement->fetch();

    if (!$user) {
        errorResponse('Authenticated user was not found.', 401);
    }

    if (($user['status'] ?? 'active') !== 'active') {
        errorResponse('Your account is disabled.', 403);
    }

    return $user;
}
