-- ====================================================================
-- OBU STUDENT MARKETPLACE - TiDB CLUSTER & CLOUD MYSQL SCRIPT
-- Tested & Optimized for TiDB Cloud Serverless & Vercel
-- ====================================================================

-- 1. USERS TABLE
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `full_name` VARCHAR(150) NOT NULL,
  `student_id` VARCHAR(50) NOT NULL,
  `university` VARCHAR(150) NOT NULL DEFAULT 'Oda Bultum University',
  `department` VARCHAR(150) NOT NULL,
  `year_of_study` VARCHAR(30) NOT NULL,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `phone` VARCHAR(30) NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `profile_image` VARCHAR(255) DEFAULT NULL,
  `role` ENUM('student', 'moderator', 'admin') NOT NULL DEFAULT 'student',
  `status` ENUM('active', 'pending', 'suspended') NOT NULL DEFAULT 'active',
  `preferred_language` VARCHAR(10) NOT NULL DEFAULT 'en',
  `theme_preference` ENUM('light', 'bright', 'dark') NOT NULL DEFAULT 'bright',
  `email_verified_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_users_role` (`role`),
  INDEX `idx_users_status` (`status`),
  INDEX `idx_users_student_id` (`student_id`)
);

-- 2. CATEGORIES TABLE
CREATE TABLE IF NOT EXISTS `categories` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name_en` VARCHAR(100) NOT NULL,
  `name_am` VARCHAR(100) NOT NULL,
  `name_om` VARCHAR(100) NOT NULL,
  `slug` VARCHAR(100) NOT NULL UNIQUE,
  `description` TEXT DEFAULT NULL,
  `icon` VARCHAR(50) DEFAULT 'bi-tag',
  `image` VARCHAR(255) DEFAULT NULL,
  `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. PRODUCTS TABLE
