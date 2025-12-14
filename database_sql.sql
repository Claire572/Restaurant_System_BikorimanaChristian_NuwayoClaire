-- ==========================================
-- Restaurant Order Management System
-- Database Setup Script
-- ==========================================

-- ==========================================
-- Table 1: users
-- Stores user accounts with authentication
-- ==========================================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'staff') DEFAULT 'staff',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- Table 2: menu_items
-- Stores restaurant menu items
-- ==========================================
CREATE TABLE menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(50) NOT NULL,
    available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_available (available)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- Table 3: orders
-- Stores customer orders with relationships
-- ==========================================
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    table_number INT NOT NULL,
    item_id INT NOT NULL,
    quantity INT NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'preparing', 'served', 'cancelled') DEFAULT 'pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id) REFERENCES menu_items(id) ON DELETE CASCADE,
    INDEX idx_status (status),
    INDEX idx_order_date (order_date),
    INDEX idx_table (table_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- Insert Default Admin User
-- Username: admin
-- Password: admin123 (hashed using bcrypt)
-- ==========================================
INSERT INTO users (username, email, password, role) 
VALUES ('admin', 'admin@restaurant.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ==========================================
-- Insert Sample Menu Items
-- ==========================================
INSERT INTO menu_items (name, description, price, category, available) VALUES
('Margherita Pizza', 'Classic Italian pizza with fresh tomato sauce, mozzarella cheese, and basil', 12.99, 'Main Course', TRUE),
('Pepperoni Pizza', 'Traditional pizza topped with pepperoni slices and mozzarella', 14.99, 'Main Course', TRUE),
('Caesar Salad', 'Fresh romaine lettuce with Caesar dressing, croutons, and parmesan', 8.99, 'Appetizer', TRUE),
('Greek Salad', 'Mixed greens with feta cheese, olives, tomatoes, and cucumber', 9.99, 'Appetizer', TRUE),
('Chocolate Cake', 'Rich, moist chocolate layer cake with chocolate frosting', 6.99, 'Dessert', TRUE),
('Tiramisu', 'Classic Italian dessert with coffee-soaked ladyfingers and mascarpone', 7.99, 'Dessert', TRUE),
('Fresh Lemonade', 'Freshly squeezed lemon juice with a touch of sweetness', 3.99, 'Beverage', TRUE),
('Iced Tea', 'Refreshing iced tea served with lemon', 2.99, 'Beverage', TRUE),
('Chicken Alfredo', 'Creamy fettuccine pasta with grilled chicken breast', 15.99, 'Main Course', TRUE),
('Grilled Salmon', 'Fresh Atlantic salmon with herbs and lemon butter', 18.99, 'Main Course', TRUE);

-- ==========================================
-- Insert Sample Orders (Optional)
-- ==========================================
INSERT INTO orders (table_number, item_id, quantity, total_price, status) VALUES
(5, 1, 2, 25.98, 'served'),
(3, 3, 1, 8.99, 'preparing'),
(7, 5, 3, 20.97, 'pending'),
(2, 7, 2, 7.98, 'served'),
(5, 9, 1, 15.99, 'preparing');

-- ==========================================
-- Verification Queries
-- ==========================================

-- Check if tables were created successfully
SHOW TABLES;

-- View all users
SELECT * FROM users;

-- View all menu items
SELECT * FROM menu_items;

-- View all orders with item details
SELECT o.*, m.name as item_name 
FROM orders o 
JOIN menu_items m ON o.item_id = m.id 
ORDER BY o.order_date DESC;

-- ==========================================
-- Additional Useful Queries
-- ==========================================

-- Get statistics
SELECT 
    (SELECT COUNT(*) FROM users) as total_users,
    (SELECT COUNT(*) FROM menu_items WHERE available = 1) as available_items,
    (SELECT COUNT(*) FROM orders WHERE status = 'pending') as pending_orders,
    (SELECT COALESCE(SUM(total_price), 0) FROM orders WHERE DATE(order_date) = CURDATE()) as today_revenue;

-- Get orders by status
-- SELECT * FROM orders WHERE status = 'pending';

-- Get menu items by category
-- SELECT * FROM menu_items WHERE category = 'Main Course';

-- Get today's orders
-- SELECT * FROM orders WHERE DATE(order_date) = CURDATE();

-- ==========================================
-- Database Setup Complete!
-- ==========================================