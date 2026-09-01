<?php
include "connection.php";
include "auth.php";   // ← handles session_start, validation, and sets $loggedUserId, $loggedRole, $loggedArea etc.

// ── ROLE CATEGORY DEFINITIONS (Matching Backend Documentation) ──
$userRoleLower = strtolower(trim($loggedRole ?? ''));

// Cat D: Admin / MD / DG (Top Management - Bird's eye view & All Area access)
$isAdminCat = in_array($userRoleLower, ['md', 'dg', 'admin', 'administrator', 'd1', 'd2']);

// Cat C1: Representative / Area Head (Head of Area - Area Attendance & Dars Details)
$isRepCat = in_array($userRoleLower, ['representative', 'c1', 'area_head', 'rep']);

// Cat B2: Committee Member (General Management - Mark & Manage Area Members)
$isCommitteeCat = in_array($userRoleLower, ['committee', 'b2']);

// Cat A & B1: General Member & Trainee (Personal Stats, Performance Track & History)
$isMemberCat = in_array($userRoleLower, ['member', 'trainee', 'b1', 'a', 'student']) || (!$isAdminCat && !$isRepCat && !$isCommitteeCat);

// Attendance Management Access (Cat D, Cat C1, Cat B2)
$canManageAttendance = $isAdminCat || $isRepCat || $isCommitteeCat;

// Area Dropdown Access (Cat D Admins can pick any area, others default to assigned area)
$canPickArea = $isAdminCat || empty($loggedArea);

// Active View Mode
$defaultMode = $canManageAttendance ? 'manage' : 'personal';
$viewMode    = $_GET['mode'] ?? $defaultMode;
if (!in_array($viewMode, ['manage', 'personal'])) {
    $viewMode = $defaultMode;
}

// Fetch all areas for dropdown & name resolution
$allAreas    = [];
$areasResult = $conn->query("SELECT id, areaName FROM dars_areas ORDER BY areaName ASC");
if ($areasResult) {
    while ($row = $areasResult->fetch_assoc()) {
        $allAreas[] = $row;
    }
}

// Resolve logged-in user's assigned area name
$loggedAreaName = 'Not Assigned';
foreach ($allAreas as $a) {
    if ((int)$a['id'] === (int)$loggedArea) {
        $loggedAreaName = $a['areaName'];
        break;
    }
}

// ── PERSONAL PERFORMANCE & STATS (For Cat A & B1 Members, and Personal Mode) ──
$myPresentCount = 0;
$myAbsentCount  = 0;
$myTotalCount   = 0;
$myOnTimeCount  = 0;
$myLateCount    = 0;
$myHistoryLogs  = [];
$todayStatus    = null;

