<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/auth.php';

requireMethod('POST');

$user = requireAuth();

successResponse([
    'user_id' => (int) $user['id'],
], 'Logout successful. Discard the token on the client.');

