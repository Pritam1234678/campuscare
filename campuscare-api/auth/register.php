<?php
declare(strict_types=1);

require_once __DIR__ . '/../bootstrap.php';

requireMethod('POST');

$input = getJsonInput();
requireFields($input, ['name', 'email', 'password', 'role', 'roll_number', 'gender', 'phone', 'hostel_id']);


$allowedStudentRoles = ['national', 'international'];

if (!in_array($input['role'], $allowedStudentRoles, true)) {
    errorResponse('Only student registration is allowed on this endpoint.', 422, [
        'allowed_roles' => $allowedStudentRoles,
    ]);
}

if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
    errorResponse('A valid email address is required.', 422);
}

if (strlen((string) $input['password']) < 6) {
    errorResponse('Password must be at least 6 characters long.', 422);
}

$pdo = getDbConnection();
$hostelStatement = $pdo->prepare('SELECT id FROM hostels WHERE id = :hostel_id LIMIT 1');
$hostelStatement->execute(['hostel_id' => (int) $input['hostel_id']]);
if ($hostelStatement->fetchColumn() === false) {
    errorResponse('Selected hostel was not found.', 422);
}

try {
    $pdo->beginTransaction();

    $insertUser = $pdo->prepare(
        'INSERT INTO users (name, email, password, role, roll_number, gender, phone, hostel_id, status)
         VALUES (:name, :email, :password, :role, :roll_number, :gender, :phone, :hostel_id, :status)'
    );
    $insertUser->execute([
        'name' => trim($input['name']),
        'email' => strtolower(trim($input['email'])),
        'password' => password_hash((string) $input['password'], PASSWORD_DEFAULT),
        'role' => $input['role'],
        'roll_number' => trim((string) $input['roll_number']),
        'gender' => trim((string) $input['gender']),
        'phone' => trim((string) $input['phone']),
        'hostel_id' => (int) $input['hostel_id'],
        'status' => 'active',
    ]);

    $studentId = (int) $pdo->lastInsertId();
    $mentorId = assignMentorToStudent($pdo, $studentId);
    $iroId = null;

    if ($input['role'] === 'international') {
        $iroId = assignIroToStudent($pdo, $studentId);
    }

    $pdo->commit();

    $token = createToken([
        'user_id' => $studentId,
        'role' => $input['role'],
        'email' => strtolower(trim($input['email'])),
    ]);

    successResponse([
        'user_id' => $studentId,
        'role' => $input['role'],
        'mentor_id' => $mentorId,
        'iro_id' => $iroId,
        'token' => $token,
    ], 'Registration successful.', 201);
} catch (RuntimeException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    errorResponse($exception->getMessage(), 500);
} catch (PDOException $exception) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $message = $exception->getCode() === '23000'
        ? 'Email or roll number already exists.'
        : 'Registration failed.';

    errorResponse($message, 400, buildExceptionErrors($exception));
}
