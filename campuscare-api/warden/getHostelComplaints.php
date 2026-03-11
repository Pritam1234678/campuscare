<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');

$warden = requireRole(['warden']);
$pdo = getDbConnection();

$statement = $pdo->prepare(
    'SELECT c.id,
            c.title,
            c.description,
            c.status,
            c.created_at,
            c.updated_at,
            h.id AS hostel_id,
            h.hostel_name,
            cat.name AS category_name,
            s.id AS student_id,
            s.name AS student_name,
            s.roll_number,
            s.email AS student_email,
            s.phone AS student_phone
     FROM complaints c
     INNER JOIN users s ON s.id = c.student_id
     INNER JOIN hostels h ON h.id = s.hostel_id
     INNER JOIN hostel_wardens hw ON hw.hostel_id = h.id
     INNER JOIN categories cat ON cat.id = c.category_id
      WHERE hw.warden_id = :warden_id
       AND c.assigned_to = :assigned_to
      ORDER BY c.updated_at DESC'
);
$statement->execute([
    'warden_id' => (int) $warden['id'],
    'assigned_to' => (int) $warden['id'],
]);

successResponse([
    'complaints' => $statement->fetchAll(),
], 'Hostel complaints fetched successfully.');
