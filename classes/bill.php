<?php
require_once dirname(__DIR__) . '/config/database.php';

class Bill {
    private $conn;
    private $table_name = "bills";
    private $log_path = __DIR__ . '/../logs/sms_errors.log'; // Configurable log path

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($filters = []) {
        try {
            $query = "SELECT b.*, la.lease_number, 
                            a.asset_code, a.block, a.lot_number, a.kebele, a.size,
                            u.full_name as creator_name
                     FROM {$this->table_name} b
                     LEFT JOIN lease_agreements la ON b.lease_id = la.id
                     LEFT JOIN assets a ON la.asset_id = a.id
                     LEFT JOIN users u ON b.created_by = u.id";
    
            $conditions = [];
            $params = [];
            $types = '';

            if (!empty($filters)) {
                foreach ($filters as $key => $value) {
                    switch ($key) {
                        case 'status':
                            if (is_array($value)) {
                                $placeholders = implode(',', array_fill(0, count($value), '?'));
                                $conditions[] = "b.status IN ($placeholders)";
                                $params = array_merge($params, $value);
                                $types .= str_repeat('s', count($value));
                            }
                            break;
                        case 'date_range':
                            if (is_array($value) && count($value) === 2) {
                                $conditions[] = "b.billing_date BETWEEN ? AND ?";
                                $params[] = $value[0];
                                $params[] = $value[1];
                                $types .= 'ss';
                            }
                            break;
                        case 'lease_id':
                            $conditions[] = "b.lease_id = ?";
                            $params[] = $value;
                            $types .= 'i';
                            break;
                        case 'search':
                            $conditions[] = "(b.bill_number LIKE ? OR la.lease_number LIKE ?)";
                            $params[] = "%$value%";
                            $params[] = "%$value%";
                            $types .= 'ss';
                            break;
                        case 'asset_id':
                            $conditions[] = "la.asset_id = ?";
                            $params[] = $value;
                            $types .= 'i';
                            break;
                    }
                }
            }

            if (!empty($conditions)) {
                $query .= " WHERE " . implode(' AND ', $conditions);
            }

            $query .= " ORDER BY b.billing_date DESC";

            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            if (!empty($params)) {
                $stmt->bind_param($types, ...$params);
            }

            $stmt->execute();
            $result = $stmt->get_result();
            
            $bills = [];
            while ($row = $result->fetch_assoc()) {
                $bills[] = $row;
            }
            
            $stmt->close();
            return $bills;
        } catch (Exception $e) {
            error_log("Get all bills failed: " . $e->getMessage(), 3, $this->log_path);
            throw $e;
        }
    }

    public function getById($id) {
        try {
            $query = "SELECT b.*, la.lease_number, la.yearly_rent_rate,
                            a.asset_code, a.block, a.lot_number, a.kebele, a.size,
                            u.full_name as creator_name
                     FROM {$this->table_name} b
                     LEFT JOIN lease_agreements la ON b.lease_id = la.id
                     LEFT JOIN assets a ON la.asset_id = a.id
                     LEFT JOIN users u ON b.created_by = u.id
                     WHERE b.id = ? LIMIT 1";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $result = $stmt->get_result();
            $bill = $result->fetch_assoc();
            
            $stmt->close();
            return $bill ?: null;
        } catch (Exception $e) {
            error_log("Get bill by ID failed: " . $e->getMessage(), 3, $this->log_path);
            throw $e;
        }
    }

    private function getTaxRate() {
        try {
            $query = "SELECT setting_value FROM system_settings WHERE setting_key = 'tax_rate'";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            return $row['setting_value'] ?? 0.15; // Default to 15%
        } catch (Exception $e) {
            error_log("Get tax rate failed: " . $e->getMessage(), 3, $this->log_path);
            return 0.15;
        }
    }

    private function calculateLateFee($due_date, $base_amount) {
        try {
            $query = "SELECT fee_type, fee_value, grace_period_days FROM late_fee_rules WHERE is_active = 1 LIMIT 1";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            $rule = $result->fetch_assoc();
            $stmt->close();

            if (!$rule || strtotime($due_date) >= time()) {
                return 0;
            }

            $days_late = (time() - strtotime($due_date)) / (60 * 60 * 24);
            if ($days_late <= $rule['grace_period_days']) {
                return 0;
            }

            return $rule['fee_type'] === 'fixed' ? $rule['fee_value'] : ($base_amount * $rule['fee_value'] / 100);
        } catch (Exception $e) {
            error_log("Calculate late fee failed: " . $e->getMessage(), 3, $this->log_path);
            return 0;
        }
    }

