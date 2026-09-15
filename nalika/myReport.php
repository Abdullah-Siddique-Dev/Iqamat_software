<?php
// myReport.php
// Personal User Performance Report Module for Members, Trainees, Representatives & Leadership
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Karachi');
} else {
    @date_default_timezone_set('Asia/Karachi');
}

include "connection.php";
include "auth.php";
// $loggedUserId, $loggedRole, $loggedArea set by auth.php

require_once "permissions.php";
if (!hasPermission("myReport")) {
    header("Location: dashboard.php");
    exit();
}

// Fetch current user details
$uStmt = $conn->prepare("SELECT u.id, u.firstName, u.lastName, u.username, u.email, u.phone, u.area, u.role, u.card, u.category, u.is_active, u.date_of_joining, u.image,
                         COALESCE(da.areaName, u.area) AS area_display
                         FROM users u
                         LEFT JOIN dars_areas da ON (u.area REGEXP '^[0-9]+$' AND da.id = CAST(u.area AS UNSIGNED))
                         WHERE u.id = ?");
$uStmt->bind_param("i", $loggedUserId);
$uStmt->execute();
$uRes = $uStmt->get_result();
$currentUser = $uRes->fetch_assoc();

if (!$currentUser) {
    header("Location: dashboard.php");
    exit();
}

$currentUser['card'] = !empty($currentUser['card']) ? $currentUser['card'] : 'Diamond';
$currentUser['category'] = !empty($currentUser['category']) ? $currentUser['category'] : 'B';
$currentUser['fullName'] = trim(($currentUser['firstName'] ?? '') . ' ' . ($currentUser['lastName'] ?? ''));
$currentUser['areaName'] = !empty($currentUser['area_display']) ? $currentUser['area_display'] : '—';
$rawPhone = trim((string)($currentUser['phone'] ?? ''));
$currentUser['phoneDisplay'] = (!empty($rawPhone) && $rawPhone !== '2147483647' && $rawPhone !== '0') ? $rawPhone : '—';
$currentUserRole = ucfirst(strtolower(trim($currentUser['role'] ?? 'Member')));

$userPhotoRaw = !empty($currentUser['image']) ? $currentUser['image'] : (!empty($loggedImage) ? $loggedImage : '');
$hasUserPhoto = (!empty($userPhotoRaw) && (file_exists($userPhotoRaw) || file_exists(__DIR__ . '/' . $userPhotoRaw)));
$userPhotoUrl = $hasUserPhoto
    ? htmlspecialchars($userPhotoRaw)
    : 'https://ui-avatars.com/api/?name='
    . urlencode(($currentUser['firstName'] ?: $currentUser['username']) . '+' . ($currentUser['lastName'] ?: ''))
    . '&background=03a9f4&color=fff&size=200';
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
/* ── My Report Theme & Styling ── */
.my-report-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 16px;
}

.my-report-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1.25rem;
    font-weight: 700;
    color: #f8fafc;
    margin: 0;
}

/* User Hero Banner */
.user-hero-card {
    background: linear-gradient(135deg, #101726 0%, #152033 100%);
    border: 1px solid #293647;
    border-radius: 14px;
    padding: 22px 26px;
    margin-bottom: 24px;
    box-shadow: 0 4px 18px rgba(0, 0, 0, 0.25);
}

.user-avatar-circle {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    overflow: hidden;
    background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 1.8rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.35);
    flex-shrink: 0;
    border: 2px solid rgba(59, 130, 246, 0.5);
}

.user-avatar-circle img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 50%;
    display: block;
}

