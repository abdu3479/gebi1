<?php
class Lease {
    private $conn;
    private $table_name = "lease_agreements";

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create($data) {
        try {
            // Validate input data
            if (empty($data['asset_id']) || $data['asset_id'] < 1) {
                throw new Exception("Valid asset ID is required");
            }
            if (empty($data['owner_id']) || $data['owner_id'] < 1) {
                throw new Exception("Valid owner ID is required");
            }
            if (empty($data['lease_number'])) {
                throw new Exception("Lease number is required");
            }
            if (strlen($data['lease_number']) > 50) {
                throw new Exception("Lease number exceeds maximum length");
            }
            if (empty($data['start_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['start_date'])) {
                throw new Exception("Valid start date (YYYY-MM-DD) is required");
            }
            if (!empty($data['end_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['end_date'])) {
                throw new Exception("Invalid end date format (YYYY-MM-DD)");
            }
            if ($data['end_date'] && strtotime($data['end_date']) <= strtotime($data['start_date'])) {
                throw new Exception("End date must be after start date");
            }
            if ($data['monthly_rent'] <= 0) {
                throw new Exception("Monthly rent must be greater than 0");
            }
            if ($data['yearly_rent_rate'] < 0) {
                throw new Exception("Yearly rent rate cannot be negative");
            }
            if ($data['lease_term_of_years'] < 1) {
                throw new Exception("Lease term must be at least 1 year");
            }
            if (!in_array($data['payment_terms'], ['monthly', 'quarterly', 'annually'])) {
                throw new Exception("Invalid payment terms");
            }
            if (!in_array($data['status'], ['active', 'terminated', 'expired'])) {
                throw new Exception("Invalid status");
            }
            if (!empty($data['terms_conditions']) && strlen($data['terms_conditions']) > 65535) {
                throw new Exception("Terms and conditions exceed maximum length");
            }
            if (empty($data['created_by']) || $data['created_by'] < 1) {
                throw new Exception("Valid created_by user ID is required");
            }

            // Check for duplicate lease number
            $query = "SELECT id FROM {$this->table_name} WHERE lease_number = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $data['lease_number']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Lease number already exists");
            }
            $stmt->close();

            // Validate asset_id
            $query = "SELECT id FROM assets WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $data['asset_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Invalid asset ID: {$data['asset_id']}");
            }
            $stmt->close();

            // Validate owner_id
            $query = "SELECT id FROM owners WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $data['owner_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Invalid owner ID: {$data['owner_id']}");
            }
            $stmt->close();

            // Validate created_by
            $query = "SELECT id FROM users WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $data['created_by']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Invalid created_by user ID: {$data['created_by']}");
            }
            $stmt->close();

            // Determine status if not explicitly set
            if (!isset($data['status'])) {
                $data['status'] = $this->determineStatus($data['start_date'], $data['end_date']);
            }

            // Prepare insert query
            $query = "INSERT INTO {$this->table_name} (
                asset_id, owner_id, lease_number, start_date, end_date, monthly_rent,
                yearly_rent_rate, payment_terms, status, terms_conditions, created_by,
                lease_term_of_years, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            // Bind parameters
            $stmt->bind_param(
                "iisssddsssis",
                $data['asset_id'],
                $data['owner_id'],
                $data['lease_number'],
                $data['start_date'],
                $data['end_date'],
                $data['monthly_rent'],
                $data['yearly_rent_rate'],
                $data['payment_terms'],
                $data['status'],
                $data['terms_conditions'],
                $data['created_by'],
                $data['lease_term_of_years']
            );

            // Execute query
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            // Get the inserted lease ID
            $lease_id = $this->conn->insert_id;
            $stmt->close();

            return $lease_id;
        } catch (Exception $e) {
            error_log("Lease create failed: " . $e->getMessage());
            throw new Exception($e->getMessage(), 1003);
        }
    }

    public function generateLeaseNumber() {
        try {
            // Create lease_sequence table if it doesn't exist
            $query = "CREATE TABLE IF NOT EXISTS lease_sequence (
                id INT AUTO_INCREMENT PRIMARY KEY,
                stub TINYINT(1) DEFAULT 1
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
            if (!$this->conn->query($query)) {
                throw new Exception("Failed to create lease_sequence table: " . $this->conn->error);
            }

            // Verify table structure
            $query = "SHOW COLUMNS FROM lease_sequence LIKE 'stub'";
            $result = $this->conn->query($query);
            if ($result->num_rows === 0) {
                $query = "ALTER TABLE lease_sequence ADD COLUMN stub TINYINT(1) DEFAULT 1";
                if (!$this->conn->query($query)) {
                    throw new Exception("Failed to add stub column to lease_sequence: " . $this->conn->error);
                }
            }
            $result->free();

            // Insert a new row to get the next sequence number
            $query = "INSERT INTO lease_sequence (stub) VALUES (1)";
            if (!$this->conn->query($query)) {
                throw new Exception("Failed to generate sequence number: " . $this->conn->error);
            }

            $sequence_number = $this->conn->insert_id;
            return 'LSE-' . date('Y') . '-' . str_pad($sequence_number, 6, '0', STR_PAD_LEFT);
        } catch (Exception $e) {
            error_log("Generate lease number failed: " . $e->getMessage());
            throw new Exception("Failed to generate lease number: " . $e->getMessage());
        }
    }

    /**
 * Get all leases
 * @param array $params Optional filters (status, limit)
 * @return array List of leases
 * @throws Exception on database errors
 */
