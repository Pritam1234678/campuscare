<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('POST');

$student = requireRole(['national', 'international']);
$input = getJsonInput();
requireFields($input, ['category_id', 'title', 'description']);

$pdo = getDbConnection();
$categoryStatement = $pdo->prepare(
    'SELECT id, name, route_to
     FROM categories
     WHERE id = :category_id
     LIMIT 1'
);
$categoryStatement->execute([
    'category_id' => (int) $input['category_id'],
]);
$category = $categoryStatement->fetch();

if (!$category) {
    errorResponse('Complaint category was not found.', 404);
}

try {
    $assigneeId = resolveComplaintAssignee($pdo, $student, $category['route_to']);

    $insertComplaint = $pdo->prepare(
        'INSERT INTO complaints (student_id, category_id, title, description, assigned_to, status)
         VALUES (:student_id, :category_id, :title, :description, :assigned_to, :status)'
    );
    $insertComplaint->execute([
        'student_id' => (int) $student['id'],
        'category_id' => (int) $category['id'],
        'title' => trim((string) $input['title']),
        'description' => trim((string) $input['description']),
        'assigned_to' => $assigneeId,
        'status' => 'submitted',
    ]);

    $complaintId = (int) $pdo->lastInsertId();

    successResponse([
        'complaint_id' => $complaintId,
        'assigned_to' => $assigneeId,
        'route_to' => $category['route_to'],
        'status' => 'submitted',
    ], 'Complaint created successfully.', 201);
} catch (RuntimeException $exception) {
    errorResponse($exception->getMessage(), 422);
}

