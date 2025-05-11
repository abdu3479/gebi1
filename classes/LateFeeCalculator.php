<?php
require_once dirname(__DIR__) . '/config/database.php';

class LateFeeCalculator {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getActiveRule() {
        $query = "SELECT id, grace_period_days, fee_type, fee_value, max_fee, sms_reminder_days 
                  FROM late_fee_rules WHERE is_active = 1 LIMIT 1";
        $stmt = $this->conn->prepare($query);
        if (!$stmt) {
            throw new Exception("Prepare failed: " . $this->conn->error);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $rule = $result->fetch_assoc();
        $stmt->close();
        return $rule ?: null;
    }

    public function getSmsReminderDays() {
        $rule = $this->getActiveRule();
        return $rule ? (int)$rule['sms_reminder_days'] : 3;
    }

    public function calculateLateFee($baseAmount, $dueDate) {
        // Validate inputs
        if ($baseAmount <= 0) {
            return ['late_fee' => 0, 'message' => 'Base amount must be positive'];
        }

        if (empty($dueDate)) {
            return ['late_fee' => 0, 'message' => 'Due date is required'];
        }

        try {
            $today = new DateTime();
            $dueDateObj = new DateTime($dueDate);
            
            // Check if payment is not late
            if ($today <= $dueDateObj) {
                return ['late_fee' => 0, 'message' => 'Payment is not yet due'];
            }

            $rule = $this->getActiveRule();
            if (!$rule) {
                return ['late_fee' => 0, 'message' => 'No active late fee policy'];
            }

            // Calculate days late
            $daysLate = $today->diff($dueDateObj)->days;

            // Apply grace period if any
            $gracePeriod = (int)$rule['grace_period_days'];
            if ($daysLate <= $gracePeriod) {
                return [
                    'late_fee' => 0,
                    'message' => 'Within grace period',
                    'grace_period' => $gracePeriod,
                    'days_late' => $daysLate
                ];
            }
            $effectiveDaysLate = $daysLate - $gracePeriod;

            // Calculate late fee based on rule type
            $lateFee = 0;
            switch ($rule['fee_type']) {
                case 'percentage':
                    $lateFee = $baseAmount * ($rule['fee_value'] / 100) * $effectiveDaysLate;
                    break;
                case 'fixed':
                    $lateFee = $rule['fee_value'] * $effectiveDaysLate;
                    break;
                case 'flat':
                    $lateFee = $rule['fee_value'];
                    break;
            }

            // Apply maximum fee if specified
            if ($rule['max_fee'] > 0 && $lateFee > $rule['max_fee']) {
                $lateFee = $rule['max_fee'];
            }

            return [
                'late_fee' => round($lateFee, 2),
                'message' => 'Late fee calculated',
                'rule_id' => $rule['id'],
                'grace_period' => $gracePeriod,
                'days_late' => $daysLate,
                'effective_days_late' => $effectiveDaysLate,
                'fee_type' => $rule['fee_type'],
                'fee_value' => $rule['fee_value'],
                'max_fee' => $rule['max_fee']
            ];
        } catch (Exception $e) {
            error_log("Late fee calculation error: " . $e->getMessage());
            return ['late_fee' => 0, 'message' => 'Error calculating late fee'];
        }
    }
}
?>