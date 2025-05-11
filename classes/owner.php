<?php

class Owner {
    private $conn;
    private $table_name = "owners";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new owner
     * @param array $data Owner data
     * @return int The inserted owner ID
     * @throws Exception on validation or database errors
     */
    public function create($data) {
        try {
            // Validate input data
            if (empty($data['first_name']) || strlen($data['first_name']) < 2) {
                throw new Exception("First name is required and must be at least 2 characters");
            }
            if (empty($data['last_name']) || strlen($data['last_name']) < 2) {
                throw new Exception("Last name is required and must be at least 2 characters");
            }
            if (empty($data['phone']) || !preg_match('/^\+251[79]\d{8}$/', $data['phone'])) {
                throw new Exception("Valid Ethiopian phone number (+251...) is required");
            }
            if (strlen($data['phone']) > 20) {
                throw new Exception("Phone number exceeds maximum length");
            }
            if (!empty($data['address']) && strlen($data['address']) > 100) {
                throw new Exception("Address exceeds maximum length");
            }
            if (!empty($data['id_number']) && strlen($data['id_number']) > 50) {
                throw new Exception("ID number exceeds maximum length");
            }
            if (!empty($data['photo']) && strlen($data['photo']) > 255) {
                throw new Exception("Photo path exceeds maximum length");
            }
            if (!in_array($data['status'], ['active', 'inactive'])) {
                throw new Exception("Invalid status");
            }
            if (!isset($data['is_active']) || !in_array((int)$data['is_active'], [0, 1])) {
                throw new Exception("Invalid is_active value");
            }

            // Check for duplicate phone number
            $query = "SELECT id FROM {$this->table_name} WHERE phone = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $data['phone']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Phone number already exists");
            }
            $stmt->close();

            // Prepare insert query
            $query = "INSERT INTO {$this->table_name} (
                first_name, last_name, phone, address, id_number, status, is_active, photo, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            // Bind parameters
            $stmt->bind_param(
                "ssssssis",
                $data['first_name'],
                $data['last_name'],
                $data['phone'],
                $data['address'],
                $data['id_number'],
                $data['status'],
                $data['is_active'],
                $data['photo']
            );

            // Execute query
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            // Get the inserted owner ID
            $owner_id = $this->conn->insert_id;
            $stmt->close();

