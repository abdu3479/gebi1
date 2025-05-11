<?php
class Asset {
    private $conn;
    private $table_name = "assets";

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Create a new asset
     * @param array $data Asset data
     * @return int The inserted asset ID
     * @throws Exception on validation or database errors
     */
   public function create($data) {
    try {
        // Validate input data
        if (empty($data['owner_id']) || $data['owner_id'] < 1) {
            throw new Exception("Valid owner ID is required");
        }
        if (empty($data['asset_code']) || strlen($data['asset_code']) > 50) {
            throw new Exception("Asset code is required and must not exceed 50 characters");
        }
        if (!empty($data['property_name']) && strlen($data['property_name']) > 100) {
            throw new Exception("Property name exceeds maximum length");
        }
        if (!empty($data['block']) && strlen($data['block']) > 50) {
            throw new Exception("Block exceeds maximum length");
        }
        if (!empty($data['kebele']) && strlen($data['kebele']) > 50) {
            throw new Exception("Kebele exceeds maximum length");
        }
        if (!empty($data['lot_number']) && strlen($data['lot_number']) > 50) {
            throw new Exception("Lot number exceeds maximum length");
        }
        if (empty($data['property_type_id']) || $data['property_type_id'] < 1) {
            throw new Exception("Valid property type ID is required");
        }
        if (isset($data['construction_type_id']) && $data['construction_type_id'] !== null && $data['construction_type_id'] < 1) {
            throw new Exception("Invalid construction type ID");
        }
        if (!empty($data['address']) && strlen($data['address']) > 255) {
            throw new Exception("Address exceeds maximum length");
        }
        if ($data['size'] <= 0) {
            throw new Exception("Size must be greater than 0");
        }
        if ($data['annual_lease_rate'] <= 0) {
            throw new Exception("Annual lease rate must be greater than 0");
        }
        if (empty($data['registration_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['registration_date'])) {
            throw new Exception("Valid registration date (YYYY-MM-DD) is required");
        }
        if (!in_array($data['status'], ['Active', 'Inactive', 'Under Maintenance', 'Leased', 'Sold', 'Confiscated'])) {
            throw new Exception("Invalid status");
        }
        if (!empty($data['document_path']) && strlen($data['document_path']) > 255) {
            throw new Exception("Document path exceeds maximum length");
        }
        if (!empty($data['registration_number']) && strlen($data['registration_number']) > 50) {
            throw new Exception("Registration number exceeds maximum length");
        }
        if (!empty($data['tax_identification_number']) && strlen($data['tax_identification_number']) > 50) {
            throw new Exception("Tax identification number exceeds maximum length");
        }
        if (!isset($data['has_electricity']) || !in_array($data['has_electricity'], [0, 1])) {
            throw new Exception("Invalid has_electricity value");
        }
        if (!isset($data['has_water']) || !in_array($data['has_water'], [0, 1])) {
            throw new Exception("Invalid has_water value");
        }
        if (!isset($data['has_sewage']) || !in_array($data['has_sewage'], [0, 1])) {
            throw new Exception("Invalid has_sewage value");
        }

        // Check for duplicate asset code
        $query = "SELECT id FROM {$this->table_name} WHERE asset_code = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $data['asset_code']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Asset code already exists");
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

        // Validate property_type_id
        $query = "SELECT id FROM property_types WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $data['property_type_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("Invalid property type ID: {$data['property_type_id']}");
        }
        $stmt->close();

        // Validate construction_type_id if provided
        if (!empty($data['construction_type_id'])) {
            $query = "SELECT id FROM construction_types WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $data['construction_type_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Invalid construction type ID: {$data['construction_type_id']}");
            }
            $stmt->close();
        }

