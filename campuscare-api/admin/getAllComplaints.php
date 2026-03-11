<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');

requireRole(['admin']);
$pdo = getDbConnection();

$statement = $pdo->query(
    'SELECT c.id,
            c.title,
            c.description,
            c.status,
            c.created_at,
            c.updated_at,
            cat.id AS category_id,
            cat.name AS category_name,
            cat.route_to,
            s.id AS student_id,
            s.name AS student_name,
            s.email AS student_email,
            s.role AS student_role,
            assignee.id AS assigned_to_id,
            assignee.name AS assigned_to_name,
            assignee.role AS assigned_to_role
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users s ON s.id = c.student_id
     INNER JOIN users assignee ON assignee.id = c.assigned_to
     ORDER BY c.updated_at DESC'
);

successResponse([
    'complaints' => $statement->fetchAll(),
], 'All complaints fetched successfully.');

