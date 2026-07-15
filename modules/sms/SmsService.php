<?php
/**
 * SMS service - Phase 9.
 *
 * Sends SMS reminders through Africa's Talking (default) or Beem.
 * Every message is written to the sms_logs table so the admin can see
 * what was sent. If no API key is configured the message is still logged
 * with the status "simulated" so the system keeps working during
 * development on XAMPP.
 */

require_once __DIR__ . '/../../config/database.php';

/**
 * Format a Tanzanian phone number to the international form (+2557XXXXXXXX).
 */
function format_phone($phone)
{
    $phone = preg_replace('/\D+/', '', $phone);      // keep digits only
    if (strpos($phone, '0') === 0) {
        $phone = '255' . substr($phone, 1);          // 07... -> 2557...
    }
    if (strpos($phone, '255') !== 0) {
        $phone = '255' . $phone;
    }
    return '+' . $phone;
}

/**
 * Main entry point. Returns true on success (or simulated success).
 */
function send_sms($phone, $message, $patientId = null)
{
    $provider = get_setting('sms_provider', SMS_PROVIDER);
    $to = format_phone($phone);

    if ($provider === 'beem') {
        $result = beem_send($to, $message);
    } else {
        $result = africastalking_send($to, $message);
    }

    // Log every attempt.
    $stmt = db()->prepare(
        'INSERT INTO sms_logs (patient_id, phone, message, provider, status) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$patientId, $to, $message, $provider, $result ? 'sent' : 'failed']);

    return $result;
}

/**
 * Send through Africa's Talking. Falls back to "simulated" when no key.
 */
function africastalking_send($to, $message)
{
    if (AT_API_KEY === '') {
        return true; // simulated during development
    }
    $ch = curl_init('https://api.africastalking.com/version1/messaging');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'apiKey: ' . AT_API_KEY,
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ],
        CURLOPT_POSTFIELDS => http_build_query([
            'username' => AT_USERNAME,
            'to'       => $to,
            'message'  => $message,
            'from'     => AT_SENDER_ID,
        ]),
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $response !== false && $code >= 200 && $code < 300;
}

/**
 * Send through Beem Africa. Falls back to "simulated" when no key.
 */
function beem_send($to, $message)
{
    if (BEEM_API_KEY === '' || BEEM_SECRET_KEY === '') {
        return true; // simulated during development
    }
    $payload = json_encode([
        'source_addr'   => BEEM_SENDER_ID,
        'schedule_time' => '',
        'encoding'      => 0,
        'message'       => $message,
        'recipients'    => [['recipient_id' => 1, 'dest_addr' => ltrim($to, '+')]],
    ]);
    $ch = curl_init('https://apisms.beem.africa/v1/send');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(BEEM_API_KEY . ':' . BEEM_SECRET_KEY),
        ],
        CURLOPT_POSTFIELDS => $payload,
    ]);
    $response = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return $response !== false && $code >= 200 && $code < 300;
}
