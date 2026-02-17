<?php
/**
 * Categories (complaint_categories) CRUD: GET list, GET one, POST create, PUT update, DELETE
 */
$allowed = ['GET' => [null, 'id'], 'POST' => [null], 'PUT' => ['id'], 'DELETE' => ['id']];
if (!isset($allowed[$method]) || ($allowed[$method][0] === 'id' && $id === null)) {
    Response::methodNotAllowed();
}

$table = 'complaint_categories';
$pk = 'category_id';
$cols = 'category_id, category_name, description, created_at';

switch ($method) {
    case 'GET':
        if ($id !== null) {
            $stmt = $conn->prepare("SELECT $cols FROM $table WHERE $pk = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $row = $res->fetch_assoc();
            $stmt->close();
            if (!$row) {
                Response::notFound('Category');
            }
            Response::success($row);
        }
        $result = $conn->query("SELECT $cols FROM $table ORDER BY category_name");
        $list = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        Response::success($list);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $name = trim($input['category_name'] ?? '');
        $desc = trim($input['description'] ?? '');
        if ($name === '') {
            Response::error('category_name is required', 400);
        }
        $stmt = $conn->prepare("INSERT INTO $table (category_name, description) VALUES (?, ?)");
        $stmt->bind_param("ss", $name, $desc);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Create failed', 400);
        }
        $newId = $conn->insert_id;
        $stmt->close();
        $res = $conn->query("SELECT $cols FROM $table WHERE $pk = $newId");
        Response::success($res->fetch_assoc(), 'Category created', 201);
        break;

    case 'PUT':
        $stmt = $conn->prepare("SELECT $pk FROM $table WHERE $pk = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            Response::notFound('Category');
        }
        $stmt->close();
        $input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
        $name = trim($input['category_name'] ?? '');
        $desc = trim($input['description'] ?? '');
        if ($name === '') {
            Response::error('category_name is required', 400);
        }
        $stmt = $conn->prepare("UPDATE $table SET category_name = ?, description = ? WHERE $pk = ?");
        $stmt->bind_param("ssi", $name, $desc, $id);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Update failed', 400);
        }
        $stmt->close();
        $res = $conn->query("SELECT $cols FROM $table WHERE $pk = $id");
        Response::success($res->fetch_assoc(), 'Category updated');
        break;

    case 'DELETE':
        $stmt = $conn->prepare("SELECT $pk FROM $table WHERE $pk = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            $stmt->close();
            Response::notFound('Category');
        }
        $stmt->close();
        $stmt = $conn->prepare("DELETE FROM $table WHERE $pk = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            $stmt->close();
            Response::error($conn->error ?: 'Delete failed (check foreign keys)', 400);
        }
        $stmt->close();
        Response::success(null, 'Category deleted');
        break;
}
