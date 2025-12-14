<?php
/**
 * Menu Management Page (CRUD for Menu Items)
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
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $success = 'Menu item deleted successfully';
    } catch (PDOException $e) {
        $error = 'Error deleting item. It may be referenced in orders.';
    }
}

// Handle ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? '';
    $category = trim($_POST['category'] ?? '');
    $available = isset($_POST['available']) ? 1 : 0;
    $id = $_POST['id'] ?? null;
    
    // Validation
    if (empty($name)) {
        $error = 'Item name is required';
    } elseif (empty($price) || !is_numeric($price) || $price <= 0) {
        $error = 'Valid price is required (must be greater than 0)';
    } elseif (empty($category)) {
        $error = 'Category is required';
    } else {
        try {
            if ($id) {
                // UPDATE using prepared statement with named placeholders
                $stmt = $pdo->prepare("UPDATE menu_items SET name = :name, description = :desc, price = :price, category = :cat, available = :avail WHERE id = :id");
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            } else {
                // INSERT using prepared statement
                $stmt = $pdo->prepare("INSERT INTO menu_items (name, description, price, category, available) VALUES (:name, :desc, :price, :cat, :avail)");
            }
            
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':desc', $description);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':cat', $category);
            $stmt->bindParam(':avail', $available, PDO::PARAM_INT);
            $stmt->execute();
            
            $success = $id ? 'Menu item updated successfully' : 'Menu item added successfully';
            
            // Clear form after success
            header('Location: menu.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: Unable to save menu item';
        }
    }
}

// Fetch item for editing
$editItem = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    try {
        $stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
        $editItem = $stmt->fetch();
        
        if (!$editItem) {
            $error = 'Menu item not found';
        }
    } catch (PDOException $e) {
        $error = 'Error fetching item';
    }
}

// Fetch all menu items
try {
    $items = $pdo->query("SELECT * FROM menu_items ORDER BY category, name")->fetchAll();
} catch (PDOException $e) {
    $items = [];
    $error = 'Error loading menu items';
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
    <title>Menu Management - Restaurant Order System</title>
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
            grid-template-columns: 1fr 1fr;
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
        
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        input:focus, textarea:focus, select:focus {
            outline: none;
            border-color: #667eea;
        }
        
        textarea {
            min-height: 80px;
            resize: vertical;
        }
        
        .checkbox-group {
            display: flex;
            align-items: center;
        }
        
        .checkbox-group input {
            width: auto;
            margin-right: 10px;
        }
        
        .checkbox-group label {
            margin-bottom: 0;
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
        
        .actions a {
            color: #667eea;
            text-decoration: none;
            margin-right: 10px;
            font-size: 14px;
        }
        
        .actions a:hover {
            text-decoration: underline;
        }
        
        .actions a.delete {
            color: #e74c3c;
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
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .badge-success {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-danger {
            background: #f8d7da;
            color: #721c24;
        }
        
        .cancel-btn {
            display: inline-block;
            margin-left: 10px;
            color: #666;
            text-decoration: none;
            padding: 12px 25px;
        }
        
        .cancel-btn:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="navbar">
        <h1>🍽️ Menu Management</h1>
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
            <h2><?php echo $editItem ? 'Edit Menu Item' : 'Add New Menu Item'; ?></h2>
            <form method="POST" action="menu.php">
                <?php if ($editItem): ?>
                    <input type="hidden" name="id" value="<?php echo $editItem['id']; ?>">
                <?php endif; ?>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Item Name *</label>
                        <input type="text" id="name" name="name" value="<?php echo escape($editItem['name'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="price">Price ($) *</label>
                        <input type="number" step="0.01" id="price" name="price" value="<?php echo escape($editItem['price'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="category">Category *</label>
                    <select id="category" name="category" required>
                        <option value="">Select Category</option>
                        <option value="Appetizer" <?php echo ($editItem['category'] ?? '') === 'Appetizer' ? 'selected' : ''; ?>>Appetizer</option>
                        <option value="Main Course" <?php echo ($editItem['category'] ?? '') === 'Main Course' ? 'selected' : ''; ?>>Main Course</option>
                        <option value="Dessert" <?php echo ($editItem['category'] ?? '') === 'Dessert' ? 'selected' : ''; ?>>Dessert</option>
                        <option value="Beverage" <?php echo ($editItem['category'] ?? '') === 'Beverage' ? 'selected' : ''; ?>>Beverage</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="description">Description</label>
                    <textarea id="description" name="description"><?php echo escape($editItem['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group checkbox-group">
                    <input type="checkbox" id="available" name="available" <?php echo (!isset($editItem) || $editItem['available']) ? 'checked' : ''; ?>>
                    <label for="available">Available for ordering</label>
                </div>
                
                <button type="submit"><?php echo $editItem ? 'Update Item' : 'Add Item'; ?></button>
                <?php if ($editItem): ?>
                    <a href="menu.php" class="cancel-btn">Cancel</a>
                <?php endif; ?>
            </form>
        </div>
        
        <div class="card">
            <h2>Current Menu Items (<?php echo count($items); ?>)</h2>
            <?php if (count($items) > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Price</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><strong><?php echo escape($item['name']); ?></strong></td>
                                <td><?php echo escape($item['category']); ?></td>
                                <td><?php echo escape(substr($item['description'], 0, 50)); ?><?php echo strlen($item['description']) > 50 ? '...' : ''; ?></td>
                                <td>$<?php echo number_format($item['price'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $item['available'] ? 'badge-success' : 'badge-danger'; ?>">
                                        <?php echo $item['available'] ? 'Available' : 'Unavailable'; ?>
                                    </span>
                                </td>
                                <td class="actions">
                                    <a href="menu.php?edit=<?php echo $item['id']; ?>">Edit</a>
                                    <a href="menu.php?delete=<?php echo $item['id']; ?>" class="delete" onclick="return confirm('Are you sure you want to delete this item?')">Delete</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <p style="color: #666; text-align: center; padding: 20px;">No menu items found. Add your first item above!</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>