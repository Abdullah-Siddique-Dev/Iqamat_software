<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

$id = intval($_GET['id'] ?? 0);
$areaId = $id;         
$currentYear = date('Y');   
$currentMonth = date('Y-m'); 
if (!$id) {
    header("Location: darsArea.php");
    exit();
}

// Fetch dars area from DB
$stmt = $conn->prepare("
    SELECT 
        d.*,
        u.firstName,
        u.lastName,
        u.phone AS rep_phone
    FROM dars_areas d
    LEFT JOIN users u ON d.representative_id = u.id
    WHERE d.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$area = $stmt->get_result()->fetch_assoc();
$repName = trim(($area['firstName'] ?? '') . ' ' . ($area['lastName'] ?? ''));
$stmt->close();

if (!$area) {
    header("Location: darsArea.php");
    exit();
}


// ── STAT 1: Avg Dars Attendance (Present per session, this year) ──────────
$stmt = mysqli_prepare($conn, "
    SELECT COALESCE(AVG(daily_present), 0)
    FROM (
        SELECT DATE(dateTime) AS session_date, 
               COUNT(*) AS daily_present
        FROM dars_attendance
        WHERE area_id = ?
          AND attendance = 'Present'
          AND YEAR(dateTime) = ?
        GROUP BY DATE(dateTime)
    ) AS per_session
");
mysqli_stmt_bind_param($stmt, 'is', $areaId, $currentYear);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $statAvgAttendance);
mysqli_stmt_fetch($stmt);
$statAvgAttendance = (int) round($statAvgAttendance ?? 0);
mysqli_stmt_close($stmt);

// ── STAT 2: New members joined this month in this area ───────────────────
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM users
    WHERE area = ?
      AND DATE_FORMAT(date_of_joining, '%Y-%m') = ?
");
mysqli_stmt_bind_param($stmt, 'is', $areaId, $currentMonth);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $statNewJoinees);
mysqli_stmt_fetch($stmt);
$statNewJoinees = (int) $statNewJoinees;
mysqli_stmt_close($stmt);

// ── STAT 3: Total dars sessions this year (distinct session dates) ────────
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(DISTINCT DATE(dateTime))
    FROM dars_attendance
    WHERE area_id = ?
      AND YEAR(dateTime) = ?
");
mysqli_stmt_bind_param($stmt, 'is', $areaId, $currentYear);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $statTotalSessions);
mysqli_stmt_fetch($stmt);
$statTotalSessions = (int) $statTotalSessions;
mysqli_stmt_close($stmt);

// ── STAT 4: Committee members (non-member/DG/MD roles) ───────────────────
$stmt = mysqli_prepare($conn, "
    SELECT COUNT(*)
    FROM users
    WHERE area = ?
      AND role NOT IN ('member', 'DG', 'MD')
");
mysqli_stmt_bind_param($stmt, 'i', $areaId);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $statCommitteeMembers);
mysqli_stmt_fetch($stmt);
$statCommitteeMembers = (int) $statCommitteeMembers;
mysqli_stmt_close($stmt);


// ── Committee Members for this area ──────────────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT firstName, lastName, role, phone
    FROM users
    WHERE area = ?
      AND role NOT IN ('member', 'DG', 'MD', 'representative')
    ORDER BY role, firstName
");
mysqli_stmt_bind_param($stmt, 'i', $areaId);
mysqli_stmt_execute($stmt);
$committeeResult = mysqli_stmt_get_result($stmt);
$committeeMembers = mysqli_fetch_all($committeeResult, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);


$darsType = $area['darsType']; // 'Weekly', 'Monthly', 'Bi-weekly'

// ── Dynamic grouping based on darsType ───────────────────────────────────
if ($darsType === 'Weekly') {
    // Group by week number within the year
    $groupBy  = "WEEK(dateTime, 1)";
    $select   = "WEEK(dateTime, 1) AS period";
    $maxPeriods = 52;
    $labels   = array_map(fn($w) => "W$w", range(1, 52));

} elseif ($darsType === 'Bi-weekly') {
    // Group by fortnight: CEIL(WEEK / 2)
    $groupBy  = "CEIL(WEEK(dateTime, 1) / 2)";
    $select   = "CEIL(WEEK(dateTime, 1) / 2) AS period";
    $maxPeriods = 26;
    $labels   = array_map(fn($i) => "F$i", range(1, 26));

} else {
    // Monthly (default)
    $groupBy  = "MONTH(dateTime)";
    $select   = "MONTH(dateTime) AS period";
    $maxPeriods = 12;
    $labels   = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
}

