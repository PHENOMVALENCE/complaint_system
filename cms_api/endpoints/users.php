<?php
/**
 * Users CRUD: GET list, GET one, POST create, PUT update, DELETE
 * Password must be hashed (e.g. password_hash in PHP).
 */
$allowed = ['GET' => [null, 'id'], 'POST' => [null], 'PUT' => ['id'], 'DELETE' => ['id']];
if (!isset($allowed[$method]) || ($allowed[$method][0] === 'id' && $id === null)) {
    Response::methodNotAllowed();
}

$cols = 'user_id, username, role, approved, department_id';

switch ($method) {
    case 'GET':
        if ($id !== null) {
            $stmt = $conn->prepare("SELECT $cols FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row) {
                Response::notFound('User');
            }
            Response::success($row);
        }
        $result = $conn->query("SELECT $cols FROM users ORDER BY username");
        $list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        Response::success($list);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $username = trim($input['username'] ?? '');
        $password = $input['password'] ?? '';
        $role = trim($input['role'] ?? 'student');
        $approved = isset($input['approved']) ? (int)(bool)$input['approved'] : 0;
        $department_id = isset($input['department_id']) ? (int)$input['department_id'] : null;
        if ($username === '') {
            Response::error('username is required', 400);
        }
        if ($password === '') {
            Response::error('password is required', 400);
        }
        $allowed_roles = ['student', 'teacher', 'admin', 'department_officer'];
        if (!in_array($role, $allowed_roles, true)) {
            Response::error('Invalid role', 400);
        }
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO users (username, password, role, approved, department_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $username, $hash, $role, $approved, $department_id);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Create failed (e.g. duplicate username)', 400);
        }
        $newId = $conn->insert_id;
        $stmt->close();
        $res = $conn->query("SELECT $cols FROM users WHERE user_id = $newId");
        Response::success($res->fetch_assoc(), 'User created', 201);
        break;

    case 'PUT':
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            Response::notFound('User');
        }
        $stmt->close();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $role = trim($input['role'] ?? '');
        $approved = array_key_exists('approved', $input) ? (int)(bool)$input['approved'] : null;
        $department_id = array_key_exists('department_id', $input) ? (int)$input['department_id'] : null;
        $password = $input['password'] ?? null;
        $allowed_roles = ['student', 'teacher', 'admin', 'department_officer'];
        $updates = [];
        $types = '';
        $params = [];
        if ($role !== '' && in_array($role, $allowed_roles, true)) {
            $updates[] = 'role = ?';
            $types .= 's';
            $params[] = $role;
        }
        if ($approved !== null) {
            $updates[] = 'approved = ?';
            $types .= 'i';
            $params[] = $approved;
        }
        if ($department_id !== null || (array_key_exists('department_id', $input))) {
            $updates[] = 'department_id = ?';
            $types .= 'i';
            $params[] = $department_id;
        }
        if ($password !== null && $password !== '') {
            $updates[] = 'password = ?';
            $types .= 's';
            $params[] = password_hash($password, PASSWORD_DEFAULT);
        }
        if (empty($updates)) {
            $res = $conn->query("SELECT $cols FROM users WHERE user_id = $id");
            Response::success($res->fetch_assoc(), 'No changes');
        }
        $params[] = $id;
        $types .= 'i';
        $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Update failed', 400);
        }
        $stmt->close();
        $res = $conn->query("SELECT $cols FROM users WHERE user_id = $id");
        Response::success($res->fetch_assoc(), 'User updated');
        break;

    case 'DELETE':
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            Response::notFound('User');
        }
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Delete failed (check foreign keys)', 400);
        }
        $stmt->close();
        Response::success(null, 'User deleted');
        break;
}
