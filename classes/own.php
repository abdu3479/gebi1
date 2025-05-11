<?php
class Owner {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
        if (!$this->conn) {
            throw new Exception("Database connection is null");
        }
        if ($this->conn->connect_error) {
            throw new Exception("Database connection failed: " . $this->conn->connect_error);
        }
        $this->conn->set_charset('utf8mb4');
    }

    private function isConnectionActive() {
        return $this->conn && !$this->conn->connect_error && $this->conn->ping();
    }

    private function logAction($record_id, $action_type, $user_id, $details = null) {
        try {
            $stmt = $this->conn->prepare(
                "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent, created_at) 
                 VALUES (?, ?, 'owners', ?, ?, ?, ?, NOW())"
            );
            $details_json = $details ? json_encode($details, JSON_UNESCAPED_UNICODE) : null;
            $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $stmt->bind_param(
                'isisss',
                $user_id,
                $action_type,
                $record_id,
                $details_json,
                $ip_address,
                $user_agent
            );
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Error logging action for owner ID $record_id: " . $e->getMessage());
        }
    }

    private function validateData($data, $isUpdate = false) {
        $errors = [];
        $requiredFields = ['first_name', 'last_name'];

        foreach ($requiredFields as $field) {
            if (!$isUpdate || isset($data[$field])) {
                if (empty($data[$field])) {
                    $errors[] = "Field '$field' is required";
                }
            }
        }

        if (isset($data['phone']) && !empty($data['phone']) && !preg_match('/^\+251[79]\d{8}$/', $data['phone'])) {
            $errors[] = "Invalid phone number format (must be +2519... or +2517...)";
        }

        if (isset($data['email']) && !empty($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format";
        }

        if (isset($data['status']) && !in_array($data['status'], ['active', 'inactive'])) {
            $errors[] = "Invalid status value";
        }

        if (isset($data['is_active']) && !in_array($data['is_active'], [0, 1])) {
            $errors[] = "Invalid is_active value";
        }

        foreach (['first_name', 'last_name', 'address'] as $field) {
            if (isset($data[$field]) && !empty($data[$field]) && !mb_check_encoding($data[$field], 'UTF-8')) {
                $errors[] = "Field '$field' contains invalid characters";
            }
        }

        return $errors;
    }

    public function create($data) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $errors = $this->validateData($data);
        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode("; ", $errors));
        }
        $this->conn->begin_transaction();
        try {
            $query = "INSERT INTO owners (first_name, last_name, email, phone, id_number, address, status, is_active) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->conn->prepare($query);
            $data = array_merge([
                'first_name' => '',
                'last_name' => '',
                'email' => null,
                'phone' => null,
                'id_number' => null,
                'address' => null,
                'status' => 'active',
                'is_active' => 1
            ], $data);
            $stmt->bind_param(
                'sssssssi',
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'],
                $data['id_number'],
                $data['address'],
                $data['status'],
                $data['is_active']
            );
            $stmt->execute();
            $id = $this->conn->insert_id;
            $stmt->close();

            $this->logAction($id, 'create', $_SESSION['user_id'] ?? 0, $data);
            $this->conn->commit();
            return $id;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Owner creation failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function update($id, $data) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $errors = $this->validateData($data, true);
        if (!empty($errors)) {
            throw new Exception("Validation failed: " . implode("; ", $errors));
        }
        $this->conn->begin_transaction();
        try {
            $query = "UPDATE owners SET 
                      first_name = COALESCE(?, first_name),
                      last_name = COALESCE(?, last_name),
                      email = COALESCE(?, email),
                      phone = COALESCE(?, phone),
                      id_number = COALESCE(?, id_number),
                      address = COALESCE(?, address),
                      status = COALESCE(?, status),
                      is_active = COALESCE(?, is_active)
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $data = array_merge([
                'first_name' => null,
                'last_name' => null,
                'email' => null,
                'phone' => null,
                'id_number' => null,
                'address' => null,
                'status' => null,
                'is_active' => null
            ], $data);
            $stmt->bind_param(
                'ssssssssi',
                $data['first_name'],
                $data['last_name'],
                $data['email'],
                $data['phone'],
                $data['id_number'],
                $data['address'],
                $data['status'],
                $data['is_active'],
                $id
            );
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            if ($affected_rows > 0) {
                $this->logAction($id, 'update', $_SESSION['user_id'] ?? 0, $data);
            }
            $this->conn->commit();
            return $affected_rows > 0;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Owner update failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function delete($id) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $this->conn->begin_transaction();
        try {
            $stmt = $this->conn->prepare("DELETE FROM owners WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            if ($affected_rows > 0) {
                $this->logAction($id, 'delete', $_SESSION['user_id'] ?? 0);
            }
            $this->conn->commit();
            return $affected_rows > 0;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Owner deletion failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function getById($id) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $query = "SELECT id, first_name, last_name, full_name, email, phone, id_number, address, status, is_active, created_at 
                  FROM owners WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $owner = $result->fetch_assoc();
        $stmt->close();
        return $owner;
    }

    public function getAll($filters = []) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $query = "SELECT id, first_name, last_name, full_name, email, phone, id_number, address, status, is_active, created_at 
                  FROM owners WHERE 1=1";
        $params = [];
        $types = '';
        if (!empty($filters['phone'])) {
            $query .= " AND phone = ?";
            $params[] = $filters['phone'];
            $types .= 's';
        }
        if (!empty($filters['status'])) {
            $query .= " AND status = ?";
            $params[] = $filters['status'];
            $types .= 's';
        }
        $query .= " ORDER BY full_name ASC";
        $limit = max(1, (int)($filters['limit'] ?? 100));
        $offset = max(0, (int)($filters['offset'] ?? 0));
        $query .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        $stmt = $this->conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $owners = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $owners;
    }

    public function search($searchTerm, $limit = 100, $offset = 0) {
        if (!$this->isConnectionActive()) {
            throw new Exception("Database connection is not active");
        }
        $query = "SELECT id, first_name, last_name, full_name, email, phone, id_number, address, status, is_active, created_at 
                  FROM owners 
                  WHERE full_name LIKE ? OR first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? OR email LIKE ?
                  ORDER BY full_name ASC
                  LIMIT ? OFFSET ?";
        $searchTerm = "%$searchTerm%";
        $limit = max(1, (int)$limit);
        $offset = max(0, (int)$offset);
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param('sssssii', $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit, $offset);
        $stmt->execute();
        $result = $stmt->get_result();
        $owners = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
        return $owners;
    }
}
?>