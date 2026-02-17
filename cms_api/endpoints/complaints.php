<?php
/**
 * Complaints CRUD: GET list, GET one, POST create, PUT update, DELETE
 */
$allowed = ['GET' => [null, 'id'], 'POST' => [null], 'PUT' => ['id'], 'DELETE' => ['id']];
if (!isset($allowed[$method]) || ($allowed[$method][0] === 'id' && $id === null)) {
    Response::methodNotAllowed();
}

$cols = 'complaint_id, title, student_username, complaint, category_id, department_id, routed_at, status, response, created_at, updated_at';
$hasAnonymous = false;
$checkCol = $conn->query("SHOW COLUMNS FROM complaints LIKE 'is_anonymous'");
if ($checkCol && $checkCol->num_rows > 0) {
    $hasAnonymous = true;
    $cols .= ', is_anonymous';
}

switch ($method) {
    case 'GET':
        if ($id !== null) {
            $stmt = $conn->prepare("SELECT $cols FROM complaints WHERE complaint_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row) {
                Response::notFound('Complaint');
            }
            Response::success($row);
        }
        $where = [];
        $params = [];
        $types = '';
        if (!empty($_GET['student_username'])) {
            $where[] = 'student_username = ?';
            $params[] = $_GET['student_username'];
            $types .= 's';
        }
        if (!empty($_GET['department_id'])) {
            $where[] = 'department_id = ?';
            $params[] = (int)$_GET['department_id'];
            $types .= 'i';
        }
        if (!empty($_GET['status'])) {
            $where[] = 'status = ?';
            $params[] = $_GET['status'];
            $types .= 's';
        }
        if (!empty($_GET['category_id'])) {
            $where[] = 'category_id = ?';
            $params[] = (int)$_GET['category_id'];
            $types .= 'i';
        }
        $sql = "SELECT $cols FROM complaints";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY created_at DESC";
        if (!empty($params)) {
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            $stmt->close();
        } else {
            $result = $conn->query($sql);
        }
        $list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        Response::success($list);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $title = trim($input['title'] ?? '');
        $student_username = trim($input['student_username'] ?? '');
        $complaint = trim($input['complaint'] ?? '');
        $category_id = isset($input['category_id']) ? (int)$input['category_id'] : null;
        $department_id = isset($input['department_id']) ? (int)$input['department_id'] : null;
        $is_anonymous = isset($input['is_anonymous']) ? (int)(bool)$input['is_anonymous'] : 0;
        if ($title === '') {
            Response::error('title is required', 400);
        }
        if ($student_username === '') {
            Response::error('student_username is required', 400);
        }
        if ($complaint === '') {
            Response::error('complaint is required', 400);
        }
        if (!$department_id) {
            Response::error('department_id is required', 400);
        }
        $userCheck = $conn->prepare("SELECT username FROM users WHERE username = ?");
        $userCheck->bind_param("s", $student_username);
        $userCheck->execute();
        if ($userCheck->get_result()->num_rows === 0) {
            $userCheck->close();
            Response::error('student_username must exist in users', 400);
        }
        $userCheck->close();
        $deptCheck = $conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
        $deptCheck->bind_param("i", $department_id);
        $deptCheck->execute();
        if ($deptCheck->get_result()->num_rows === 0) {
            $deptCheck->close();
            Response::error('department_id must exist in departments', 400);
        }
        $deptCheck->close();
        if ($hasAnonymous) {
            $stmt = $conn->prepare("INSERT INTO complaints (title, student_username, complaint, category_id, department_id, status, is_anonymous) VALUES (?, ?, ?, ?, ?, 'pending', ?)");
            $stmt->bind_param("sssiii", $title, $student_username, $complaint, $category_id, $department_id, $is_anonymous);
        } else {
            $stmt = $conn->prepare("INSERT INTO complaints (title, student_username, complaint, category_id, department_id, status) VALUES (?, ?, ?, ?, ?, 'pending')");
            $stmt->bind_param("sssii", $title, $student_username, $complaint, $category_id, $department_id);
        }
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Create failed', 400);
        }
        $newId = $conn->insert_id;
        $stmt->close();
        $res = $conn->query("SELECT $cols FROM complaints WHERE complaint_id = $newId");
        Response::success($res->fetch_assoc(), 'Complaint created', 201);
        break;

    case 'PUT':
        $stmt = $conn->prepare("SELECT complaint_id FROM complaints WHERE complaint_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            Response::notFound('Complaint');
        }
        $stmt->close();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $updates = [];
        $params = [];
        $types = '';
        $allowed_status = ['pending', 'in_progress', 'resolved', 'denied', 'awaiting_student_response'];
        if (isset($input['title']) && trim($input['title']) !== '') {
            $updates[] = 'title = ?';
            $params[] = trim($input['title']);
            $types .= 's';
        }
        if (isset($input['complaint'])) {
            $updates[] = 'complaint = ?';
            $params[] = $input['complaint'];
            $types .= 's';
        }
        if (array_key_exists('category_id', $input)) {
            $updates[] = 'category_id = ?';
            $params[] = $input['category_id'] === null ? null : (int)$input['category_id'];
            $types .= 'i';
        }
        if (array_key_exists('department_id', $input) && (int)$input['department_id'] > 0) {
            $updates[] = 'department_id = ?';
            $params[] = (int)$input['department_id'];
            $types .= 'i';
        }
        if (isset($input['status']) && in_array($input['status'], $allowed_status, true)) {
            $updates[] = 'status = ?';
            $params[] = $input['status'];
            $types .= 's';
        }
        if (isset($input['response'])) {
            $updates[] = 'response = ?';
            $params[] = $input['response'];
            $types .= 's';
        }
        if ($hasAnonymous && array_key_exists('is_anonymous', $input)) {
            $updates[] = 'is_anonymous = ?';
            $params[] = (int)(bool)$input['is_anonymous'];
            $types .= 'i';
        }
        if (empty($updates)) {
            $res = $conn->query("SELECT $cols FROM complaints WHERE complaint_id = $id");
            Response::success($res->fetch_assoc(), 'No changes');
        }
        $params[] = $id;
        $types .= 'i';
        $sql = "UPDATE complaints SET " . implode(', ', $updates) . " WHERE complaint_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Update failed', 400);
        }
        $stmt->close();
        $res = $conn->query("SELECT $cols FROM complaints WHERE complaint_id = $id");
        Response::success($res->fetch_assoc(), 'Complaint updated');
        break;

    case 'DELETE':
        $stmt = $conn->prepare("SELECT complaint_id FROM complaints WHERE complaint_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            Response::notFound('Complaint');
        }
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM complaints WHERE complaint_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Delete failed', 400);
        }
        $stmt->close();
        Response::success(null, 'Complaint deleted');
        break;
}