// ── Attendance Trend ──────────────────────────────────────────────────────
$stmt = mysqli_prepare($conn, "
    SELECT $select, COUNT(*) AS present_count
    FROM dars_attendance
    WHERE area_id = ?
      AND attendance = 'Present'
      AND YEAR(dateTime) = ?
    GROUP BY $groupBy
    ORDER BY $groupBy
");
mysqli_stmt_bind_param($stmt, 'is', $areaId, $currentYear);
mysqli_stmt_execute($stmt);
$attendanceResult = mysqli_stmt_get_result($stmt);

$periodAttendance = array_fill(1, $maxPeriods, 0);
while ($row = mysqli_fetch_assoc($attendanceResult)) {
    $periodAttendance[(int)$row['period']] = (int)$row['present_count'];
}
mysqli_stmt_close($stmt);



// For joinees, always use monthly grouping regardless of darsType
$stmt = mysqli_prepare($conn, "
    SELECT MONTH(date_of_joining) AS period, COUNT(*) AS new_count
    FROM users
    WHERE area = ?
      AND YEAR(date_of_joining) = ?
    GROUP BY MONTH(date_of_joining)
    ORDER BY MONTH(date_of_joining)
");
mysqli_stmt_bind_param($stmt, 'is', $areaId, $currentYear);
mysqli_stmt_execute($stmt);
$joinResult = mysqli_stmt_get_result($stmt);

// Stretch joinees to match attendance periods
$monthlyJoinees = array_fill(1, 12, 0);
while ($row = mysqli_fetch_assoc($joinResult)) {
    $monthlyJoinees[(int)$row['period']] = (int)$row['new_count'];
}
mysqli_stmt_close($stmt);

// Stretch monthly joinees to match period count (repeat monthly value per period)
$periodJoinees = array_fill(1, $maxPeriods, 0);
if ($darsType === 'Weekly') {
    // Map each week → its month's joinee count
    for ($w = 1; $w <= 52; $w++) {
        $month = (int) ceil($w / 4.33);
        $month = min($month, 12);
        $periodJoinees[$w] = $monthlyJoinees[$month] ?? 0;
    }
} elseif ($darsType === 'Bi-monthly') {
    for ($f = 1; $f <= 26; $f++) {
        $month = (int) ceil($f / 2.17);
        $month = min($month, 12);
        $periodJoinees[$f] = $monthlyJoinees[$month] ?? 0;
    }
} else {
    $periodJoinees = $monthlyJoinees;
}

// ── Final JSON for JS ─────────────────────────────────────────────────────
$attendanceJson = json_encode(array_values($periodAttendance));
$joineesJson    = json_encode(array_values($periodJoinees));
$labelsJson     = json_encode($labels);
$totalAttendees = array_sum($periodAttendance);
$peakSession    = max($periodAttendance);
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
    /* ── Summary stat cards ── */
    .nk-stat-card {
        background: #1b2a47;
        border-radius: 12px;
        padding: 20px 18px 16px;
        position: relative;
        overflow: hidden;
    }
    .nk-stat-card .stat-icon {
        width: 42px; height: 42px; border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        font-size: 18px; margin-bottom: 12px;
    }
    .nk-stat-card .stat-value {
        font-size: 1.8rem; font-weight: 700; color: #fff; line-height: 1;
    }
    .nk-stat-card .stat-label {
        font-size: .78rem; color: rgba(255,255,255,.5);
        margin-top: 4px; text-transform: uppercase; letter-spacing: .5px;
    }
    .nk-stat-card .stat-badge {
        margin-top: 10px; font-size: .72rem; font-weight: 600;
        padding: 3px 9px; border-radius: 20px; display: inline-block;
    }
    .nk-stat-card::after {
        content: '';
        position: absolute; right: -20px; bottom: -20px;
        width: 90px; height: 90px; border-radius: 50%;
        background: rgba(255,255,255,.04);
    }

    /* ── Profile panel ── */
    .profile-panel {
        background: #1b2a47;
        border-radius: 14px;
        overflow: hidden;
    }
    .profile-panel-header {
        background: linear-gradient(135deg, #0f2040 0%, #1b2a47 100%);
        padding: 24px 22px 20px;
        position: relative; overflow: hidden;
    }
    .profile-panel-header::before {
        content: '';
        position: absolute; top: -30px; right: -30px;
        width: 140px; height: 140px; border-radius: 50%;
        background: rgba(255,255,255,.04);
    }
    .profile-panel-header::after {
        content: '';
        position: absolute; bottom: -40px; right: 40px;
        width: 100px; height: 100px; border-radius: 50%;
        background: rgba(255,255,255,.03);
    }
    .area-avatar {
        width: 56px; height: 56px; border-radius: 14px;
        background: rgba(255,255,255,.1);
        border: 1.5px solid rgba(255,255,255,.2);
        display: flex; align-items: center; justify-content: center;
        font-size: 24px; font-weight: 700; color: #fff;
        margin-bottom: 12px;
        position: relative;
    }
    .status-dot {
        position: absolute; bottom: -2px; right: -2px;
        width: 14px; height: 14px; border-radius: 50%;
        border: 2px solid #1b2a47;
    }
    .status-dot.active { background: #00e396; }
    .status-dot.paused { background: #feb019; }

    .profile-panel-header h4 {
        color: #fff; font-size: 1.2rem; font-weight: 600; margin: 0 0 4px;
    }
    .profile-panel-header .area-sub {
        font-size: .75rem; color: rgba(255,255,255,.45);
        text-transform: uppercase; letter-spacing: 1px;
    }
    .type-chip-lg {
        display: inline-block; margin-top: 10px;
        padding: 4px 14px; border-radius: 20px;
        font-size: .72rem; font-weight: 700; letter-spacing: .5px;
    }
    .chip-weekly   { background: #d4edda; color: #155724; }
    .chip-monthly  { background: #fff3cd; color: #7d5a00; }
    .chip-biweekly { background: #cce5ff; color: #004085; }

    .profile-info-list { padding: 16px 20px; }
    .pi-row {
        display: flex; align-items: flex-start; gap: 12px;
        padding: 9px 0; border-bottom: 1px solid rgba(255,255,255,.07);
        font-size: .84rem;
    }
    .pi-row:last-child { border-bottom: none; }
    .pi-icon {
        width: 30px; height: 30px; border-radius: 8px;
        background: rgba(255,255,255,.07);
        display: flex; align-items: center; justify-content: center;
        color: rgba(255,255,255,.5); font-size: .85rem; flex-shrink: 0;
    }
    .pi-label { font-size: .7rem; color: rgba(255,255,255,.38); display: block; margin-bottom: 2px; }
    .pi-val   { color: rgba(255,255,255,.88); font-weight: 500; }
    .pi-val a { color: #74b4ff; text-decoration: none; }
    .pi-val a:hover { text-decoration: underline; }

    /* ── Rep card ── */
    .rep-card {
        background: #1b2a47;
        border-radius: 14px;
        padding: 18px 20px;
    }
    .rep-avatar {
        width: 48px; height: 48px; border-radius: 50%;
        background: rgba(0,227,150,.15);
        border: 2px solid rgba(0,227,150,.3);
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; font-weight: 700; color: #00e396;
        flex-shrink: 0;
    }
    .rep-name  { color: #fff; font-weight: 600; font-size: .95rem; }
    .rep-phone { color: rgba(255,255,255,.45); font-size: .8rem; margin-top: 2px; }
    .rep-role  { font-size: .7rem; color: #00e396; font-weight: 600; letter-spacing: .5px; margin-top: 4px; text-transform: uppercase; }

    /* ── Committee table ── */
    .committee-table { width: 100%; border-collapse: collapse; font-size: .83rem; }
    .committee-table th {
        color: rgba(255,255,255,.4); font-weight: 600; font-size: .7rem;
        text-transform: uppercase; letter-spacing: .7px;
        padding: 8px 12px; border-bottom: 1px solid rgba(255,255,255,.08);
        text-align: left;
    }
    .committee-table td {
        padding: 10px 12px; color: rgba(255,255,255,.8);
        border-bottom: 1px solid rgba(255,255,255,.05);
    }
    .committee-table tr:last-child td { border-bottom: none; }
    .role-badge {
        padding: 2px 9px; border-radius: 20px;
        font-size: .68rem; font-weight: 600;
    }

    /* ── White-box style (reuse from theme) ── */
    .white-box { background: #1b2a47; border-radius: 14px; padding: 20px 20px; margin-bottom: 0; }
    .box-title { color: #fff; font-size: .95rem; font-weight: 600; margin: 0 0 16px; }

    /* ── Activity timeline ── */
    .activity-item { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 16px; }
    .activity-icon {
        width: 34px; height: 34px; border-radius: 50%;
        background: #253a5c;
        display: flex; align-items: center; justify-content: center;
        font-size: 13px; flex-shrink: 0;
    }
    .activity-text { color: rgba(255,255,255,.85); font-size: .83rem; }
    .activity-time { color: rgba(255,255,255,.35); font-size: .73rem; margin-top: 2px; }

    /* ── Quick Actions ── */
    .nk-pm-quick-action {
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        gap: 8px; padding: 16px 8px; border-radius: 12px;
        text-decoration: none; font-size: .78rem; font-weight: 600;
        text-align: center; transition: opacity .18s;
    }
    .nk-pm-quick-action:hover { opacity: .8; text-decoration: none; }

    /* ── Back button ── */
    .btn-back {
        background: rgba(255,255,255,.08);
        color: rgba(255,255,255,.7);
        border: 1px solid rgba(255,255,255,.12);
        padding: 6px 16px; border-radius: 8px;
        font-size: .82rem; text-decoration: none;
        display: inline-flex; align-items: center; gap: 6px;
        transition: background .18s;
    }
    .btn-back:hover { background: rgba(255,255,255,.14); color: #fff; }

    /* ── Responsive ── */
    @media(max-width:768px) {
        .nk-stat-card .stat-value { font-size: 1.4rem; }
    }
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <!-- <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="breadcome-list single-page-breadcome">
                        <div class="row align-items-center">
                            <div class="col-lg-6 col-6">
                                <div class="breadcome-heading d-flex align-items-center gap-3">
                                    <a href="darsAreasInfo.php" class="btn-back mb-3">
                                        <i class="bi bi-arrow-left"></i> Back
                                    </a>
                                    <span class="text-muted mb-3" style="font-size:.85rem;">
                                        <?= htmlspecialchars($area['areaName']) ?> — Details
                                    </span>
                                </div>
                            </div>
                            <div class="col-lg-6 col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                                    <li><a href="darsAreasInfo.php">Dars Areas</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod"><?= htmlspecialchars($area['areaName']) ?></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div> -->
    </div>

    <!-- ══ ROW 1: Quick Stat Cards ══ -->
    <div class="container-fluid mt-4">
        <div class="row g-3">
            <div class="col-lg-3 col-md-6">
                <div class="nk-stat-card">
                    <div class="stat-icon" style="background:rgba(0,227,150,.15);">
                        <i class="bi bi-people-fill" style="color:#00e396;"></i>
                    </div>
                 <div class="stat-value" id="statAvgAttendance"><?= $statAvgAttendance ?></div>
                    <div class="stat-label">Avg. Attendance</div>
                    <span class="stat-badge" style="background:rgba(0,227,150,.15);color:#00e396;">
                         <i class="bi bi-arrow-up"></i> ++ 
                    </span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="nk-stat-card">
                    <div class="stat-icon" style="background:rgba(0,143,251,.15);">
                        <i class="bi bi-person-plus-fill" style="color:#008ffb;"></i>
                    </div>
                    <div class="stat-value" id="statNewJoinees"><?= $statNewJoinees ?></div>
                    <div class="stat-label">New Joinees (Month)</div>
                    <span class="stat-badge" style="background:rgba(0,143,251,.15);color:#008ffb;">
                        <i class="bi bi-arrow-up"></i> Growing
                    </span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="nk-stat-card">
                    <div class="stat-icon" style="background:rgba(254,176,25,.15);">
                        <i class="bi bi-calendar-check-fill" style="color:#feb019;"></i>
                    </div>
                    <div class="stat-value" id="statTotalSessions"><?= $statTotalSessions ?></div>
                    <div class="stat-label">Total Sessions</div>
                    <span class="stat-badge" style="background:rgba(254,176,25,.15);color:#feb019;">
                        Since <?= $area['startDate'] ? date('M Y', strtotime($area['startDate'])) : 'N/A' ?>
                    </span>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="nk-stat-card">
                    <div class="stat-icon" style="background:rgba(119,93,208,.15);">
                        <i class="bi bi-megaphone-fill" style="color:#775dd0;"></i>
                    </div>
                    <div class="stat-value" id="statDawatActivities"><?= $statCommitteeMembers ?></div>
                    <div class="stat-label">Total Team</div>
                    <span class="stat-badge" style="background:rgba(119,93,208,.15);color:#775dd0;">
                        Active
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ ROW 2: Profile Panel + Rep Info + Activity ══ -->
    <div class="container-fluid mt-4">
        <div class="row g-3">

            <!-- Profile Panel -->
            <div class="col-lg-3 col-md-6">
                <div class="profile-panel">
                    <div class="profile-panel-header">
                        <div class="area-avatar">
                            <?= strtoupper(substr($area['areaName'], 0, 1)) ?>
                            <span class="status-dot active"></span>
                        </div>
                        <h4><?= htmlspecialchars($area['areaName']) ?></h4>
                        <div class="area-sub">Dars Area</div>
                        <?php
                            $chipClass = $area['darsType'] === 'Weekly' ? 'chip-weekly'
                                       : ($area['darsType'] === 'Monthly' ? 'chip-monthly' : 'chip-biweekly');
                        ?>
                        <span class="type-chip-lg <?= $chipClass ?>"><?= htmlspecialchars($area['darsType']) ?></span>
                    </div>
                    <div class="profile-info-list">
                        <div class="pi-row">
                            <div class="pi-icon"><i class="bi bi-clock"></i></div>
                            <div>
                                <span class="pi-label">Day &amp; Time</span>
                                <span class="pi-val"><?= htmlspecialchars($area['dayTime'] ?? '—') ?></span>
                            </div>
                        </div>
                        <div class="pi-row">
                            <div class="pi-icon"><i class="bi bi-calendar3"></i></div>
                            <div>
                                <span class="pi-label">Start Date</span>
                                <span class="pi-val">
                                    <?= $area['startDate'] ? date('d M Y', strtotime($area['startDate'])) : '—' ?>
                                </span>
                            </div>
                        </div>
                        <div class="pi-row">
                            <div class="pi-icon"><i class="bi bi-geo-alt"></i></div>
                            <div>
                                <span class="pi-label">Location</span>
                                <span class="pi-val"><?= htmlspecialchars($area['location'] ?? '—') ?></span>
                            </div>
                        </div>
                        <?php if (!empty($area['mapLink'])): ?>
                        <div class="pi-row">
                            <div class="pi-icon"><i class="bi bi-map"></i></div>
                            <div>
                                <span class="pi-label">Map</span>
                                <span class="pi-val">
                                    <a href="<?= htmlspecialchars($area['mapLink']) ?>" target="_blank">
                                        Open Map <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="pi-row">
                            <div class="pi-icon"><i class="bi bi-activity"></i></div>
                            <div>
                                <span class="pi-label">Status</span>
                                <span class="pi-val">
                                    <span style="color:#00e396;">● Active</span>
                                    <!-- Change to ● Temporarily Paused with color:#feb019 when needed -->
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Representative + Committee -->
            <div class="col-lg-5 col-md-6">
                <div class="white-box mb-3">
                    <div class="box-title">Representative</div>
                    <div class="rep-card d-flex align-items-center gap-3" style="background:#253a5c;border-radius:10px;padding:14px 16px;">
                                 <div class="rep-avatar">
                <?= strtoupper(substr($repName ?: 'R', 0, 1)) ?>
            </div>

            <div>
                <div class="rep-name">
                    <?= htmlspecialchars($repName ?: '—') ?>
                </div>

                <div class="rep-phone">
                    <i class="bi bi-telephone me-1"></i>
                    <?= htmlspecialchars($area['rep_phone'] ?? '—') ?>
                </div>
                            <div class="rep-role">Area Representative</div>
                        </div>
                    </div>

                    <div class="box-title mt-4">Committee</div>
                    <!-- Committee table — replace rows with DB data when available -->
                    <table class="committee-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Role</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
<tbody id="committeeTableBody">
    <?php if (empty($committeeMembers)): ?>
        <tr>
            <td colspan="3" style="text-align:center;color:rgba(255,255,255,.3);font-size:.8rem;padding:16px;">
                No committee members found for this area.
            </td>
        </tr>
    <?php else: ?>
        <?php
        $roleColors = [
            'committee'      => ['bg'=>'rgba(0,227,150,.15)',  'color'=>'#00e396'],
            'trainer'        => ['bg'=>'rgba(0,143,251,.15)',  'color'=>'#008ffb'],
            'trainee'        => ['bg'=>'rgba(254,176,25,.15)', 'color'=>'#feb019'],
            'supervisor'     => ['bg'=>'rgba(119,93,208,.15)', 'color'=>'#775dd0'],
            'coordinator'    => ['bg'=>'rgba(255,69,96,.15)',  'color'=>'#ff4560'],
        ];
        foreach ($committeeMembers as $cm):
            $role = strtolower($cm['role']);
            $c = $roleColors[$role] ?? ['bg'=>'rgba(255,255,255,.1)', 'color'=>'rgba(255,255,255,.6)'];
        ?>
        <tr>
            <td><?= htmlspecialchars(trim($cm['firstName'] . ' ' . $cm['lastName'])) ?></td>
            <td>
                <span class="role-badge" style="background:<?= $c['bg'] ?>;color:<?= $c['color'] ?>;">
                    <?= htmlspecialchars(ucfirst($cm['role'])) ?>
                </span>
            </td>
            <td><?= htmlspecialchars($cm['phone'] ?? '—') ?></td>
        </tr>
        <?php endforeach; ?>
    <?php endif; ?>
</tbody>
                    </table>
                </div>
            </div>

            <!-- Activity + Quick Actions -->
            <div class="col-lg-4">
                <div class="white-box mb-3" style="height:auto;">
                    <div class="box-title">Recent Activity</div>
                    <div data-simplebar style="max-height:220px;">
                        <!-- Replace with dynamic activity feed from DB -->
                        <div class="activity-item">
                            <div class="activity-icon" style="color:#00e396;"><i class="bi bi-check-lg"></i></div>
                            <div>
                                <div class="activity-text">Session held — 52 attendees</div>
                                <div class="activity-time">Last Sunday</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="color:#008ffb;"><i class="bi bi-person-plus"></i></div>
                            <div>
                                <div class="activity-text">3 new members joined this week</div>
                                <div class="activity-time">3 days ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="color:#feb019;"><i class="bi bi-megaphone"></i></div>
                            <div>
                                <div class="activity-text">Dawat activity in local market</div>
                                <div class="activity-time">5 days ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="color:#775dd0;"><i class="bi bi-chat-dots"></i></div>
                            <div>
                                <div class="activity-text">Q&amp;A session on Fiqh topics</div>
                                <div class="activity-time">1 week ago</div>
                            </div>
                        </div>
                        <div class="activity-item">
                            <div class="activity-icon" style="color:#ff4560;"><i class="bi bi-flag"></i></div>
                            <div>
                                <div class="activity-text">Representative updated contact info</div>
                                <div class="activity-time">2 weeks ago</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="white-box">
                    <div class="box-title">Quick Actions</div>
                    <div class="row g-2">
                        <div class="col-6">
                            <a href="eventDars.php" class="nk-pm-quick-action" style="background:rgba(0,227,150,.1);color:#00e396;">
                                <i class="bi bi-plus-circle" style="font-size:22px;"></i>
                                <span>Add Session</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="pdDarsAttendance.php" class="nk-pm-quick-action" style="background:rgba(0,143,251,.1);color:#008ffb;">
                                <i class="bi bi-people" style="font-size:22px;"></i>
                                <span>Attendance</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="#" class="nk-pm-quick-action" style="background:rgba(254,176,25,.1);color:#feb019;">
                                <i class="bi bi-megaphone" style="font-size:22px;"></i>
                                <span>Dawat Log</span>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="darsAreasInfo.php" class="nk-pm-quick-action" style="background:rgba(119,93,208,.1);color:#775dd0;">
                                <i class="bi bi-pencil-square" style="font-size:22px;"></i>
                                <span>Edit Area</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ ROW 3: Attendance Trend + Participant Breakdown ══ -->
    <div class="container-fluid mt-4">
        <div class="row g-3">
            <div class="col-lg-8">
                <div class="white-box">
                    <h3 class="box-title">Attendance Trend</h3>
                    <div id="chartAttendance" style="height:300px;"></div>
                    <div class="d-flex justify-content-around text-center mt-2 pt-2"
                         style="border-top:1px solid rgba(255,255,255,.08);">
                        <div>
                            <div class="text-white fw-bold" id="summaryTotal">—</div>
                            <small style="color:rgba(255,255,255,.4);">Total Attendees (YTD)</small>
                        </div>
                        <div>
                            <div style="color:#00e396;" class="fw-bold" id="summaryPeak">—</div>
                            <small style="color:rgba(255,255,255,.4);">Peak Session</small>
                        </div>
                        <div>
                            <div style="color:#feb019;" class="fw-bold" id="summaryRetention">—</div>
                            <small style="color:rgba(255,255,255,.4);">Retention Rate</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="white-box">
                    <h3 class="box-title">Participant Breakdown</h3>
                    <div id="chartParticipants" style="height:260px;"></div>
                    <div class="mt-2">
                        <div class="d-flex justify-content-between" style="font-size:.78rem;color:rgba(255,255,255,.5);padding:4px 0;">
                            <span><span style="color:#008ffb;">●</span> Students</span><span>35%</span>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:.78rem;color:rgba(255,255,255,.5);padding:4px 0;">
                            <span><span style="color:#00e396;">●</span> Working</span><span>45%</span>
                        </div>
                        <div class="d-flex justify-content-between" style="font-size:.78rem;color:rgba(255,255,255,.5);padding:4px 0;">
                            <span><span style="color:#feb019;">●</span> Elders</span><span>20%</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ ROW 4: Growth Metrics + Burndown / Session Timeline ══ -->
    <!-- <div class="container-fluid mt-4">
        <div class="row g-3">
            <div class="col-lg-4">
                <div class="white-box">
                    <h3 class="box-title">Growth Metrics</h3>
                    <div id="chartGrowth" style="height:260px;"></div>
                    <div class="row g-2 mt-2">
                        <div class="col-6">
                            <div style="background:#253a5c;border-radius:10px;padding:12px 14px;text-align:center;">
                                <div style="color:#00e396;font-size:1.3rem;font-weight:700;">+12%</div>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.4);">Monthly Growth</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div style="background:#253a5c;border-radius:10px;padding:12px 14px;text-align:center;">
                                <div style="color:#feb019;font-size:1.3rem;font-weight:700;">78%</div>
                                <div style="font-size:.72rem;color:rgba(255,255,255,.4);">Retention Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="white-box">
                    <h3 class="box-title">Session Timeline (This Year)</h3>
                    <div id="chartSessions" style="height:320px;"></div>
                </div>
            </div>
        </div>
    </div> -->

    <!-- ══ ROW 5: Dawat Activities ══ -->
    <!-- <div class="container-fluid mt-4 mb-4">
        <div class="row g-3">
            <div class="col-lg-5">
                <div class="white-box">
                    <h3 class="box-title">Dawat o Tableegh Activity</h3>
                    <div id="chartDawat" style="height:260px;"></div>
                    <div class="row g-2 mt-2 text-center">
                        <div class="col-4">
                            <div style="color:#fff;font-size:1.2rem;font-weight:700;">32</div>
                            <div style="font-size:.72rem;color:rgba(255,255,255,.4);">Outreach Events</div>
                        </div>
                        <div class="col-4">
                            <div style="color:#fff;font-size:1.2rem;font-weight:700;">280</div>
                            <div style="font-size:.72rem;color:rgba(255,255,255,.4);">People Contacted</div>
                        </div>
                        <div class="col-4">
                            <div style="color:#00e396;font-size:1.2rem;font-weight:700;">44</div>
                            <div style="font-size:.72rem;color:rgba(255,255,255,.4);">Converted</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="white-box">
                    <h3 class="box-title">Upcoming Sessions &amp; Deadlines</h3>
                    
                    <div class="mb-2">
                        <?php
                        $upcomingDates = [
                            ['day'=>'18','mon'=>'MAY','title'=>'Regular Dars Session','sub'=>$area['dayTime'] ?? '—'],
                            ['day'=>'25','mon'=>'MAY','title'=>'Monthly Review Meeting','sub'=>'All committee members'],
                            ['day'=>'01','mon'=>'JUN','title'=>'Dawat Activity — Market','sub'=>'Outreach team required'],
                            ['day'=>'15','mon'=>'JUN','title'=>'Attendance Review','sub'=>'Monthly analysis'],
                        ];
                        foreach ($upcomingDates as $ev):
                        ?>
                        <div class="d-flex align-items-start mb-3">
                            <div class="text-center flex-shrink-0 rounded"
                                 style="width:48px;padding:4px 8px;background:#253a5c;">
                                <div class="text-info fw-bold" style="font-size:18px;line-height:1;"><?= $ev['day'] ?></div>
                                <small style="color:rgba(255,255,255,.4);font-size:10px;"><?= $ev['mon'] ?></small>
                            </div>
                            <div class="ms-3">
                                <div class="text-white" style="font-size:13px;"><?= $ev['title'] ?></div>
                                <small style="color:rgba(255,255,255,.4);"><?= $ev['sub'] ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div> -->

<?php
include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/apexcharts@5.3.6/dist/apexcharts.min.js"></script>
    <script src="js/main.js"></script>

    <script>
    // ══════════════════════════════════════════════════════
    // CHART DEFAULTS — all charts share this base config
    // Replace the `series` / `categories` data with real
    // DB values fetched via a PHP/JSON endpoint later.
    // ══════════════════════════════════════════════════════

    const chartDefaults = {
        chart:  { background: 'transparent', foreColor: 'rgba(255,255,255,.5)', toolbar: { show: false } },
        grid:   { borderColor: 'rgba(255,255,255,.07)', strokeDashArray: 4 },
        tooltip:{ theme: 'dark' },
        legend: { labels: { colors: 'rgba(255,255,255,.6)' } },
    };

    // ── 1. Attendance Trend (Line) ──

const attendanceData = {
    categories: <?= $labelsJson ?>,   // ← was hardcoded, now dynamic
    series: [
        { name: 'Attendance',  data: <?= $attendanceJson ?> },
        { name: 'New Joinees', data: <?= $joineesJson ?>   },
    ]
};

new ApexCharts(document.getElementById('chartAttendance'), {
    ...chartDefaults,
    chart: { ...chartDefaults.chart, type: 'area', height: 300 },
    series: attendanceData.series,
    xaxis: { categories: attendanceData.categories },
    colors: ['#008ffb','#00e396'],
    stroke: { curve: 'smooth', width: 2.5 },
    fill:   { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: .35, opacityTo: .02 } },
    dataLabels: { enabled: false },
    markers: { size: 4 },
}).render().then(() => {
    document.getElementById('summaryTotal').textContent   = <?= $totalAttendees ?>;
    document.getElementById('summaryPeak').textContent    = <?= $peakSession ?>;
    document.getElementById('summaryRetention').textContent = '—'; // no retention data yet
});

    // ── 2. Participant Breakdown (Donut) ──
    new ApexCharts(document.getElementById('chartParticipants'), {
        ...chartDefaults,
        chart: { ...chartDefaults.chart, type: 'donut', height: 260 },
        // TODO: replace with fetch('fetchParticipants.php?area_id=<?= $id ?>')
        series: [35, 45, 20],
        labels: ['Students','Working','Elders'],
        colors: ['#008ffb','#00e396','#feb019'],
        plotOptions: { pie: { donut: { size: '65%', labels: { show: true, total: { show: true, color: '#fff', label: 'Total', fontSize: '13px' } } } } },
        dataLabels: { enabled: false },
        legend: { show: false },
    }).render();

    // ── 3. Growth Metrics (Radial Bar) ──
    new ApexCharts(document.getElementById('chartGrowth'), {
        ...chartDefaults,
        chart: { ...chartDefaults.chart, type: 'radialBar', height: 260 },
        // TODO: replace with fetch('fetchGrowth.php?area_id=<?= $id ?>')
        series: [78, 62, 45],
        labels: ['Retention','Regulars','New'],
        colors: ['#00e396','#008ffb','#feb019'],
        plotOptions: { radialBar: {
            hollow: { size: '30%' },
            dataLabels: { name: { fontSize: '11px', color: 'rgba(255,255,255,.5)' }, value: { color: '#fff', fontSize: '13px' } }
        }},
    }).render();

    // ── 4. Session Timeline (Bar) ──
    new ApexCharts(document.getElementById('chartSessions'), {
        ...chartDefaults,
        chart: { ...chartDefaults.chart, type: 'bar', height: 320 },
        // TODO: replace with fetch('fetchSessions.php?area_id=<?= $id ?>')
        series: [
            { name: 'Sessions Held', data: [4, 4, 5, 4, 5, 4, 5, 5, 4, 5, 4, 5] },
            { name: 'Cancelled',     data: [0, 1, 0, 0, 0, 1, 0, 0, 0, 0, 1, 0] },
        ],
        xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
        colors: ['#008ffb','#ff4560'],
        plotOptions: { bar: { borderRadius: 5, columnWidth: '50%' } },
        dataLabels: { enabled: false },
        legend: { position: 'top' },
    }).render();

    // ── 5. Dawat Activity (Bar) ──
    new ApexCharts(document.getElementById('chartDawat'), {
        ...chartDefaults,
        chart: { ...chartDefaults.chart, type: 'bar', height: 260 },
        // TODO: replace with fetch('fetchDawat.php?area_id=<?= $id ?>')
        series: [
            { name: 'Contacted', data: [20, 30, 25, 35, 28, 40] },
            { name: 'Converted', data: [3, 5, 4, 7, 5, 8] },
        ],
        xaxis: { categories: ['Jan','Feb','Mar','Apr','May','Jun'] },
        colors: ['#775dd0','#00e396'],
        plotOptions: { bar: { borderRadius: 5, columnWidth: '55%', horizontal: false } },
        dataLabels: { enabled: false },
    }).render();
    </script>

</body>
</html>