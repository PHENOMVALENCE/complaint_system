# Complaint Management System (CMS) API

REST CRUD API for the same database used by the complaint system. Use for integrations, mobile apps, or headless frontends.

## Base URL

- With XAMPP (project in subdir): `http://localhost/complaint_system/cms_api/`
- With rewrite: `http://localhost/complaint_system/cms_api/departments` (no `index.php`)

If mod_rewrite is off, use: `http://localhost/complaint_system/cms_api/index.php?path=departments`

## Endpoints

All responses are JSON. Success: `{ "success": true, "message": "...", "data": ... }`. Error: `{ "success": false, "message": "...", "errors": ... }`.

### Departments

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/departments` | List all departments |
| GET | `/departments/{id}` | Get one department |
| POST | `/departments` | Create department (body: `department_name`, optional `description`) |
| PUT | `/departments/{id}` | Update department (body: `department_name`, optional `description`) |
| DELETE | `/departments/{id}` | Delete department |

### Categories (complaint_categories)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/categories` | List all categories |
| GET | `/categories/{id}` | Get one category |
| POST | `/categories` | Create category (body: `category_name`, optional `description`) |
| PUT | `/categories/{id}` | Update category |
| DELETE | `/categories/{id}` | Delete category |

### Users

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/users` | List all users (no password in response) |
| GET | `/users/{id}` | Get one user by `user_id` |
| POST | `/users` | Create user (body: `username`, `password`, `role`, optional `approved`, `department_id`) |
| PUT | `/users/{id}` | Update user (body: optional `role`, `approved`, `department_id`, `password`) |
| DELETE | `/users/{id}` | Delete user |

Roles: `student`, `teacher`, `admin`, `department_officer`.

### Complaints

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/complaints` | List complaints. Query: `?student_username=`, `?department_id=`, `?status=`, `?category_id=` |
| GET | `/complaints/{id}` | Get one complaint |
| POST | `/complaints` | Create complaint (body: `title`, `student_username`, `complaint`, `department_id`, optional `category_id`, `is_anonymous`) |
| PUT | `/complaints/{id}` | Update complaint (body: optional `title`, `complaint`, `category_id`, `department_id`, `status`, `response`, `is_anonymous`) |
| DELETE | `/complaints/{id}` | Delete complaint |

## Request body

Send JSON with `Content-Type: application/json` for POST/PUT, or use form-encoded body.

## Examples

```bash
# List departments
curl -X GET "http://localhost/complaint_system/cms_api/departments"

# Create category
curl -X POST "http://localhost/complaint_system/cms_api/categories" \
  -H "Content-Type: application/json" \
  -d "{\"category_name\":\"New Category\",\"description\":\"Optional\"}"

# Update complaint status
curl -X PUT "http://localhost/complaint_system/cms_api/complaints/5" \
  -H "Content-Type: application/json" \
  -d "{\"status\":\"in_progress\"}"

# Delete user (by user_id)
curl -X DELETE "http://localhost/complaint_system/cms_api/users/10"
```

## Database

Uses the same MySQL database and `config/connect.php` as the main project (database: `complaintsystem`).
