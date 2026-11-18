-- ====================================================================
-- OBU STUDENT MARKETPLACE - COMPLETE DATABASE SCRIPT
-- Database Name: `obu-marketplace`
-- Project Name: OBU Student Marketplace (Oda Bultum University)
-- Engine: InnoDB | Charset: utf8mb4 | Collation: utf8mb4_unicode_ci
-- ====================================================================

-- 1. CREATE DATABASE
CREATE DATABASE IF NOT EXISTS `obu_marketplace` 
CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE `obu_marketplace`;

-- --------------------------------------------------------------------
-- 2. USERS TABLE (Roles: student, moderator, admin)
-- --------------------------------------------------------------------
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
  `profile_image` MEDIUMTEXT DEFAULT NULL,
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 3. CATEGORIES TABLE (Multilingual: English, Amharic, Afaan Oromoo)
-- --------------------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 4. PRODUCTS TABLE (Item Listings)
-- --------------------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 5. PRODUCT IMAGES TABLE
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `product_images` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `image_path` MEDIUMTEXT NOT NULL,
  `is_primary` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_product_images_pid` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 6. FAVORITES TABLE (Unique Saved Items)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `favorites` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `product_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_product_favorite` (`user_id`, `product_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 7. CONVERSATIONS TABLE (Chat Threads)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `conversations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `buyer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_conversation` (`product_id`, `buyer_id`, `seller_id`),
  INDEX `idx_conv_buyer` (`buyer_id`),
  INDEX `idx_conv_seller` (`seller_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 8. MESSAGES TABLE (Chat Content)
