<?php
include "connection.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user']) || !is_array($_SESSION['user']) || empty($_SESSION['user']['id'])) {
    session_unset(); session_destroy();
    header("Location: ../index.php"); exit();
}

$sessionUserId = (int)$_SESSION['user']['id'];

// Always fetch latest logged-in user from DB
$stmt = $conn->prepare("
    SELECT id, username, role, area, firstName, lastName, email, phone, cnic, gender, age, image, card, category
    FROM users WHERE id = ? LIMIT 1
");
$stmt->bind_param("i", $sessionUserId);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) { session_unset(); session_destroy(); header("Location: ../index.php"); exit(); }
$loggedUser = $result->fetch_assoc();
$stmt->close();
$_SESSION['user'] = array_intersect_key($loggedUser, array_flip(['id','username','role','area','firstName','lastName','email','card','category']));
$loggedRole = $loggedUser['role'];
$loggedId   = (int)$loggedUser['id'];

// Which profile to view
$profileId    = isset($_GET['id']) ? (int)$_GET['id'] : $loggedId;
$canViewOther = in_array(strtolower($loggedRole), ['dg','md','admin','administrator','adminsir','representative','committee','ithead','researchhead'])
                || stripos($loggedRole, 'admin') !== false;
if ($profileId !== $loggedId && !$canViewOther) { header("Location: userProfile.php"); exit(); }

// Auto-check columns for card & category
$colCard = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'card'");
if (!$colCard || mysqli_num_rows($colCard) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN card VARCHAR(50) DEFAULT 'Diamond' AFTER area");
}
$colCategory = mysqli_query($conn, "SHOW COLUMNS FROM users LIKE 'category'");
if (!$colCategory || mysqli_num_rows($colCategory) == 0) {
    mysqli_query($conn, "ALTER TABLE users ADD COLUMN category VARCHAR(50) DEFAULT 'B' AFTER card");
}

