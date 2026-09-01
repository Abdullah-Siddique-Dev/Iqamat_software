<?php
header('Content-Type: application/json');

if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} elseif (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

include "connection.php";

$eventId = $_POST['eventId'] ?? null;

if (!$eventId) {
    echo json_encode(["success" => false, "message" => "Missing eventId"]);
    exit;
}

// Get event details
$evStmt = $conn->prepare("SELECT topic, dateTime, phone, location, type, organisier FROM events WHERE id = ? LIMIT 1");
$evStmt->bind_param("i", $eventId);
$evStmt->execute();
$event = $evStmt->get_result()->fetch_assoc();
$evStmt->close();

if (!$event) {
    echo json_encode(["success" => false, "message" => "Event not found"]);
    exit;
}

$dateTimeFormatted = $event['dateTime'] ? date('d M Y, h:i A', strtotime($event['dateTime'])) : '—';

// Organiser name
$oStmt = $conn->prepare("SELECT firstName, lastName FROM users WHERE id = ? LIMIT 1");
$oStmt->bind_param("i", $event['organisier']);
$oStmt->execute();
$oRow = $oStmt->get_result()->fetch_assoc();
$oStmt->close();
$organiserName = $oRow ? trim($oRow['firstName'] . ' ' . $oRow['lastName']) : 'Organiser';

// Get everyone who was notified about this event, with a valid email
$usersStmt = $conn->prepare("
    SELECT u.firstName, u.email
    FROM notifications n
    JOIN users u ON u.id = n.user_id
    WHERE n.event_id = ? AND u.email IS NOT NULL AND u.email != ''
");
$usersStmt->bind_param("i", $eventId);
$usersStmt->execute();
$usersResult = $usersStmt->get_result();

$sent   = 0;
$failed = 0;

while ($user = $usersResult->fetch_assoc()) {
    $ok = sendEventEmail(
        $user['email'],
        $user['firstName'],
        $event['type'],
        $event['topic'],
        $event['location'],
        $dateTimeFormatted,
        $event['phone'],
        $organiserName
    );
    $ok ? $sent++ : $failed++;
}

$usersStmt->close();
$conn->close();

echo json_encode(["success" => true, "sent" => $sent, "failed" => $failed]);

// ── Helpers ──────────────────────────────────────────────────────────────────

function getTypeColor(string $type): string {
    return match(strtolower($type)) {
        'dars'     => '#00e396',
        'workshop' => '#03a9f4',
        'dawah'    => '#feb019',
        default    => '#775dd0',
    };
}

function buildEmailBody(
    string $firstName,
    string $type,
    string $topic,
    string $location,
    string $dateTime,
    string $phone,
    string $organiserName
): string {
    $color     = getTypeColor($type);
    $typeUpper = strtoupper($type);
    $phone     = $phone ?: '—';
    $location  = $location ?: '—';
    $year      = date('Y');

    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<meta name="viewport" content="width=device-width,initial-scale=1"/>
<title>New $type Event</title>
</head>
<body style="margin:0;padding:0;background:#0f1c2e;font-family:'Segoe UI',Arial,sans-serif;">
  <div style="max-width:580px;margin:40px auto;background:#1b2a47;border-radius:16px;overflow:hidden;border:1px solid rgba(255,255,255,.07);">
    <div style="background:linear-gradient(135deg,#0d1a2e 0%,#1b2a47 60%,#0d2040 100%);padding:36px 32px 28px;text-align:center;border-bottom:1px solid rgba(255,255,255,.07);">
      <div style="display:inline-block;background:rgba(255,255,255,.07);border:1px solid rgba(255,255,255,.12);border-radius:20px;padding:3px 14px;font-size:11px;font-weight:700;letter-spacing:1.5px;color:$color;text-transform:uppercase;margin-bottom:14px;">
        $typeUpper
      </div>
      <h1 style="color:#fff;font-size:1.45rem;font-weight:700;margin:0 0 6px;">New Event Added</h1>
      <p style="color:rgba(255,255,255,.4);font-size:.85rem;margin:0;">Iqamat E Islam</p>
    </div>
    <div style="padding:28px 32px 0;">
      <p style="color:rgba(255,255,255,.75);font-size:.95rem;margin:0 0 6px;">
        Assalam o Alaikum, <strong style="color:#fff;">$firstName</strong>
      </p>
      <p style="color:rgba(255,255,255,.45);font-size:.85rem;margin:0;">
        A new <strong style="color:$color;">$type</strong> event has been scheduled. Here are the details:
      </p>
    </div>
    <div style="margin:24px 32px;background:#152036;border-radius:12px;border:1px solid rgba(255,255,255,.07);overflow:hidden;">
      <div style="background:linear-gradient(90deg,rgba(255,255,255,.04),transparent);padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.06);">
        <div style="font-size:.68rem;color:$color;font-weight:700;letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;">Topic</div>
        <div style="color:#fff;font-size:1.05rem;font-weight:700;">$topic</div>
      </div>
      <table style="width:100%;border-collapse:collapse;">
        <tr style="border-bottom:1px solid rgba(255,255,255,.05);">
          <td style="padding:13px 20px;width:44%;">
            <div style="font-size:.68rem;color:rgba(255,255,255,.3);font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px;">Date & Time</div>
            <div style="color:rgba(255,255,255,.8);font-size:.88rem;font-weight:500;">$dateTime</div>
          </td>
          <td style="padding:13px 20px;border-left:1px solid rgba(255,255,255,.05);">
            <div style="font-size:.68rem;color:rgba(255,255,255,.3);font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px;">Location</div>
            <div style="color:rgba(255,255,255,.8);font-size:.88rem;font-weight:500;">$location</div>
          </td>
        </tr>
        <tr>
          <td style="padding:13px 20px;">
            <div style="font-size:.68rem;color:rgba(255,255,255,.3);font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px;">Contact</div>
            <div style="color:rgba(255,255,255,.8);font-size:.88rem;font-weight:500;">$phone</div>
          </td>
          <td style="padding:13px 20px;border-left:1px solid rgba(255,255,255,.05);">
            <div style="font-size:.68rem;color:rgba(255,255,255,.3);font-weight:600;text-transform:uppercase;letter-spacing:.8px;margin-bottom:3px;">Organiser</div>
            <div style="color:rgba(255,255,255,.8);font-size:.88rem;font-weight:500;">$organiserName</div>
          </td>
        </tr>
      </table>
    </div>
    <div style="text-align:center;padding:0 32px 28px;">
      <p style="color:rgba(255,255,255,.35);font-size:.78rem;margin:0 0 18px;">
        Log in to your dashboard to view full event details and mark your attendance.
      </p>
      <a href="http://iqamateislam.site" 
         style="display:inline-block;background:#03a9f4;color:#fff;text-decoration:none;border-radius:8px;padding:12px 30px;font-size:.9rem;font-weight:600;">
        View Dashboard
      </a>
    </div>
    <div style="background:#111e32;padding:18px 32px;text-align:center;border-top:1px solid rgba(255,255,255,.05);">
      <p style="color:rgba(255,255,255,.2);font-size:.72rem;margin:0;">
        © $year Iqamat E Islam &nbsp;·&nbsp; This is an automated notification. Do not reply.
      </p>
    </div>
  </div>
</body>
</html>
HTML;
}

function sendEventEmail(string $toEmail, string $firstName, string $type, string $topic, string $location, string $dateTime, string $phone, string $organiserName): bool {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chosama7777@gmail.com';
        $mail->Password   = 'uutd jpxa uaue oibd';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        $mail->setFrom('chosama7777@gmail.com', 'Iqamat E Islam');
        $mail->addAddress($toEmail, $firstName);
        $mail->isHTML(true);
        $mail->Subject = "📅 New $type: $topic";
        $mail->Body    = buildEmailBody($firstName, $type, $topic, $location, $dateTime, $phone, $organiserName);

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email failed to $toEmail: " . $mail->ErrorInfo);
        return false;
    }
}
?>