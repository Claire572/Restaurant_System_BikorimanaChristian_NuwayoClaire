<?php
/**
 * Orders Management Page (CRUD for Orders)
 * Restaurant Order Management System
 */

require_once 'config.php';
requireLogin();

$db = new Database();
$pdo = $db->connect();
$error = '';
$success = '';

// Handle DELETE
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM orders WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Order deleted successfully';
    } catch (PDOException $e) {
        $error = 'Error deleting order';
    }
}

// Handle Status Update
if (isset($_GET['update_status']) && isset($_GET['status'])) {
    $id = (int)$_GET['update_status'];
    $status = $_GET['status'];
    
    $validStatuses = ['pending', 'preparing', 'served', 'cancelled'];
    
    if (in_array($status, $validStatuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE orders SET status = :status WHERE id = :id");
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $success = 'Order status updated successfully';
        } catch (PDOException $e) {
            $error = 'Error updating order status';
        }
    }
}

// Handle CREATE Order
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $table_number = (int)($_POST['table_number'] ?? 0);
    $item_id = (int)($_POST['item_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    
    // Validation
    if ($table_number <= 0) {
        $error = 'Valid table number is required';
    } elseif ($item_id <= 0) {
        $error = 'Please select a menu item';
    } elseif ($quantity <= 0) {
        $error = 'Quantity must be at least 1';
    } else {
        try {
            // Fetch item price
            $stmt = $pdo->prepare("SELECT price, name, available FROM menu_items WHERE id = ?");
            $stmt->execute([$item_id]);
            $item = $stmt->fetch();
            
            if (!$item) {
                $error = 'Selected menu item not found';
            } elseif (!$item['available']) {
                $error = 'Selected item is not available';
            } else {
                $total_price = $item['price'] * $quantity;
                
                // Insert order using prepared statement
                $stmt = $pdo->prepare("INSERT INTO orders (table_number, item_id, quantity, total_price, status) VALUES (:table, :item, :qty, :total, 'pending')");
                $stmt->bindParam(':table', $table_number, PDO::PARAM_INT);
                $stmt->bindParam(':item', $item_id, PDO::PARAM_INT);
                $stmt->bindParam(':qty', $quantity, PDO::PARAM_INT);
                $stmt->bindParam(':total', $total_price);
                $stmt->execute();
                
                $success = 'Order created successfully';
                header('Location: orders.php?success=1');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Database error: Unable to create order';
        }
    }
}

// Fetch all orders with item details
try {
    $orders = $pdo->query("SELECT o.*, m.name as item_name, m.price as item_price 
        FROM orders o 
        JOIN menu_items m ON o.item_id = m.id 
        ORDER BY o.order_date DESC")->fetchAll();
} catch (PDOException $e) {
    $orders = [];
    $error = 'Error loading orders';
}

// Fetch available menu items for dropdown
try {
    $menuItems = $pdo->query("SELECT id, name, price FROM menu_items WHERE available = 1 ORDER BY name")->fetchAll();
} catch (PDOException $e) {
    $menuItems = [];
}

if (isset($_GET['success'])) {
    $success = 'Operation completed successfully';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders Management - Restaurant Order System</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f5f5f5;
        }
        
        .navbar {
            background: #333;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .navbar h1 {
            font-size: 24px;
        }
        
        .navbar nav a {
            color: white;
            text-decoration: none;
            margin-left: 20px;
            padding: 8px 15px;
            border-radius: 5px;
            transition: background 0.3s;
        }
        
        .navbar nav a:hover {
            background: #555;
        }
        
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        h2 {
            color: #333;
            margin-bottom: 20px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            color: #555;
            font-weight: bold;
        }
        
        input, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        input:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            padding: 12px 25px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #5568d3;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        th {
            background: #f8f8f8;
            font-weight: bold;
            color: #555;
        }
        
        .actions {
            white-space: nowrap;
        }
        
        .actions a, .actions select {
            display: inline-block;
            font-size: 13px;
            padding: 5px 10px;
            margin-right: 5px;
            text-decoration: none;
            border-radius: 3px;
        }
        
        .actions a {
            background: #667eea;
            color: white;
        }
        
        .actions a:hover {
            background: #5568d3;
        }
        
        .actions a.delete {
            background: #e74c3c;
        }
        
        .actions a.delete:hover {
            background: #c0392b;
        }
        
        .actions select {
            border: 1px solid #ddd;
            cursor: pointer;
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #fcc;
        }
        
        .success {
            background: #efe;
            color: #3c3;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 20px;
            border: 1px solid #cfc;
        }
        
        .badge {
            padding: 5px 10px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
            display: inline-block;
        }
        
        .badge-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-preparing {
            background: #cce5ff;
            color: #004085;
        }
        
        .badge-served {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .filter-section select {
            width: auto;
            display: inline-block;
            margin-right: 10px;
        }
    </style>
    <script>
        function updateStatus(orderId, select) {
            const status = select.value;
            if (confirm('Are you sure you want to update this order status?')) {
                window.location.href = 'orders.php?update_status=' + orderId + '&status=' + status;
            } else {
                select.value = select.getAttribute('data-current');
            }
        }
    </script>
</head>
<body>
    <div class="navbar">
        <h1>🍽️ Orders Management</h1>
        <nav>
            <a href="dashboard.php">Dashboard</a>
            <a href="menu.php">Menu Items</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
    
    <div class="container">
        <?php if ($error): ?>
            <div class="error"><?php echo escape($error); ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?php echo escape($success); ?></div>
        <?php endif; ?>
        
        <div class="card">
            <h2>Create New Order</h2>
            <?php if (count($menuItems) > 0): ?>
                <form method="POST" action="orders.php">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="table_number">Table Number *</label>
                            <input type="number" id="table_number" name="table_number" min="1" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="item_id">Menu Item *</label>
                            <select id="item_id" name="item_id" required>
                                <option value="">Select Item</option>
                                <?php foreach ($menuItems as $item): ?>
                                    <option value="<?php echo $item['id']; ?>">
                                        <?php echo escape($item['name']); ?> - $<?php echo number_format($item['price'], 2); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="quantity">Quantity *</label>
                            <input type="number" id="quantity" name="quantity" min="1" value="1" required>
                        </div>
                    </div>
                    
                    <button type="submit">Create Order</button>
                </form>
            <?php else: ?>
                <p style="color: #999;">No available menu items. Please add items to the menu first.</p>
            <?php endif; ?>
        </div>
        
        <div class="card">
            <h2>All Orders (<?php echo count($orders); ?>)</h2>
            
            <?php if (count($orders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Table</th>
                            <th>Item</th>
                            <th>Qty</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><strong>#<?php echo $order['id']; ?></strong></td>
                                <td><?php echo $order['table_number']; ?></td>
                                <td><?php echo escape($order['item_name']); ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>$<?php echo number_format($order['item_price'], 2); ?></td>
                                <td><strong>$<?php echo number_format($order['total_price'], 2); ?></strong></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?></td>
                                <td class="actions">
                                    <select onchange="updateStatus(<?php echo $order['id']; ?>, this)" data-current="<?php echo $order['status']; ?>">
                                        <option value="">Change Status</option>
                                        <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="preparing" <?php echo $order['status'] === 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                        <option value="served" <?php echo $order['status'] === 'served' ? 'selected' : ''; ?>>Served</option>
                                        <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                    </select>
                                    <a href="orders.php?delete=<?php echo $order['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this order?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 20px;">No orders found. Create your first order above!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>