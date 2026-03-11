<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');

$student = requireRole(['national', 'international']);
$pdo = getDbConnection();

$statement = $pdo->prepare(
    'SELECT c.id,
            c.title,
            c.description,
            c.status,
            c.created_at,
            c.updated_at,
            cat.name AS category_name,
            cat.route_to,
            assignee.id AS assigned_to_id,
            assignee.name AS assigned_to_name,
            assignee.role AS assigned_to_role
     FROM complaints c
     INNER JOIN categories cat ON cat.id = c.category_id
     INNER JOIN users assignee ON assignee.id = c.assigned_to
     WHERE c.student_id = :student_id
     ORDER BY c.created_at DESC'
);
$statement->execute([
    'student_id' => (int) $student['id'],
]);
$complaints = $statement->fetchAll();

foreach ($complaints as &$complaint) {
    $commentsStatement = $pdo->prepare(
        'SELECT cc.id, cc.message, cc.created_at, u.id AS user_id, u.name AS user_name, u.role AS user_role
         FROM complaint_comments cc
         INNER JOIN users u ON u.id = cc.user_id
         WHERE cc.complaint_id = :complaint_id
         ORDER BY cc.created_at ASC'
    );
    $commentsStatement->execute([
        'complaint_id' => (int) $complaint['id'],
    ]);
    $complaint['comments'] = $commentsStatement->fetchAll();
}
unset($complaint);

successResponse([
    'complaints' => $complaints,
], 'Complaints fetched successfully.');

