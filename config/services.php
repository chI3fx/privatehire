<?php

use PHPMailer\PHPMailer\PHPMailer;

if (!function_exists('privatehire_send_email')) {
    function privatehire_send_email(string $to, string $name, string $subject, string $body): bool
    {
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }

        require_once __DIR__ . '/../vendor/autoload.php';

        $mail = new PHPMailer(true);
        $smtpUser = getenv('MAIL_USERNAME') ?: 'karungokeith@gmail.com';
        $smtpPass = getenv('MAIL_APP_PASSWORD') ?: 'zbok nzfx btdy rgox';
        $fromAddress = getenv('MAIL_FROM_ADDRESS') ?: $smtpUser;
        $fromName = getenv('MAIL_FROM_NAME') ?: 'PrivateHire Team';

        try {
            $mail->isSMTP();
            $mail->Host = getenv('MAIL_HOST') ?: 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = $smtpUser;
            $mail->Password = $smtpPass;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)(getenv('MAIL_PORT') ?: 587);

            $mail->setFrom($fromAddress, $fromName);
            $mail->addAddress($to, $name ?: $to);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->send();

            return true;
        } catch (Throwable $e) {
            error_log('Email send failed: ' . $e->getMessage());
            return false;
        }
    }
}

if (!function_exists('privatehire_send_sms')) {
    function privatehire_send_sms(string $phone, string $message): bool
    {
        // Stub SMS gateway. Replace this with Twilio/Africa's Talking integration.
        if (trim($phone) === '') {
            return false;
        }

        $logDir = __DIR__ . '/../logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }

        $line = sprintf("[%s] SMS to %s: %s\n", date('Y-m-d H:i:s'), $phone, $message);
        return file_put_contents($logDir . '/sms.log', $line, FILE_APPEND) !== false;
    }
}

if (!function_exists('privatehire_process_payment')) {
    function privatehire_process_payment(
        string $method,
        float $amount,
        ?string $cardBrand = null,
        ?string $cardNumber = null
    ): array {
        if ($amount <= 0) {
            return ['success' => false, 'message' => 'Invalid payment amount.'];
        }

        if ($method === 'paypal') {
            return [
                'success' => true,
                'status' => 'paid',
                'reference' => 'PAYPAL-' . strtoupper(bin2hex(random_bytes(4))),
                'card_brand' => null,
                'card_last4' => null
            ];
        }

        if ($method !== 'card') {
            return ['success' => false, 'message' => 'Unsupported payment method.'];
        }

        $normalizedBrand = strtolower(trim((string)$cardBrand));
        if (!in_array($normalizedBrand, ['visa', 'mastercard', 'amex'], true)) {
            return ['success' => false, 'message' => 'Invalid card type.'];
        }

        $digits = preg_replace('/\D+/', '', (string)$cardNumber);
        if (strlen($digits) < 13) {
            return ['success' => false, 'message' => 'Card number is not valid.'];
        }

        // Simulated VISA Check integration gate.
        $isVisaCheckPass = true;
        if ($normalizedBrand === 'visa' && !str_starts_with($digits, '4')) {
            $isVisaCheckPass = false;
        }
        if ($normalizedBrand === 'mastercard' && !preg_match('/^5[1-5]/', $digits)) {
            $isVisaCheckPass = false;
        }
        if ($normalizedBrand === 'amex' && !preg_match('/^3[47]/', $digits)) {
            $isVisaCheckPass = false;
        }

        if (!$isVisaCheckPass) {
            return ['success' => false, 'message' => 'Card verification failed via VISA Check.'];
        }

        return [
            'success' => true,
            'status' => 'paid',
            'reference' => 'CARD-' . strtoupper(bin2hex(random_bytes(4))),
            'card_brand' => $normalizedBrand,
            'card_last4' => substr($digits, -4)
        ];
    }
}

if (!function_exists('privatehire_mask_card')) {
    function privatehire_mask_card(?string $brand, ?string $last4): string
    {
        if (!$brand || !$last4) {
            return 'N/A';
        }

        return strtoupper($brand) . ' **** **** **** ' . $last4;
    }
}
