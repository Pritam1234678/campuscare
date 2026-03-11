<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';

function requireRole(array $allowedRoles): array
{
    $user = requireAuth();

    if (!in_array($user['role'], $allowedRoles, true)) {
        errorResponse('You do not have permission to access this resource.', 403, [
            'allowed_roles' => $allowedRoles,
        ]);
    }

    return $user;
}