            return $owner_id;
        } catch (Exception $e) {
            error_log("Owner create failed: " . $e->getMessage());
            throw new Exception($e->getMessage(), 1001);
        }
    }

    /**
     * Get an owner by ID
     * @param int $id Owner ID
     * @return array|null Owner data or null if not found
     * @throws Exception on database errors
     */
    public function getById($id) {
        try {
            $query = "SELECT id, first_name, last_name, phone, address, id_number, status, is_active, photo, created_at 
                      FROM {$this->table_name} 
                      WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $owner = $result->fetch_assoc();
            $stmt->close();
            return $owner ?: null;
        } catch (Exception $e) {
            error_log("Get owner by ID failed: " . $e->getMessage());
            throw new Exception("Failed to fetch owner: " . $e->getMessage(), 1002);
        }
    }

    /**
     * Get all owners
     * @param string $status Optional status filter ('active', 'inactive')
     * @return array List of owners
     * @throws Exception on database errors
     */
    public function getAll($status = null) {
        try {
            $query = "SELECT id, first_name, last_name, phone, address, id_number, status, is_active, photo, created_at 
                      FROM {$this->table_name}";
            if ($status && in_array($status, ['active', 'inactive'])) {
                $query .= " WHERE status = ?";
            }
            $query .= " ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            if ($status && in_array($status, ['active', 'inactive'])) {
                $stmt->bind_param("s", $status);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $owners = [];
            while ($row = $result->fetch_assoc()) {
                $owners[] = $row;
            }
            $stmt->close();
            return $owners;
        } catch (Exception $e) {
            error_log("Get all owners failed: " . $e->getMessage());
            throw new Exception("Failed to fetch owners: " . $e->getMessage(), 1003);
        }
    }

    /**
     * Update an owner
     * @param int $id Owner ID
     * @param array $data Updated owner data
     * @return bool True on success
     * @throws Exception on validation or database errors
     */
    public function update($id, $data) {
        try {
            // Validate input data
            if (empty($data['first_name']) || strlen($data['first_name']) < 2) {
                throw new Exception("First name is required and must be at least 2 characters");
            }
            if (empty($data['last_name']) || strlen($data['last_name']) < 2) {
                throw new Exception("Last name is required and must be at least 2 characters");
            }
            if (empty($data['phone']) || !preg_match('/^\+251[79]\d{8}$/', $data['phone'])) {
                throw new Exception("Valid Ethiopian phone number (+251...) is required");
            }
            if (strlen($data['phone']) > 20) {
                throw new Exception("Phone number exceeds maximum length");
            }
            if (!empty($data['address']) && strlen($data['address']) > 100) {
                throw new Exception("Address exceeds maximum length");
            }
            if (!empty($data['id_number']) && strlen($data['id_number']) > 50) {
                throw new Exception("ID number exceeds maximum length");
            }
            if (!empty($data['photo']) && strlen($data['photo']) > 255) {
                throw new Exception("Photo path exceeds maximum length");
            }
            if (!in_array($data['status'], ['active', 'inactive'])) {
                throw new Exception("Invalid status");
            }
            if (!isset($data['is_active']) || !in_array((int)$data['is_active'], [0, 1])) {
                throw new Exception("Invalid is_active value");
            }

            // Check for duplicate phone number (excluding current owner)
            $query = "SELECT id FROM {$this->table_name} WHERE phone = ? AND id != ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $data['phone'], $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Phone number already exists");
            }
            $stmt->close();

            // Prepare update query
            $query = "UPDATE {$this->table_name} SET 
                first_name = ?, 
                last_name = ?, 
                phone = ?, 
                address = ?, 
                id_number = ?, 
                status = ?, 
                is_active = ?, 
                photo = ? 
                WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            // Bind parameters
            $stmt->bind_param(
                "ssssssisi",
                $data['first_name'],
                $data['last_name'],
                $data['phone'],
                $data['address'],
                $data['id_number'],
                $data['status'],
                $data['is_active'],
                $data['photo'],
                $id
            );

            // Execute query
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $stmt->close();
            return true;
        } catch (Exception $e) {
            error_log("Owner update failed: " . $e->getMessage());
            throw new Exception($e->getMessage(), 1004);
        }
    }

    /**
     * Delete an owner
     * @param int $id Owner ID
     * @return bool True on success
     * @throws Exception on database errors or if owner has dependencies
     */
    public function delete($id) {
        try {
            // Check if owner has associated leases
            $query = "SELECT id FROM lease_agreements WHERE owner_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Cannot delete owner with associated lease agreements");
            }
            $stmt->close();

            // Prepare delete query
            $query = "DELETE FROM {$this->table_name} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("i", $id);

            // Execute query
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            return $affected_rows > 0;
        } catch (Exception $e) {
            error_log("Owner delete failed: " . $e->getMessage());
            throw new Exception($e->getMessage(), 1005);
        }
    }

    /**
     * Search owners by name or phone
     * @param string $search_term Search term
     * @return array List of matching owners
     * @throws Exception on database errors
     */
    public function search($search_term) {
        try {
            $search_term = "%{$search_term}%";
            $query = "SELECT id, first_name, last_name, phone, address, id_number, status, is_active, photo, created_at 
                      FROM {$this->table_name} 
                      WHERE first_name LIKE ? OR last_name LIKE ? OR phone LIKE ? 
                      ORDER BY created_at DESC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("sss", $search_term, $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            $owners = [];
            while ($row = $result->fetch_assoc()) {
                $owners[] = $row;
            }
            $stmt->close();
            return $owners;
        } catch (Exception $e) {
            error_log("Owner search failed: " . $e->getMessage());
            throw new Exception("Failed to search owners: " . $e->getMessage(), 1006);
        }
    }

    /**
     * Get all active owners
     * @return array List of active owners
     * @throws Exception on database errors
     */
    public function getActiveOwners() {
        try {
            $query = "SELECT id, first_name, last_name, phone 
                      FROM {$this->table_name} 
                      WHERE status = 'active' AND is_active = 1 
                      ORDER BY first_name ASC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $owners = [];
            while ($row = $result->fetch_assoc()) {
                $owners[] = $row;
            }
            $stmt->close();
            return $owners;
        } catch (Exception $e) {
            error_log("Get active owners failed: " . $e->getMessage());
            throw new Exception("Failed to fetch active owners: " . $e->getMessage(), 1007);
        }
    }
}
?>