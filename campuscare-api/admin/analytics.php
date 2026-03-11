<?php
declare(strict_types=1);

require_once __DIR__ . '/../middleware/roleGuard.php';

requireMethod('GET');

requireRole(['admin']);
$pdo = getDbConnection();

$summary = [
    'total_users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'total_complaints' => (int) $pdo->query('SELECT COUNT(*) FROM complaints')->fetchColumn(),
    'open_complaints' => (int) $pdo->query(
        "SELECT COUNT(*) FROM complaints WHERE status IN ('submitted', 'in_progress', 'escalated')"
    )->fetchColumn(),
    'closed_complaints' => (int) $pdo->query(
        "SELECT COUNT(*) FROM complaints WHERE status = 'closed'"
    )->fetchColumn(),
];

$complaintsByStatus = $pdo->query(
    'SELECT status, COUNT(*) AS total
     FROM complaints
     GROUP BY status
     ORDER BY total DESC'
)->fetchAll();

$complaintsByCategory = $pdo->query(
    'SELECT cat.name AS category_name, cat.route_to, COUNT(c.id) AS total
     FROM categories cat
     LEFT JOIN complaints c ON c.category_id = cat.id
     GROUP BY cat.id, cat.name, cat.route_to
     ORDER BY total DESC, cat.name ASC'
)->fetchAll();

$usersByRole = $pdo->query(
    'SELECT role, COUNT(*) AS total
     FROM users
     GROUP BY role
     ORDER BY total DESC'
)->fetchAll();

successResponse([
    'summary' => $summary,
    'complaints_by_status' => $complaintsByStatus,
    'complaints_by_category' => $complaintsByCategory,
    'users_by_role' => $usersByRole,
], 'Analytics fetched successfully.');

