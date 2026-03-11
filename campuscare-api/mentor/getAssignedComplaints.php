<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');

$mentor = requireRole(['mentor']);
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
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users s ON s.id = c.student_id
     WHERE c.assigned_to = :mentor_id
     ORDER BY c.updated_at DESC'
);
$statement->execute([
    'mentor_id' => (int) $mentor['id'],
]);

successResponse([
    'complaints' => $statement->fetchAll(),
], 'Assigned complaints fetched successfully.');