        // Prepare insert query
        $query = "INSERT INTO {$this->table_name} (
            owner_id, asset_code, property_name, block, kebele, lot_number,
            property_type_id, construction_type_id, address, size, annual_lease_rate,
            status, registration_date, document_path, registration_number,
            tax_identification_number, has_electricity, has_water, has_sewage
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        // Bind parameters (19 parameters)
        $stmt->bind_param(
            "isssssissdssdsssiii",
            $data['owner_id'],                    // i
            $data['asset_code'],                  // s
            $data['property_name'],               // s
            $data['block'],                       // s
            $data['kebele'],                      // s
            $data['lot_number'],                  // s
            $data['property_type_id'],            // i
            $data['construction_type_id'],        // i (nullable)
            $data['address'],                     // s
            $data['size'],                        // d
            $data['annual_lease_rate'],           // d
            $data['status'],                      // s
            $data['registration_date'],           // s
            $data['document_path'],               // s
            $data['registration_number'],         // s
            $data['tax_identification_number'],   // s
            $data['has_electricity'],             // i
            $data['has_water'],                   // i
            $data['has_sewage']                   // i
        );

        // Execute query
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Get the inserted asset ID
        $asset_id = $this->conn->insert_id;
        $stmt->close();

        return $asset_id;
    } catch (Exception $e) {
        error_log("Asset create failed: " . $e->getMessage());
        throw new Exception($e->getMessage(), 1002);
    }
}
    /**
     * Get an asset by ID
     * @param int $id Asset ID
     * @return array|null Asset data or null if not found
     * @throws Exception on database errors
     */
    public function getById($id) {
        try {
        $query = "SELECT 
                    a.id, a.owner_id, a.asset_code, a.property_name, a.block, a.kebele, a.lot_number,
                    a.property_type_id, a.construction_type_id, a.address, a.size, a.annual_lease_rate,
                    a.status, a.registration_date, a.document_path, a.registration_number,
                    a.tax_identification_number, a.has_electricity, a.has_water, a.has_sewage,
                    a.construction_type, pt.name AS property_type_name
                  FROM {$this->table_name} a
                  LEFT JOIN property_types pt ON a.property_type_id = pt.id
                  WHERE a.id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $asset = $result->fetch_assoc();
        $stmt->close();
        return $asset ?: null;
    } catch (Exception $e) {
        error_log("Get asset by ID failed: " . $e->getMessage(), 3, $this->log_path);
        throw new Exception("Failed to fetch asset: " . $e->getMessage(), 1003);
    }
    }

    /**
     * Get all assets
     * @param string $status Optional status filter
     * @return array List of assets
     * @throws Exception on database errors
     */
    public function getAll($status = null) {
        try {
            $query = "SELECT 
                        id, owner_id, asset_code, property_name, block, kebele, lot_number,
                        property_type_id, construction_type_id, address, size, annual_lease_rate,
                        status, registration_date, document_path, registration_number,
                        tax_identification_number, has_electricity, has_water, has_sewage,
                        construction_type
                      FROM {$this->table_name}";
            if ($status && in_array($status, ['Active', 'Inactive', 'Under Maintenance', 'Leased', 'Sold', 'Confiscated'])) {
                $query .= " WHERE status = ?";
            }
            $query .= " ORDER BY registration_date DESC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            if ($status && in_array($status, ['Active', 'Inactive', 'Under Maintenance', 'Leased', 'Sold', 'Confiscated'])) {
                $stmt->bind_param("s", $status);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $assets = [];
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
            $stmt->close();
            return $assets;
        } catch (Exception $e) {
            error_log("Get all assets failed: " . $e->getMessage());
            throw new Exception("Failed to fetch assets: " . $e->getMessage(), 1004);
        }
    }

    /**
     * Update an asset
     * @param int $id Asset ID
     * @param array $data Updated asset data
     * @return bool True on success
     * @throws Exception on validation or database errors
     */
   public function update($id, $data) {
    try {
        // Validate input data
        if (empty($data['owner_id']) || $data['owner_id'] < 1) {
            throw new Exception("Valid owner ID is required");
        }
        if (empty($data['asset_code']) || strlen($data['asset_code']) > 50) {
            throw new Exception("Asset code is required and must not exceed 50 characters");
        }
        if (!empty($data['property_name']) && strlen($data['property_name']) > 100) {
            throw new Exception("Property name exceeds maximum length");
        }
        if (!empty($data['block']) && strlen($data['block']) > 50) {
            throw new Exception("Block exceeds maximum length");
        }
        if (!empty($data['kebele']) && strlen($data['kebele']) > 50) {
            throw new Exception("Kebele exceeds maximum length");
        }
        if (!empty($data['lot_number']) && strlen($data['lot_number']) > 50) {
            throw new Exception("Lot number exceeds maximum length");
        }
        if (empty($data['property_type_id']) || $data['property_type_id'] < 1) {
            throw new Exception("Valid property type ID is required");
        }
        if (isset($data['construction_type_id']) && $data['construction_type_id'] !== null && $data['construction_type_id'] < 1) {
            throw new Exception("Invalid construction type ID");
        }
        if (!empty($data['address']) && strlen($data['address']) > 255) {
            throw new Exception("Address exceeds maximum length");
        }
        if ($data['size'] <= 0) {
            throw new Exception("Size must be greater than 0");
        }
        if ($data['annual_lease_rate'] <= 0) {
            throw new Exception("Annual lease rate must be greater than 0");
        }
        if (empty($data['registration_date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $data['registration_date'])) {
            throw new Exception("Valid registration date (YYYY-MM-DD) is required");
        }
        if (!in_array($data['status'], ['Active', 'Inactive', 'Under Maintenance', 'Leased', 'Sold', 'Confiscated'])) {
            throw new Exception("Invalid status");
        }
        if (!empty($data['document_path']) && strlen($data['document_path']) > 255) {
            throw new Exception("Document path exceeds maximum length");
        }
        if (!empty($data['registration_number']) && strlen($data['registration_number']) > 50) {
            throw new Exception("Registration number exceeds maximum length");
        }
        if (!empty($data['tax_identification_number']) && strlen($data['tax_identification_number']) > 50) {
            throw new Exception("Tax identification number exceeds maximum length");
        }
        if (!isset($data['has_electricity']) || !in_array($data['has_electricity'], [0, 1])) {
            throw new Exception("Invalid has_electricity value");
        }
        if (!isset($data['has_water']) || !in_array($data['has_water'], [0, 1])) {
            throw new Exception("Invalid has_water value");
        }
        if (!isset($data['has_sewage']) || !in_array($data['has_sewage'], [0, 1])) {
            throw new Exception("Invalid has_sewage value");
        }

        // Check for duplicate asset code (excluding current asset)
        $query = "SELECT id FROM {$this->table_name} WHERE asset_code = ? AND id != ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $data['asset_code'], $id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception("Asset code already exists");
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

        // Validate property_type_id
        $query = "SELECT id FROM property_types WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $data['property_type_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            throw new Exception("Invalid property type ID: {$data['property_type_id']}");
        }
        $stmt->close();

        // Validate construction_type_id if provided
        if (!empty($data['construction_type_id'])) {
            $query = "SELECT id FROM construction_types WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $data['construction_type_id']);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                throw new Exception("Invalid construction type ID: {$data['construction_type_id']}");
            }
            $stmt->close();
        }

        // Prepare update query
        $query = "UPDATE {$this->table_name} SET 
            owner_id = ?, 
            asset_code = ?, 
            property_name = ?, 
            block = ?, 
            kebele = ?, 
            lot_number = ?, 
            property_type_id = ?, 
            construction_type_id = ?, 
            address = ?, 
            size = ?, 
            annual_lease_rate = ?, 
            status = ?, 
            registration_date = ?, 
            document_path = ?, 
            registration_number = ?, 
            tax_identification_number = ?, 
            has_electricity = ?, 
            has_water = ?, 
            has_sewage = ? 
            WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        // Bind parameters (20 parameters)
        $stmt->bind_param(
            "isssssissdssdsssiiii",
            $data['owner_id'],                    // i
            $data['asset_code'],                  // s
            $data['property_name'],               // s
            $data['block'],                       // s
            $data['kebele'],                      // s
            $data['lot_number'],                  // s
            $data['property_type_id'],            // i
            $data['construction_type_id'],        // i (nullable)
            $data['address'],                     // s
            $data['size'],                        // d
            $data['annual_lease_rate'],           // d
            $data['status'],                      // s
            $data['registration_date'],           // s
            $data['document_path'],               // s
            $data['registration_number'],         // s
            $data['tax_identification_number'],   // s
            $data['has_electricity'],             // i
            $data['has_water'],                   // i
            $data['has_sewage'],                  // i
            $id                                   // i
        );

        // Execute query
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $stmt->close();
        return true;
    } catch (Exception $e) {
        error_log("Asset update failed: " . $e->getMessage());
        throw new Exception($e->getMessage(), 1005);
    }
}
    /**
     * Delete an asset
     * @param int $id Asset ID
     * @return bool True on success
     * @throws Exception on database errors or if asset has dependencies
     */
    public function delete($id) {
        try {
            // Check if asset has associated leases
            $query = "SELECT id FROM lease_agreements WHERE asset_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Cannot delete asset with associated lease agreements");
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
            error_log("Asset delete failed: " . $e->getMessage());
            throw new Exception($e->getMessage(), 1006);
        }
    }

    /**
     * Search assets by asset_code, property_name, or kebele
     * @param string $search_term Search term
     * @return array List of matching assets
     * @throws Exception on database errors
     */
    public function search($search_term) {
        try {
            $search_term = "%{$search_term}%";
            $query = "SELECT 
                        id, owner_id, asset_code, property_name, block, kebele, lot_number,
                        property_type_id, construction_type_id, address, size, annual_lease_rate,
                        status, registration_date, document_path, registration_number,
                        tax_identification_number, has_electricity, has_water, has_sewage,
                        construction_type
                      FROM {$this->table_name} 
                      WHERE asset_code LIKE ? OR property_name LIKE ? OR kebele LIKE ? 
                      ORDER BY registration_date DESC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->bind_param("sss", $search_term, $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            $assets = [];
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
            $stmt->close();
            return $assets;
        } catch (Exception $e) {
            error_log("Asset search failed: " . $e->getMessage());
            throw new Exception("Failed to search assets: " . $e->getMessage(), 1007);
        }
    }

    /**
     * Get all active assets
     * @return array List of active assets
     * @throws Exception on database errors
     */
    public function getActiveAssets() {
        try {
            $query = "SELECT 
                        id, asset_code, property_name, kebele, lot_number, size, annual_lease_rate 
                      FROM {$this->table_name} 
                      WHERE status = 'Active' 
                      ORDER BY asset_code ASC";
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            $stmt->execute();
            $result = $stmt->get_result();
            $assets = [];
            while ($row = $result->fetch_assoc()) {
                $assets[] = $row;
            }
            $stmt->close();
            return $assets;
        } catch (Exception $e) {
            error_log("Get active assets failed: " . $e->getMessage());
            throw new Exception("Failed to fetch active assets: " . $e->getMessage(), 1008);
        }
    }

    /**
     * Get all property types
     * @return array List of active property types
     */
    /**
 * Get all active property types
 * @return array List of active property types
 * @throws Exception On database error
 */
/**
 * Get all property types
 * @return array List of property types
 * @throws Exception On database error
 */
public function getPropertyTypes() {
    try {
        $query = "SELECT id, name FROM property_types ORDER BY name";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare query for property types: " . $this->conn->error, 1002);
        }
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query for property types: " . $stmt->error, 1003);
        }

        $result = $stmt->get_result();
        $types = [];
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
        $stmt->close();

        // Log warning if no property types are found
        if (empty($types)) {
            error_log("Warning: No property types found in database.");
        }

        return $types;
    } catch (Exception $e) {
        error_log("Get property types failed: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        throw new Exception($e->getMessage(), $e->getCode()); // Re-throw for calling code
    }
}

/**
 * Get all construction types
 * @return array List of construction types
 * @throws Exception On database error
 */
public function getConstructionTypes() {
    try {
        $query = "SELECT id, name FROM construction_types ORDER BY name";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Failed to prepare query for construction types: " . $this->conn->error, 1004);
        }
        if (!$stmt->execute()) {
            throw new Exception("Failed to execute query for construction types: " . $stmt->error, 1005);
        }

        $result = $stmt->get_result();
        $types = [];
        while ($row = $result->fetch_assoc()) {
            $types[] = $row;
        }
        $stmt->close();

        // Log warning if no construction types are found
        if (empty($types)) {
            error_log("Warning: No construction types found in database.");
        }

        return $types;
    } catch (Exception $e) {
        error_log("Get construction types failed: " . $e->getMessage() . " (Code: " . $e->getCode() . ")");
        throw new Exception($e->getMessage(), $e->getCode()); // Re-throw for calling code
    }
}
}
?>