public function getAll($params = []) {
    try {
        $status = $params['status'] ?? null;
        $limit = isset($params['limit']) ? (int)$params['limit'] : 1000;

        $query = "SELECT l.id, l.lease_number, l.asset_id, l.owner_id, l.status, 
                         l.monthly_rent, l.yearly_rent_rate, l.payment_terms, 
                         l.start_date, l.end_date, l.created_at,
                         a.property_name, a.asset_code, a.block, a.lot_number, a.kebele,
                         CONCAT(o.first_name, ' ', o.last_name) AS owner_name,
                         o.phone AS owner_phone
                  FROM {$this->table_name} l
                  LEFT JOIN assets a ON l.asset_id = a.id
                  LEFT JOIN owners o ON l.owner_id = o.id";
        
        if ($status && in_array($status, ['active', 'inactive', 'expired'])) {
            $query .= " WHERE l.status = ?";
        }
        $query .= " ORDER BY l.created_at DESC";
        
        if ($limit > 0) {
            $query .= " LIMIT ?";
        }

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        if ($status && $limit > 0) {
            $stmt->bind_param("si", $status, $limit);
        } elseif ($status) {
            $stmt->bind_param("s", $status);
        } elseif ($limit > 0) {
            $stmt->bind_param("i", $limit);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $leases = [];
        while ($row = $result->fetch_assoc()) {
            // Calculate annual rent
            $row['annual_rent'] = $row['monthly_rent'] * 12;
            $leases[] = $row;
        }
        $stmt->close();
        return $leases;
    } catch (Exception $e) {
        error_log("Get all leases failed: " . $e->getMessage());
        throw new Exception("Failed to fetch leases: " . $e->getMessage(), 1004);
    }
}
    /**
     * Get lease by ID with calculated annual rent
     * @param int $id Lease ID
     * @return array Lease data with annual_rent
     * @throws Exception on database errors
     */
    public function getById($id) {
        try {
            if ($id < 1) {
                throw new Exception("Valid lease ID is required");
            }

            $query = "SELECT la.*, 
                             CONCAT(o.first_name, ' ', o.last_name) AS owner_name,
                             o.phone AS owner_phone,
                             a.property_name, a.asset_code, a.block, a.lot_number, a.kebele
                      FROM {$this->table_name} la
                      LEFT JOIN owners o ON la.owner_id = o.id
                      LEFT JOIN assets a ON la.asset_id = a.id
                      WHERE la.id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $lease = $result->fetch_assoc();
            $stmt->close();

            if (!$lease) {
                throw new Exception("Lease not found");
            }

            // Add calculated fields
            $lease['annual_rent'] = $lease['monthly_rent'] * 12;
            
            return $lease;
        } catch (Exception $e) {
            error_log("Get lease by ID failed: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function update($id, $data) {
        try {
            if ($id < 1) {
                throw new Exception("Valid lease ID is required");
            }

            // Validate input data (similar to create)
            if (isset($data['asset_id']) && ($data['asset_id'] < 1)) {
                throw new Exception("Valid asset ID is required");
            }
            if (isset($data['owner_id']) && ($data['owner_id'] < 1)) {
                throw new Exception("Valid owner ID is required");
            }
            if (isset($data['lease_number']) && empty($data['lease_number'])) {
                throw new Exception("Lease number is required");
            }
            if (isset($data['lease_number']) && strlen($data['lease_number']) > 50) {
                throw new Exception("Lease number exceeds maximum length");
            }
            if (isset($data['start_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['start_date'])) {
                throw new Exception("Valid start date (YYYY-MM-DD) is required");
            }
            if (isset($data['end_date']) && !empty($data['end_date']) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['end_date'])) {
                throw new Exception("Invalid end date format (YYYY-MM-DD)");
            }
            if (isset($data['end_date']) && $data['end_date'] && isset($data['start_date']) && strtotime($data['end_date']) <= strtotime($data['start_date'])) {
                throw new Exception("End date must be after start date");
            }
            if (isset($data['monthly_rent']) && $data['monthly_rent'] <= 0) {
                throw new Exception("Monthly rent must be greater than 0");
            }
            if (isset($data['yearly_rent_rate']) && $data['yearly_rent_rate'] < 0) {
                throw new Exception("Yearly rent rate cannot be negative");
            }
            if (isset($data['lease_term_of_years']) && $data['lease_term_of_years'] < 1) {
                throw new Exception("Lease term must be at least 1 year");
            }
            if (isset($data['payment_terms']) && !in_array($data['payment_terms'], ['monthly', 'quarterly', 'annually'])) {
                throw new Exception("Invalid payment terms");
            }
            if (isset($data['status']) && !in_array($data['status'], ['active', 'terminated', 'expired'])) {
                throw new Exception("Invalid status");
            }
            if (isset($data['terms_conditions']) && strlen($data['terms_conditions']) > 65535) {
                throw new Exception("Terms and conditions exceed maximum length");
            }
            if (isset($data['created_by']) && $data['created_by'] < 1) {
                throw new Exception("Valid created_by user ID is required");
            }

            // Check for duplicate lease number (excluding current lease)
            if (isset($data['lease_number'])) {
                $query = "SELECT id FROM {$this->table_name} WHERE lease_number = ? AND id != ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("si", $data['lease_number'], $id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("Lease number already exists");
                }
                $stmt->close();
            }

            // Validate asset_id if provided
            if (isset($data['asset_id'])) {
                $query = "SELECT id FROM assets WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("i", $data['asset_id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception("Invalid asset ID: {$data['asset_id']}");
                }
                $stmt->close();
            }

            // Validate owner_id if provided
            if (isset($data['owner_id'])) {
                $query = "SELECT id FROM owners WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("i", $data['owner_id']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception("Invalid owner ID: {$data['owner_id']}");
                }
                $stmt->close();
            }

            // Validate created_by if provided
            if (isset($data['created_by'])) {
                $query = "SELECT id FROM users WHERE id = ?";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("i", $data['created_by']);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception("Invalid created_by user ID: {$data['created_by']}");
                }
                $stmt->close();
            }

            // Build update query dynamically
            $fields = [];
            $params = [];
            $types = "";
            if (isset($data['asset_id'])) {
                $fields[] = "asset_id = ?";
                $params[] = $data['asset_id'];
                $types .= "i";
            }
            if (isset($data['owner_id'])) {
                $fields[] = "owner_id = ?";
                $params[] = $data['owner_id'];
                $types .= "i";
            }
            if (isset($data['lease_number'])) {
                $fields[] = "lease_number = ?";
                $params[] = $data['lease_number'];
                $types .= "s";
            }
            if (isset($data['start_date'])) {
                $fields[] = "start_date = ?";
                $params[] = $data['start_date'];
                $types .= "s";
            }
            if (isset($data['end_date'])) {
                $fields[] = "end_date = ?";
                $params[] = $data['end_date'];
                $types .= "s";
            }
            if (isset($data['monthly_rent'])) {
                $fields[] = "monthly_rent = ?";
                $params[] = $data['monthly_rent'];
                $types .= "d";
            }
            if (isset($data['yearly_rent_rate'])) {
                $fields[] = "yearly_rent_rate = ?";
                $params[] = $data['yearly_rent_rate'];
                $types .= "d";
            }
            if (isset($data['payment_terms'])) {
                $fields[] = "payment_terms = ?";
                $params[] = $data['payment_terms'];
                $types .= "s";
            }
            if (isset($data['status'])) {
                $fields[] = "status = ?";
                $params[] = $data['status'];
                $types .= "s";
            }
            if (isset($data['terms_conditions'])) {
                $fields[] = "terms_conditions = ?";
                $params[] = $data['terms_conditions'];
                $types .= "s";
            }
            if (isset($data['created_by'])) {
                $fields[] = "created_by = ?";
                $params[] = $data['created_by'];
                $types .= "i";
            }
            if (isset($data['lease_term_of_years'])) {
                $fields[] = "lease_term_of_years = ?";
                $params[] = $data['lease_term_of_years'];
                $types .= "i";
            }
            $fields[] = "updated_at = NOW()";

            if (empty($fields)) {
                throw new Exception("No fields provided for update");
            }

            $query = "UPDATE {$this->table_name} SET " . implode(", ", $fields) . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            // Add ID to params
            $params[] = $id;
            $types .= "i";

            // Bind parameters dynamically
            $stmt->bind_param($types, ...$params);

            // Execute query
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            return $affected_rows > 0;
        } catch (Exception $e) {
            error_log("Lease update failed: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    public function delete($id) {
        try {
            if ($id < 1) {
                throw new Exception("Valid lease ID is required");
            }

            $query = "DELETE FROM {$this->table_name} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param("i", $id);
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affected_rows = $stmt->affected_rows;
            $stmt->close();

            return $affected_rows > 0;
        } catch (Exception $e) {
            error_log("Lease delete failed: " . $e->getMessage());
            throw new Exception($e->getMessage());
        }
    }

    private function determineStatus($start_date, $end_date) {
        $today = date('Y-m-d');
        if ($end_date && $end_date < $today) {
            return 'expired';
        }
        if ($start_date <= $today) {
            return 'active';
        }
        return 'active'; // Default for future start dates
    }
}
?>