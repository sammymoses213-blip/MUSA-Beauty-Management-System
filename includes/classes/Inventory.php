<?php
/**
 * Inventory Management System
 * Handles products, stock levels, purchase orders, and stock tracking
 */

class Inventory {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * Add a new product
     * 
     * @param string $name Product name
     * @param int $category_id Category ID
     * @param string $sku Stock keeping unit
     * @param int $unit_price Price in KES
     * @param int $reorder_point Minimum stock level
     * @param int $supplier_id Optional supplier ID
     * @param array $additional_data Additional product data
     * @return array Result
     */
    public function addProduct($name, $category_id, $sku, $unit_price, $reorder_point = 10, $supplier_id = null, $additional_data = []) {
        try {
            // Check if SKU already exists
            $stmt = $this->db->prepare("SELECT id FROM products WHERE sku = ?");
            $stmt->execute([$sku]);
            if ($stmt->fetch()) {
                return ['ok' => false, 'message' => 'SKU already exists'];
            }

            // Insert product
            $stmt = $this->db->prepare("
                INSERT INTO products 
                (name, category_id, sku, unit_price, reorder_point, supplier_id, description)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $name,
                $category_id,
                $sku,
                $unit_price,
                $reorder_point,
                $supplier_id,
                $additional_data['description'] ?? ''
            ]);

            $product_id = $this->db->lastInsertId();

            // Initialize stock level
            $stmt = $this->db->prepare("
                INSERT INTO stock_levels (product_id, quantity_on_hand)
                VALUES (?, 0)
            ");
            $stmt->execute([$product_id]);

            return [
                'ok' => true,
                'product_id' => $product_id,
                'message' => 'Product added successfully'
            ];
        } catch (PDOException $e) {
            error_log("Inventory::addProduct() - " . $e->getMessage());
            return ['ok' => false, 'message' => 'Failed to add product'];
        }
    }

    /**
     * Update product
     * 
     * @param int $product_id Product ID
     * @param array $data Updated data
     * @return array Result
     */
    public function updateProduct($product_id, $data) {
        try {
            $allowed_fields = ['name', 'category_id', 'unit_price', 'reorder_point', 'supplier_id', 'description', 'is_active'];
            $updates = [];
            $values = [];

            foreach ($allowed_fields as $field) {
                if (isset($data[$field])) {
                    $updates[] = "$field = ?";
                    $values[] = $data[$field];
                }
            }

            if (empty($updates)) {
                return ['ok' => false, 'message' => 'No fields to update'];
            }

            $values[] = $product_id;
            $sql = "UPDATE products SET " . implode(', ', $updates) . ", updated_at = NOW() WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);

            return ['ok' => true, 'message' => 'Product updated successfully'];
        } catch (PDOException $e) {
            error_log("Inventory::updateProduct() - " . $e->getMessage());
            return ['ok' => false, 'message' => 'Failed to update product'];
        }
    }