// Fetch profile user
$stmt2 = $conn->prepare("
    SELECT id, username, role, area, firstName, lastName, email, phone, cnic, gender, age, image, date_of_joining, card, category
    FROM users WHERE id = ? LIMIT 1
");
$stmt2->bind_param("i", $profileId);
$stmt2->execute();
$profileResult = $stmt2->get_result();
if ($profileResult->num_rows === 0) { header("Location: userProfile.php"); exit(); }
$profileUser = $profileResult->fetch_assoc();
$stmt2->close();

// Representative can only view members in their area
if ($loggedRole === 'representative' && $profileId !== $loggedId) {
    if ((int)$profileUser['area'] !== (int)$loggedUser['area']) { header("Location: userProfile.php"); exit(); }
}

$isOwnProfile = ($profileId === $loggedId);

// ── User's area name ──────────────────────────────────────
$userAreaName = '—';
$userAreaId   = (int)($profileUser['area'] ?? 0);
if ($userAreaId) {
    $aStmt = $conn->prepare("SELECT areaName FROM dars_areas WHERE id = ? LIMIT 1");
    $aStmt->bind_param("i", $userAreaId);
    $aStmt->execute();
    $aRes = $aStmt->get_result()->fetch_assoc();
    if ($aRes) $userAreaName = $aRes['areaName'];
    $aStmt->close();
}

// ── Teams this user is a MEMBER of ───────────────────────
$teamsStmt = $conn->prepare("
    SELECT t.id, t.name, t.description, tm.team_role, t.leader_id
    FROM team_members tm
    JOIN teams t ON t.id = tm.team_id
    WHERE tm.user_id = ?
");
$teamsStmt->bind_param("i", $profileId);
$teamsStmt->execute();
$teamsResult = $teamsStmt->get_result();
$userTeams   = [];
while ($row = $teamsResult->fetch_assoc()) $userTeams[] = $row;
$teamsStmt->close();

// ── AREA the user is representative of (supervised) ──────
$supervisedAreas = [];
if (in_array($profileUser['role'], ['representative','DG','MD'])) {
    $areaStmt = $conn->prepare("
        SELECT id, areaName, darsType, dayTime, location
        FROM dars_areas WHERE representative_id = ?
    ");
    $areaStmt->bind_param("i", $profileId);
    $areaStmt->execute();
    $areaResult = $areaStmt->get_result();
    while ($row = $areaResult->fetch_assoc()) $supervisedAreas[] = $row;
    $areaStmt->close();
}

// ── TEAMS this user LEADS ─────────────────────────────────
$ledTeams = [];
$ltStmt   = $conn->prepare("SELECT id, name, description FROM teams WHERE leader_id = ?");
$ltStmt->bind_param("i", $profileId);
$ltStmt->execute();
$ltResult = $ltStmt->get_result();
while ($row = $ltResult->fetch_assoc()) $ledTeams[] = $row;
$ltStmt->close();

// ── For each led team, get its members ───────────────────
$ledTeamMembers = [];
foreach ($ledTeams as $lt) {
    $lmStmt = $conn->prepare("
        SELECT u.id, u.firstName, u.lastName, u.role, tm.team_role
        FROM team_members tm
        JOIN users u ON u.id = tm.user_id
        WHERE tm.team_id = ? AND tm.user_id != ?
        ORDER BY u.firstName
    ");
    $lmStmt->bind_param("ii", $lt['id'], $profileId);
    $lmStmt->execute();
    $lmRes = $lmStmt->get_result();
    while ($r = $lmRes->fetch_assoc()) $ledTeamMembers[$lt['id']][] = $r;
    $lmStmt->close();
}

// ── TEAM stats: total teams led + total team members ─────
$totalTeamsLed   = count($ledTeams);
$totalTeamMembers = 0;
foreach ($ledTeamMembers as $members) $totalTeamMembers += count($members);

// Also count area committee/staff members shown in Team tab
// (run AFTER $areaCommitteeUsers is populated)

// ── AREA stats: non-member committee/staff in supervised area ──
// For Team tab "area" section: all users in supervised areas with role != member
$areaCommitteeUsers = [];
foreach ($supervisedAreas as $sa) {
    $acStmt = $conn->prepare("
        SELECT id, firstName, lastName, role
        FROM users
        WHERE area = ? AND role != 'member' AND id != ?
        ORDER BY role, firstName
    ");
    $acStmt->bind_param("ii", $sa['id'], $profileId);
    $acStmt->execute();
    $acRes = $acStmt->get_result();
    while ($r = $acRes->fetch_assoc()) {
        $areaCommitteeUsers[$sa['areaName']][] = $r;
    }
    $acStmt->close();
}

// ── Total visible members in Team tab ────────────────────
$totalMembersDisplay = $totalTeamMembers;
foreach ($areaCommitteeUsers as $areaUsers) {
    $totalMembersDisplay += count($areaUsers);
}

// ── Dars attendance stats ─────────────────────────────────
$attStmt = $conn->prepare("
    SELECT COUNT(*) as total,
           SUM(attendance='Present') as present,
           SUM(attendance='Absent')  as absent
    FROM dars_attendance WHERE user_id = ?
");
$attStmt->bind_param("i", $profileId);
$attStmt->execute();
$attStats = $attStmt->get_result()->fetch_assoc();
$attStmt->close();

// ── Namaz stats ───────────────────────────────────────────
$namazStmt = $conn->prepare("
    SELECT COUNT(*) as total,
           SUM(status='with_jamaat')    as with_jamaat,
           SUM(status='without_jamaat') as without_jamaat,
           SUM(status='missed')         as missed
    FROM namaz_attendance WHERE user_id = ?
");
$namazStmt->bind_param("i", $profileId);
$namazStmt->execute();
$namazStats = $namazStmt->get_result()->fetch_assoc();
$namazStmt->close();

// ── Quran days this week ──────────────────────────────────
// ── Quran days this week (Mon → today) ───────────────────
$todayTs   = strtotime('today');
$dayOfWeek = (int)date('N'); // 1=Mon … 7=Sun
$monTs     = $todayTs - (($dayOfWeek - 1) * 86400);
$weekStart = date('Y-m-d', $monTs);
$weekEnd   = date('Y-m-d', $todayTs);
$daysElapsed = $dayOfWeek; // Mon=1 … Thu=4 … Sun=7

$quranDays = 0;
$qs = $conn->prepare("
    SELECT COUNT(DISTINCT attendance_date)
    FROM quran_attendance
    WHERE user_id = ?
      AND attendance_date >= ?
      AND attendance_date <= ?
      AND status = 'Present'
");
$qs->bind_param("iss", $profileId, $weekStart, $weekEnd);
$qs->execute(); $qs->bind_result($quranDays); $qs->fetch(); $qs->close();

// ── Namaz % this week ─────────────────────────────────────
// ── Namaz this week (Mon → today): prayed / total possible ──
// 5 prayers/day × days elapsed
$namazPossible = $daysElapsed * 5;
$namazPrayed   = 0;
$ns = $conn->prepare("
    SELECT COUNT(*)
    FROM namaz_attendance
    WHERE user_id = ?
      AND attendance_date >= ?
      AND attendance_date <= ?
      AND status != 'missed'
");
$ns->bind_param("iss", $profileId, $weekStart, $weekEnd);
$ns->execute(); $ns->bind_result($namazPrayed); $ns->fetch(); $ns->close();

// ── Time today ────────────────────────────────────────────
// ── Time this week (Mon → today) ─────────────────────────
$weekMins = 0;
$ts = $conn->prepare("
    SELECT COALESCE(SUM(duration_minutes), 0)
    FROM time_sessions
    WHERE user_id = ?
      AND session_date >= ?
      AND session_date <= ?
      AND end_time IS NOT NULL
");
$ts->bind_param("iss", $profileId, $weekStart, $weekEnd);
$ts->execute(); $ts->bind_result($weekMins); $ts->fetch(); $ts->close();
$weekTimeStr = $weekMins >= 60
    ? floor($weekMins/60).'h '.($weekMins%60).'m'
    : $weekMins.'m';


    // ── Quran streak (consecutive days read, no weekly limit) ─
// ── Quran streak (consecutive days, no limit) ─────────────
// Fetch all distinct quran-read dates DESC, then count consecutive days
$quranStreak = 0;
$sqAll = $conn->prepare("
    SELECT DISTINCT attendance_date
    FROM quran_attendance
    WHERE user_id = ? AND status = 'Present'
    ORDER BY attendance_date DESC
");
$sqAll->bind_param("i", $profileId);
$sqAll->execute();
$sqRes = $sqAll->get_result();
$readDates = [];
while ($r = $sqRes->fetch_assoc()) $readDates[] = $r['attendance_date'];
$sqAll->close();

if (!empty($readDates)) {
    // Allow streak to start from today OR yesterday
    // (user may not have logged today yet — don't break streak)
    $today     = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));

    // If most recent read date is neither today nor yesterday, streak is 0
    if ($readDates[0] === $today || $readDates[0] === $yesterday) {
        $expected = new DateTime($readDates[0]);
        foreach ($readDates as $d) {
            if ($d === $expected->format('Y-m-d')) {
                $quranStreak++;
                $expected->modify('-1 day');
            } else {
                break; // gap found, streak ends
            }
        }
    }
}


// ── Recent dars sessions ──────────────────────────────────
$recentDars = [];
$rdStmt = $conn->prepare("
    SELECT da.attendance, da.dateTime, da.timing, d.areaName
    FROM dars_attendance da
    LEFT JOIN dars_areas d ON d.id = da.area_id
    WHERE da.user_id = ?
    ORDER BY da.dateTime DESC LIMIT 6
");
$rdStmt->bind_param("i", $profileId);
$rdStmt->execute();
$rdResult = $rdStmt->get_result();
while ($row = $rdResult->fetch_assoc()) $recentDars[] = $row;
$rdStmt->close();

// ── Helper vars ───────────────────────────────────────────
$fullName  = trim($profileUser['firstName'].' '.($profileUser['lastName'] ?? ''));
$initials  = strtoupper(
    substr($profileUser['firstName'],0,1) .
    substr($profileUser['lastName'] ?? '',0,1)
);
$darsRate  = ($attStats['total'] > 0)  ? round(($attStats['present']/$attStats['total'])*100)   : 0;
$namazRate = ($namazStats['total'] > 0) ? round(($namazStats['with_jamaat']/$namazStats['total'])*100) : 0;

$roleLabels = [
    'DG'             => 'Director General',
    'MD'             => 'Managing Director',
    'representative' => 'Representative',
    'committee'      => 'Committee Member',
    'itHead'         => 'IT Head',
    'researchHead'   => 'Research Head',
    'trainee'        => 'Trainee',
    'member'         => 'Member',
];
$roleLabel = $roleLabels[$profileUser['role']] ?? ucfirst($profileUser['role']);

// Role chip colors
$roleChipColors = [
    'DG'             => ['bg'=>'rgba(124,58,237,.25)', 'border'=>'rgba(124,58,237,.4)',  'color'=>'#c4b5fd'],
    'MD'             => ['bg'=>'rgba(8,145,178,.25)',  'border'=>'rgba(8,145,178,.4)',   'color'=>'#67e8f9'],
    'representative' => ['bg'=>'rgba(8,145,178,.25)',  'border'=>'rgba(8,145,178,.4)',   'color'=>'#67e8f9'],
    'committee'      => ['bg'=>'rgba(13,148,136,.25)', 'border'=>'rgba(13,148,136,.4)',  'color'=>'#5eead4'],
    'itHead'         => ['bg'=>'rgba(59,130,246,.25)', 'border'=>'rgba(59,130,246,.4)',  'color'=>'#93c5fd'],
    'researchHead'   => ['bg'=>'rgba(245,158,11,.2)',  'border'=>'rgba(245,158,11,.35)', 'color'=>'#fcd34d'],
    'trainee'        => ['bg'=>'rgba(100,116,139,.2)', 'border'=>'rgba(100,116,139,.35)','color'=>'#94a3b8'],
    'member'         => ['bg'=>'rgba(100,116,139,.2)', 'border'=>'rgba(100,116,139,.35)','color'=>'#94a3b8'],
];
$rc = $roleChipColors[$profileUser['role']] ?? $roleChipColors['member'];

// ── Correct total teams count ─────────────────────────────
// Count: supervised areas + teams from team_members table (led or member)
$totalTeamsCount = count($supervisedAreas) + count($userTeams);
// If user leads a team AND is in team_members for it, userTeams already includes it
// so no double-count needed. supervisedAreas are separate from teams.

?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
/* ═══════════════════════════════════════════════════════════
   USER PROFILE — Full dark navy theme matching screenshot
═══════════════════════════════════════════════════════════ */

/* ── HERO ─────────────────────────────────────────────────── */
.profile-hero-wrap {
    position: relative; overflow: hidden;
    padding: 0; margin-bottom: 0;
}
.profile-stats-strip {
    margin-top: 4%;
}
.profile-hero-bg {
    position: absolute; inset: 0;
    background: linear-gradient(135deg, #0d1a2e 0%, #1b2a47 55%, #0d2040 100%);
    height: 50%;
    margin-bottom: 10%;
}
.profile-hero-bg::before {
    content: ''; position: absolute; inset: 0;
    background:
        radial-gradient(ellipse at 75% 25%, rgba(8,145,178,.14) 0%, transparent 55%),
        radial-gradient(ellipse at 15% 75%, rgba(13,148,136,.08) 0%, transparent 50%);
}
.profile-hero-bg::after {
    content: ''; position: absolute; inset: 0;
    background-image:
        repeating-linear-gradient(0deg,  transparent, transparent 39px, rgba(255,255,255,.025) 40px),
        repeating-linear-gradient(90deg, transparent, transparent 39px, rgba(255,255,255,.025) 40px);
}
.profile-hero-inner {
    position: relative; z-index: 2;
    padding: 28px 28px 0;
    display: flex; align-items: flex-end;
    justify-content: space-between; flex-wrap: wrap; gap: 16px;
}

/* Avatar */
.ph-avatar-wrap { position: relative; flex-shrink: 0; }
.ph-avatar {
    width: 120px; height: 120px; border-radius: 50%;
    border: 3px solid rgba(255,255,255,.18);
    background: rgba(255,255,255,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; font-weight: 700; color: #fff; overflow: hidden;
    box-shadow: 0 0 0 6px rgba(255,255,255,.05);
}
.ph-avatar img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
.ph-online-dot {
    position: absolute; bottom: 5px; right: 5px;
    width: 20px; height: 20px; border-radius: 50%;
    background: #00e396; border: 2.5px solid #1b2a47;
}

/* Name + role */
.ph-identity { flex: 1; min-width: 180px; padding-bottom: 4px; }
.ph-name { color: #fff; font-size: 1.4rem; font-weight: 700; margin: 0 0 6px; line-height: 1.2; }
.ph-role-chip {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 20px; padding: 3px 12px;
    font-size: .72rem; font-weight: 700; letter-spacing: .3px;
}

/* Team + Areas boxes (top right) */
.ph-right-stats { display: flex; gap: 8px; align-items: flex-start; padding-bottom: 4px; }
.ph-right-stat {
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.1);
    border-radius: 10px; padding: 10px 18px; text-align: center; min-width: 72px;
}
.ph-right-stat-val { color: #fff; font-size: 1.3rem; font-weight: 700; line-height: 1; }
.ph-right-stat-lbl {
    color: rgba(255,255,255,.4); font-size: .63rem;
    text-transform: uppercase; letter-spacing: .8px; margin-top: 3px;
}

/* ── QUICK STATS STRIP (below hero, above tabs) ─────────── */
.profile-stats-strip {
    background: #1b2a47;
    display: grid; grid-template-columns: repeat(4,1fr);
    border-top: 1px solid rgba(255,255,255,.07);
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.pss-item {
    padding: 14px 20px; display: flex; align-items: center; gap: 12px;
    border-right: 1px solid rgba(255,255,255,.07); transition: background .18s;
}
.pss-item:last-child { border-right: none; }
.pss-item:hover { background: rgba(255,255,255,.03); }
.pss-icon {
    width: 36px; height: 36px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 15px; flex-shrink: 0;
}
.pss-val { color: #fff; font-size: 1.05rem; font-weight: 700; line-height: 1; }
.pss-lbl { color: rgba(255,255,255,.4); font-size: .62rem; text-transform: uppercase; letter-spacing: .7px; margin-top: 2px; }

/* ── ATTENDANCE SUMMARY STRIP (above tabs) ──────────────── */
.att-summary-strip {
    background: #172540;
    display: flex; flex-wrap: wrap;
    border-bottom: 1px solid rgba(255,255,255,.06);
}
.att-summary-item {
    flex: 1; min-width: 120px;
    padding: 14px 20px;
    border-right: 1px solid rgba(255,255,255,.05);
    display: flex; flex-direction: column; gap: 2px;
}
.att-summary-item:last-child { border-right: none; }
.att-prog-label {
    display: flex; justify-content: space-between;
    font-size: .72rem; color: rgba(255,255,255,.5); margin-bottom: 6px;
}
.att-prog-track {
    height: 5px; border-radius: 4px; background: rgba(255,255,255,.08); overflow: hidden;
}
.att-prog-fill { height: 100%; border-radius: 4px; transition: width .6s; }
.att-stat-nums { display: flex; gap: 14px; margin-top: 8px; }
.att-num { text-align: center; }
.att-num-val { color: #fff; font-size: .95rem; font-weight: 700; line-height: 1; }
.att-num-lbl { color: rgba(255,255,255,.35); font-size: .6rem; text-transform: uppercase; letter-spacing: .5px; margin-top: 1px; }

/* ── TAB NAV ─────────────────────────────────────────────── */
.profile-tab-nav {
    background: #1b2a47; padding: 0 20px;
    display: flex; gap: 2px; flex-wrap: wrap;
    border-bottom: 1px solid rgba(255,255,255,.07);
}
.ptab-btn {
    background: transparent; border: none;
    color: rgba(255,255,255,.45); font-size: .82rem; font-weight: 600;
    padding: 13px 18px; cursor: pointer;
    border-bottom: 2.5px solid transparent;
    transition: color .18s, border-color .18s;
    display: flex; align-items: center; gap: 6px;
    white-space: nowrap; font-family: inherit;
}
.ptab-btn svg { width: 14px; height: 14px; stroke: currentColor; fill: none; stroke-width: 2; stroke-linecap: round; stroke-linejoin: round; }
.ptab-btn:hover { color: rgba(255,255,255,.75); }
.ptab-btn.active { color: #fff; border-bottom-color: #0d6efd; background: rgba(13,110,253,.08); }

/* ── TAB PANES ───────────────────────────────────────────── */
.ptab-pane { display: none; padding: 20px; }
.ptab-pane.active { display: block; }

/* ── PANELS (shared card style) ─────────────────────────── */
.dark-panel {
    background: #1e3154; border-radius: 12px;
    border: 1px solid rgba(255,255,255,.07); overflow: hidden; margin-bottom: 16px;
}
.dark-panel:last-child { margin-bottom: 0; }
.dp-title {
    padding: 12px 16px; font-size: .68rem; font-weight: 700;
    letter-spacing: 1px; text-transform: uppercase; color: rgba(255,255,255,.35);
    border-bottom: 1px solid rgba(255,255,255,.07);
    display: flex; align-items: center; gap: 8px;
}
.dp-title .dp-count {
    background: rgba(0,143,251,.18); color: #74b4ff;
    border-radius: 20px; padding: 1px 8px; font-size: .67rem; margin-left: 4px;
}

/* ── About rows ──────────────────────────────────────────── */
.about-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
@media(max-width:640px) { .about-grid { grid-template-columns: 1fr; } }

.about-row {
    display: flex; align-items: center; gap: 12px;
    padding: 11px 16px; border-bottom: 1px solid rgba(255,255,255,.05);
    font-size: .84rem;
}
.about-row:last-child { border-bottom: none; }
.about-row-icon {
    width: 30px; height: 30px; border-radius: 8px;
    background: rgba(255,255,255,.07);
    display: flex; align-items: center; justify-content: center;
    color: rgba(255,255,255,.45); font-size: .85rem; flex-shrink: 0;
}
.about-lbl { font-size: .69rem; color: rgba(255,255,255,.35); display: block; margin-bottom: 1px; }
.about-val { color: rgba(255,255,255,.85); font-weight: 500; }

/* ── Team / member rows ──────────────────────────────────── */
.team-row {
    display: flex; align-items: center; gap: 12px;
    padding: 10px 16px; border-bottom: 1px solid rgba(255,255,255,.05);
    font-size: .83rem;
}
.team-row:last-child { border-bottom: none; }
.team-avatar-sm {
    width: 34px; height: 34px; border-radius: 50%;
    background: rgba(255,255,255,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: .82rem; font-weight: 700; color: #fff; flex-shrink: 0;
}
.team-mname { color: rgba(255,255,255,.85); font-weight: 500; }
.team-mrole { font-size: .7rem; color: rgba(255,255,255,.38); margin-top: 1px; }
.team-chip-sm {
    margin-left: auto; padding: 2px 9px; border-radius: 20px;
    font-size: .67rem; font-weight: 700; flex-shrink: 0;
}

/* ── Session rows ────────────────────────────────────────── */
.session-row {
    display: flex; align-items: center; gap: 10px;
    padding: 9px 16px; border-bottom: 1px solid rgba(255,255,255,.05); font-size: .8rem;
}
.session-row:last-child { border-bottom: none; }
.s-dot { width: 7px; height: 7px; border-radius: 50%; flex-shrink: 0; }
.s-present { background: #00e396; }
.s-absent  { background: #ff4560; }
.s-area { flex: 1; color: rgba(255,255,255,.75); font-weight: 500; }
.s-date { color: rgba(255,255,255,.35); font-size: .73rem; }
.s-badge { padding: 2px 8px; border-radius: 8px; font-size: .68rem; font-weight: 600; }
.sb-p { background: rgba(0,227,150,.15); color: #00e396; }
.sb-a { background: rgba(255,69,96,.13); color: #ff4560; }

/* ── Coming soon ─────────────────────────────────────────── */
.coming-soon-wrap {
    padding: 60px 20px; text-align: center;
}
.coming-soon-icon {
    width: 72px; height: 72px; border-radius: 50%;
    background: rgba(255,255,255,.05); border: 1px solid rgba(255,255,255,.1);
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 16px;
}
.coming-soon-title { color: rgba(255,255,255,.7); font-size: 1.05rem; font-weight: 600; margin-bottom: 8px; }
.coming-soon-sub   { color: rgba(255,255,255,.3); font-size: .83rem; }

/* ── Settings ────────────────────────────────────────────── */
.sf-label { display: block; font-size: .7rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: rgba(255,255,255,.4); margin-bottom: 5px; }
.sf-input {
    width: 100%; background: rgba(255,255,255,.06); border: 1.5px solid rgba(255,255,255,.1);
    color: rgba(255,255,255,.88); border-radius: 9px; padding: 9px 13px;
    font-size: .88rem; font-family: inherit; outline: none; transition: border-color .18s;
}
.sf-input:focus { border-color: #0d6efd; background: rgba(255,255,255,.09); color: #fff; }
.sf-input::placeholder { color: rgba(255,255,255,.2); }
.sf-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 12px; }
@media(max-width:500px) { .sf-row { grid-template-columns: 1fr; } }
.sf-group { margin-bottom: 12px; }
.btn-sf-save {
    background: #0d6efd; border: none; color: #fff; padding: 10px 24px; border-radius: 9px;
    font-family: inherit; font-size: .85rem; font-weight: 600; cursor: pointer;
    transition: background .18s; display: inline-flex; align-items: center; gap: 6px;
}
.btn-sf-save:hover { background: #0b5ed7; }
.btn-sf-danger {
    background: rgba(220,53,69,.15); border: 1.5px solid rgba(220,53,69,.3);
    color: #ff6b7a; padding: 9px 20px; border-radius: 9px;
    font-family: inherit; font-size: .85rem; font-weight: 600; cursor: pointer;
    transition: background .18s; display: inline-flex; align-items: center; gap: 6px;
}
.btn-sf-danger:hover { background: rgba(220,53,69,.25); }

/* ── Toast ───────────────────────────────────────────────── */
.profile-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    background: #1a2332; border: 1px solid #293647; border-radius: 10px;
    padding: 12px 20px; font-size: .85rem; font-weight: 500; color: #fff;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.4);
    transform: translateY(80px); opacity: 0;
    transition: transform .3s cubic-bezier(.22,1,.36,1), opacity .3s;
    pointer-events: none; min-width: 220px;
}
.profile-toast.show { transform: translateY(0); opacity: 1; }

/* ── Responsive ──────────────────────────────────────────── */
@media(max-width: 768px) {
    .profile-stats-strip { grid-template-columns: repeat(2,1fr); }
    .pss-item:nth-child(2) { border-right: none; }
    .ph-right-stats { display: none; }
    .profile-hero-inner { padding: 20px 16px 0; }
    .ptab-pane { padding: 14px; }
    .att-summary-strip { flex-direction: column; }
    .att-summary-item { border-right: none; border-bottom: 1px solid rgba(255,255,255,.05); }
}

@media(max-width: 430px) {
    .profile-hero-bg {
    height: 24%;
}
}
</style>

<body>
    <?php include "auth.php"; ?>
<?php include "sidebar.php"; ?>
<?php include "mainTopBar.php"; ?>

<!-- <div class="breadcome-area">
    <div class="container-fluid"><div class="row"><div class="col-lg-12">
        <div class="breadcome-list single-page-breadcome"><div class="row">
            <div class="col-6">
                <div class="breadcome-heading">
                    <h6 style="margin:0;font-size:.9rem;color:rgba(255,255,255,.5);">
                        <?= $isOwnProfile ? 'Profile' : 'User Profile' ?>
                    </h6>
                </div>
            </div>
            <div class="col-6">
                <ul class="breadcome-menu">
                    <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                    <li><span class="bread-blod">Profile</span></li>
                </ul>
            </div>
        </div></div>
    </div></div></div>
</div>
</div> -->

<!-- ══ HERO BANNER ══ -->
<?php if (!$isOwnProfile): ?>
<div class="container-fluid pt-3 px-4">
    <a href="registeredUsers.php" class="btn btn-sm btn-outline-secondary text-white" style="background:rgba(255,255,255,0.08); border-color:rgba(255,255,255,0.2); border-radius:6px; font-weight:500;">
        <i class="bi bi-arrow-left me-1"></i> Back to User Management
    </a>
</div>
<?php endif; ?>
<div class="profile-hero-wrap">
    <div class="profile-hero-bg"></div>
    <div class="profile-hero-inner">

        <!-- Left: avatar + name -->
        <div style="display:flex;align-items:flex-end;gap:18px;flex:1;min-width:200px;">
            <div class="ph-avatar-wrap">
                <div class="ph-avatar">
                    <?php if (!empty($profileUser['image']) && file_exists($profileUser['image'])): ?>
                        <img src="<?= htmlspecialchars($profileUser['image']) ?>" alt="">
                    <?php else: ?>
                        <?= $initials ?>
                    <?php endif; ?>
                </div>
                <div class="ph-online-dot"></div>
            </div>
            <div class="ph-identity" style="padding-bottom:14px;">
                <div class="ph-name"><?= htmlspecialchars($fullName) ?></div>
                <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <div class="ph-role-chip"
                         style="background:<?= $rc['bg'] ?>;border:1px solid <?= $rc['border'] ?>;color:<?= $rc['color'] ?>;">
                        <i class="bi bi-shield-fill-check" style="font-size:.75rem;"></i>
                        <?= htmlspecialchars($roleLabel) ?>
                    </div>
                    <div class="ph-role-chip"
                         style="background:rgba(13,110,253,.18);border:1px solid rgba(13,110,253,.35);color:#60a5fa;">
                        <i class="bi bi-credit-card-2-front-fill" style="font-size:.75rem;"></i>
                        <?= htmlspecialchars($profileUser['card'] ?? 'Diamond') ?> | <?= htmlspecialchars($profileUser['category'] ?? 'B') ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right: Teams led + Team members -->
        <div class="ph-right-stats" style="padding-bottom:14px;">
<div class="ph-right-stat">
    <div class="ph-right-stat-val"><?= $totalTeamsCount ?></div>
    <div class="ph-right-stat-lbl">Teams</div>
</div>
            <div class="ph-right-stat">
               <div class="ph-right-stat-val"><?= $totalMembersDisplay ?></div>
                <div class="ph-right-stat-lbl">Members</div>
            </div>
        </div>
    </div>

    <!-- ── Stats strip: Quran / Namaz / Time / Streak ── -->
    <div class="profile-stats-strip">
<div class="pss-item">
    <div class="pss-icon" style="background:rgba(0,227,150,.15);">
        <i class="bi bi-book-fill" style="color:#00e396;"></i>
    </div>
    <div>
        <div class="pss-val"><?= $quranDays ?> / <?= $daysElapsed ?></div>
        <div class="pss-lbl">Quran Days</div>
    </div>
</div>
<div class="pss-item">
    <div class="pss-icon" style="background:rgba(254,176,25,.15);">
        <i class="bi bi-moon-stars-fill" style="color:#feb019;"></i>
    </div>
    <div>
        <div class="pss-val"><?= $namazPrayed ?> / <?= $namazPossible ?></div>
        <div class="pss-lbl">Namaz</div>
    </div>
</div>
<div class="pss-item">
    <div class="pss-icon" style="background:rgba(0,143,251,.15);">
        <i class="bi bi-stopwatch-fill" style="color:#008ffb;"></i>
    </div>
    <div>
        <div class="pss-val"><?= $weekTimeStr ?></div>
        <div class="pss-lbl">This Week</div>
    </div>
</div>
<div class="pss-item">
    <div class="pss-icon" style="background:rgba(255,69,96,.15);">
        <i class="bi bi-fire" style="color:#ff4560;"></i>
    </div>
    <div>
        <div class="pss-val"><?= $quranStreak >= 3 ? '🔥 '.$quranStreak : $quranStreak ?></div>
        <div class="pss-lbl">Quran Streak</div>
    </div>
</div>
    </div>

    <!-- ── Attendance summary (between stats strip and tabs) ── -->
    <div class="att-summary-strip">
        <!-- Dars attendance -->
        <div class="att-summary-item" style="flex:2;min-width:220px;">
            <div>
                <div class="att-prog-label">
                    <span>Dars Attendance</span>
                    <span style="color:#00e396;font-weight:600;"><?= $darsRate ?>%</span>
                </div>
                <div class="att-prog-track">
                    <div class="att-prog-fill" style="width:<?= $darsRate ?>%;background:#00e396;"></div>
                </div>
            </div>
            <div class="att-stat-nums">
                <div class="att-num">
                    <div class="att-num-val"><?= (int)$attStats['total'] ?></div>
                    <div class="att-num-lbl">Total</div>
                </div>
                <div class="att-num">
                    <div class="att-num-val" style="color:#00e396;"><?= (int)$attStats['present'] ?></div>
                    <div class="att-num-lbl">Present</div>
                </div>
                <div class="att-num">
                    <div class="att-num-val" style="color:#ff4560;"><?= (int)$attStats['absent'] ?></div>
                    <div class="att-num-lbl">Absent</div>
                </div>
            </div>
        </div>

        <!-- Namaz attendance -->
        <div class="att-summary-item" style="flex:2;min-width:220px;">
            <div>
                <div class="att-prog-label">
                    <span>Namaz with Jamaat</span>
                    <span style="color:#feb019;font-weight:600;"><?= $namazRate ?>%</span>
                </div>
                <div class="att-prog-track">
                    <div class="att-prog-fill" style="width:<?= $namazRate ?>%;background:#feb019;"></div>
                </div>
            </div>
            <div class="att-stat-nums">
                <div class="att-num">
                    <div class="att-num-val" style="color:#00e396;"><?= (int)$namazStats['with_jamaat'] ?></div>
                    <div class="att-num-lbl">W/ Jamaat</div>
                </div>
                <div class="att-num">
                    <div class="att-num-val" style="color:#feb019;"><?= (int)$namazStats['without_jamaat'] ?></div>
                    <div class="att-num-lbl">No Jamaat</div>
                </div>
                <div class="att-num">
                    <div class="att-num-val" style="color:#ff4560;"><?= (int)$namazStats['missed'] ?></div>
                    <div class="att-num-lbl">Missed</div>
                </div>
            </div>
        </div>

        <!-- Recent sessions mini -->
        <!-- <div class="att-summary-item" style="flex:1.5;min-width:180px;">
            <div class="att-prog-label" style="margin-bottom:8px;">
                <span>Recent Sessions</span>
            </div>
            <?php if (empty($recentDars)): ?>
                <div style="color:rgba(255,255,255,.25);font-size:.75rem;">No sessions yet</div>
            <?php else: ?>
                <?php foreach (array_slice($recentDars, 0, 3) as $s): ?>
                <div style="display:flex;align-items:center;gap:7px;margin-bottom:5px;">
                    <div class="s-dot <?= $s['attendance']==='Present'?'s-present':'s-absent' ?>"></div>
                    <span style="font-size:.75rem;color:rgba(255,255,255,.65);flex:1;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                        <?= htmlspecialchars($s['areaName'] ?: 'Session') ?>
                    </span>
                    <span class="s-badge <?= $s['attendance']==='Present'?'sb-p':'sb-a' ?>" style="font-size:.63rem;">
                        <?= $s['attendance'] ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div> -->
    </div>
</div><!-- /profile-hero-wrap -->

<!-- ══ TAB NAV ══ -->
<div class="profile-tab-nav">
    <button class="ptab-btn" onclick="switchTab('feed',this)">
        <svg viewBox="0 0 24 24"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
        Feed
    </button>
    <button class="ptab-btn active" onclick="switchTab('about',this)">
        <svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
        About
    </button>
    <button class="ptab-btn" onclick="switchTab('team',this)">
        <svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/></svg>
        Team
    </button>
    <?php if ($isOwnProfile || in_array($loggedRole, ['DG','MD'])): ?>
    <button class="ptab-btn" onclick="switchTab('settings',this)">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 010-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 012.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 014 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 2.83l-.06.06A1.65 1.65 0 0019.4 9a1.65 1.65 0 001.51 1H21a2 2 0 010 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
        Settings
    </button>
    <?php endif; ?>
</div>

<!-- ══ TAB CONTENT ══ -->
<div style="background:#152036;min-height:400px;">

    <!-- ── FEED TAB — Coming Soon ── -->
    <div class="ptab-pane" id="ptab-feed">
        <div class="dark-panel">
            <div class="coming-soon-wrap">
                <div class="coming-soon-icon">💬</div>
                <div class="coming-soon-title">Social Feed — Coming Soon</div>
                <div class="coming-soon-sub">
                    Post updates, share milestones and connect with your team.<br>
                    This feature is currently under development.
                </div>
            </div>
        </div>
    </div>

    <!-- ── ABOUT TAB (default active) ── -->
    <div class="ptab-pane active" id="ptab-about">
        <div class="about-grid">

            <!-- Personal Information -->
            <div class="dark-panel">
                <div class="dp-title">Personal Information</div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-person-fill"></i></div>
                    <div><span class="about-lbl">Full Name</span><span class="about-val"><?= htmlspecialchars($fullName ?: '—') ?></span></div>
                </div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-award-fill"></i></div>
                    <div>
                        <span class="about-lbl">Card &amp; Category</span>
                        <span class="about-val">
                            <span class="badge" style="background:rgba(13,110,253,0.2);color:#60a5fa;border:1px solid rgba(13,110,253,0.4);font-size:0.78rem;"><?= htmlspecialchars($profileUser['card'] ?? 'Diamond') ?></span>
                            <span style="opacity:0.5;margin:0 4px;">|</span>
                            <span class="badge" style="background:rgba(168,85,247,0.2);color:#c084fc;border:1px solid rgba(168,85,247,0.4);font-size:0.78rem;">Category <?= htmlspecialchars($profileUser['category'] ?? 'B') ?></span>
                        </span>
                    </div>
                </div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-envelope-fill"></i></div>
                    <div><span class="about-lbl">Email</span><span class="about-val"><?= htmlspecialchars($profileUser['email'] ?: '—') ?></span></div>
                </div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-telephone-fill"></i></div>
                    <div><span class="about-lbl">Phone</span><span class="about-val"><?= htmlspecialchars($profileUser['phone'] ?: '—') ?></span></div>
                </div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-person-badge-fill"></i></div>
                    <div>
                        <span class="about-lbl">Age / Gender</span>
                        <span class="about-val">
                            <?= $profileUser['age'] ? $profileUser['age'].' yrs' : '—' ?>
                            <?= $profileUser['gender'] ? ' · '.$profileUser['gender'] : '' ?>
                        </span>
                    </div>
                </div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-credit-card-2-front-fill"></i></div>
                    <div><span class="about-lbl">CNIC</span><span class="about-val"><?= htmlspecialchars($profileUser['cnic'] ?: '—') ?></span></div>
                </div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-calendar3"></i></div>
                    <div>
                        <span class="about-lbl">Joined</span>
                        <span class="about-val">
                            <?= $profileUser['date_of_joining'] ? date('d M Y', strtotime($profileUser['date_of_joining'])) : '—' ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Organisation -->
            <div class="dark-panel">
                <div class="dp-title">Organisation</div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <div><span class="about-lbl">Dars Area</span><span class="about-val"><?= htmlspecialchars($userAreaName) ?></span></div>
                </div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-at"></i></div>
                    <div><span class="about-lbl">Username</span><span class="about-val">@<?= htmlspecialchars($profileUser['username'] ?: '—') ?></span></div>
                </div>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-shield-fill-check"></i></div>
                    <div><span class="about-lbl">Role</span><span class="about-val"><?= htmlspecialchars($roleLabel) ?></span></div>
                </div>
                <?php if (!empty($userTeams)): ?>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-diagram-3-fill"></i></div>
                    <div>
                        <span class="about-lbl">Teams Member Of</span>
                        <span class="about-val"><?= implode(', ', array_map(fn($t) => htmlspecialchars($t['name']), $userTeams)) ?></span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if (!empty($supervisedAreas)): ?>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-map-fill"></i></div>
                    <div>
                        <span class="about-lbl">Supervised Area<?= count($supervisedAreas)>1?'s':'' ?></span>
                        <span class="about-val">
                            <?= implode(', ', array_map(fn($a) => htmlspecialchars($a['areaName']), $supervisedAreas)) ?>
                        </span>
                    </div>
                </div>
                <?php endif; ?>
                <?php if ($totalTeamsLed > 0): ?>
                <div class="about-row">
                    <div class="about-row-icon"><i class="bi bi-people-fill"></i></div>
                    <div>
                        <span class="about-lbl">Teams Led / Members</span>
                        <span class="about-val"><?= $totalTeamsLed ?> team<?= $totalTeamsLed>1?'s':'' ?> · <?= $totalTeamMembers ?> member<?= $totalTeamMembers!=1?'s':'' ?></span>
                    </div>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <!-- ── TEAM TAB ── -->
    <div class="ptab-pane" id="ptab-team">

        <?php if (empty($supervisedAreas) && empty($ledTeams) && empty($userTeams)): ?>
        <div class="dark-panel">
            <div class="coming-soon-wrap">
                <div class="coming-soon-icon">👥</div>
                <div class="coming-soon-title">No team data available</div>
                <div class="coming-soon-sub">This user has not been assigned to any team or area yet.</div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 1. Supervised areas + their non-member committee users -->
        <?php foreach ($supervisedAreas as $sa): ?>
        <div class="dark-panel">
            <div class="dp-title">
                <i class="bi bi-geo-alt-fill" style="color:#008ffb;font-size:.8rem;"></i>
                <?= htmlspecialchars($sa['areaName']) ?>
                <?php
                    $areaCount = count($areaCommitteeUsers[$sa['areaName']] ?? []);
                    if ($areaCount): ?>
                <span class="dp-count"><?= $areaCount ?></span>
                <?php endif; ?>
                <span style="margin-left:auto;font-size:.63rem;color:rgba(255,255,255,.25);font-weight:400;text-transform:none;letter-spacing:0;">
                    <?= htmlspecialchars($sa['darsType']) ?>
                    <?= $sa['dayTime'] ? ' · '.$sa['dayTime'] : '' ?>
                </span>
            </div>
            <?php if (empty($areaCommitteeUsers[$sa['areaName']])): ?>
                <div style="padding:16px;color:rgba(255,255,255,.3);font-size:.8rem;text-align:center;">No committee members in this area yet.</div>
            <?php else: ?>
                <?php foreach ($areaCommitteeUsers[$sa['areaName']] as $m):
                    $mn = trim(($m['firstName']??'').' '.($m['lastName']??''));
                    $mi = strtoupper(substr($m['firstName']??'U',0,1));
                    $mr = $roleLabels[$m['role']] ?? ucfirst($m['role']);
                    $mbg = match($m['role']) {
                        'committee'      => ['rgba(13,148,136,.18)','#5eead4'],
                        'representative' => ['rgba(8,145,178,.18)','#67e8f9'],
                        'itHead'         => ['rgba(59,130,246,.18)','#93c5fd'],
                        'researchHead'   => ['rgba(245,158,11,.18)','#fcd34d'],
                        'trainee'        => ['rgba(100,116,139,.18)','#94a3b8'],
                        default          => ['rgba(100,116,139,.18)','#94a3b8'],
                    };
                ?>
                <div class="team-row">
                    <div class="team-avatar-sm"><?= $mi ?></div>
                    <div>
                        <div class="team-mname"><?= htmlspecialchars($mn) ?></div>
                        <div class="team-mrole"><?= htmlspecialchars($mr) ?></div>
                    </div>
                    <span class="team-chip-sm" style="background:<?= $mbg[0] ?>;color:<?= $mbg[1] ?>;">
                        <?= htmlspecialchars($mr) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- 2. Teams this user leads -->
        <?php foreach ($ledTeams as $lt): ?>
        <div class="dark-panel">
            <div class="dp-title">
                <i class="bi bi-diagram-3-fill" style="color:#feb019;font-size:.8rem;"></i>
                <?= htmlspecialchars($lt['name']) ?> <span style="color:rgba(255,255,255,.25);font-weight:400;">— Team Lead</span>
                <?php $mc = count($ledTeamMembers[$lt['id']] ?? []); if ($mc): ?>
                <span class="dp-count"><?= $mc ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($lt['description'])): ?>
            <div style="padding:10px 16px 0;font-size:.78rem;color:rgba(255,255,255,.35);">
                <?= htmlspecialchars($lt['description']) ?>
            </div>
            <?php endif; ?>
            <?php if (empty($ledTeamMembers[$lt['id']])): ?>
                <div style="padding:16px;color:rgba(255,255,255,.3);font-size:.8rem;text-align:center;">No members assigned yet.</div>
            <?php else: ?>
                <?php foreach ($ledTeamMembers[$lt['id']] as $m):
                    $mn  = trim(($m['firstName']??'').' '.($m['lastName']??''));
                    $mi  = strtoupper(substr($m['firstName']??'U',0,1));
                    $tr  = ucfirst($m['team_role'] ?: 'member');
                    $trStyle = match($m['team_role']) {
                        'lead'    => ['rgba(254,176,25,.18)','#feb019'],
                        'trainee' => ['rgba(100,116,139,.18)','#94a3b8'],
                        default   => ['rgba(0,143,251,.18)','#74b4ff'],
                    };
                ?>
                <div class="team-row">
                    <div class="team-avatar-sm"><?= $mi ?></div>
                    <div>
                        <div class="team-mname"><?= htmlspecialchars($mn) ?></div>
                        <div class="team-mrole"><?= htmlspecialchars($roleLabels[$m['role']] ?? ucfirst($m['role'])) ?></div>
                    </div>
                    <span class="team-chip-sm" style="background:<?= $trStyle[0] ?>;color:<?= $trStyle[1] ?>;">
                        <?= $tr ?>
                    </span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <!-- 3. Teams this user is a member of (but not leading) -->
        <?php
        $memberOnlyTeams = array_filter($userTeams, fn($t) => $t['leader_id'] != $profileId);
        if (!empty($memberOnlyTeams)):
        ?>
        <div class="dark-panel">
            <div class="dp-title">
                <i class="bi bi-people-fill" style="color:#74b4ff;font-size:.8rem;"></i>
                Team Memberships
                <span class="dp-count"><?= count($memberOnlyTeams) ?></span>
            </div>
            <?php foreach ($memberOnlyTeams as $t):
                $isLead = ($t['leader_id'] == $profileId);
                $tbg = match($t['team_role']) {
                    'lead'    => ['rgba(254,176,25,.18)','#feb019'],
                    'trainee' => ['rgba(100,116,139,.18)','#94a3b8'],
                    default   => ['rgba(0,143,251,.18)','#74b4ff'],
                };
            ?>
            <div class="team-row">
                <div class="team-avatar-sm" style="border-radius:9px;"><?= strtoupper(substr($t['name'],0,1)) ?></div>
                <div>
                    <div class="team-mname"><?= htmlspecialchars($t['name']) ?></div>
                    <div class="team-mrole"><?= htmlspecialchars($t['description'] ?: '') ?></div>
                </div>
                <span class="team-chip-sm" style="background:<?= $tbg[0] ?>;color:<?= $tbg[1] ?>;">
                    <?= ucfirst($t['team_role'] ?: 'member') ?>
                </span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

    </div><!-- /ptab-team -->

    <!-- ── SETTINGS TAB ── -->
    <?php if ($isOwnProfile || in_array($loggedRole, ['DG','MD'])): ?>
    <div class="ptab-pane" id="ptab-settings">

        <div class="dark-panel">
            <div class="dp-title">Edit Profile</div>
            <div style="padding:16px;">
                <form id="editProfileForm">
                    <input type="hidden" name="id" value="<?= $profileId ?>">
                    <div class="sf-row">
                        <div class="sf-group"><label class="sf-label">First Name</label>
                            <input type="text" class="sf-input" name="firstName" value="<?= htmlspecialchars($profileUser['firstName']) ?>"></div>
                        <div class="sf-group"><label class="sf-label">Last Name</label>
                            <input type="text" class="sf-input" name="lastName" value="<?= htmlspecialchars($profileUser['lastName'] ?? '') ?>"></div>
                    </div>
                    <div class="sf-row">
                        <div class="sf-group"><label class="sf-label">Email</label>
                            <input type="email" class="sf-input" name="email" value="<?= htmlspecialchars($profileUser['email']) ?>"></div>
                        <div class="sf-group"><label class="sf-label">Phone</label>
                            <input type="text" class="sf-input" name="phone" value="<?= htmlspecialchars($profileUser['phone'] ?? '') ?>"></div>
                    </div>
                    <div class="sf-row">
                        <div class="sf-group"><label class="sf-label">Age</label>
                            <input type="number" class="sf-input" name="age" value="<?= (int)($profileUser['age'] ?? 0) ?>"></div>
                        <div class="sf-group"><label class="sf-label">Gender</label>
                            <select class="sf-input" name="gender" style="cursor:pointer;">
                                <option value="">Select…</option>
                                <option value="Male"   <?= $profileUser['gender']==='Male'?'selected':'' ?>>Male</option>
                                <option value="Female" <?= $profileUser['gender']==='Female'?'selected':'' ?>>Female</option>
                            </select>
                        </div>
                    </div>
                    <div class="sf-group"><label class="sf-label">CNIC</label>
                        <input type="text" class="sf-input" name="cnic" value="<?= htmlspecialchars($profileUser['cnic'] ?? '') ?>"></div>
                    <div class="sf-row">
                        <div class="sf-group"><label class="sf-label">Card</label>
                            <select class="sf-input" name="card" style="cursor:pointer;">
                                <option value="Diamond" <?= ($profileUser['card'] ?? 'Diamond')==='Diamond'?'selected':'' ?>>Diamond</option>
                                <option value="Gold"    <?= ($profileUser['card'] ?? '')==='Gold'?'selected':'' ?>>Gold</option>
                                <option value="Silver"  <?= ($profileUser['card'] ?? '')==='Silver'?'selected':'' ?>>Silver</option>
                            </select>
                        </div>
                        <div class="sf-group"><label class="sf-label">Category</label>
                            <select class="sf-input" name="category" style="cursor:pointer;">
                                <option value="A" <?= ($profileUser['category'] ?? 'B')==='A'?'selected':'' ?>>Category A</option>
                                <option value="B" <?= ($profileUser['category'] ?? 'B')==='B'?'selected':'' ?>>Category B</option>
                                <option value="C" <?= ($profileUser['category'] ?? 'B')==='C'?'selected':'' ?>>Category C</option>
                                <option value="D" <?= ($profileUser['category'] ?? 'B')==='D'?'selected':'' ?>>Category D</option>
                            </select>
                        </div>
                    </div>
                    <button type="button" class="btn-sf-save" onclick="saveProfile()">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </form>
            </div>
        </div>

        <div class="dark-panel">
            <div class="dp-title">Change Password</div>
            <div style="padding:16px;">
                <form id="changePwForm">
                    <input type="hidden" name="id" value="<?= $profileId ?>">
                    <div class="sf-group"><label class="sf-label">New Password</label>
                        <input type="password" class="sf-input" name="newPassword" placeholder="••••••••"></div>
                    <div class="sf-group"><label class="sf-label">Confirm Password</label>
                        <input type="password" class="sf-input" name="confirmPassword" placeholder="••••••••"></div>
                    <button type="button" class="btn-sf-save" onclick="changePassword()">
                        <i class="bi bi-lock-fill"></i> Update Password
                    </button>
                </form>
            </div>
        </div>

        <?php if ($isOwnProfile): ?>
        <div class="dark-panel" style="border-color:rgba(255,69,96,.2);">
            <div class="dp-title" style="color:rgba(255,69,96,.5);">Danger Zone</div>
            <div style="padding:16px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px;">
                <div>
                    <div style="color:rgba(255,255,255,.7);font-size:.85rem;font-weight:500;">Sign Out</div>
                    <div style="color:rgba(255,255,255,.35);font-size:.75rem;margin-top:2px;">End your current session securely</div>
                </div>
                <a href="logout.php" class="btn-sf-danger"><i class="bi bi-box-arrow-right"></i> Sign Out</a>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php endif; ?>

</div><!-- /tab content -->

<?php
include "footer.php"; ?>

<div class="profile-toast" id="profileToast">
    <i id="toastIcon" class="bi bi-check-circle-fill" style="color:#00e396;"></i>
    <span id="toastMsg">Saved</span>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
<script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
<script src="js/main.js"></script>

<script>
const VIEW_ID = <?= json_encode($profileId) ?>;

function switchTab(name, btn) {
    document.querySelectorAll('.ptab-pane').forEach(p => p.classList.remove('active'));
    document.querySelectorAll('.ptab-btn').forEach(b => b.classList.remove('active'));
    const pane = document.getElementById('ptab-' + name);
    if (pane) pane.classList.add('active');
    if (btn)  btn.classList.add('active');
}

// Auto-activate tab from URL hash on load
(function () {
    const hash = window.location.hash.replace('#', '');
    if (hash) {
        const btn = document.querySelector(`.ptab-btn[onclick*="'${hash}'"]`);
        switchTab(hash, btn);
    }
})();

// Streak
(async () => {
    try {
        const d60 = new Date(); d60.setDate(d60.getDate() - 60);
        const from = d60.toISOString().slice(0,10);
        const to   = new Date().toISOString().slice(0,10);
        const r    = await fetch(`fetchTimeData.php?user_id=${VIEW_ID}&from=${from}&to=${to}`);
        if (!r.ok) return;
        const j = await r.json();
        if (!j?.success) return;
        const map = {};
        j.days.forEach(d => map[d.date] = d);
        let streak = 0;
        const check = new Date();
        for (let i = 0; i < 60; i++) {
            const ds = check.toISOString().slice(0,10);
            if (map[ds]?.goal_achieved) { streak++; check.setDate(check.getDate()-1); }
            else break;
        }
        document.getElementById('streakVal').textContent = streak >= 3 ? '🔥 '+streak : streak;
    } catch {}
})();

function saveProfile() {
    const form = document.getElementById('editProfileForm');
    const fn = (form.querySelector('[name=firstName]').value || '').trim();
    const ln = (form.querySelector('[name=lastName]').value || '').trim();
    const ph = (form.querySelector('[name=phone]').value || '').trim();

    if (/[0-9]/.test(fn)) {
        showToast('First Name: Only letters allowed. No digits permitted.', false);
        form.querySelector('[name=firstName]').focus();
        return;
    }
    if (/[0-9]/.test(ln)) {
        showToast('Last Name: Only letters allowed. No digits permitted.', false);
        form.querySelector('[name=lastName]').focus();
        return;
    }
    if (/[a-zA-Z]/.test(ph)) {
        showToast('Phone: Only numbers allowed. No letters permitted.', false);
        form.querySelector('[name=phone]').focus();
        return;
    }

    fetch('updateUserProfile.php', { method:'POST', body: new FormData(form) })
        .then(r => r.text()).then(msg => showToast(msg, !msg.toLowerCase().includes('error') && !msg.toLowerCase().includes('failed')))
        .catch(() => showToast('Error saving profile.', false));
}

function changePassword() {
    const form = document.getElementById('changePwForm');
    const np = form.querySelector('[name=newPassword]').value;
    const cp = form.querySelector('[name=confirmPassword]').value;
    if (!np) { showToast('Please enter a new password.', false); return; }
    if (np !== cp) { showToast('Passwords do not match.', false); return; }
    fetch('changeUserPassword.php', { method:'POST', body: new FormData(form) })
        .then(r => r.text()).then(msg => { showToast(msg, true); form.reset(); })
        .catch(() => showToast('Error changing password.', false));
}

let _tt;
function showToast(msg, success) {
    const t  = document.getElementById('profileToast');
    const ic = document.getElementById('toastIcon');
    document.getElementById('toastMsg').textContent = msg;
    ic.className   = success ? 'bi bi-check-circle-fill' : 'bi bi-exclamation-circle-fill';
    ic.style.color = success ? '#00e396' : '#ff4560';
    t.classList.add('show');
    clearTimeout(_tt);
    _tt = setTimeout(() => t.classList.remove('show'), 3000);
}
</script>
</body>
</html>