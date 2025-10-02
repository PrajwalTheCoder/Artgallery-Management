-- Database: ecommerce_simple
CREATE DATABASE IF NOT EXISTS ecommerce_simple CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce_simple;

-- Products table
DROP TABLE IF EXISTS products;
CREATE TABLE products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  description TEXT,
  price DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  image VARCHAR(255) DEFAULT 'placeholder.png',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Sample data
INSERT INTO products (name, description, price, image) VALUES
('Wireless Mouse', 'A smooth and responsive wireless mouse', 599.00, 'mouse.jpg'),
('Mechanical Keyboard', 'Tactile switches, great for typing and gaming', 3499.00, 'keyboard.jpg'),
('USB-C Hub', 'Expand your laptop ports: HDMI, USB 3.0, SD', 1299.00, 'hub.jpg'),
('Noise Cancelling Headphones', 'Immersive sound with active noise cancellation', 7499.00, 'headphones.jpg'),
('Webcam 1080p', 'Full HD webcam ideal for video calls', 2199.00, 'webcam.jpg');

-- Reviews table (new)
CREATE TABLE IF NOT EXISTS reviews (
  id INT AUTO_INCREMENT PRIMARY KEY,
  product_id INT NOT NULL,
  name VARCHAR(120) NOT NULL,
  rating TINYINT NOT NULL CHECK (rating BETWEEN 1 AND 5),
  comment TEXT,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;
