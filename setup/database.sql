-- Business Directory Database Schema

-- Create businesses table
CREATE TABLE IF NOT EXISTS `businesses` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `name` VARCHAR(255) NOT NULL,
    `category` VARCHAR(100) NOT NULL,
    `address` TEXT NOT NULL,
    `city` VARCHAR(100) NOT NULL,
    `state` VARCHAR(100),
    `phone` VARCHAR(20),
    `website` VARCHAR(255),
    `email` VARCHAR(255),
    `description` LONGTEXT,
    `rating` DECIMAL(3,2) DEFAULT 0,
    `reviews_count` INT DEFAULT 0,
    `latitude` DECIMAL(10,8),
    `longitude` DECIMAL(11,8),
    `status` ENUM('active', 'inactive') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_city (city),
    INDEX idx_rating (rating),
    INDEX idx_status (status),
    FULLTEXT INDEX ft_search (name, description)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sample data
INSERT INTO `businesses` (`name`, `category`, `address`, `city`, `state`, `phone`, `website`, `email`, `description`, `rating`, `reviews_count`, `latitude`, `longitude`) VALUES
('ABC Electricals', 'Electrician', '123 Main St', 'Dehradun', 'Uttarakhand', '+91-9876543210', 'https://abcelectricals.com', 'info@abc.com', 'Trusted electrical services for residential and commercial needs', 4.8, 156, 30.1975, 78.1629),
('Best Salon', 'Salon', '456 Park Ave', 'Dehradun', 'Uttarakhand', '+91-9876543211', 'https://bestsalon.com', 'info@salon.com', 'Premium salon and spa services', 4.6, 89, 30.1975, 78.1629),
('Fast Plumbing', 'Plumbing', '789 Water St', 'Dehradun', 'Uttarakhand', '+91-9876543212', 'https://fastplumbing.com', 'info@plumbing.com', 'Emergency and routine plumbing services', 4.5, 73, 30.1975, 78.1629),
('Tech Support Plus', 'IT Services', '321 Tech Park', 'Dehradun', 'Uttarakhand', '+91-9876543213', 'https://techsupport.com', 'info@tech.com', 'Computer repair and IT solutions', 4.7, 120, 30.1975, 78.1629),
('Green Gardening', 'Gardening', '654 Garden Lane', 'Dehradun', 'Uttarakhand', '+91-9876543214', 'https://greengardening.com', 'info@garden.com', 'Landscaping and garden maintenance services', 4.4, 62, 30.1975, 78.1629);

-- Sitemap.xml generation note
-- Remember to generate sitemap.xml dynamically based on businesses table
-- URL structure: /business/{id}/{slug}

-- Robots.txt should include:
-- User-agent: *
-- Allow: /
-- Disallow: /admin/
-- Sitemap: https://yourdomain.com/sitemap.xml
