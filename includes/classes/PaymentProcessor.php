<?php
/**
 * Payment Processor Factory
 * Manages payment method handlers (Cash, M-Pesa, Card)
 */

class PaymentProcessor {
    private $db;
    private $payment;

    public function __construct($db) {
        $this->db = $db;
        $this->payment = new Payment($db);
    }

    /**
     * Get appropriate payment handler based on method
     * 
     * @param string $payment_method 'cash', 'mpesa', or 'card'
     * @return object Handler instance
     */
    public function getHandler($payment_method) {
        switch ($payment_method) {
            case 'cash':
                return new CashPayment($this->db, $this->payment);
            case 'mpesa':
                return new MpesaPayment($this->db, $this->payment);
            case 'card':
                return new CardPayment($this->db, $this->payment);
            default:
                throw new Exception("Unknown payment method: $payment_method");
        }
    }

    /**
     * Process payment through appropriate handler
     * 
     * @param int $appointment_id Appointment ID
     * @param int $amount Amount in KES
     * @param string $payment_method Payment method
     * @param array $additional_data Additional data for specific methods
     * @return array Result
     */
    public function processPayment($appointment_id, $amount, $payment_method, $additional_data = []) {
        try {
            // Create payment record
            $result = $this->payment->create($appointment_id, $amount, $payment_method);
            if (!$result['ok']) {
                return $result;
            }

            $payment_id = $result['payment_id'];

            // Get handler for specific payment method
            $handler = $this->getHandler($payment_method);

            // Process through handler
            $process_result = $handler->process($payment_id, $amount, $additional_data);

            if (!$process_result['ok']) {
                // Mark as failed
                $this->payment->markAsFailed($payment_id, $process_result['message']);
                return $process_result;
            }

            return [
                'ok' => true,
                'payment_id' => $payment_id,
                'payment_method' => $payment_method,
                'status' => $process_result['status'] ?? 'pending',
                'message' => $process_result['message'],
                'data' => $process_result['data'] ?? []
            ];
        } catch (Exception $e) {
            error_log("PaymentProcessor::processPayment() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Payment processing failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get payment details with handler-specific info
     * 
     * @param int $payment_id Payment ID
     * @return array Payment details
     */
    public function getPaymentDetails($payment_id) {
        $payment = $this->payment->getPayment($payment_id);
        
        if (!$payment) {
            return null;
        }

        // Get handler-specific details
        if ($payment['payment_method'] === 'card') {
            $handler = $this->getHandler('card');
            $payment['card_details'] = $handler->getCardDetails($payment_id);
        } elseif ($payment['payment_method'] === 'mpesa') {
            // M-Pesa details can be fetched from mpesa_payments table
            $stmt = $this->db->prepare("
                SELECT * FROM mpesa_payments 
                WHERE checkout_request_id = ?
            ");
            $stmt->execute([$payment['reference_id'] ?? '']);
            $payment['mpesa_details'] = $stmt->fetch(PDO::FETCH_ASSOC);
        }

        return $payment;
    }

    /**
     * Validate payment before processing
     * 
     * @param int $appointment_id Appointment ID
     * @param int $amount Amount in KES
     * @param string $payment_method Payment method
     * @return array Validation result
     */
    public function validatePayment($appointment_id, $amount, $payment_method) {
        $errors = [];

        // Check if appointment exists
        $stmt = $this->db->prepare("SELECT * FROM appointments WHERE id = ?");
        $stmt->execute([$appointment_id]);
        $appointment = $stmt->fetch();

        if (!$appointment) {
            $errors[] = "Appointment not found";
        }

        // Check if amount is valid
        if ($amount <= 0) {
            $errors[] = "Amount must be greater than 0";
        }

        // Check if payment method is valid
        if (!in_array($payment_method, ['cash', 'mpesa', 'card'])) {
            $errors[] = "Invalid payment method";
        }

        // Check if already paid
        if ($appointment && $appointment['payment_status'] === 'paid') {
            $errors[] = "Appointment already paid";
        }

        if (!empty($errors)) {
            return [
                'ok' => false,
                'errors' => $errors,
                'message' => implode(', ', $errors)
            ];
        }

        return ['ok' => true];
    }

    /**
     * Get payment receipt
     * 
     * @param int $payment_id Payment ID
     * @return array Receipt data
     */
    public function generateReceipt($payment_id) {
        try {
            $payment = $this->getPaymentDetails($payment_id);

            if (!$payment) {
                return ['ok' => false, 'message' => 'Payment not found'];
            }

            // Generate receipt number if not exists
            $stmt = $this->db->prepare("SELECT id FROM receipts WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
            $receipt = $stmt->fetch();

            if (!$receipt) {
                $receipt_number = $this->generateReceiptNumber();
                $stmt = $this->db->prepare("
                    INSERT INTO receipts (receipt_number, payment_id) 
                    VALUES (?, ?)
                ");
                $stmt->execute([$receipt_number, $payment_id]);
            } else {
                $stmt = $this->db->prepare("SELECT receipt_number FROM receipts WHERE id = ?");
                $stmt->execute([$receipt['id']]);
                $receipt_data = $stmt->fetch();
                $receipt_number = $receipt_data['receipt_number'];
            }

            return [
                'ok' => true,
                'receipt_number' => $receipt_number,
                'payment' => $payment
            ];
        } catch (Exception $e) {
            error_log("PaymentProcessor::generateReceipt() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to generate receipt'
            ];
        }
    }

    /**
     * Generate unique receipt number
     * 
     * @return string Receipt number (RCP-YYYYMMDD-XXXXX)
     */
    private function generateReceiptNumber() {
        $date = date('Ymd');
        $random = str_pad(random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        return "RCP-{$date}-{$random}";
    }

    /**
     * Get daily sales summary
     * 
     * @param string $date Date in YYYY-MM-DD format
     * @return array Sales summary
     */
    public function getDailySummary($date) {
        try {
            $revenue_data = $this->payment->getDailyRevenue($date, 'all');

            $summary = [
                'date' => $date,
                'total_revenue' => 0,
                'by_method' => [],
                'transaction_count' => 0,
                'average_transaction' => 0
            ];

            foreach ($revenue_data as $method) {
                $summary['total_revenue'] += $method['total'] ?? 0;
                $summary['transaction_count'] += $method['count'] ?? 0;
                $summary['by_method'][$method['payment_method']] = [
                    'total' => $method['total'],
                    'count' => $method['count'],
                    'average' => $method['average']
                ];
            }

            $summary['average_transaction'] = $summary['transaction_count'] > 0 
                ? $summary['total_revenue'] / $summary['transaction_count'] 
                : 0;

            return [
                'ok' => true,
                'summary' => $summary
            ];
        } catch (Exception $e) {
            error_log("PaymentProcessor::getDailySummary() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to generate summary'
            ];
        }
    }
}

/**
 * Cash Payment Handler
 */
class CashPayment {
    private $db;
    private $payment;

    public function __construct($db, $payment) {
        $this->db = $db;
        $this->payment = $payment;
    }

    /**
     * Process cash payment
     * 
     * @param int $payment_id Payment ID
     * @param int $amount Amount in KES
     * @param array $data Additional data
     * @return array Result
     */
    public function process($payment_id, $amount, $data = []) {
        try {
            // For cash, mark as pending until confirmed by staff
            $this->payment->updateStatus($payment_id, 'pending', 'Cash payment initiated', $data['user_id'] ?? null);

            return [
                'ok' => true,
                'status' => 'pending',
                'message' => 'Cash payment initiated. Please complete payment at counter.',
                'data' => [
                    'payment_id' => $payment_id,
                    'amount' => $amount,
                    'message' => 'Ready to receive payment'
                ]
            ];
        } catch (Exception $e) {
            error_log("CashPayment::process() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Cash payment processing failed'
            ];
        }
    }

    /**
     * Confirm cash payment receipt
     * 
     * @param int $payment_id Payment ID
     * @param int $amount_received Amount received
     * @param int $confirmed_by User ID
     * @return array Result
     */
    public function confirmReceipt($payment_id, $amount_received, $confirmed_by = null) {
        return $this->payment->markAsPaid($payment_id, 'CASH-' . date('YmdHis'), $amount_received);
    }
}

/**
 * M-Pesa Payment Handler
 */
class MpesaPayment {
    private $db;
    private $payment;

    public function __construct($db, $payment) {
        $this->db = $db;
        $this->payment = $payment;
    }

    /**
     * Process M-Pesa payment (STK Push)
     * 
     * @param int $payment_id Payment ID
     * @param int $amount Amount in KES
     * @param array $data Phone number and other data
     * @return array Result
     */
    public function process($payment_id, $amount, $data = []) {
        try {
            require_once __DIR__ . '/../mpesa.php';

            $phone = $data['phone'] ?? '';
            if (!$phone) {
                return ['ok' => false, 'message' => 'Phone number required for M-Pesa'];
            }

            // Normalize phone to Kenyan format
            $phone = mpesaNormalizePhone($phone);

            // Initiate STK push
            $result = mpesaInitiateStkPush(
                $amount,
                $phone,
                'APT-' . $payment_id,
                'Appointment booking payment'
            );

            if (!$result['ok']) {
                return $result;
            }

            // Store checkout request ID for callback
            $stmt = $this->db->prepare("
                UPDATE payments 
                SET reference_id = ?, payment_status = 'pending', updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$result['response']['CheckoutRequestID'] ?? '', $payment_id]);

            // Update appointment with checkout request ID
            $stmt = $this->db->prepare("
                UPDATE appointments 
                SET mpesa_checkout_request_id = ?, payment_status = 'pending'
                WHERE id = (SELECT appointment_id FROM payments WHERE id = ?)
            ");
            $stmt->execute([$result['response']['CheckoutRequestID'] ?? '', $payment_id]);

            $this->payment->updateStatus($payment_id, 'pending', 'M-Pesa STK push initiated');

            return [
                'ok' => true,
                'status' => 'pending',
                'message' => 'M-Pesa STK prompt sent to ' . $phone,
                'data' => [
                    'payment_id' => $payment_id,
                    'checkout_request_id' => $result['response']['CheckoutRequestID'] ?? '',
                    'customer_message' => $result['response']['CustomerMessage'] ?? 'Please enter your M-Pesa PIN'
                ]
            ];
        } catch (Exception $e) {
            error_log("MpesaPayment::process() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'M-Pesa payment processing failed: ' . $e->getMessage()
            ];
        }
    }
}

/**
 * Card Payment Handler
 */
class CardPayment {
    private $db;
    private $payment;

    public function __construct($db, $payment) {
        $this->db = $db;
        $this->payment = $payment;
    }

    /**
     * Process card payment (Stripe)
     * 
     * @param int $payment_id Payment ID
     * @param int $amount Amount in KES
     * @param array $data Card and additional data
     * @return array Result
     */
    public function process($payment_id, $amount, $data = []) {
        try {
            // For card payments, return redirect URL to payment gateway
            // This is a placeholder - real implementation depends on Stripe/Pesapal setup
            
            $processor = $data['processor'] ?? 'stripe';
            
            if ($processor === 'stripe') {
                // Generate redirect URL for Stripe checkout
                $session_data = [
                    'payment_id' => $payment_id,
                    'amount' => $amount,
                    'timestamp' => time()
                ];
                
                $redirect_url = 'https://checkout.stripe.com/pay/' . base64_encode(json_encode($session_data));
            } else {
                // Pesapal or other processor
                $redirect_url = 'https://pesapal.com/payment/' . $payment_id;
            }

            $this->payment->updateStatus($payment_id, 'pending', "Card payment initiated via {$processor}");

            return [
                'ok' => true,
                'status' => 'pending',
                'message' => 'Redirecting to payment gateway',
                'data' => [
                    'payment_id' => $payment_id,
                    'redirect_url' => $redirect_url,
                    'processor' => $processor
                ]
            ];
        } catch (Exception $e) {
            error_log("CardPayment::process() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Card payment processing failed'
            ];
        }
    }

    /**
     * Handle card payment callback from processor
     * 
     * @param int $payment_id Payment ID
     * @param array $callback_data Data from processor
     * @return array Result
     */
    public function handleCallback($payment_id, $callback_data = []) {
        try {
            $status = $callback_data['status'] ?? 'failed';
            
            if ($status === 'success') {
                $this->payment->markAsPaid(
                    $payment_id,
                    $callback_data['transaction_id'] ?? '',
                    $callback_data['amount'] ?? null
                );

                // Store card details
                $stmt = $this->db->prepare("
                    INSERT INTO card_payments 
                    (payment_id, card_last_four, card_brand, processor_transaction_id, processor_response)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $payment_id,
                    $callback_data['card_last_four'] ?? '',
                    $callback_data['card_brand'] ?? '',
                    $callback_data['transaction_id'] ?? '',
                    json_encode($callback_data)
                ]);

                return ['ok' => true, 'message' => 'Card payment confirmed'];
            } else {
                return $this->payment->markAsFailed($payment_id, 'Card payment failed: ' . ($callback_data['error'] ?? 'Unknown error'));
            }
        } catch (Exception $e) {
            error_log("CardPayment::handleCallback() - " . $e->getMessage());
            return [
                'ok' => false,
                'message' => 'Failed to process callback'
            ];
        }
    }

    /**
     * Get stored card details
     * 
     * @param int $payment_id Payment ID
     * @return array Card details
     */
    public function getCardDetails($payment_id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM card_payments WHERE payment_id = ?");
            $stmt->execute([$payment_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log("CardPayment::getCardDetails() - " . $e->getMessage());
            return null;
        }
    }
}