if ($loggedUserId > 0) {
    // 1. Stats Query
    $statsStmt = $conn->prepare("
        SELECT 
            COUNT(*) as total_sessions,
            SUM(attendance = 'Present') as present_count,
            SUM(attendance = 'Absent') as absent_count,
            SUM(attendance = 'Present' AND timing = 'OnTime') as ontime_count,
            SUM(attendance = 'Present' AND timing = 'Late') as late_count
        FROM dars_attendance
        WHERE user_id = ?
    ");
    if ($statsStmt) {
        $statsStmt->bind_param("i", $loggedUserId);
        $statsStmt->execute();
        $statsRes = $statsStmt->get_result()->fetch_assoc();
        if ($statsRes) {
            $myTotalCount   = (int)($statsRes['total_sessions'] ?? 0);
            $myPresentCount = (int)($statsRes['present_count']  ?? 0);
            $myAbsentCount  = (int)($statsRes['absent_count']   ?? 0);
            $myOnTimeCount  = (int)($statsRes['ontime_count']   ?? 0);
            $myLateCount    = (int)($statsRes['late_count']     ?? 0);
        }
        $statsStmt->close();
    }

    // 2. Personal History Logs Query
    $logsStmt = $conn->prepare("
        SELECT da.id, da.area_id, da.dateTime, da.attendance, da.timing, da.note, COALESCE(a.areaName, da.area_id) AS areaName
        FROM dars_attendance da
        LEFT JOIN dars_areas a ON (CAST(a.id AS UNSIGNED) = CAST(da.area_id AS UNSIGNED) OR a.areaName = da.area_id)
        WHERE da.user_id = ?
        ORDER BY da.dateTime DESC
        LIMIT 100
    ");
    if ($logsStmt) {
        $logsStmt->bind_param("i", $loggedUserId);
        $logsStmt->execute();
        $logsRes = $logsStmt->get_result();
        if ($logsRes) {
            while ($row = $logsRes->fetch_assoc()) {
                $myHistoryLogs[] = $row;
                if ($row['dateTime'] === date('Y-m-d')) {
                    $todayStatus = $row['attendance'];
                }
            }
        }
        $logsStmt->close();
    }
}

// Attendance Rate & Consistency Calculation (Section 1.6.1)
$myAttPercentage = $myTotalCount > 0 ? round(($myPresentCount / $myTotalCount) * 100) : 0;
$consistencyBadge = 'New Member';
$consistencyClass = 'badge bg-secondary';
if ($myTotalCount > 0) {
    if ($myAttPercentage >= 85) {
        $consistencyBadge = 'Highly Consistent';
        $consistencyClass = 'badge bg-success';
    } elseif ($myAttPercentage >= 65) {
        $consistencyBadge = 'Regular Attender';
        $consistencyClass = 'badge bg-info text-dark';
    } else {
        $consistencyBadge = 'Needs Improvement';
        $consistencyClass = 'badge bg-warning text-dark';
    }
}
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
    /* Global Layout & Theme */
    .att-card {
        background: #1b2a47; border: 1px solid rgba(255,255,255,0.06);
        border-radius: 12px; padding: 22px; margin-bottom: 24px;
        box-shadow: 0 4px 18px rgba(0,0,0,0.15);
    }
    .att-top-bar {
        display: flex; align-items: center;
        justify-content: space-between;
        flex-wrap: wrap; gap: 12px; margin-bottom: 20px;
    }
    .att-top-bar h5 { margin: 0; font-size: 1.1rem; font-weight: 700; color: #ffffff; }

    /* View Switcher Pill (For Committee / Rep / Admin) */
    .view-pill-group {
        display: flex; background: #152036; border: 1px solid #253a5c;
        border-radius: 10px; padding: 3px; gap: 4px;
    }
    .view-pill-btn {
        border: none; background: transparent; color: #8a9bb5;
        font-size: .82rem; font-weight: 600; padding: 6px 16px;
        border-radius: 8px; text-decoration: none; transition: all .18s;
        display: inline-flex; align-items: center; gap: 6px;
    }
    .view-pill-btn.active {
        background: #03a9f4; color: #ffffff; font-weight: 700;
        box-shadow: 0 2px 8px rgba(3,169,244,0.3);
    }
    .view-pill-btn:hover:not(.active) { color: #ffffff; background: rgba(255,255,255,0.05); }

    /* Role Category Badges */
    .cat-badge {
        font-size: .75rem; font-weight: 700; padding: 4px 12px;
        border-radius: 20px; text-transform: uppercase; letter-spacing: .5px;
    }
    .cat-badge-d { background: rgba(255,82,82,0.15); color: #ff5252; border: 1px solid #ff5252; }
    .cat-badge-c1 { background: rgba(3,169,244,0.15); color: #03a9f4; border: 1px solid #03a9f4; }
    .cat-badge-b2 { background: rgba(171,71,188,0.15); color: #ab47bc; border: 1px solid #ab47bc; }
    .cat-badge-a { background: rgba(0,200,83,0.15); color: #00c853; border: 1px solid #00c853; }

    /* Member KPI Stat Cards */
    .member-stat-card {
        background: #152036; border: 1px solid #253a5c;
        border-radius: 12px; padding: 18px 20px;
        display: flex; align-items: center; justify-content: space-between;
        margin-bottom: 18px; transition: transform .18s, border-color .18s;
    }
    .member-stat-card:hover { transform: translateY(-2px); border-color: #03a9f4; }
    .member-stat-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.4rem;
    }
    .stat-val { font-size: 1.5rem; font-weight: 800; color: #ffffff; margin-bottom: 2px; }
    .stat-lbl { font-size: .78rem; text-transform: uppercase; letter-spacing: .5px; color: #8a9bb5; margin: 0; }

    /* Controls & Table Formatting */
    .search-wrap { position: relative; min-width: 240px; flex: 1; max-width: 380px; }
    .search-wrap input {
        width: 100%; padding: 8px 14px 8px 36px;
        border-radius: 9px; border: 1.5px solid #253a5c;
        font-size: .88rem; background: #152036; color: #ffffff;
    }
    .search-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #6c7a8d; font-size: .9rem; }
    .ctrl-group { display: flex; flex-direction: column; gap: 4px; }
    .ctrl-group label { font-size: .75rem; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; color: #8a9bb5; }
    .ctrl-group .form-control, .ctrl-group .form-select {
        border-radius: 8px; border: 1px solid #253a5c;
        font-size: .88rem; background: #152036; color: #ffffff; padding: 7px 12px;
    }
    .att-controls {
        display: flex; align-items: flex-end;
        flex-wrap: wrap; gap: 14px; margin-bottom: 20px;
        padding-bottom: 18px; border-bottom: 1px solid #253a5c;
    }
    .att-label-present { background: #00c853; color: #fff; font-size: .75rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
    .att-label-absent { background: #ff5252; color: #fff; font-size: .75rem; font-weight: 700; padding: 3px 10px; border-radius: 20px; }
    .late-select { font-size: .8rem; padding: 2px 6px; border-radius: 6px; border: 1px solid #253a5c; background: #152036; color: #fff; }
    .mark-all-wrap { margin-left: auto; display: flex; align-items: center; gap: 8px; }
    .save-bar {
        position: sticky; bottom: 16px; z-index: 99;
        background: #1b2a47; border: 1px solid #03a9f4;
        border-radius: 12px; padding: 14px 20px;
        display: flex; align-items: center; justify-content: space-between;
        box-shadow: 0 8px 24px rgba(0,0,0,0.3); margin-top: 16px;
    }
    .save-bar .note-wrap { flex: 1; max-width: 480px; }
    .save-bar .note-wrap input { width: 100%; border-radius: 8px; border: 1px solid #253a5c; background: #152036; color: #fff; padding: 7px 12px; font-size: .88rem; }
    .btn-save-att { background: #03a9f4; border: none; color: #fff; font-weight: 600; font-size: .9rem; padding: 9px 24px; border-radius: 8px; transition: background .18s; }
    .btn-save-att:hover { background: #0290d1; }
</style>

<body>
    <?php include "sidebar.php"; ?>
    <div class="all-content-wrapper">
        <?php include "mainTopBar.php"; ?>

        <div class="breadcome-area">
            <div class="container-fluid">
                <div class="row"><div class="col-lg-12"><div class="breadcome-list">
                    <div class="row">
                        <div class="col-lg-6"><div class="breadcome-heading d-flex align-items-center gap-3">
                            <h4 style="color:#fff;margin:0;">Dars Attendance System</h4>
                            <?php if ($isAdminCat): ?>
                                <span class="cat-badge cat-badge-d">Cat D: Admin / MD</span>
                            <?php elseif ($isRepCat): ?>
                                <span class="cat-badge cat-badge-c1">Cat C1: Representative</span>
                            <?php elseif ($isCommitteeCat): ?>
                                <span class="cat-badge cat-badge-b2">Cat B2: Committee Member</span>
                            <?php else: ?>
                                <span class="cat-badge cat-badge-a">Cat A: General Member</span>
                            <?php endif; ?>
                        </div></div>
                        <div class="col-lg-6 text-end">
                            <ul class="breadcome-menu" style="margin:0;padding:0;list-style:none;">
                                <li><a href="dashboard.php" style="color:#03a9f4;">Home</a> <span class="bread-slash">/</span></li>
                                <li><span class="bread-bld" style="color:#8a9bb5;">Dars Attendance</span></li>
                            </ul>
                        </div>
                    </div>
                </div></div></div>
            </div>
        </div>

        <div class="product-status mg-b-30">
            <div class="container-fluid">

                <?php if ($viewMode === 'personal' || !$canManageAttendance): ?>
                <!-- ════════════════════════════════════════════════════════════════════════
                     GENERAL MEMBER & TRAINEE INTERFACE (Cat A & Cat B1 - Section 1.6.1)
                ════════════════════════════════════════════════════════════════════════ -->
                
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                    <div>
                        <h4 style="color:#fff; margin:0; font-weight:700;">
                            <i class="bi bi-graph-up-arrow me-2 text-info"></i>My Personal Dars Attendance & Consistency
                        </h4>
                        <p style="color:#8a9bb5; margin:4px 0 0 0; font-size:.85rem;">
                            Assigned Dars Area: <strong style="color:#03a9f4;"><?= htmlspecialchars($loggedAreaName) ?></strong>
                            <span class="ms-2 <?= $consistencyClass ?>"><?= $consistencyBadge ?></span>
                        </p>
                    </div>

                    <?php if ($canManageAttendance): ?>
                    <div class="view-pill-group">
                        <a href="pdDarsAttendance.php?mode=personal" class="view-pill-btn active">
                            <i class="bi bi-person me-1"></i>My Performance
                        </a>
                        <a href="pdDarsAttendance.php?mode=manage" class="view-pill-btn">
                            <i class="bi bi-people me-1"></i>Area Attendance Console
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- MEMBER KPI PERFORMANCE CARDS (Section 1.6.1) -->
                <div class="row">
                    <!-- Overall Attendance Rate -->
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="member-stat-card">
                            <div>
                                <h3 class="stat-val" style="color:<?= $myAttPercentage >= 75 ? '#00c853' : ($myAttPercentage >= 50 ? '#ffb300' : '#ff5252') ?>;">
                                    <?= $myAttPercentage ?>%
                                </h3>
                                <p class="stat-lbl">Consistency Score</p>
                            </div>
                            <div class="member-stat-icon" style="background:rgba(0,200,83,0.12); color:#00c853;">
                                <i class="bi bi-speedometer2"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Total Attended Sessions -->
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="member-stat-card">
                            <div>
                                <h3 class="stat-val"><?= $myPresentCount ?> <span style="font-size:.85rem; color:#8a9bb5; font-weight:400;">/ <?= $myTotalCount ?></span></h3>
                                <p class="stat-lbl">Attended Sessions</p>
                            </div>
                            <div class="member-stat-icon" style="background:rgba(3,169,244,0.12); color:#03a9f4;">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Punctuality Breakdown -->
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="member-stat-card">
                            <div>
                                <h3 class="stat-val" style="color:#ab47bc;"><?= $myOnTimeCount ?> <span style="font-size:.85rem; color:#8a9bb5; font-weight:400;">(<?= $myLateCount ?> Late)</span></h3>
                                <p class="stat-lbl">On-Time Attendance</p>
                            </div>
                            <div class="member-stat-icon" style="background:rgba(171,71,188,0.12); color:#ab47bc;">
                                <i class="bi bi-alarm-fill"></i>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Session Check-In Card -->
                    <div class="col-lg-3 col-md-6 col-sm-6">
                        <div class="member-stat-card" style="border-color:<?= $todayStatus === 'Present' ? '#00c853' : '#03a9f4' ?>;">
                            <div>
                                <h3 class="stat-val" style="font-size:1.05rem; line-height:1.4;">
                                    <?php if ($todayStatus === 'Present'): ?>
                                        <span style="color:#00c853;"><i class="bi bi-patch-check-fill me-1"></i>Present Today</span>
                                    <?php elseif ($todayStatus === 'Absent'): ?>
                                        <span style="color:#ff5252;"><i class="bi bi-x-circle-fill me-1"></i>Marked Absent</span>
                                    <?php else: ?>
                                        <span style="color:#03a9f4;"><i class="bi bi-calendar-event me-1"></i>Today's Dars</span>
                                    <?php endif; ?>
                                </h3>
                                <p class="stat-lbl"><?= date('D, d M Y') ?></p>
                            </div>
                            <?php if ($todayStatus !== 'Present' && $loggedArea > 0): ?>
                            <button class="btn btn-sm btn-success px-3" id="btnSelfCheckIn" onclick="markSelfAttendance()" style="border-radius:8px;">
                                Self Check-In
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- PERSONAL ATTENDANCE PERFORMANCE LOG TABLE -->
                <div class="att-card">
                    <div class="att-top-bar">
                        <h5 style="color:#fff;"><i class="bi bi-journal-check me-2 text-info"></i>Dars Attendance History Log</h5>
                        <small style="color:#8a9bb5;">View your consistency record across past sessions</small>
                    </div>

                    <div class="table-responsive sparkline12-graph">
                        <div class="static-table-list">
                            <table class="table table-hover table-bordered">
                                <thead class="thead-dark">
                                    <tr>
                                        <th style="width:50px;">#</th>
                                        <th>Session Date</th>
                                        <th>Dars Area</th>
                                        <th>Attendance Status</th>
                                        <th>Punctuality</th>
                                        <th>Session Topic / Note</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($myHistoryLogs)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4" style="color:#8a9bb5;">
                                            <i class="bi bi-inbox me-2"></i>No attendance records logged yet for your account.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($myHistoryLogs as $idx => $log): 
                                            $isPresent = ($log['attendance'] === 'Present');
                                            $formattedDate = date('l, d M Y', strtotime($log['dateTime']));
                                        ?>
                                        <tr>
                                            <td><?= $idx + 1 ?></td>
                                            <td><strong><?= htmlspecialchars($formattedDate) ?></strong></td>
                                            <td><span class="badge bg-secondary"><?= htmlspecialchars($log['areaName']) ?></span></td>
                                            <td>
                                                <span class="<?= $isPresent ? 'att-label-present' : 'att-label-absent' ?>">
                                                    <?= $isPresent ? 'Present' : 'Absent' ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($isPresent): ?>
                                                    <span class="badge <?= $log['timing'] === 'OnTime' ? 'bg-info' : 'bg-warning text-dark' ?>">
                                                        <?= $log['timing'] === 'OnTime' ? 'On Time' : 'Late' ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">—</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><small style="color:#cccccc;"><?= htmlspecialchars($log['note'] ?: '—') ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <?php else: ?>
                <!-- ════════════════════════════════════════════════════════════════════════
                     REPRESENTATIVE & COMMITTEE & ADMIN CONSOLE (Cat C1, B2 & Cat D - Section 3.6.1)
                ════════════════════════════════════════════════════════════════════════ -->
                
                <div class="att-card">
                    <div class="att-top-bar">
                        <div>
                            <h5 style="color:#fff;">
                                <?php if ($isRepCat): ?>
                                    <i class="bi bi-diagram-3-fill me-2 text-info"></i>Representative Dars Portal (Cat C1)
                                <?php elseif ($isAdminCat): ?>
                                    <i class="bi bi-shield-lock-fill me-2 text-danger"></i>System Attendance Console (Cat D)
                                <?php else: ?>
                                    <i class="bi bi-people-fill me-2 text-primary"></i>Committee Attendance Console (Cat B2)
                                <?php endif; ?>
                            </h5>
                            <small style="color:#8a9bb5;">
                                <?php if ($isRepCat): ?>
                                    Record Dars session topics, mark member attendance, and suggest members for promotion.
                                <?php else: ?>
                                    Manage and mark member attendance for your assigned area.
                                <?php endif; ?>
                            </small>
                        </div>

                        <div class="d-flex align-items-center gap-3 flex-wrap">
                            <div class="search-wrap">
                                <i class="bi bi-search"></i>
                                <input type="text" id="memberSearch" placeholder="Search by name, username or email…">
                            </div>

                            <div class="view-pill-group">
                                <a href="pdDarsAttendance.php?mode=personal" class="view-pill-btn">
                                    <i class="bi bi-person me-1"></i>My Performance
                                </a>
                                <a href="pdDarsAttendance.php?mode=manage" class="view-pill-btn active">
                                    <i class="bi bi-people me-1"></i>Area Console
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="att-controls">
                        <div class="ctrl-group">
                            <label><i class="bi bi-calendar3 me-1"></i>Session Date</label>
                            <input type="date" id="attDate" class="form-control"
                                   value="<?= date('Y-m-d') ?>" onchange="onDateChange()">
                        </div>

                        <?php if ($canPickArea): ?>
                        <div class="ctrl-group">
                            <label><i class="bi bi-geo-alt me-1"></i>Select Dars Area</label>
                            <select id="areaSelect" class="form-select" onchange="onAreaChange()">
                                <option value="">— Select Area —</option>
                                <?php foreach ($allAreas as $a): ?>
                                <option value="<?= (int)$a['id'] ?>" <?= ((int)$a['id'] === (int)$loggedArea) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($a['areaName']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="ctrl-group">
                            <label>&nbsp;</label>
                            <button class="btn btn-primary" onclick="loadMembers()"
                                    style="border-radius:8px;padding:7px 20px;">
                                <i class="bi bi-arrow-clockwise me-1"></i>Load Area
                            </button>
                        </div>
                        <?php else: ?>
                        <div class="ctrl-group">
                            <label><i class="bi bi-geo-alt me-1"></i>Assigned Area</label>
                            <input type="text" class="form-control"
                                   value="<?= htmlspecialchars($loggedAreaName) ?>" readonly>
                        </div>
                        <?php endif; ?>

                        <div class="mark-all-wrap">
                            <div class="form-check form-switch mb-0">
                                <input class="form-check-input" type="checkbox" id="markAllToggle" style="margin-right:5px; width:3.7em; height:1.7em;"
                                       onchange="toggleMarkAll(this.checked)">
                                <label class="form-check-label" style="color: #cccccc;" for="markAllToggle"
                                       id="markAllLabel">Mark All Present</label>
                            </div>
                        </div>
                    </div>

                    <!-- AREA MEMBERS ATTENDANCE TABLE -->
                    <div class="table-responsive sparkline12-graph"><div class="static-table-list">
                        <table class="table table-hover table-bordered">
                            <thead class="thead-dark">
                                <tr>
                                    <th style="width:44px;">#</th>
                                    <th>Full Name</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th style="width:160px;">Present / Absent</th>
                                    <th style="width:120px;">Timing</th>
                                    <?php if ($isRepCat): ?>
                                    <th style="width:130px;">Action</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody id="attendanceTableBody">
                                <tr><td colspan="<?= $isRepCat ? '7' : '6' ?>" class="text-center py-4" style="color:#cccccc;">
                                    <span class="spinner-border spinner-border-sm me-2 text-primary"></span>
                                    Loading members…
                                </td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="save-bar" id="saveBar" style="display:none;">
                        <div class="note-wrap">
                            <input type="text" id="attNote"
                                   placeholder="Session Details / Note (e.g. Topic: Seerah, Speaker: Sheikh Ali)…">
                        </div>
                        <button class="btn-save-att" onclick="saveAttendance()">
                            <i class="bi bi-check-circle me-1"></i>Save Session Attendance
                        </button>
                    </div>
                    </div>

                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>

<?php include "footer.php"; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>
    <script>
    const LOGGED_USER_ID = <?= json_encode((int)$loggedUserId) ?>;
    const CAN_PICK_AREA  = <?= json_encode((bool)$canPickArea)  ?>;
    const MY_AREA_ID     = <?= json_encode((int)$loggedArea)   ?>;
    const VIEW_MODE      = <?= json_encode($viewMode)          ?>;
    const IS_REP_CAT     = <?= json_encode((bool)$isRepCat)    ?>;

    let memberList = [], displayList = [];

    function getActiveAreaId() {
        if (CAN_PICK_AREA) {
            const sel = document.getElementById('areaSelect');
            if (sel && sel.value) {
                return parseInt(sel.value, 10) || 0;
            }
        }
        return MY_AREA_ID || 0;
    }

    // 1-Click Self Check-In for Member & Representative
    function markSelfAttendance() {
        const btn = document.getElementById('btnSelfCheckIn');
        if (!MY_AREA_ID) { alert('No area assigned to your profile. Please contact administrator.'); return; }
        if (btn) { btn.disabled = true; btn.innerHTML = 'Saving…'; }

        const today = new Date().toISOString().split('T')[0];
        fetch('saveAttendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                area_id: MY_AREA_ID,
                submitted_by: LOGGED_USER_ID,
                dateTime: today,
                note: 'Self Check-in',
                records: [{ user_id: LOGGED_USER_ID, attendance: 'Present', timing: 'OnTime' }]
            }),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                location.reload();
            } else {
                alert(res.message || 'Failed to mark attendance.');
                if (btn) { btn.disabled = false; btn.innerHTML = 'Self Check-In'; }
            }
        })
        .catch(err => {
            alert('Network error. Please try again.');
            if (btn) { btn.disabled = false; btn.innerHTML = 'Self Check-In'; }
        });
    }

    // Load Area Members for Committee / Representative / Admin Management Mode
    async function loadMembers() {
        if (VIEW_MODE !== 'manage') return;

        const areaId = getActiveAreaId();
        const date   = document.getElementById('attDate').value;
        const colSpan = IS_REP_CAT ? 7 : 6;

        if (!areaId) {
            if (CAN_PICK_AREA) {
                setTableBody(`<tr><td colspan="${colSpan}" class="text-center py-4" style="color:#cccccc;">
                    <i class="bi bi-geo-alt me-2"></i>Please select an area from the dropdown above then click <strong>Load Area</strong>.
                </td></tr>`);
            } else {
                setTableBody(`<tr><td colspan="${colSpan}" class="text-center py-4" style="color:#cccccc;">
                    <i class="bi bi-info-circle me-2"></i>No area is assigned to your account. Please contact an administrator.
                </td></tr>`);
            }
            document.getElementById('saveBar').style.display = 'none';
            return;
        }

        if (!date) { alert('Please select a date.'); return; }

        setTableBody(`<tr><td colspan="${colSpan}" class="text-center py-4" style="color:#cccccc;">
            <span class="spinner-border spinner-border-sm me-2 text-primary"></span>Loading area members…
        </td></tr>`);
        document.getElementById('saveBar').style.display = 'none';

        try {
            const res  = await fetch(`fetchAttendanceMembers.php?area_id=${areaId}&date=${encodeURIComponent(date)}`);
            if (!res.ok) throw new Error(`HTTP ${res.status}`);
            const text = await res.text();
            let data;
            try   { data = JSON.parse(text); }
            catch { throw new Error('Server returned invalid data — check PHP error log.'); }
            if (!Array.isArray(data)) throw new Error('Unexpected response format.');

            memberList  = data;
            displayList = [...memberList];
            renderTable(displayList);
            document.getElementById('saveBar').style.display = memberList.length > 0 ? 'flex' : 'none';
            document.getElementById('markAllToggle').checked = false;
            document.getElementById('markAllLabel').textContent = 'Mark All Present';
            document.getElementById('memberSearch').value = '';
        } catch(e) {
            console.error(e);
            setTableBody(`<tr><td colspan="${colSpan}" class="text-center text-danger py-4">
                <i class="bi bi-exclamation-triangle me-2"></i>${escHtml(e.message)}
            </td></tr>`);
        }
    }

    function renderTable(list) {
        const colSpan = IS_REP_CAT ? 7 : 6;
        if (!list || list.length === 0) {
            setTableBody(`<tr><td colspan="${colSpan}" class="text-center py-4" style="color:#cccccc;">
                <i class="bi bi-inbox me-2"></i>No members found for this Dars area.
            </td></tr>`); return;
        }
        let html = '';
        list.forEach((m, i) => {
            const existChk  = document.getElementById(`att-${m.id}`);
            const isPresent = existChk ? existChk.checked : (m.saved_attendance === 'Present');
            const existTmg  = document.getElementById(`timing-${m.id}`);
            const timing    = existTmg ? existTmg.value : (m.saved_timing ?? 'OnTime');

            html += `<tr id="row-${m.id}">
                <td style="font-size:.82rem;">${i+1}</td>
                <td><strong>${escHtml(m.firstName)} ${escHtml(m.lastName)}</strong></td>
                <td><small>${escHtml(m.username ?? '')}</small></td>
                <td><small>${escHtml(m.email ?? '')}</small></td>
                <td>
                    <div class="d-flex align-items-center gap-2">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input att-toggle" type="checkbox"
                                   id="att-${m.id}" ${isPresent?'checked':''}
                                   onchange="updateAttLabel(${m.id},this.checked)">
                        </div>
                        <span id="att-label-${m.id}"
                              class="${isPresent?'att-label-present':'att-label-absent'}">
                            ${isPresent?'Present':'Absent'}
                        </span>
                    </div>
                </td>
                <td>
                    <select class="late-select" id="timing-${m.id}">
                        <option value="OnTime" ${timing==='OnTime'?'selected':''}>On Time</option>
                        <option value="Late"   ${timing==='Late'  ?'selected':''}>Late</option>
                    </select>
                </td>
                ${IS_REP_CAT ? `<td>
                    <button type="button" class="btn btn-xs btn-outline-info" onclick="suggestHire('${m.id}', '${escHtml(m.firstName)} ${escHtml(m.lastName)}')">
                        <i class="bi bi-star me-1"></i>Suggest
                    </button>
                </td>` : ''}
            </tr>`;
        });
        setTableBody(html);
    }

    function setTableBody(html) {
        const tb = document.getElementById('attendanceTableBody');
        if (tb) tb.innerHTML = html;
    }

    function suggestHire(userId, userName) {
        showSuccessModal(`Member <strong>${userName}</strong> suggested for Committee/Research team evaluation (Cat C1 Section 3.6.1).`);
    }

    function updateAttLabel(userId, isPresent) {
        const lbl = document.getElementById(`att-label-${userId}`);
        if (!lbl) return;
        lbl.textContent = isPresent ? 'Present' : 'Absent';
        lbl.className   = isPresent ? 'att-label-present' : 'att-label-absent';
        syncMarkAllToggle();
    }

    function toggleMarkAll(checked) {
        memberList.forEach(m => {
            const t = document.getElementById(`att-${m.id}`);
            const l = document.getElementById(`att-label-${m.id}`);
            if (!t) return;
            t.checked = checked;
            l.textContent = checked ? 'Present' : 'Absent';
            l.className   = checked ? 'att-label-present' : 'att-label-absent';
        });
        document.getElementById('markAllLabel').textContent = checked ? 'Mark All Absent' : 'Mark All Present';
    }

    function syncMarkAllToggle() {
        if (!memberList.length) return;
        const all = memberList.every(m => document.getElementById(`att-${m.id}`)?.checked);
        document.getElementById('markAllToggle').checked = all;
        document.getElementById('markAllLabel').textContent = all ? 'Mark All Absent' : 'Mark All Present';
    }

    const memberSearch = document.getElementById('memberSearch');
    if (memberSearch) {
        memberSearch.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            displayList = !q ? [...memberList] : memberList.filter(m =>
                (`${m.firstName} ${m.lastName}`).toLowerCase().includes(q) ||
                (m.username??'').toLowerCase().includes(q) ||
                (m.email??'').toLowerCase().includes(q)
            );
            renderTable(displayList);
        });
    }

    function onAreaChange() { if (getActiveAreaId()) loadMembers(); }
    function onDateChange() { if (getActiveAreaId()) loadMembers(); }

    function saveAttendance() {
        const areaId = getActiveAreaId();
        const date   = document.getElementById('attDate').value;
        const note   = document.getElementById('attNote').value.trim();
        if (!areaId || !date) { alert('Area and date are required.'); return; }
        if (!memberList.length) { alert('No members loaded.'); return; }

        const records = memberList.map(m => ({
            user_id    : m.id,
            attendance : document.getElementById(`att-${m.id}`)?.checked ? 'Present' : 'Absent',
            timing     : document.getElementById(`timing-${m.id}`)?.value ?? 'OnTime',
        }));

        const btn = document.querySelector('.btn-save-att');
        btn.disabled  = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Saving…';

        fetch('saveAttendance.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ area_id: areaId, submitted_by: LOGGED_USER_ID, dateTime: date, note, records }),
        })
        .then(r => r.json())
        .then(res => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Session Attendance';
            showSuccessModal(res.message ?? 'Attendance saved.');
        })
        .catch(err => {
            btn.disabled  = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Session Attendance';
            alert('Failed to save. Please try again.');
        });
    }

    function escHtml(str) {
        return (str??'').toString()
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    function showSuccessModal(msg) {
        const div = document.createElement('div');
        div.innerHTML = `<div class="modal fade" id="successModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
                <div class="modal-body text-center p-4">
                    <i class="bi bi-check-circle-fill text-success" style="font-size:2.5rem;"></i>
                    <h5 class="mt-3">${msg}</h5>
                    <button class="btn btn-primary mt-3" data-bs-dismiss="modal">OK</button>
                </div>
            </div></div>
        </div>`;
        document.body.appendChild(div.firstElementChild);
        const modal = new bootstrap.Modal(document.getElementById('successModal'));
        modal.show();
        document.getElementById('successModal').addEventListener('hidden.bs.modal', function() { this.remove(); });
    }

    window.addEventListener('DOMContentLoaded', () => {
        if (VIEW_MODE === 'manage') {
            if (getActiveAreaId() > 0) {
                loadMembers();
            } else if (CAN_PICK_AREA) {
                const colSpan = IS_REP_CAT ? 7 : 6;
                setTableBody(`<tr><td colspan="${colSpan}" class="text-center py-4" style="color:#cccccc;">
                    <i class="bi bi-geo-alt me-2"></i>Please select an area from the dropdown above.
                </td></tr>`);
            } else {
                const colSpan = IS_REP_CAT ? 7 : 6;
                setTableBody(`<tr><td colspan="${colSpan}" class="text-center py-4" style="color:#cccccc;">
                    <i class="bi bi-info-circle me-2"></i>No area is assigned to your account. Please contact an administrator.
                </td></tr>`);
            }
        }
    });
    </script>
</body>
</html>