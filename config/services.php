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

if (!function_exists('privatehire_log_admin_activity')) {
    function privatehire_log_admin_activity(
        mysqli $conn,
        int $adminUserId,
        string $actionType,
        string $entityType,
        ?int $entityId = null,
        ?string $details = null
    ): void {
        $adminUserId = (int)$adminUserId;
        $safeAction = $conn->real_escape_string($actionType);
        $safeEntity = $conn->real_escape_string($entityType);
        $safeEntityId = $entityId !== null ? (int)$entityId : "NULL";
        $safeDetails = $details !== null ? "'" . $conn->real_escape_string($details) . "'" : "NULL";

        $conn->query("
            INSERT INTO admin_activity_logs (admin_user_id, action_type, entity_type, entity_id, details)
            VALUES ({$adminUserId}, '{$safeAction}', '{$safeEntity}', {$safeEntityId}, {$safeDetails})
        ");
    }
}

if (!function_exists('privatehire_generate_offer_code')) {
    function privatehire_generate_offer_code(mysqli $conn, int $userId, float $discountPercent, string $expiresAt, string $source = 'manual'): ?array
    {
        $userId = (int)$userId;
        $discountPercent = round($discountPercent, 2);
        $safeExpiresAt = $conn->real_escape_string($expiresAt);
        $safeSource = $conn->real_escape_string($source);
        $code = 'PH-' . strtoupper(bin2hex(random_bytes(4)));
        $safeCode = $conn->real_escape_string($code);

        $ok = $conn->query("
            INSERT INTO offer_codes (user_id, code, discount_percent, expires_at, is_used, source)
            VALUES ({$userId}, '{$safeCode}', {$discountPercent}, '{$safeExpiresAt}', 0, '{$safeSource}')
        ");

        if (!$ok) {
            return null;
        }

        return [
            'id' => (int)$conn->insert_id,
            'code' => $code,
            'discount_percent' => $discountPercent,
            'expires_at' => $expiresAt
        ];
    }
}

if (!function_exists('privatehire_apply_offer_code')) {
    function privatehire_apply_offer_code(mysqli $conn, int $userId, string $rawCode, float $baseAmount): array
    {
        $code = strtoupper(trim($rawCode));
        if ($code === '') {
            return ['valid' => false, 'message' => 'Offer code is empty.'];
        }
        $safeCode = $conn->real_escape_string($code);
        $userId = (int)$userId;

        $result = $conn->query("
            SELECT * FROM offer_codes
            WHERE user_id={$userId}
              AND code='{$safeCode}'
              AND is_used=0
              AND expires_at > NOW()
            LIMIT 1
        ");

        if (!$result || $result->num_rows !== 1) {
            return ['valid' => false, 'message' => 'Offer code is invalid, used, or expired.'];
        }

        $offer = $result->fetch_assoc();
        $discountPercent = (float)$offer['discount_percent'];
        $discountAmount = round($baseAmount * ($discountPercent / 100), 2);

        return [
            'valid' => true,
            'offer_id' => (int)$offer['id'],
            'code' => $offer['code'],
            'discount_percent' => $discountPercent,
            'discount_amount' => $discountAmount
        ];
    }
}

if (!function_exists('privatehire_mark_offer_used')) {
    function privatehire_mark_offer_used(mysqli $conn, int $offerId): void
    {
        $offerId = (int)$offerId;
        if ($offerId <= 0) {
            return;
        }
        $conn->query("UPDATE offer_codes SET is_used=1, used_at=NOW() WHERE id={$offerId} AND is_used=0");
    }
}