-- --------------------------------------------------------------------
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 9. PURCHASE REQUESTS TABLE (Offer Workflow)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `purchase_requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `product_id` INT NOT NULL,
  `buyer_id` INT NOT NULL,
  `seller_id` INT NOT NULL,
  `message` TEXT DEFAULT NULL,
  `status` ENUM('pending', 'accepted', 'rejected', 'cancelled', 'completed') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_req_buyer` (`buyer_id`),
  INDEX `idx_req_seller` (`seller_id`),
  INDEX `idx_req_product` (`product_id`),
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`buyer_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`seller_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 10. NOTIFICATIONS TABLE (Alerts Center)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `notifications` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `related_id` INT DEFAULT NULL,
  `link_url` VARCHAR(255) DEFAULT NULL,
  `is_read` TINYINT(1) NOT NULL DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_notif_user` (`user_id`),
  INDEX `idx_notif_read` (`is_read`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 11. REPORTS TABLE (Complaints & Moderation Queue)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `reports` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `reporter_id` INT NOT NULL,
  `reported_user_id` INT DEFAULT NULL,
  `product_id` INT DEFAULT NULL,
  `reason` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `status` ENUM('pending', 'resolved', 'rejected') NOT NULL DEFAULT 'pending',
  `reviewed_by` INT DEFAULT NULL,
  `admin_notes` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_reports_status` (`status`),
  FOREIGN KEY (`reporter_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`reported_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`product_id`) REFERENCES `products`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`reviewed_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 12. ANNOUNCEMENTS TABLE (Campus Broadcasts)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `announcements` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `title_en` VARCHAR(255) NOT NULL,
  `title_am` VARCHAR(255) NOT NULL,
  `title_om` VARCHAR(255) NOT NULL,
  `content_en` TEXT NOT NULL,
  `content_am` TEXT NOT NULL,
  `content_om` TEXT NOT NULL,
  `created_by` INT NOT NULL,
  `status` ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 13. ACTIVITY LOGS TABLE (Security Audit Trail)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `action` VARCHAR(100) NOT NULL,
  `description` TEXT NOT NULL,
  `ip_address` VARCHAR(45) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_activity_user` (`user_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 14. PASSWORD RESETS TABLE (Single-use Expiring Tokens)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `password_resets` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `token_hash` VARCHAR(64) NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_reset_token` (`token_hash`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 15. LOGIN ATTEMPTS TABLE (Rate Limiting)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `login_attempts` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `email` VARCHAR(150) NOT NULL,
  `ip_address` VARCHAR(45) NOT NULL,
  `attempted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_login_ip` (`ip_address`),
  INDEX `idx_login_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------------------
-- 16. SETTINGS TABLE (Platform Configurations)
-- --------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `settings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `setting_key` VARCHAR(100) NOT NULL UNIQUE,
  `setting_value` TEXT DEFAULT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ====================================================================
-- SEED DATA INSERTION
-- ====================================================================

-- 1. System Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('site_name', 'OBU Student Marketplace'),
('university_name', 'Oda Bultum University'),
('default_currency', 'ETB'),
('contact_email', 'marketplace@obu.edu.et'),
('contact_phone', '+251 25 551 0352'),
('campuses_list', 'Main Campus (Chiro), Agricultural Campus, Health Science Campus, Asbe Teferi Campus'),
('max_upload_size_mb', '5'),
('allowed_extensions', 'jpg,jpeg,png,webp'),
('enable_registration', '1'),
('items_per_page', '12'),
('theme_mode_default', 'bright')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);

-- 2. Initial Multilingual Categories
INSERT INTO `categories` (`id`, `name_en`, `name_am`, `name_om`, `slug`, `icon`, `description`) VALUES
(1, 'Books & Educational Materials', 'መጽሐፍት እና የትምህርት ቁሳቁሶች', 'Kitaabota fi Meeshaalee Barnootaa', 'books-education', 'bi-book', 'Textbooks, reference manuals, notebooks, study guides, and past exams.'),
(2, 'Electronics & Gadgets', 'ኤሌክትሮኒክስ እና መሣሪያዎች', 'Elektirooniksii fi Meeshaalee', 'electronics', 'bi-plug', 'Chargers, headphones, power banks, desk lamps, flash drives, and small gadgets.'),
(3, 'Computers & Accessories', 'ኮምፒውተሮች እና ተጨማሪ ዕቃዎች', 'Kompiwutaroota fi Meeshaalee Isaanii', 'computers-accessories', 'bi-laptop', 'Laptops, keyboards, mice, monitors, bags, and RAM modules.'),
(4, 'Mobile Phones & Tablets', 'ተንቀሳቃሽ ስልኮች እና ታብሌቶች', 'Bilbila Harkaa fi Taabileettii', 'phones-tablets', 'bi-phone', 'Smartphones, tablets, phone cases, and screen protectors.'),
(5, 'Clothes & Fashion', 'አልባሳት እና ፋሽን', 'Uffata fi Faashinii', 'clothes-fashion', 'bi-handbag', 'Jackets, sweaters, shirts, traditional Ethiopian clothes, and casual wear.'),
(6, 'Shoes & Footwear', 'ጫማዎች', 'Kophee fi Meeshaalee Miilaa', 'shoes', 'bi-smartwatch', 'Sneakers, formal shoes, boots, and slippers for campus life.'),
(7, 'Dorm & Furniture', 'የዶርም ዕቃዎች እና የቤት እቃዎች', 'Meeshaalee Doormii fi Manatti Barbaachisan', 'furniture-dorm', 'bi-lamp', 'Study desks, chairs, bed sheets, hangers, mattress covers, and storage boxes.'),
(8, 'Sports & Fitness Equipment', 'የስፖርት ዕቃዎች', 'Meeshaalee Ispoortii', 'sports-fitness', 'bi-trophy', 'Footballs, basketballs, gym gear, sports shoes, and tracksuits.'),
(9, 'Kitchen & Cooking Items', 'የወጥ ቤት እና የምግብ ማብሰያ ዕቃዎች', 'Meeshaalee Kushiinaa', 'kitchen-items', 'bi-cup-hot', 'Electric stoves, kettles, thermos flasks, plates, cups, and cooking utensils.'),
(10, 'Stationery & Drawing Tools', 'የጽሕፈት መሣሪያዎች እና የስዕል ዕቃዎች', 'Meeshaalee Barreessaa fi Saxaxaa', 'stationery', 'bi-pencil-square', 'Scientific calculators, drafting boards, pens, binders, and art supplies.'),
(11, 'Other Permitted Items', 'ሌሎች የተፈቀዱ ዕቃዎች', 'Meeshaalee Biroo Hayyamaman', 'other-items', 'bi-box-seam', 'Musical instruments, umbrellas, lab coats, and other student gear.')
ON DUPLICATE KEY UPDATE `name_en` = VALUES(`name_en`);

-- 3. Initial Users (Simplified to 2 Main Roles: Admin & Student)
-- Password for all seed users is 'Password123!'
-- Hashed using password_hash('Password123!', PASSWORD_BCRYPT):
-- $2y$10$pPTRZNsKPRwWFVNJGjDYT.MhteCj/gLO6ks/QoW/11oacHa6sAbtq
INSERT INTO `users` (`id`, `full_name`, `student_id`, `university`, `department`, `year_of_study`, `email`, `phone`, `password`, `role`, `status`, `preferred_language`, `theme_preference`) VALUES
(1, 'Admin', 'ADMIN-001', 'Oda Bultum University', 'ICT Directorate', 'Staff', 'admin@obu.edu.et', '+251 91 123 4567', '$2y$10$pPTRZNsKPRwWFVNJGjDYT.MhteCj/gLO6ks/QoW/11oacHa6sAbtq', 'admin', 'active', 'en', 'bright'),
(2, 'Chala Gemechu', 'OBU/0101/14', 'Oda Bultum University', 'Economics', '3rd Year', 'chala@obu.edu.et', '+251 92 234 5678', '$2y$10$pPTRZNsKPRwWFVNJGjDYT.MhteCj/gLO6ks/QoW/11oacHa6sAbtq', 'student', 'active', 'om', 'light'),
(3, 'Tolosa Bekele', 'OBU/0452/14', 'Oda Bultum University', 'Computer Science', '3rd Year', 'student@obu.edu.et', '+251 93 345 6789', '$2y$10$pPTRZNsKPRwWFVNJGjDYT.MhteCj/gLO6ks/QoW/11oacHa6sAbtq', 'student', 'active', 'en', 'bright'),
(4, 'Hanan Mohammed', 'OBU/0889/15', 'Oda Bultum University', 'Software Engineering', '2nd Year', 'student2@obu.edu.et', '+251 94 456 7890', '$2y$10$pPTRZNsKPRwWFVNJGjDYT.MhteCj/gLO6ks/QoW/11oacHa6sAbtq', 'student', 'active', 'am', 'light'),
(5, 'Yonas Abera', 'OBU/1123/13', 'Oda Bultum University', 'Electrical Engineering', '4th Year', 'student3@obu.edu.et', '+251 95 567 8901', '$2y$10$pPTRZNsKPRwWFVNJGjDYT.MhteCj/gLO6ks/QoW/11oacHa6sAbtq', 'student', 'active', 'en', 'dark')
ON DUPLICATE KEY UPDATE `email` = VALUES(`email`);

-- 4. Sample Products
INSERT INTO `products` (`id`, `user_id`, `category_id`, `title`, `description`, `price`, `condition`, `university`, `location`, `contact_preference`, `status`, `views_count`) VALUES
(1, 3, 1, 'Advanced Engineering Mathematics by Erwin Kreyszig 10th Ed', 'Original hard-cover textbook in great condition. Essential for 2nd and 3rd year engineering students. No torn pages, light pencil highlights on two chapters.', 450.00, 'Good', 'Oda Bultum University', 'Main Campus - Library Entrance', 'internal_message', 'active', 48),
(2, 4, 3, 'HP ProBook 450 G6 Core i5 8th Gen 8GB RAM 256GB SSD', 'Smooth running laptop with good battery health (lasts 3.5 hours on code compilation and research). Includes original charger. Perfect for CS and Engineering students.', 18500.00, 'Like New', 'Oda Bultum University', 'Main Campus - Block 14 Dorms', 'both', 'active', 125),
(3, 5, 10, 'Casio fx-991EX ClassWiz Scientific Calculator', 'High-speed scientific calculator with 552 functions. Original Japanese Casio with QR code verification and solar dual-power. Must-have for calculus and physics.', 1250.00, 'Like New', 'Oda Bultum University', 'Technology Campus Gate', 'internal_message', 'active', 72),
(4, 3, 4, 'Samsung Galaxy A14 64GB / 4GB RAM Black', 'Purchased last semester. Clear screen with tempered glass installed, battery health 100%. Selling because I got a new phone from my brother. Includes case.', 7800.00, 'Good', 'Oda Bultum University', 'Main Campus - Student Cafeteria', 'both', 'active', 89),
(5, 4, 7, 'Solid Wood Dorm Study Desk & Ergonomic Chair', 'Custom-built wooden study desk with 2 drawers and comfortable padded chair. Fits perfectly into OBU standard dorm room. Pick up at Block 08.', 2400.00, 'Good', 'Oda Bultum University', 'Main Campus - Block 08 Dorm', 'internal_message', 'active', 34),
(6, 5, 2, 'Anker PowerCore 20,000mAh Dual USB Fast Charger', 'Reliable heavy-duty power bank that charges a smartphone up to 5 times. Very helpful during campus load-shedding and weekend library study sessions.', 1600.00, 'Brand New', 'Oda Bultum University', 'ICT Laboratory Ground Floor', 'internal_message', 'active', 64),
(7, 3, 8, 'Original Nike Mercurial Turf Soccer Shoes (Size 42)', 'Original football shoes suitable for natural grass and turf campus pitches. Worn only three times in intramural tournament.', 1400.00, 'Like New', 'Oda Bultum University', 'Main Campus - Sports Ground', 'both', 'active', 51),
(8, 4, 11, 'White Medical/Lab Coat & Protective Safety Goggles', 'Standard OBU Health Sciences lab coat size Medium, 100% thick cotton, clean and washed. Comes with UV anti-fog safety glasses.', 550.00, 'Good', 'Oda Bultum University', 'Health Sciences Campus', 'internal_message', 'active', 42)
ON DUPLICATE KEY UPDATE `title` = VALUES(`title`);

-- 5. Sample Product Images
INSERT INTO `product_images` (`product_id`, `image_path`, `is_primary`) VALUES
(1, 'assets/images/placeholders/book-engineering.svg', 1),
(2, 'assets/images/placeholders/laptop-hp.svg', 1),
(3, 'assets/images/placeholders/calculator-casio.svg', 1),
(4, 'assets/images/placeholders/phone-samsung.svg', 1),
(5, 'assets/images/placeholders/desk-chair.svg', 1),
(6, 'assets/images/placeholders/powerbank-anker.svg', 1),
(7, 'assets/images/placeholders/shoes-football.svg', 1),
(8, 'assets/images/placeholders/lab-coat.svg', 1);

-- 6. Sample Chat Conversation
INSERT INTO `conversations` (`id`, `product_id`, `buyer_id`, `seller_id`) VALUES
(1, 1, 4, 3)
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

INSERT INTO `messages` (`conversation_id`, `sender_id`, `message`, `is_read`, `created_at`) VALUES
(1, 4, 'Hello Tolosa! Is the Kreyszig Mathematics textbook still available for meetup near the library?', 1, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 3, 'Hi Hanan! Yes it is. I can meet you tomorrow around 10:30 AM right outside the campus library entrance.', 1, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 4, 'Great, thank you! I will see you then.', 0, DATE_SUB(NOW(), INTERVAL 30 MINUTE));

-- 7. Sample Purchase Offers (Both Incoming and Outgoing for student@obu.edu.et)
INSERT INTO `purchase_requests` (`id`, `product_id`, `buyer_id`, `seller_id`, `message`, `status`, `created_at`) VALUES
(1, 1, 4, 3, 'I would like to buy the mathematics textbook tomorrow morning at 10:30 AM.', 'pending', NOW()),
(2, 2, 3, 4, 'Hello Hanan! I am interested in checking out your HP ProBook laptop today after class.', 'pending', NOW())
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

-- 8. Sample Favorites
INSERT INTO `favorites` (`user_id`, `product_id`) VALUES
(4, 2),
(4, 3),
(3, 6)
ON DUPLICATE KEY UPDATE `user_id` = VALUES(`user_id`);

-- 9. Sample Broadcast Announcements (Multilingual)
INSERT INTO `announcements` (`id`, `title_en`, `title_am`, `title_om`, `content_en`, `content_am`, `content_om`, `created_by`, `status`) VALUES
(1, 
'Welcome to OBU Student Marketplace!', 
'እንኳን ወደ ኦዳ ቡልቱም ዩኒቨርሲቲ ተማሪዎች ገበያ በደህና መጡ!', 
'Baga gara Gabaa Barattoota Yuunivarsiitii Oda Bultum nagaan dhuftan!', 
'Official centralized marketplace for Oda Bultum University students. Buy and sell textbooks, electronics, dorm essentials safely inside our campus.', 
'ይህ ለኦዳ ቡልቱም ዩኒቨርሲቲ ተማሪዎች የተዘጋጀ ይፋዊ የግብይት መድረክ ነው። መጽሐፍትን፣ ኤሌክትሮኒክስን እና ሌሎች ቁሳቁሶችን በካምፓሳችን ውስጥ በደህና ይገበያዩ።', 
'Kun gabaa seera qabeessa barattoota Yuunivarsiitii Oda Bultumti. Kitaabota, elektirooniksii fi meeshaalee doormii nagaan keessa kaampaasitti bitaa gurguraa.', 
1, 'published')
ON DUPLICATE KEY UPDATE `id` = VALUES(`id`);

-- 10. Sample Security Activity Log
INSERT INTO `activity_logs` (`user_id`, `action`, `description`, `ip_address`) VALUES
(1, 'SYSTEM_INIT', 'System database initialized with default OBU categories and settings', '127.0.0.1');
