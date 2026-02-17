<?php
/**
 * Departments CRUD: GET list, GET one, POST create, PUT update, DELETE
 */
$allowed = ['GET' => [null, 'id'], 'POST' => [null], 'PUT' => ['id'], 'DELETE' => ['id']];
if (!isset($allowed[$method]) || ($allowed[$method][0] === 'id' && $id === null)) {
    Response::methodNotAllowed();
}

switch ($method) {
    case 'GET':
        if ($id !== null) {
            $stmt = $conn->prepare("SELECT department_id, department_name, description, created_at FROM departments WHERE department_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row) {
                Response::notFound('Department');
            }
            Response::success($row);
        }
        $result = $conn->query("SELECT department_id, department_name, description, created_at FROM departments ORDER BY department_name");
        $list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        Response::success($list);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $name = trim($input['department_name'] ?? '');
        $desc = trim($input['description'] ?? '');
        if ($name === '') {
            Response::error('department_name is required', 400);
        }
        $stmt = $conn->prepare("INSERT INTO departments (department_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $desc);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Create failed', 400);
        }
        $newId = $conn->insert_id;
        $stmt->close();
        $res = $conn->query("SELECT department_id, department_name, description, created_at FROM departments WHERE department_id = $newId");
        Response::success($res->fetch_assoc(), 'Department created', 201);
        break;

    case 'PUT':
        $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            Response::notFound('Department');
        }
        $stmt->close();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $name = trim($input['department_name'] ?? '');
        $desc = trim($input['description'] ?? '');
        if ($name === '') {
            Response::error('department_name is required', 400);
        }
        $stmt = $conn->prepare("UPDATE departments SET department_name = ?, description = ? WHERE department_id = ?");
        $stmt->bind_param("ssi", $name, $desc, $id);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Update failed', 400);
        }
        $stmt->close();
        $res = $conn->query("SELECT department_id, department_name, description, created_at FROM departments WHERE department_id = $id");
        Response::success($res->fetch_assoc(), 'Department updated');
        break;

    case 'DELETE':
        $stmt = $conn->prepare("SELECT department_id FROM departments WHERE department_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            Response::notFound('Department');
        }
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM departments WHERE department_id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Delete failed (check foreign keys)', 400);
        }
        $stmt->close();
        Response::success(null, 'Department deleted');
        break;
}
