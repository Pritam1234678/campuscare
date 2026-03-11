<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');

$iro = requireRole(['iro']);
$pdo = getDbConnection();

$statement = $pdo->prepare(
    'SELECT c.id,
            c.title,
            c.description,
            c.status,
            c.created_at,
            c.updated_at,
            cat.name AS category_name,
            s.id AS student_id,
            s.name AS student_name,
            s.roll_number,
            s.email AS student_email,
            s.phone AS student_phone
     FROM complaints c
     INNER JOIN users s ON s.id = c.student_id
     INNER JOIN categories cat ON cat.id = c.category_id
     WHERE c.assigned_to = :iro_id
       AND s.role = :student_role
     ORDER BY c.updated_at DESC'
);
$statement->execute([
    'iro_id' => (int) $iro['id'],
    'student_role' => 'international',
]);

successResponse([
    'complaints' => $statement->fetchAll(),
], 'International complaints fetched successfully.');

