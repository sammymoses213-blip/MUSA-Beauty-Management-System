<?php
/**
 * Payment Processing System
 * Base Payment class for managing payment transactions
 */

class Payment {
    private $db;
    private $payment_id;
    public $appointment_id;
    public $amount;
    public $payment_method;
    public $payment_status;
    public $reference_id;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Create a new payment record
     * 
     * @param int $appointment_id Appointment ID
     * @param int $amount Amount in KES
     * @param string $payment_method 'cash', 'mpesa', or 'card'
     * @return array Result with payment_id and success status
     */
    public function create($appointment_id, $amount, $payment_method = 'cash') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO payments 
                (appointment_id, amount, payment_method, payment_status) 
                VALUES (?, ?, ?, 'unpaid')
            ");
            
            $stmt->execute([$appointment_id, $amount, $payment_method]);
            
            $this->payment_id = $this->db->lastInsertId();
            $this->appointment_id = $appointment_id;
            $this->amount = $amount;
            $this->payment_method = $payment_method;
            $this->payment_status = 'unpaid';
            
            return [
                'ok' => true,
                'payment_id' => $this->payment_id,
                'message' => 'Payment record created'
            ];
        } catch (PDOException $e) {
            error_log("Payment::create() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to create payment record'
            ];
        }
    }

    /**
     * Update payment status
     * 
     * @param int $payment_id Payment ID
     * @param string $new_status New status
     * @param string $reason Reason for change
     * @param int $changed_by User ID who made the change
     * @return array Result
     */
    public function updateStatus($payment_id, $new_status, $reason = '', $changed_by = null) {
        try {
            // Get current status for audit log
            $stmt = $this->db->prepare("SELECT payment_status FROM payments WHERE id = ?");
            $stmt->execute([$payment_id]);
            $result = $stmt->fetch();
            $old_status = $result['payment_status'] ?? null;

            // Update payment status
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET payment_status = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$new_status, $payment_id]);

            // Log status change
            $stmt = $this->db->prepare("
                INSERT INTO payment_status_log 
                (payment_id, old_status, new_status, reason, changed_by) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$payment_id, $old_status, $new_status, $reason, $changed_by]);

            return [
                'ok' => true,
                'message' => 'Payment status updated'
            ];
        } catch (PDOException $e) {
            error_log("Payment::updateStatus() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to update payment status'
            ];
        }
    }

    /**
     * Mark payment as paid
     * 
     * @param int $payment_id Payment ID
     * @param string $reference_id Transaction reference
     * @param int $amount_paid Amount actually paid
     * @return array Result
     */
    public function markAsPaid($payment_id, $reference_id = '', $amount_paid = null) {
        try {
            $stmt = $this->db->prepare("
                SELECT amount FROM payments WHERE id = ?
            ");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch();

            if (!$payment) {
                return ['ok' => false, 'message' => 'Payment not found'];
            }

            $final_amount = $amount_paid ?? $payment['amount'];

            $stmt = $this->db->prepare("
                UPDATE payments 
                SET payment_status = 'paid', 
                    amount_paid = ?,
                    reference_id = ?,
                    paid_at = NOW(),
                    updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$final_amount, $reference_id, $payment_id]);

            // Also update appointment status to 'paid' if exists
            $stmt = $this->db->prepare("
                UPDATE appointments 
                SET payment_status = 'paid', amount_paid = ? 
                WHERE id = (SELECT appointment_id FROM payments WHERE id = ?)
            ");
            $stmt->execute([$final_amount, $payment_id]);

            return [
                'ok' => true,
                'message' => 'Payment marked as paid',
                'payment_id' => $payment_id
            ];
        } catch (PDOException $e) {
            error_log("Payment::markAsPaid() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to mark payment as paid'
            ];
        }
    }

    /**
     * Mark payment as failed
     * 
     * @param int $payment_id Payment ID
     * @param string $reason Failure reason
     * @return array Result
     */
    public function markAsFailed($payment_id, $reason = '') {
        try {
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET payment_status = 'failed', updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$payment_id]);

            // Update appointment payment_status
            $stmt = $this->db->prepare("
                UPDATE appointments 
                SET payment_status = 'failed' 
                WHERE id = (SELECT appointment_id FROM payments WHERE id = ?)
            ");
            $stmt->execute([$payment_id]);

            return [
                'ok' => true,
                'message' => 'Payment marked as failed',
                'payment_id' => $payment_id
            ];
        } catch (PDOException $e) {
            error_log("Payment::markAsFailed() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to mark payment as failed'
            ];
        }
    }

    /**
     * Get payment details
     * 
     * @param int $payment_id Payment ID
     * @return array Payment details or null
     */
    public function getPayment($payment_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       a.client_id, a.stylist_id, a.service_id, a.appointment_date,
                       s.name as service_name, s.price as service_price,
                       u.name as client_name, u.phone as client_phone
                FROM payments p
                LEFT JOIN appointments a ON p.appointment_id = a.id
                LEFT JOIN services s ON a.service_id = s.id
                LEFT JOIN users u ON a.client_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$payment_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Payment::getPayment() - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get payment by appointment ID
     * 
     * @param int $appointment_id Appointment ID
     * @return array Payment details or null
     */
    public function getPaymentByAppointment($appointment_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM payments WHERE appointment_id = ?
            ");
            $stmt->execute([$appointment_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Payment::getPaymentByAppointment() - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Apply discount to payment
     * 
     * @param int $payment_id Payment ID
     * @param string $discount_type 'percentage' or 'fixed'
     * @param int $discount_value Value or percentage
     * @param string $reason Discount reason
     * @return array Result
     */
    public function applyDiscount($payment_id, $discount_type, $discount_value, $reason = '') {
        try {
            $stmt = $this->db->prepare("SELECT amount FROM payments WHERE id = ?");
            $stmt->execute([$payment_id]);
            $payment = $stmt->fetch();

            if (!$payment) {
                return ['ok' => false, 'message' => 'Payment not found'];
            }

            $discount_amount = ($discount_type === 'percentage') 
                ? ($payment['amount'] * $discount_value / 100)
                : $discount_value;

            // Update payment discount
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET discount_amount = discount_amount + ? 
                WHERE id = ?
            ");
            $stmt->execute([$discount_amount, $payment_id]);

            // Log discount
            $stmt = $this->db->prepare("
                INSERT INTO payment_discounts 
                (payment_id, discount_type, discount_amount, reason) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$payment_id, $discount_type, $discount_amount, $reason]);

            return [
                'ok' => true,
                'discount_amount' => $discount_amount,
                'message' => 'Discount applied'
            ];
        } catch (PDOException $e) {
            error_log("Payment::applyDiscount() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to apply discount'
            ];
        }
    }

    /**
     * Refund payment
     * 
     * @param int $payment_id Payment ID
     * @param string $reason Refund reason
     * @param int $refunded_by User ID
     * @return array Result
     */
    public function refund($payment_id, $reason = '', $refunded_by = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET payment_status = 'refunded', 
                    refund_reason = ?,
                    refunded_at = NOW(),
                    updated_at = NOW()
                WHERE id = ? AND payment_status = 'paid'
            ");
            $stmt->execute([$reason, $payment_id]);

            if ($stmt->rowCount() === 0) {
                return ['ok' => false, 'message' => 'Only paid payments can be refunded'];
            }

            // Update appointment
            $stmt = $this->db->prepare("
                UPDATE appointments 
                SET payment_status = 'refunded' 
                WHERE id = (SELECT appointment_id FROM payments WHERE id = ?)
            ");
            $stmt->execute([$payment_id]);

            // Log refund
            $this->updateStatus($payment_id, 'refunded', $reason, $refunded_by);

            return [
                'ok' => true,
                'message' => 'Payment refunded',
                'payment_id' => $payment_id
            ];
        } catch (PDOException $e) {
            error_log("Payment::refund() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to refund payment'
            ];
        }
    }

    /**
     * Get daily revenue
     * 
     * @param string $date Date in YYYY-MM-DD format
     * @param string $filter Optional: 'all', 'cash', 'mpesa', 'card'
     * @return array Revenue data
     */
    public function getDailyRevenue($date, $filter = 'all') {
        try {
            $where = "WHERE DATE(paid_at) = ? AND payment_status = 'paid'";
            $params = [$date];

            if ($filter !== 'all') {
                $where .= " AND payment_method = ?";
                $params[] = $filter;
            }

            $stmt = $this->db->prepare("
                SELECT 
                    SUM(amount_paid) as total,
                    COUNT(*) as count,
                    payment_method,
                    AVG(amount_paid) as average
                FROM payments
                $where
                GROUP BY payment_method
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Payment::getDailyRevenue() - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get revenue by date range
     * 
     * @param string $start_date Start date (YYYY-MM-DD)
     * @param string $end_date End date (YYYY-MM-DD)
     * @return array Revenue data
     */
    public function getRevenueRange($start_date, $end_date) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    DATE(paid_at) as date,
                    SUM(amount_paid) as daily_total,
                    payment_method,
                    COUNT(*) as transaction_count
                FROM payments
                WHERE DATE(paid_at) BETWEEN ? AND ? 
                  AND payment_status = 'paid'
                GROUP BY DATE(paid_at), payment_method
                ORDER BY DATE(paid_at) DESC
            ");
            $stmt->execute([$start_date, $end_date]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Payment::getRevenueRange() - " . $e->getMessage());
            return [];
        }
    }
}
