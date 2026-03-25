<?php
$conn = new mysqli("localhost","root","","privatehire");

if($conn->connect_error){
die("Connection failed");
}

function ensure_privatehire_schema(mysqli $conn): void
{
    $ddlStatements = [
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_token VARCHAR(255) DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS reset_expires_at DATETIME DEFAULT NULL",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS account_source ENUM('web','phone') DEFAULT 'web'",
        "ALTER TABLE users ADD COLUMN IF NOT EXISTS loyalty_tier VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS notification_preference ENUM('sms','email','both') DEFAULT 'sms'",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS confirmation_sent TINYINT(1) DEFAULT 0",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS reminder_sent TINYINT(1) DEFAULT 0",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS booking_channel ENUM('online','phone') DEFAULT 'online'",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS total_cost DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS discount_percent DECIMAL(5,2) DEFAULT 0.00",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS discount_amount DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS final_cost DECIMAL(10,2) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_method ENUM('paypal','card') DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_status ENUM('pending','paid','failed','refunded') DEFAULT 'pending'",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS card_brand ENUM('visa','mastercard','amex') DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS card_last4 VARCHAR(4) DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS cancelled_at DATETIME DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS offer_code_id INT DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS offer_discount_amount DECIMAL(10,2) DEFAULT 0.00",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS delayed_notified TINYINT(1) DEFAULT 0",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS eta_minutes INT DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS actual_eta DATETIME DEFAULT NULL",
        "ALTER TABLE bookings ADD COLUMN IF NOT EXISTS service_type VARCHAR(100) DEFAULT 'Standard Ride'",
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS registration_number VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS colour VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS make VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS model VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS active TINYINT(1) DEFAULT 1",
        "ALTER TABLE drivers ADD COLUMN IF NOT EXISTS licence_number VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE drivers ADD COLUMN IF NOT EXISTS email VARCHAR(100) DEFAULT NULL",
        "ALTER TABLE drivers ADD COLUMN IF NOT EXISTS active TINYINT(1) DEFAULT 1",
        "ALTER TABLE enquiries ADD COLUMN IF NOT EXISTS user_id INT DEFAULT NULL",
        "ALTER TABLE enquiries ADD COLUMN IF NOT EXISTS booking_id INT DEFAULT NULL",
        "ALTER TABLE enquiries ADD COLUMN IF NOT EXISTS status ENUM('open','resolved') DEFAULT 'open'",
        "ALTER TABLE enquiries ADD COLUMN IF NOT EXISTS response TEXT DEFAULT NULL",
        "ALTER TABLE enquiries ADD COLUMN IF NOT EXISTS response_date DATETIME DEFAULT NULL",
        "CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            user_id INT NOT NULL,
            journey_rating TINYINT NOT NULL,
            vehicle_rating TINYINT NOT NULL,
            driver_rating TINYINT NOT NULL,
            review_text TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY unique_booking_user_review (booking_id, user_id)
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS offer_codes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            code VARCHAR(50) NOT NULL UNIQUE,
            discount_percent DECIMAL(5,2) NOT NULL DEFAULT 0.00,
            expires_at DATETIME NOT NULL,
            is_used TINYINT(1) DEFAULT 0,
            used_at DATETIME DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            source VARCHAR(50) DEFAULT 'manual'
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS delay_notifications (
            id INT AUTO_INCREMENT PRIMARY KEY,
            booking_id INT NOT NULL,
            user_id INT NOT NULL,
            message TEXT NOT NULL,
            eta_minutes INT DEFAULT NULL,
            sent_via ENUM('sms','email') NOT NULL,
            sent_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB",
        "CREATE TABLE IF NOT EXISTS admin_activity_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            admin_user_id INT NOT NULL,
            action_type VARCHAR(100) NOT NULL,
            entity_type VARCHAR(100) NOT NULL,
            entity_id INT DEFAULT NULL,
            details TEXT DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB"
    ];

    foreach ($ddlStatements as $sql) {
        $conn->query($sql);
    }
}

ensure_privatehire_schema($conn);
?>