.badge-card-diamond { background: linear-gradient(135deg, #0284c7, #0369a1); color: #fff; font-weight: 600; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }
.badge-card-gold { background: linear-gradient(135deg, #d97706, #b45309); color: #fff; font-weight: 600; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }
.badge-card-silver { background: linear-gradient(135deg, #64748b, #475569); color: #fff; font-weight: 600; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }
.badge-card-metal { background: linear-gradient(135deg, #52525b, #27272a); color: #fff; font-weight: 600; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }
.badge-category { background: #1e293b; color: #38bdf8; border: 1px solid #0284c7; font-weight: 700; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }

.badge-role-admin { background: #dc2626; color: #fff; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }
.badge-role-committee { background: #7c3aed; color: #fff; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }
.badge-role-representative { background: #0891b2; color: #fff; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }
.badge-role-trainee { background: #ea580c; color: #fff; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }
.badge-role-member { background: #2563eb; color: #fff; font-size: 0.78rem; padding: 4px 10px; border-radius: 6px; }

/* Filter Container */
.period-filter-wrapper {
    background: #101726;
    border: 1px solid #293647;
    border-radius: 10px;
    padding: 6px 14px;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.period-select {
    background: #152033;
    color: #f8fafc;
    border: 1px solid #3b82f6;
    border-radius: 6px;
    font-size: 0.88rem;
    font-weight: 600;
    padding: 6px 12px;
    outline: none;
    cursor: pointer;
}

.period-select:focus {
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.25);
}

/* Score Circle */
.score-circle-lg {
    width: 120px;
    height: 120px;
    border-radius: 50%;
    border: 6px solid #3b82f6;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    transition: all 0.3s;
}

.score-circle-lg .score-num {
    font-size: 2.1rem;
    font-weight: 800;
    line-height: 1;
}

/* KPI Summary Cards */
.metric-box {
    background: #101726;
    border: 1px solid #293647;
    border-radius: 10px;
    padding: 14px 16px;
    text-align: center;
    transition: transform 0.2s, border-color 0.2s;
    height: 100%;
}

.metric-box:hover {
    transform: translateY(-2px);
    border-color: #3b82f6;
}

.metric-box .metric-icon {
    font-size: 1.4rem;
    margin-bottom: 6px;
    display: inline-block;
}

.metric-box .metric-val {
    font-size: 1.35rem;
    font-weight: 700;
    color: #f8fafc;
    line-height: 1.2;
    margin-bottom: 4px;
}

.metric-box .metric-lbl {
    font-size: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    color: #94a3b8;
    margin: 0;
}

/* Navigation Tabs */
.report-tabs {
    display: flex;
    gap: 8px;
    border-bottom: 1px solid #293647;
    margin-bottom: 22px;
    overflow-x: auto;
    padding-bottom: 2px;
}

.report-tab-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    padding: 10px 18px;
    font-size: 0.9rem;
    font-weight: 600;
    border-bottom: 3px solid transparent;
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
}

.report-tab-btn:hover {
    color: #f8fafc;
}

.report-tab-btn.active {
    color: #3b82f6;
    border-bottom-color: #3b82f6;
}

/* Print Stylesheet */
@media print {
    body { background: #ffffff !important; color: #000000 !important; }
    .no-print, .left-sidebar-pro, .header-top-area, footer, .breadcome-area { display: none !important; }
    .all-content-wrapper { margin-left: 0 !important; padding: 0 !important; }
    .user-hero-card, .metric-box, .p-3, .p-4 { background: #ffffff !important; border: 1px solid #cbd5e1 !important; color: #000000 !important; box-shadow: none !important; }
    .text-white { color: #0f172a !important; }
    .text-muted { color: #475569 !important; }
    table { border: 1px solid #cbd5e1 !important; }
    table th, table td { color: #000000 !important; border: 1px solid #cbd5e1 !important; }
    .badge { border: 1px solid #94a3b8 !important; color: #000000 !important; }
}
</style>

<body>
    <!-- Sidebar -->
    <?php include "sidebar.php"; ?>

    <?php include "mainTopBar.php"; ?>

        <!-- Breadcome Area -->
        <div class="breadcome-area no-print">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                        <div class="breadcome-list" style="background:#152033; border:1px solid #293647; border-radius:10px; padding:14px 20px; margin-top:20px;">
                            <div class="row">
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                    <div class="breadcome-heading">
                                        <h4 style="margin:0; color:#fff; font-size:1.15rem;">
                                            <i class="bi bi-file-earmark-person text-primary me-2"></i>My Performance Report
                                        </h4>
                                    </div>
                                </div>
                                <div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
                                    <ul class="breadcome-menu" style="text-align:right; margin:0; padding:0; list-style:none;">
                                        <li><a href="index.php" style="color:#94a3b8;">Home</a> <span class="bread-slash" style="color:#475569;">/</span></li>
                                        <li><span class="bread-blod" style="color:#38bdf8;">My Report</span></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="container-fluid mb-5">

            <!-- Header & Filter Bar -->
            <div class="my-report-header no-print">
                <h3 class="my-report-title">
                    <i class="bi bi-graph-up-arrow text-primary"></i>
                    Personal Performance Summary
                </h3>

                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!-- Period Filter Dropdown -->
                    <div class="period-filter-wrapper">
                        <label for="reportPeriodFilter" class="text-muted mb-0 font-weight-bold" style="font-size:0.85rem;">
                            <i class="bi bi-calendar-event me-1 text-primary"></i> Period:
                        </label>
                        <select id="reportPeriodFilter" class="period-select" onchange="onPeriodChange()">
                            <option value="all" selected>All Time</option>
                            <option value="yesterday">Yesterday</option>
                            <option value="last_3_days">Last 3 Days</option>
                            <option value="this_week">This Week</option>
                            <option value="last_week">Last Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="yearly">Yearly</option>
                            <option value="today">Today</option>
                        </select>
                    </div>

                    <!-- Print Button -->
                    <button type="button" class="btn btn-outline-primary btn-sm px-3 py-2 font-weight-bold d-inline-flex align-items-center gap-2" onclick="window.print()">
                        <i class="bi bi-printer"></i> Print Report
                    </button>
                </div>
            </div>

            <!-- User Hero Banner -->
            <div class="user-hero-card">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div class="d-flex align-items-center gap-3">
                        <div class="user-avatar-circle">
                            <img src="<?php echo $userPhotoUrl; ?>" alt="<?php echo htmlspecialchars($currentUser['fullName']); ?>" />
                        </div>
                        <div>
                            <h4 class="text-white font-weight-bold mb-1 d-flex align-items-center gap-2" style="font-size:1.35rem;">
                                <span><?php echo htmlspecialchars($currentUser['fullName']); ?></span>
                                <span class="badge bg-dark text-info border border-info border-opacity-25" style="font-size:0.82rem;">ID: #<?php echo $currentUser['id']; ?></span>
                            </h4>
                            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                                <span class="text-muted" style="font-size:0.88rem;">@<?php echo htmlspecialchars($currentUser['username']); ?></span>
                                <span class="badge-role-<?php echo strtolower($currentUser['role']); ?>">
                                    <?php echo htmlspecialchars($currentUserRole); ?>
                                </span>
                                <span class="badge-card-<?php echo strtolower($currentUser['card']); ?>">
                                    <i class="bi bi-award-fill me-1"></i><?php echo htmlspecialchars($currentUser['card']); ?>
                                </span>
                                <span class="badge-category">
                                    Cat: <?php echo htmlspecialchars($currentUser['category']); ?>
                                </span>
                            </div>
                            <div class="d-flex align-items-center gap-4 text-muted flex-wrap" style="font-size:0.83rem;">
                                <span><i class="bi bi-geo-alt me-1 text-primary"></i> Area: <strong class="text-white"><?php echo htmlspecialchars($currentUser['areaName']); ?></strong></span>
                                <span><i class="bi bi-telephone me-1 text-success"></i> Phone: <strong class="text-white"><?php echo htmlspecialchars($currentUser['phoneDisplay']); ?></strong></span>
                                <?php if (!empty($currentUser['date_of_joining'])): ?>
                                <span><i class="bi bi-calendar me-1 text-warning"></i> Joined: <strong class="text-white"><?php echo date('M d, Y', strtotime($currentUser['date_of_joining'])); ?></strong></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Active Filter Display Pill -->
                    <div class="no-print">
                        <span class="badge bg-dark py-2 px-3 border border-secondary text-info" style="font-size:0.85rem;" id="activePeriodLabel">
                            <i class="bi bi-funnel-fill me-1"></i> Showing: All Time
                        </span>
                    </div>
                </div>
            </div>

            <!-- Loader Spinner -->
            <div id="reportLoader" class="text-center py-5">
                <div class="spinner-border text-primary" role="status" style="width: 3.5rem; height: 3.5rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <h5 class="text-muted mt-3">Fetching your performance analytics...</h5>
            </div>

            <!-- Report Content Body -->
            <div id="reportContent" style="display:none;">

                <!-- Score and Quick KPIs Row -->
                <div class="row g-3 mb-4">
                    <!-- Overall Score Box -->
                    <div class="col-lg-3 col-md-4 col-sm-12">
                        <div class="p-4 rounded text-center h-100 d-flex flex-column justify-content-center" style="background:#101726; border:1px solid #293647;">
                            <div class="score-circle-lg" id="overallScoreCircle">
                                <span class="score-num" id="overallScoreVal">0%</span>
                                <small style="font-size:0.75rem; text-transform:uppercase; letter-spacing:1px;">Performance</small>
                            </div>
                            <h5 class="font-weight-bold mb-1" id="overallScoreGrade" style="font-size:1.15rem;">—</h5>
                            <small class="text-muted">Calculated across all activities</small>
                        </div>
                    </div>

                    <!-- 6 Metric Cards (2x3 Grid) -->
                    <div class="col-lg-9 col-md-8 col-sm-12">
                        <div class="row g-3">
                            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="metric-box">
                                    <span class="metric-icon text-success"><i class="bi bi-clock-history"></i></span>
                                    <div class="metric-val text-success" id="kpiNamazRate">0%</div>
                                    <p class="metric-lbl">Namaz (With Jamaat)</p>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="metric-box">
                                    <span class="metric-icon text-primary"><i class="bi bi-book-half"></i></span>
                                    <div class="metric-val text-primary" id="kpiDarsRate">0%</div>
                                    <p class="metric-lbl">Dars Attendance</p>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="metric-box">
                                    <span class="metric-icon text-info"><i class="bi bi-book"></i></span>
                                    <div class="metric-val text-info" id="kpiQuranRate">0%</div>
                                    <p class="metric-lbl">Quran Consistency</p>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="metric-box">
                                    <span class="metric-icon text-warning"><i class="bi bi-check2-circle"></i></span>
                                    <div class="metric-val text-warning" id="kpiTaskRate">0%</div>
                                    <p class="metric-lbl">Task Completion</p>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="metric-box">
                                    <span class="metric-icon" style="color:#c084fc;"><i class="bi bi-stopwatch"></i></span>
                                    <div class="metric-val" style="color:#c084fc;" id="kpiStudyTime">0m</div>
                                    <p class="metric-lbl">Study / Time Tracked</p>
                                </div>
                            </div>
                            <div class="col-lg-4 col-md-6 col-sm-6 col-12">
                                <div class="metric-box">
                                    <span class="metric-icon" style="color:#2dd4bf;"><i class="bi bi-megaphone"></i></span>
                                    <div class="metric-val" style="color:#2dd4bf;" id="kpiDawahCount">0</div>
                                    <p class="metric-lbl">Dawah Outreach</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Navigation Tabs -->
                <div class="report-tabs no-print">
                    <button class="report-tab-btn active" id="tabBtnOverview" onclick="switchTab('overview')">
                        <i class="bi bi-speedometer2"></i> Overview
                    </button>
                    <button class="report-tab-btn" id="tabBtnTasks" onclick="switchTab('tasks')">
                        <i class="bi bi-card-checklist"></i> My Tasks (<span id="tabTaskCount">0</span>)
                    </button>
                    <button class="report-tab-btn" id="tabBtnAttendance" onclick="switchTab('attendance')">
                        <i class="bi bi-calendar-check"></i> Attendance Details
                    </button>
                    <button class="report-tab-btn" id="tabBtnDawahStudy" onclick="switchTab('dawahStudy')">
                        <i class="bi bi-compass"></i> Dawah &amp; Study Log
                    </button>
                </div>

                <!-- ── TAB 1: OVERVIEW ── -->
                <div id="paneOverview" class="tab-pane">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="p-3 rounded mb-3" style="background:#101726; border:1px solid #293647;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-white font-weight-bold"><i class="bi bi-clock-history text-success me-2"></i>Namaz with Jamaat</span>
                                    <span class="text-success font-weight-bold" id="txtNamazJamaat">0 / 0</span>
                                </div>
                                <div class="progress" style="height:10px; background:#1e293b; border-radius:5px;">
                                    <div id="barNamazJamaat" class="progress-bar bg-success" style="width:0%;"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-3 text-muted" style="font-size:0.83rem;">
                                    <span>With Jamaat: <strong class="text-success" id="cntNamazJamaat">0</strong></span>
                                    <span>Without: <strong class="text-info" id="cntNamazNoJamaat">0</strong></span>
                                    <span>Missed: <strong class="text-danger" id="cntNamazMissed">0</strong></span>
                                </div>
                            </div>

                            <div class="p-3 rounded" style="background:#101726; border:1px solid #293647;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-white font-weight-bold"><i class="bi bi-book-half text-primary me-2"></i>Dars Attendance</span>
                                    <span class="text-primary font-weight-bold" id="txtDarsRate">0%</span>
                                </div>
                                <div class="progress" style="height:10px; background:#1e293b; border-radius:5px;">
                                    <div id="barDarsRate" class="progress-bar bg-primary" style="width:0%;"></div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded mb-3" style="background:#101726; border:1px solid #293647;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-white font-weight-bold"><i class="bi bi-card-checklist text-warning me-2"></i>Tasks Completed</span>
                                    <span class="text-warning font-weight-bold" id="txtTaskRate">0%</span>
                                </div>
                                <div class="progress" style="height:10px; background:#1e293b; border-radius:5px;">
                                    <div id="barTaskRate" class="progress-bar bg-warning" style="width:0%;"></div>
                                </div>
                                <div class="d-flex justify-content-between mt-3 text-muted" style="font-size:0.83rem;">
                                    <span>Assigned: <strong class="text-white" id="statTasksAssigned">0</strong></span>
                                    <span>Submitted: <strong class="text-info" id="statTasksSubmitted">0</strong></span>
                                    <span>Approved: <strong class="text-success" id="statTasksApproved">0</strong></span>
                                    <span>Late: <strong class="text-danger" id="statTasksLate">0</strong></span>
                                </div>
                            </div>

                            <div class="p-3 rounded" style="background:#101726; border:1px solid #293647;">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="text-white font-weight-bold"><i class="bi bi-book text-info me-2"></i>Quran Days Present</span>
                                    <span class="text-info font-weight-bold" id="txtQuranRate">0%</span>
                                </div>
                                <div class="progress" style="height:10px; background:#1e293b; border-radius:5px;">
                                    <div id="barQuranRate" class="progress-bar bg-info" style="width:0%;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB 2: TASKS ── -->
                <div id="paneTasks" class="tab-pane" style="display:none;">
                    <div class="table-responsive rounded" style="border:1px solid #293647; background:#101726;">
                        <table class="table table-dark table-hover mb-0" style="font-size:0.86rem; background:transparent;">
                            <thead style="background:#152033; border-bottom:1px solid #293647;">
                                <tr>
                                    <th>Task Code</th>
                                    <th>Title / Description</th>
                                    <th>Submission Date</th>
                                    <th>Status</th>
                                    <th>Timing</th>
                                    <th>Submission Notes</th>
                                </tr>
                            </thead>
                            <tbody id="tblTaskSubmissions">
                                <tr><td colspan="6" class="text-center py-3 text-muted">No tasks found.</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- ── TAB 3: ATTENDANCE ── -->
                <div id="paneAttendance" class="tab-pane" style="display:none;">
                    <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-clock-history text-success me-2"></i>5 Daily Prayers Performance</h6>
                    <div class="table-responsive rounded mb-4" style="border:1px solid #293647; background:#101726;">
                        <table class="table table-dark table-hover mb-0 text-center" style="font-size:0.86rem; background:transparent;">
                            <thead style="background:#152033; border-bottom:1px solid #293647;">
                                <tr>
                                    <th class="text-start ps-3">Prayer</th>
                                    <th class="text-success">With Jamaat</th>
                                    <th class="text-info">Without Jamaat</th>
                                    <th class="text-danger">Missed</th>
                                    <th>Jamaat %</th>
                                </tr>
                            </thead>
                            <tbody id="tblPrayerBreakdown">
                                <tr><td colspan="5" class="text-center py-3 text-muted">No attendance records.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background:#101726; border:1px solid #293647;">
                                <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-book-half text-success me-2"></i>Dars Attendance Summary</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Sessions Held:</span>
                                    <strong class="text-white" id="darsTotalCnt">0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Present (On Time):</span>
                                    <strong class="text-success" id="darsOnTimeCnt">0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Present (Late):</span>
                                    <strong class="text-warning" id="darsLateCnt">0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Absent:</span>
                                    <strong class="text-danger" id="darsAbsentCnt">0</strong>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background:#101726; border:1px solid #293647;">
                                <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-book text-info me-2"></i>Quran Attendance Summary</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Days Tracked:</span>
                                    <strong class="text-white" id="quranTotalCnt">0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Days Present:</span>
                                    <strong class="text-success" id="quranPresentCnt">0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Days Absent:</span>
                                    <strong class="text-danger" id="quranAbsentCnt">0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── TAB 4: DAWAH & STUDY ── -->
                <div id="paneDawahStudy" class="tab-pane" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background:#101726; border:1px solid #293647; height:100%;">
                                <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-megaphone text-teal me-2" style="color:#2dd4bf;"></i>Dawah Outreach Activity</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Dawah Interactions:</span>
                                    <strong class="text-white" id="dwTotalCnt">0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Personal Dawah:</span>
                                    <strong class="text-primary" id="dwPersonalCnt">0</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Collective Dawah:</span>
                                    <strong class="text-info" id="dwCollectiveCnt">0</strong>
                                </div>
                                <div class="d-flex justify-content-between">
                                    <span class="text-muted">Physical vs Online:</span>
                                    <span class="text-white"><span id="dwPhysicalCnt">0</span> Physical / <span id="dwOnlineCnt">0</span> Online</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="p-3 rounded" style="background:#101726; border:1px solid #293647; height:100%;">
                                <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-stopwatch text-purple me-2" style="color:#c084fc;"></i>Study &amp; Time Tracking</h6>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Time Logged:</span>
                                    <strong class="text-white" style="color:#c084fc !important;" id="ttTotalTime">0m</strong>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Total Sessions Completed:</span>
                                    <strong class="text-white" id="ttSessionsCnt">0</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

        </div>

        <!-- Footer -->
        <?php include "footer.php"; ?>

    <!-- Script dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
    var currentUserId = <?php echo (int) $loggedUserId; ?>;

    function switchTab(tabKey) {
        var tabs = ['overview', 'tasks', 'attendance', 'dawahStudy'];
        tabs.forEach(function(t) {
            var btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            var pane = document.getElementById('pane' + t.charAt(0).toUpperCase() + t.slice(1));
            if (btn && pane) {
                if (t === tabKey) {
                    btn.classList.add('active');
                    pane.style.display = '';
                } else {
                    btn.classList.remove('active');
                    pane.style.display = 'none';
                }
            }
        });
    }

    function onPeriodChange() {
        var sel = document.getElementById('reportPeriodFilter');
        var period = sel.value;
        var periodText = sel.options[sel.selectedIndex].text;
        document.getElementById('activePeriodLabel').innerHTML = '<i class="bi bi-funnel-fill me-1"></i> Showing: ' + periodText;
        loadMyReport(period);
    }

    function loadMyReport(period) {
        var loader = document.getElementById('reportLoader');
        var content = document.getElementById('reportContent');

        loader.style.display = '';
        content.style.display = 'none';

        fetch('fetchUserPerformance.php?user_id=' + currentUserId + '&period=' + encodeURIComponent(period))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                loader.style.display = 'none';
                if (!data.success) {
                    alert(data.message || 'Unable to load performance report.');
                    return;
                }
                content.style.display = '';
                renderPerformanceData(data);
            })
            .catch(function(err) {
                loader.style.display = 'none';
                alert('Network error while loading performance data.');
            });
    }

    function renderPerformanceData(data) {
        // Overall Score
        var ov = data.overall || { score: 0, grade: 'Needs Attention', color: '#ef4444' };
        document.getElementById('overallScoreVal').textContent = ov.score + '%';
        document.getElementById('overallScoreGrade').textContent = ov.grade;
        document.getElementById('overallScoreGrade').style.color = ov.color;
        var circle = document.getElementById('overallScoreCircle');
        circle.style.borderColor = ov.color;
        circle.style.color = ov.color;

        // KPI Box values
        document.getElementById('kpiNamazRate').textContent = data.namaz.jamaat_rate + '%';
        document.getElementById('kpiDarsRate').textContent = data.dars.attendance_rate + '%';
        document.getElementById('kpiQuranRate').textContent = data.quran.rate + '%';
        document.getElementById('kpiTaskRate').textContent = data.tasks.completion_rate + '%';
        document.getElementById('kpiStudyTime').textContent = data.time_tracker.formatted_time;
        document.getElementById('kpiDawahCount').textContent = data.dawah.total;

        // Overview Bars
        document.getElementById('barNamazJamaat').style.width = data.namaz.jamaat_rate + '%';
        document.getElementById('txtNamazJamaat').textContent = data.namaz.with_jamaat + ' / ' + data.namaz.total;
        document.getElementById('cntNamazJamaat').textContent = data.namaz.with_jamaat;
        document.getElementById('cntNamazNoJamaat').textContent = data.namaz.without_jamaat;
        document.getElementById('cntNamazMissed').textContent = data.namaz.missed;

        document.getElementById('barDarsRate').style.width = data.dars.attendance_rate + '%';
        document.getElementById('txtDarsRate').textContent = data.dars.attendance_rate + '% (' + data.dars.present + '/' + data.dars.total + ')';

        document.getElementById('barQuranRate').style.width = data.quran.rate + '%';
        document.getElementById('txtQuranRate').textContent = data.quran.rate + '% (' + data.quran.present + '/' + data.quran.total + ')';

        document.getElementById('barTaskRate').style.width = data.tasks.completion_rate + '%';
        document.getElementById('txtTaskRate').textContent = data.tasks.completion_rate + '% (' + data.tasks.submitted_count + '/' + data.tasks.assigned_count + ')';

        // Tasks Tab
        document.getElementById('tabTaskCount').textContent = data.tasks.assigned_count;
        document.getElementById('statTasksAssigned').textContent = data.tasks.assigned_count;
        document.getElementById('statTasksSubmitted').textContent = data.tasks.submitted_count;
        document.getElementById('statTasksApproved').textContent = data.tasks.approved_count;
        document.getElementById('statTasksLate').textContent = data.tasks.late_count;

        var subTbl = document.getElementById('tblTaskSubmissions');
        subTbl.innerHTML = '';
        if (data.tasks.submissions && data.tasks.submissions.length > 0) {
            data.tasks.submissions.forEach(function(sub) {
                var tr = document.createElement('tr');
                var statusBadge = '';
                if (sub.status === 'Approved') {
                    statusBadge = '<span class="badge bg-success">Approved</span>';
                } else if (sub.status === 'Pending') {
                    statusBadge = '<span class="badge bg-warning text-dark">Pending Review</span>';
                } else if (sub.status === 'Not Submitted') {
                    statusBadge = '<span class="badge bg-secondary">Not Submitted</span>';
                } else {
                    statusBadge = '<span class="badge bg-info text-dark">' + (sub.status || 'Assigned') + '</span>';
                }

                var timingBadge = '';
                if (sub.is_late) {
                    timingBadge = '<span class="badge bg-danger ms-1">Overdue / Late</span>';
                } else if (sub.is_submitted) {
                    timingBadge = '<span class="badge bg-success ms-1">On Time</span>';
                } else {
                    timingBadge = '<span class="badge bg-secondary ms-1">Open</span>';
                }

                var dateInfo = '—';
                if (sub.submitted_at) {
                    dateInfo = '<span class="text-success"><i class="bi bi-check-circle me-1"></i>' + new Date(sub.submitted_at).toLocaleDateString() + '</span>';
                } else if (sub.expiry_date) {
                    dateInfo = '<span class="text-warning"><i class="bi bi-clock me-1"></i>Due: ' + new Date(sub.expiry_date).toLocaleDateString() + '</span>';
                }

                tr.innerHTML = '<td><span class="badge bg-primary">' + (sub.task_code || 'T' + sub.task_id) + '</span></td>' +
                               '<td>' + (sub.task_name || sub.description || '—') + '</td>' +
                               '<td>' + dateInfo + '</td>' +
                               '<td>' + statusBadge + '</td>' +
                               '<td>' + timingBadge + '</td>' +
                               '<td><small class="text-muted">' + (sub.submission_notes || '—') + '</small></td>';
                subTbl.appendChild(tr);
            });
        } else {
            subTbl.innerHTML = '<tr><td colspan="6" class="text-center py-3 text-muted">No tasks recorded for this period.</td></tr>';
        }

        // Attendance Tab (Prayers)
        var pTbl = document.getElementById('tblPrayerBreakdown');
        pTbl.innerHTML = '';
        var pNames = ['Fajr', 'Zuhr', 'Asr', 'Maghrib', 'Isha'];
        pNames.forEach(function(p) {
            var pData = data.namaz.prayers[p] || { with_jamaat: 0, without_jamaat: 0, missed: 0 };
            var pTotal = pData.with_jamaat + pData.without_jamaat + pData.missed;
            var pPct = (pData.with_jamaat + pData.without_jamaat) > 0 ? Math.round((pData.with_jamaat / (pData.with_jamaat + pData.without_jamaat)) * 100) : 0;
            var tr = document.createElement('tr');
            tr.innerHTML = '<td class="text-start ps-3 fw-bold">' + p + '</td>' +
                           '<td class="text-success font-weight-bold">' + pData.with_jamaat + '</td>' +
                           '<td class="text-info">' + pData.without_jamaat + '</td>' +
                           '<td class="text-danger">' + pData.missed + '</td>' +
                           '<td><span class="badge bg-secondary">' + pPct + '%</span></td>';
            pTbl.appendChild(tr);
        });

        document.getElementById('darsTotalCnt').textContent = data.dars.total;
        document.getElementById('darsOnTimeCnt').textContent = data.dars.on_time;
        document.getElementById('darsLateCnt').textContent = data.dars.late;
        document.getElementById('darsAbsentCnt').textContent = data.dars.absent;

        document.getElementById('quranTotalCnt').textContent = data.quran.total;
        document.getElementById('quranPresentCnt').textContent = data.quran.present;
        document.getElementById('quranAbsentCnt').textContent = data.quran.absent;

        // Dawah & Study Tab
        document.getElementById('dwTotalCnt').textContent = data.dawah.total;
        document.getElementById('dwPersonalCnt').textContent = data.dawah.personal;
        document.getElementById('dwCollectiveCnt').textContent = data.dawah.collective;
        document.getElementById('dwPhysicalCnt').textContent = data.dawah.physical;
        document.getElementById('dwOnlineCnt').textContent = data.dawah.online;

        document.getElementById('ttTotalTime').textContent = data.time_tracker.formatted_time;
        document.getElementById('ttSessionsCnt').textContent = data.time_tracker.total_sessions;
    }

    // Auto-load on page ready
    document.addEventListener('DOMContentLoaded', function() {
        loadMyReport('all');
    });
    </script>
</body>
</html>
