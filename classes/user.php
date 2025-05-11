<?php
require_once'../config/database.php';

class User {
    private $conn;
    private $table_name = "users";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getById($id) {
        $query = "SELECT id, username, role, phone, is_active, last_login, login_ip 
                  FROM " . $this->table_name . " 
                  WHERE id = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        return $user ?: false;
    }

    public function authenticate($username, $password) {
        $query = "SELECT id, username, password, role, is_active 
                  FROM " . $this->table_name . " 
                  WHERE username = ? LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password']) && $user['is_active']) {
            $this->updateLoginInfo($user['id']);
            return $user;
        }
        return false;
    }

    public function create($data) {
        $query = "INSERT INTO " . $this->table_name . " 
                  (username, password, role, phone, email, first_name, last_name, is_active, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $password = password_hash($data['password'], PASSWORD_BCRYPT);
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $stmt->bind_param(
            "sssssssi",
            $data['username'],
            $password,
            $data['role'],
            $data['phone'],
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $is_active
        );
        $result = $stmt->execute();
        $user_id = $stmt->insert_id;
        $stmt->close();

        if ($result) {
            $this->logAction($user_id, 'create', 'users', $user_id, json_encode($data, JSON_UNESCAPED_UNICODE));
            return ['user_id' => $user_id];
        }
        return false;
    }

    public function update($id, $data) {
        $query = "UPDATE " . $this->table_name . " 
                  SET username = ?, role = ?, phone = ?, email = ?, first_name = ?, last_name = ?, is_active = ? 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $is_active = isset($data['is_active']) ? (int)$data['is_active'] : 1;
        $stmt->bind_param(
            "ssssssii",
            $data['username'],
            $data['role'],
            $data['phone'],
            $data['email'],
            $data['first_name'],
            $data['last_name'],
            $is_active,
            $id
        );
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            $this->logAction($id, 'update', 'users', $id, json_encode($data, JSON_UNESCAPED_UNICODE));
            return true;
        }
        return false;
    }

    private function updateLoginInfo($id) {
        $query = "UPDATE " . $this->table_name . " 
                  SET last_login = NOW(), login_ip = ? 
                  WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $stmt->bind_param("si", $ip, $id);
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }

    public function logAction($user_id, $action_type, $table_name, $record_id, $details) {
        $query = "INSERT INTO audit_logs 
                  (user_id, action_type, table_name, record_id, new_values, ip_address, user_agent, created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $stmt->bind_param(
            "ississs",
            $user_id,
            $action_type,
            $table_name,
            $record_id,
            $details,
            $ip,
            $user_agent
        );
        $result = $stmt->execute();
        $stmt->close();
        return $result;
    }
}
?>