CREATE TABLE IF NOT EXISTS `products` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `category_id` INT NOT NULL,
  `title` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `price` DECIMAL(10,2) NOT NULL,
  `condition` ENUM('Brand New', 'Like New', 'Good', 'Used', 'Needs Repair') NOT NULL,
  `university` VARCHAR(150) NOT NULL DEFAULT 'Oda Bultum University',
  `location` VARCHAR(150) NOT NULL,
  `contact_preference` VARCHAR(50) NOT NULL DEFAULT 'internal_message',
  `status` ENUM('pending', 'active', 'rejected', 'sold', 'hidden') NOT NULL DEFAULT 'active',
  `views_count` INT UNSIGNED NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_products_user` (`user_id`),
  INDEX `idx_products_category` (`category_id`),
  INDEX `idx_products_status` (`status`),
  INDEX `idx_products_price` (`price`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE RESTRICT
);

-- 4. PRODUCT IMAGES TABLE
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_path` VARCHAR(255) NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_product_images_pid` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- 5. FAVORITES TABLE
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_favorite` (`user_id`, `product_id`),
  INDEX `idx_favorites_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
);

-- 6. CONVERSATIONS TABLE
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `buyer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `last_message_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_conv` (`product_id`, `buyer_id`, `seller_id`),
  INDEX `idx_conversations_buyer` (`buyer_id`),
  INDEX `idx_conversations_seller` (`seller_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 7. MESSAGES TABLE
CREATE TABLE IF NOT EXISTS `messages` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `conversation_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `message` TEXT NOT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_messages_conv` (`conversation_id`),
  INDEX `idx_messages_sender` (`sender_id`),
  FOREIGN KEY (`conversation_id`) REFERENCES `conversations`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 8. PURCHASE REQUESTS TABLE
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `buyer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `offered_price` DECIMAL(10,2) NOT NULL,
  `message` TEXT DEFAULT NULL,
  `meeting_location` VARCHAR(150) DEFAULT NULL,
  `preferred_date` DATE DEFAULT NULL,
  `status` ENUM('pending', 'accepted', 'declined', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_pr_product` (`product_id`),
  INDEX `idx_pr_buyer` (`buyer_id`),
  INDEX `idx_pr_seller` (`seller_id`),
  INDEX `idx_pr_status` (`status`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 9. NOTIFICATIONS TABLE
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(150) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL DEFAULT 'general',
  `reference_id` INT DEFAULT NULL,
  `link` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_read` (`is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 10. REPORTS TABLE
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT NOT NULL,
  `reported_user_id` INT DEFAULT NULL,
  `reported_product_id` INT DEFAULT NULL,
  `reason` ENUM('scam', 'inappropriate', 'counterfeit', 'wrong_category', 'harassment', 'other') NOT NULL,
  `details` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'reviewed', 'dismissed', 'action_taken') NOT NULL DEFAULT 'pending',
  `moderator_notes` TEXT DEFAULT NULL,
  `reviewed_by` INT DEFAULT NULL,
  `reviewed_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_reports_status` (`status`),
  FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reported_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reported_product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 11. ANNOUNCEMENTS TABLE
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title_en` VARCHAR(200) NOT NULL,
  `title_am` VARCHAR(200) NOT NULL,
  `title_om` VARCHAR(200) NOT NULL,
  `content_en` TEXT NOT NULL,
  `content_am` TEXT NOT NULL,
  `content_om` TEXT NOT NULL,
  `type` ENUM('general', 'safety', 'system', 'event') NOT NULL DEFAULT 'general',
  `is_active` TINYINT(1) NOT NULL DEFAULT 1,
  `created_by` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
);

-- 12. ACTIVITY LOGS TABLE
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `user_agent` VARCHAR(255) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_logs_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- 13. PASSWORD RESETS TABLE
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `token` VARCHAR(100) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `expires_at` DATETIME NOT NULL,
  INDEX `idx_resets_token` (`token`),
  INDEX `idx_resets_email` (`email`)
);

-- 14. LOGIN ATTEMPTS TABLE
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `ip_address` VARCHAR(45) NOT NULL,
  `email_attempted` VARCHAR(150) NOT NULL,
  `attempt_time` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_attempts_ip_time` (`ip_address`, `attempt_time`)
);

-- 15. SETTINGS TABLE
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- ====================================================================
-- SEED DATA (Default Users, Categories, Settings)
-- ====================================================================

-- 1. Default Users (Password for all: password123)
-- Hash: $2y$12$N3q78FhB1Oa4r9K81yKjUeWfE3pXwV0uBwAov67z0a6s5p1t4Qd6G
INSERT INTO `users` (`id`, `full_name`, `student_id`, `university`, `department`, `year_of_study`, `email`, `phone`, `password`, `role`, `status`) VALUES
(1, 'System Administrator', 'ADMIN-001', 'Oda Bultum University', 'ICT Directorate', 'Staff', 'admin@obu.edu.et', '+251911000001', '$2y$12$N3q78FhB1Oa4r9K81yKjUeWfE3pXwV0uBwAov67z0a6s5p1t4Qd6G', 'admin', 'active'),
(2, 'Campus Moderator', 'MOD-001', 'Oda Bultum University', 'Student Affairs', 'Staff', 'moderator@obu.edu.et', '+251911000002', '$2y$12$N3q78FhB1Oa4r9K81yKjUeWfE3pXwV0uBwAov67z0a6s5p1t4Qd6G', 'moderator', 'active'),
(3, 'Chala Bekele', 'OBU/1042/13', 'Oda Bultum University', 'Computer Science', '4th Year', 'student1@obu.edu.et', '+251912345678', '$2y$12$N3q78FhB1Oa4r9K81yKjUeWfE3pXwV0uBwAov67z0a6s5p1t4Qd6G', 'student', 'active'),
(4, 'Hawine Tolosa', 'OBU/2085/14', 'Oda Bultum University', 'Economics', '3rd Year', 'student2@obu.edu.et', '+251923456789', '$2y$12$N3q78FhB1Oa4r9K81yKjUeWfE3pXwV0uBwAov67z0a6s5p1t4Qd6G', 'student', 'active'),
(5, 'Yonas Abera', 'OBU/1123/13', 'Oda Bultum University', 'Electrical Engineering', '4th Year', 'yonas@obu.edu.et', '+251934567890', '$2y$12$N3q78FhB1Oa4r9K81yKjUeWfE3pXwV0uBwAov67z0a6s5p1t4Qd6G', 'student', 'active')
ON DUPLICATE KEY UPDATE `full_name` = VALUES(`full_name`);

-- 2. Categories (Multilingual)
INSERT INTO `categories` (`id`, `name_en`, `name_am`, `name_om`, `slug`, `icon`) VALUES
(1, 'Academic Textbooks & Handouts', 'የትምህርት መጻሕፍት እና ማስታወሻዎች', 'Kitaabota Barnootaa fi Qophii', 'textbooks', 'bi-book'),
(2, 'Electronics & Laptops', 'ኤሌክትሮኒክስ እና ላፕቶፖች', 'Meeshaalee Elektirooniksii', 'electronics', 'bi-laptop'),
(3, 'Dorm & Living Essentials', 'የመኝታ ክፍል እቃዎች', 'Meeshaalee Kutaa Ciisichaa', 'dorm-gear', 'bi-house-door'),
(4, 'Stationery & Study Tools', 'የጽሕፈት መሳሪያዎች', 'Meeshaalee Barreessaa', 'stationery', 'bi-pen'),
(5, 'Campus Fashion & Sportswear', 'የግቢ አልባሳት እና ጫማዎች', 'Uffata fi Kophee Kaampaasii', 'fashion', 'bi-bag'),
(6, 'Laboratory & Department Gear', 'የላብራቶሪ እቃዎች', 'Meeshaalee Laabiraatoorii', 'lab-gear', 'bi-tools')
ON DUPLICATE KEY UPDATE `name_en` = VALUES(`name_en`);

-- 3. Default Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'OBU Student Marketplace'),
('university_name', 'Oda Bultum University'),
('currency', 'ETB'),
('max_upload_size_mb', '5'),
('default_language', 'en'),
('allow_registration', '1')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 4. Sample Products
INSERT INTO `products` (`id`, `user_id`, `category_id`, `title`, `description`, `price`, `condition`, `university`, `location`, `status`) VALUES
(1, 3, 2, 'HP Pavilion 15 (Core i5, 8GB RAM, 256GB SSD)', 'Clean student laptop in excellent working condition. Battery lasts 4+ hours.', 28500.00, 'Like New', 'Oda Bultum University', 'Main Campus - Library Gate', 'active'),
(2, 4, 1, 'Engineering Mathematics by K.A. Stroud (7th Edition)', 'Clean textbook with no missing pages. Very helpful for 1st and 2nd year students.', 650.00, 'Good', 'Oda Bultum University', 'Block 12 Dormitory Area', 'active'),
(3, 5, 2, 'Casio fx-991EX Scientific Calculator', 'Original ClassWiz scientific calculator for engineering and science exams.', 1400.00, 'Brand New', 'Oda Bultum University', 'Technology Faculty Cafe', 'active')
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);
