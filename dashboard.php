<?php
/**
 * Dashboard Page
 * Restaurant Order Management System
 */

require_once 'config.php';
requireLogin();

$db = new Database();
$pdo = $db->connect();

// Fetch statistics
try {
    $statsStmt = $pdo->query("SELECT 
        (SELECT COUNT(*) FROM menu_items WHERE available = 1) as active_items,
        (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
        (SELECT COUNT(*) FROM orders WHERE DATE(order_date) = CURDATE()) as today_orders,
        (SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(order_date) = CURDATE()) as today_revenue
    ");
    $stats = $statsStmt->fetch();
} catch (PDOException $e) {
    $stats = [
        'active_items' => 0,
        'pending_orders' => 0,
        'today_orders' => 0,
        'today_revenue' => 0
    ];
}

// Fetch recent orders
try {
    $recentOrdersStmt = $pdo->query("SELECT o.*, m.name as item_name 
        FROM orders o 
        JOIN menu_items m ON o.item_id = m.id 
        ORDER BY o.order_date DESC 
        LIMIT 5");
    $recentOrders = $recentOrdersStmt->fetchAll();
} catch (PDOException $e) {
    $recentOrders = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Restaurant Order System</title>
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
        
        .navbar nav {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .navbar nav span {
            margin-right: 10px;
        }
        
        .navbar nav a {
            color: white;
            text-decoration: none;
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
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card h3 {
            color: #666;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #667eea;
        }
        
        .quick-actions {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .quick-actions h2 {
            margin-bottom: 20px;
            color: #333;
        }
        
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }
        
        .action-btn {
            display: block;
            padding: 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            text-align: center;
            font-weight: bold;
            transition: all 0.3s;
        }
        
        .action-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .recent-orders {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .recent-orders h2 {
            margin-bottom: 20px;
            color: #333;
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
        
        .badge {
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
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
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🍽️ Restaurant Dashboard</h1>
        <nav>
            <span>Welcome, <strong><?php echo escape($_SESSION['username']); ?></strong>!</span>
            <a href="dashboard.php">Dashboard</a>
            <a href="menu.php">Menu Items</a>
            <a href="orders.php">Orders</a>
            <a href="logout.php">Logout</a>
        </nav>
    </div>
    
    <div class="container">
        <div class="stats">
            <div class="stat-card">
                <h3>Active Menu Items</h3>
                <div class="number"><?php echo $stats['active_items']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Pending Orders</h3>
                <div class="number"><?php echo $stats['pending_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Today's Orders</h3>
                <div class="number"><?php echo $stats['today_orders']; ?></div>
            </div>
            <div class="stat-card">
                <h3>Today's Revenue</h3>
                <div class="number">$<?php echo number_format($stats['today_revenue'], 2); ?></div>
            </div>
        </div>
        
        <div class="quick-actions">
            <h2>Quick Actions</h2>
            <div class="action-grid">
                <a href="menu.php" class="action-btn">📋 Manage Menu</a>
                <a href="orders.php" class="action-btn">🛎️ View Orders</a>
                <a href="menu.php?action=add" class="action-btn">➕ Add Menu Item</a>
                <a href="orders.php?action=new" class="action-btn">🆕 New Order</a>
            </div>
        </div>
        
        <div class="recent-orders">
            <h2>Recent Orders</h2>
            <?php if (count($recentOrders) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Order ID</th>
                            <th>Item</th>
                            <th>Table</th>
                            <th>Quantity</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo escape($order['item_name']); ?></td>
                                <td><?php echo $order['table_number']; ?></td>
                                <td><?php echo $order['quantity']; ?></td>
                                <td>$<?php echo number_format($order['total_price'], 2); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, H:i', strtotime($order['order_date'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 20px;">No recent orders found.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>