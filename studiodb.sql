CREATE DATABASE photo_studio CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE photo_studio;

CREATE TABLE bookings (
  id INT AUTO_INCREMENT PRIMARY KEY,
  customer_name VARCHAR(255) NOT NULL,
  phone VARCHAR(50) NOT NULL,
  booking_date DATE NOT NULL,
  booking_time TIME NOT NULL,
  package VARCHAR(100),
  notes TEXT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