    private function validateStatus($status) {
        $valid_statuses = ['pending', 'paid', 'overdue', 'cancelled'];
        if (!in_array($status, $valid_statuses)) {
            throw new Exception("Invalid status: $status");
        }
        return $status;
    }

    private function validateDate($date) {
        $format = 'Y-m-d';
        $dateTime = DateTime::createFromFormat($format, $date);
        if (!$dateTime || $dateTime->format($format) !== $date) {
            throw new Exception("Invalid date format: $date. Expected YYYY-MM-DD");
        }
        return $date;
    }

    public function logAudit($action, $record_id, $old_values, $new_values, $user_id = 1) {
        try {
            $query = "INSERT INTO audit_logs (user_id, action_type, table_name, record_id, old_values, new_values, ip_address, user_agent, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
            $stmt = $this->conn->prepare($query);
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
            $table = $this->table_name;
            $old_values_json = json_encode($old_values);
            $new_values_json = json_encode($new_values);
            $stmt->bind_param("ississss", $user_id, $action, $table, $record_id, $old_values_json, $new_values_json, $ip, $user_agent);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            error_log("Audit log failed: " . $e->getMessage(), 3, $this->log_path);
        }
    }

    private function queueSMS($bill_id, $data, $lang = 'en') {
        try {
            $bill = $this->getById($bill_id);
            if (!$bill) {
                throw new Exception("Bill not found for SMS queuing");
            }

            $query = "SELECT o.phone, o.id AS owner_id 
                     FROM lease_agreements la 
                     JOIN owners o ON la.owner_id = o.id 
                     WHERE la.id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $bill['lease_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $owner = $result->fetch_assoc();
            $stmt->close();

            if ($owner['phone']) {
                $template_name = $lang === 'am' ? 'new_bill_am' : 'new_bill_en';
                $template_query = "SELECT template FROM sms_templates WHERE name = ? AND is_active = 1";
                $stmt = $this->conn->prepare($template_query);
                $stmt->bind_param("s", $template_name);
                $stmt->execute();
                $result = $stmt->get_result();
                $template = $result->fetch_assoc()['template'] ?? ($lang === 'am' 
                    ? 'አዲስ ቢል {{bill_number}} ብር {{amount}} እስከ {{due_date}} ለከበሌ {{kebele}} መክፈል አለበት'
                    : 'New bill {{bill_number}} of {{amount}} ETB due by {{due_date}} for Kebele {{kebele}}.'
                );
                $stmt->close();

                $message = str_replace(
                    ['{{amount}}', '{{due_date}}', '{{kebele}}', '{{bill_number}}'],
                    [$data['total_amount'], $data['due_date'], $bill['kebele'] ?? 'N/A', $data['bill_number']],
                    $template
                );

                $sms_query = "INSERT INTO sms_notifications (template_id, recipient_type, recipient_id, phone_number, message, status, send_date, created_by)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt = $this->conn->prepare($sms_query);
                $template_id = $lang === 'am' ? 2 : 1; // Assume template_id 1 for en, 2 for am
                $recipient_type = 'owner';
                $recipient_id = $owner['owner_id'];
                $status = 'pending';
                $send_date = date('Y-m-d H:i:s');
                $created_by = $data['created_by'];
                $stmt->bind_param("issssssi", $template_id, $recipient_type, $recipient_id, $owner['phone'], $message, $status, $send_date, $created_by);
                $stmt->execute();
                $stmt->close();
            }
        } catch (Exception $e) {
            error_log("Queue SMS failed: " . $e->getMessage(), 3, $this->log_path);
        }
    }

    public function create($data) {
    $this->conn->begin_transaction();
    
    try {
        // Define allowed status values
        $allowedStatuses = ['pending', 'paid', 'overdue', 'cancelled'];
        
        // Validate required fields
        $required = ['bill_number', 'lease_id', 'billing_date', 'due_date', 'base_amount', 'created_by'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new Exception("Missing required field: $field");
            }
        }

        // Validate inputs
        $data['billing_date'] = $this->validateDate($data['billing_date']);
        $data['due_date'] = $this->validateDate($data['due_date']);
        if (strtotime($data['due_date']) < strtotime($data['billing_date'])) {
            throw new Exception("Due date cannot be before billing date");
        }
        $data['base_amount'] = filter_var($data['base_amount'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.01]]);
        if ($data['base_amount'] === false) {
            throw new Exception("Base amount must be greater than 0");
        }
        $data['tax_amount'] = filter_var($data['tax_amount'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: 0;
        $data['late_fee'] = $data['late_fee'] ?? 0; // Set to 0 as per add.php
        $data['additional_charges'] = filter_var($data['additional_charges'] ?? 0, FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: 0;
        
        // Validate status
        $data['status'] = $data['status'] ?? 'pending';
        if (!in_array($data['status'], $allowedStatuses)) {
            throw new Exception("Invalid status: {$data['status']}. Allowed values are: " . implode(', ', $allowedStatuses));
        }
        
        $data['notes'] = $data['notes'] ?? '';
        $data['late_fee_applied'] = $data['late_fee'] > 0 ? 1 : 0;
        $data['late_fee_amount'] = $data['late_fee'];
        $data['total_amount'] = $data['base_amount'] + $data['tax_amount'] + $data['late_fee'] + $data['additional_charges'];

        $query = "INSERT INTO {$this->table_name} (
            bill_number, lease_id, billing_date, due_date, 
            base_amount, tax_amount, late_fee, total_amount, 
            status, notes, created_by, created_at, late_fee_applied, late_fee_amount,
            additional_charges
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }

        $stmt->bind_param(
            "sisssddddssiid",
            $data['bill_number'],
            $data['lease_id'],
            $data['billing_date'],
            $data['due_date'],
            $data['base_amount'],
            $data['tax_amount'],
            $data['late_fee'],
            $data['total_amount'],
            $data['status'],
            $data['notes'],
            $data['created_by'],
            $data['late_fee_applied'],
            $data['late_fee_amount'],
            $data['additional_charges']
        );

        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        $bill_id = $this->conn->insert_id;
        $this->logAudit('create', $bill_id, [], $data, $data['created_by']);
        $this->queueSMS($bill_id, $data, isset($_SESSION['lang']) ? $_SESSION['lang'] : 'en');
        $this->conn->commit();
        $stmt->close();

        return [
            'success' => true,
            'bill_id' => $bill_id,
            'bill_number' => $data['bill_number'],
            'total_amount' => $data['total_amount']
        ];

    } catch (Exception $e) {
        $this->conn->rollback();
        if (isset($stmt)) {
            $stmt->close();
        }
        
        error_log("Bill creation error: " . $e->getMessage() . "\nData: " . print_r($data, true), 3, $this->log_path);
        
        return [
            'success' => false,
            'message' => $e->getMessage(),
            'error_code' => $this->conn->errno ?? null,
            'error_details' => $this->conn->error ?? null
        ];
    }
}

    public function update($id, $data) {
        $this->conn->begin_transaction();
        
        try {
            $current = $this->getById($id);
            if (!$current) {
                throw new Exception("Bill not found");
            }

            // Validate inputs
            $data['billing_date'] = $this->validateDate($data['billing_date'] ?? $current['billing_date']);
            $data['due_date'] = $this->validateDate($data['due_date'] ?? $current['due_date']);
            if (strtotime($data['due_date']) < strtotime($data['billing_date'])) {
                throw new Exception("Due date cannot be before billing date");
            }
            $data['base_amount'] = filter_var($data['base_amount'] ?? $current['base_amount'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0.01]]);
            if ($data['base_amount'] === false) {
                throw new Exception("Base amount must be greater than 0");
            }
            $data['tax_amount'] = filter_var($data['tax_amount'] ?? $current['tax_amount'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: 0;
            $data['late_fee'] = $data['late_fee'] ?? $current['late_fee'];
            $data['additional_charges'] = filter_var($data['additional_charges'] ?? $current['additional_charges'], FILTER_VALIDATE_FLOAT, ['options' => ['min_range' => 0]]) ?: 0;
            $data['status'] = $this->validateStatus($data['status'] ?? $current['status']);
            $data['notes'] = $data['notes'] ?? $current['notes'];
            $data['total_amount'] = $data['base_amount'] + $data['tax_amount'] + $data['late_fee'] + $data['additional_charges'];
            $data['late_fee_applied'] = $data['late_fee'] > 0 ? 1 : 0;
            $data['late_fee_amount'] = $data['late_fee'];

            $query = "UPDATE {$this->table_name} 
                     SET bill_number = ?, lease_id = ?, billing_date = ?, due_date = ?, 
                         base_amount = ?, tax_amount = ?, late_fee = ?, total_amount = ?, 
                         status = ?, notes = ?, late_fee_applied = ?, late_fee_amount = ?,
                         additional_charges = ?
                     WHERE id = ?";
            
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param(
                "sisssddddssidi",
                $data['bill_number'] ?? $current['bill_number'],
                $data['lease_id'] ?? $current['lease_id'],
                $data['billing_date'],
                $data['due_date'],
                $data['base_amount'],
                $data['tax_amount'],
                $data['late_fee'],
                $data['total_amount'],
                $data['status'],
                $data['notes'],
                $data['late_fee_applied'],
                $data['late_fee_amount'],
                $data['additional_charges'],
                $id
            );

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $affected_rows = $stmt->affected_rows;
            $this->logAudit('update', $id, $current, $data, $data['created_by'] ?? $_SESSION['user_id'] ?? 1);
            $this->conn->commit();
            $stmt->close();
            
            return [
                'success' => true,
                'affected_rows' => $affected_rows
            ];

        } catch (Exception $e) {
            $this->conn->rollback();
            if (isset($stmt)) {
                $stmt->close();
            }
            
            error_log("Bill update error: " . $e->getMessage(), 3, $this->log_path);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function generateBillNumber() {
        $this->conn->begin_transaction();
        try {
            $this->conn->query("CREATE TABLE IF NOT EXISTS bill_sequence (id INT PRIMARY KEY, last_number INT)");
            $this->conn->query("INSERT IGNORE INTO bill_sequence (id, last_number) VALUES (1, 0)");

            $query = "UPDATE bill_sequence SET last_number = last_number + 1 WHERE id = 1";
            $this->conn->query($query);

            $query = "SELECT last_number FROM bill_sequence WHERE id = 1 FOR UPDATE";
            $result = $this->conn->query($query);
            $row = $result->fetch_assoc();

            $bill_number = 'BILL-' . str_pad($row['last_number'], 6, '0', STR_PAD_LEFT);
            $this->conn->commit();
            return $bill_number;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Bill number generation failed: " . $e->getMessage(), 3, $this->log_path);
            return 'BILL-' . str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        }
    }

    public function delete($id) {
        $this->conn->begin_transaction();
        
        try {
            $bill = $this->getById($id);
            if (!$bill) {
                throw new Exception("Bill not found");
            }

            $payment_check = "SELECT COUNT(*) as payment_count FROM payments WHERE bill_id = ?";
            $stmt = $this->conn->prepare($payment_check);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row['payment_count'] > 0) {
                throw new Exception("Cannot delete bill with existing payments");
            }

            $charge_check = "SELECT COUNT(*) as charge_count FROM charges WHERE bill_id = ?";
            $stmt = $this->conn->prepare($charge_check);
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if ($row['charge_count'] > 0) {
                throw new Exception("Cannot delete bill with existing charges");
            }

            $query = "DELETE FROM {$this->table_name} WHERE id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $id);
            
            if (!$stmt->execute()) {
                throw new Exception("Delete failed: " . $stmt->error);
            }

            $this->logAudit('delete', $id, $bill, [], $_SESSION['user_id'] ?? 1);
            $this->conn->commit();
            $stmt->close();
            
            return [
                'success' => true,
                'deleted_id' => $id
            ];
            
        } catch (Exception $e) {
            $this->conn->rollback();
            if (isset($stmt)) {
                $stmt->close();
            }
            
            error_log("Bill deletion error: " . $e->getMessage(), 3, $this->log_path);
            
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }
}
?>