-- 建立並選擇資料庫
CREATE DATABASE IF NOT EXISTS pos_system DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE pos_system;

-- 關閉外鍵檢查，方便重新建置表單
SET FOREIGN_KEY_CHECKS = 0;

-- 清除舊有資料表（若存在）
DROP TABLE IF EXISTS order_items, orders, products, users;

-- 開啟外鍵檢查
SET FOREIGN_KEY_CHECKS = 1;

-- 1. 建立會員表 (users)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(20) UNIQUE NOT NULL COMMENT '登入帳號',
    email VARCHAR(255) UNIQUE NULL COMMENT '信箱，用於開通',
    name VARCHAR(50) NULL COMMENT '真實姓名',
    title ENUM('先生', '小姐') NULL COMMENT '稱謂',
    password VARCHAR(255) NOT NULL COMMENT '加密密碼',
    points INT DEFAULT 0 COMMENT '累積點數',
    is_verified TINYINT(1) DEFAULT 0 COMMENT '是否開通 0:否 1:是',
    verify_token VARCHAR(100) NULL COMMENT '開通驗證碼',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. 建立商品表 (products)
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT '商品名稱',
    price INT NOT NULL COMMENT '價格',
    stock INT NOT NULL DEFAULT 0 COMMENT '庫存數量',
    status ENUM('active', 'inactive') DEFAULT 'active' COMMENT '上下架狀態',
    image_path VARCHAR(255) NULL COMMENT '圖片路徑',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. 建立訂單表 (orders)
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL COMMENT '會員ID，NULL代表訪客',
    total_amount INT NOT NULL COMMENT '總金額',
    earned_points INT DEFAULT 0 COMMENT '本次獲得點數',
    status ENUM('pending', 'completed') DEFAULT 'pending' COMMENT '訂單狀態',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. 建立訂單明細表 (order_items)
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    qty INT NOT NULL COMMENT '數量',
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ==========================================
-- 寫入基礎測試資料，方便立即測試前台畫面
-- ==========================================
INSERT INTO products (name, price, stock, status, image_path) VALUES 
('經典雙層牛肉堡', 150, 50, 'active', NULL),
('松露脆薯', 80, 100, 'active', NULL),
('可口可樂 (大)', 40, 200, 'active', NULL),
('豪華主廚特製套餐', 320, 20, 'active', NULL);