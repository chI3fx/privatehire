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
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS registration_number VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS colour VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS make VARCHAR(50) DEFAULT NULL",
        "ALTER TABLE vehicles ADD COLUMN IF NOT EXISTS model VARCHAR(50) DEFAULT NULL"
    ];

    foreach ($ddlStatements as $sql) {
        $conn->query($sql);
    }
}

ensure_privatehire_schema($conn);
?>