    /**
     * Get product details
     * 
     * @param int $product_id Product ID
     * @return array Product details with stock level
     */
    public function getProduct($product_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       pc.name as category_name,
                       s.quantity_on_hand,
                       s.quantity_reserved,
                       sup.name as supplier_name
                FROM products p
                LEFT JOIN product_categories pc ON p.category_id = pc.id
                LEFT JOIN stock_levels s ON p.id = s.product_id
                LEFT JOIN suppliers sup ON p.supplier_id = sup.id
                WHERE p.id = ?
            ");
            $stmt->execute([$product_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Inventory::getProduct() - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all products with filters
     * 
     * @param array $filters Optional filters (category_id, is_active, low_stock_only)
     * @return array Products list
     */
    public function getProducts($filters = []) {
        try {
            $where = "WHERE p.is_active = 1";
            $params = [];

            if (isset($filters['category_id']) && $filters['category_id']) {
                $where .= " AND p.category_id = ?";
                $params[] = $filters['category_id'];
            }

            if (isset($filters['low_stock_only']) && $filters['low_stock_only']) {
                $where .= " AND s.quantity_on_hand <= p.reorder_point";
            }

            $stmt = $this->db->prepare("
                SELECT p.*, 
                       pc.name as category_name,
                       s.quantity_on_hand,
                       s.quantity_reserved,
                       sup.name as supplier_name
                FROM products p
                LEFT JOIN product_categories pc ON p.category_id = pc.id
                LEFT JOIN stock_levels s ON p.id = s.product_id
                LEFT JOIN suppliers sup ON p.supplier_id = sup.id
                $where
                ORDER BY p.name
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Inventory::getProducts() - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update stock quantity
     * 
     * @param int $product_id Product ID
     * @param int $quantity Change in quantity (positive or negative)
     * @param string $movement_type 'in', 'out', 'adjustment', 'expiry', 'damage'
     * @param string $reason Reason for movement
     * @param int $created_by User ID
     * @return array Result
     */
    public function updateStock($product_id, $quantity, $movement_type = 'adjustment', $reason = '', $created_by = null) {
        try {
            // Get current stock level
            $stmt = $this->db->prepare("SELECT quantity_on_hand FROM stock_levels WHERE product_id = ?");
            $stmt->execute([$product_id]);
            $stock = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$stock) {
                return ['ok' => false, 'message' => 'Product not found'];
            }

            $new_quantity = $stock['quantity_on_hand'] + $quantity;

            if ($new_quantity < 0) {
                return ['ok' => false, 'message' => 'Insufficient stock. Current: ' . $stock['quantity_on_hand']];
            }

            // Update stock level
            $stmt = $this->db->prepare("
                UPDATE stock_levels 
                SET quantity_on_hand = ?, updated_at = NOW()
                WHERE product_id = ?
            ");
            $stmt->execute([$new_quantity, $product_id]);

            // Log movement
            $stmt = $this->db->prepare("
                INSERT INTO stock_movements 
                (product_id, movement_type, quantity, notes, created_by)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$product_id, $movement_type, $quantity, $reason, $created_by]);

            // Check if stock is now below reorder point
            $this->checkAndAlertLowStock($product_id);

            return [
                'ok' => true,
                'new_quantity' => $new_quantity,
                'message' => 'Stock updated successfully'
            ];
        } catch (PDOException $e) {
            error_log("Inventory::updateStock() - " . $e->getMessage());
            return ['ok' => false, 'message' => 'Failed to update stock'];
        }
    }

    /**
     * Check if product is below reorder point and create alert
     * 
     * @param int $product_id Product ID
     * @return bool Alert was created
     */
    private function checkAndAlertLowStock($product_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.reorder_point, s.quantity_on_hand
                FROM products p
                LEFT JOIN stock_levels s ON p.id = s.product_id
                WHERE p.id = ?
            ");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                return false;
            }

            if ($product['quantity_on_hand'] <= $product['reorder_point']) {
                // Check if alert already exists
                $stmt = $this->db->prepare("
                    SELECT id FROM low_stock_alerts 
                    WHERE product_id = ? AND acknowledged = 0
                ");
                $stmt->execute([$product_id]);

                if (!$stmt->fetch()) {
                    // Create new alert
                    $stmt = $this->db->prepare("
                        INSERT INTO low_stock_alerts (product_id)
                        VALUES (?)
                        ON DUPLICATE KEY UPDATE acknowledged = 0
                    ");
                    $stmt->execute([$product_id]);
                    return true;
                }
            }

            return false;
        } catch (PDOException $e) {
            error_log("Inventory::checkAndAlertLowStock() - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get low stock products
     * 
     * @return array Products below reorder point
     */
    public function getLowStockProducts() {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, 
                       pc.name as category_name,
                       s.quantity_on_hand,
                       s.quantity_reserved,
                       lsa.id as alert_id
                FROM products p
                LEFT JOIN product_categories pc ON p.category_id = pc.id
                LEFT JOIN stock_levels s ON p.id = s.product_id
                LEFT JOIN low_stock_alerts lsa ON p.id = lsa.product_id AND lsa.acknowledged = 0
                WHERE s.quantity_on_hand <= p.reorder_point
                AND p.is_active = 1
                ORDER BY s.quantity_on_hand ASC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Inventory::getLowStockProducts() - " . $e->getMessage());
            return [];
        }
    }

    /**
     * Acknowledge low stock alert
     * 
     * @param int $alert_id Alert ID
     * @param int $acknowledged_by User ID
     * @return array Result
     */
    public function acknowledgeLowStockAlert($alert_id, $acknowledged_by = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE low_stock_alerts 
                SET acknowledged = 1, acknowledged_by = ?, acknowledged_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([$acknowledged_by, $alert_id]);

            return ['ok' => true, 'message' => 'Alert acknowledged'];
        } catch (PDOException $e) {
            error_log("Inventory::acknowledgeLowStockAlert() - " . $e->getMessage());
            return ['ok' => false, 'message' => 'Failed to acknowledge alert'];
        }
    }

    /**
     * Create purchase order
     * 
     * @param int $supplier_id Supplier ID
     * @param array $items Array of ['product_id' => X, 'quantity' => Y, 'unit_price' => Z]
     * @param string $expected_delivery_date Optional delivery date
     * @param int $created_by User ID
     * @return array Result with PO ID
     */
    public function createPurchaseOrder($supplier_id, $items, $expected_delivery_date = null, $created_by = null) {
        try {
            if (empty($items)) {
                return ['ok' => false, 'message' => 'No items in purchase order'];
            }

            // Generate PO number
            $po_number = 'PO-' . date('Ymd') . '-' . str_pad(random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

            // Create PO
            $stmt = $this->db->prepare("
                INSERT INTO purchase_orders 
                (po_number, supplier_id, order_date, expected_delivery_date, total_amount, created_by)
                VALUES (?, ?, NOW(), ?, 0, ?)
            ");
            $stmt->execute([$po_number, $supplier_id, $expected_delivery_date, $created_by]);
            
            $po_id = $this->db->lastInsertId();

            // Add items
            $total = 0;
            foreach ($items as $item) {
                $line_total = $item['quantity'] * $item['unit_price'];
                $total += $line_total;

                $stmt = $this->db->prepare("
                    INSERT INTO purchase_order_items 
                    (purchase_order_id, product_id, quantity, unit_price, line_total)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$po_id, $item['product_id'], $item['quantity'], $item['unit_price'], $line_total]);
            }

            // Update total
            $stmt = $this->db->prepare("UPDATE purchase_orders SET total_amount = ? WHERE id = ?");
            $stmt->execute([$total, $po_id]);

            return [
                'ok' => true,
                'po_id' => $po_id,
                'po_number' => $po_number,
                'message' => 'Purchase order created successfully'
            ];
        } catch (PDOException $e) {
            error_log("Inventory::createPurchaseOrder() - " . $e->getMessage());
            return ['ok' => false, 'message' => 'Failed to create purchase order'];
        }
    }

    /**
     * Record product usage in appointment
     * 
     * @param int $appointment_id Appointment ID
     * @param array $products Array of ['product_id' => X, 'quantity' => Y]
     * @param int $created_by User ID
     * @return array Result
     */
    public function recordProductUsage($appointment_id, $products, $created_by = null) {
        try {
            foreach ($products as $item) {
                $product_id = $item['product_id'];
                $quantity = $item['quantity'];

                // Get product details for cost
                $product = $this->getProduct($product_id);
                if (!$product) {
                    continue;
                }

                // Record usage
                $stmt = $this->db->prepare("
                    INSERT INTO appointment_products_used 
                    (appointment_id, product_id, quantity_used, unit_cost)
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$appointment_id, $product_id, $quantity, $product['unit_price']]);

                // Decrement stock
                $this->updateStock($product_id, -$quantity, 'out', 'Used in appointment #' . $appointment_id, $created_by);
            }

            return ['ok' => true, 'message' => 'Product usage recorded'];
        } catch (PDOException $e) {
            error_log("Inventory::recordProductUsage() - " . $e->getMessage());
            return ['ok' => false, 'message' => 'Failed to record product usage'];
        }
    }

    /**
     * Get inventory value (total stock value at cost)
     * 
     * @return int Total value in KES
     */
    public function getTotalInventoryValue() {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(s.quantity_on_hand * p.unit_price) as total_value
                FROM stock_levels s
                JOIN products p ON s.product_id = p.id
                WHERE p.is_active = 1
            ");
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total_value'] ?? 0;
        } catch (PDOException $e) {
            error_log("Inventory::getTotalInventoryValue() - " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get inventory summary statistics
     * 
     * @return array Summary data
     */
    public function getInventorySummary() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                  COUNT(DISTINCT p.id) as total_products,
                  SUM(CASE WHEN s.quantity_on_hand <= p.reorder_point THEN 1 ELSE 0 END) as low_stock_count,
                  SUM(s.quantity_on_hand) as total_units,
                  SUM(s.quantity_on_hand * p.unit_price) as total_value
                FROM products p
                LEFT JOIN stock_levels s ON p.id = s.product_id
                WHERE p.is_active = 1
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Inventory::getInventorySummary() - " . $e->getMessage());
            return null;
        }
    }
}
