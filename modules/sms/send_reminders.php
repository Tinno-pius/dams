<?php
/**
 * Appointment reminder sender (Phase 8 / 9).
 *
 * Sends an SMS reminder to patients who have an appointment
 * in 3 days or in 1 day. Run it once a day with a cron job, e.g.
 *
 *   0 8 * * *  php /path/to/dams/modules/sms/send_reminders.php
 *
 * You can also open it in the browser during development to test.
 */
require_once __DIR__ . '/../../includes/init.php';
require_once __DIR__ . '/SmsService.php';

$sent = 0;
foreach ([1, 3] as $daysBefore) {
    $stmt = db()->prepare(
        "SELECT a.id, a.appointment_date, p.id AS patient_id, p.full_name, p.phone, p.user_id
         FROM appointments a JOIN patients p ON p.id = a.patient_id
         WHERE a.status IN ('scheduled','rescheduled')
           AND a.appointment_date = DATE_ADD(CURDATE(), INTERVAL ? DAY)"
    );
    $stmt->execute([$daysBefore]);

    foreach ($stmt->fetchAll() as $row) {
        if (empty($row['phone'])) {
            continue;
        }
        $message = 'Habari ' . $row['full_name'] . ', kumbusho: una miadi ya kliniki tarehe '
            . date('d/m/Y', strtotime($row['appointment_date'])) . ' (siku ' . $daysBefore . ' zijazo). DAMS';
        send_sms($row['phone'], $message, (int) $row['patient_id']);
        notify($row['user_id'] ?? null, 'Appointment Reminder', 'You have a clinic appointment on ' . date('d M Y', strtotime($row['appointment_date'])) . '.');
        $sent++;
    }
}

echo "Reminders processed. Messages sent: {$sent}\n";
