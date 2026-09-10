<?php
// reports.php
// User Performance Reports Module
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Karachi');
} else {
    @date_default_timezone_set('Asia/Karachi');
}

include "connection.php";
include "auth.php";
// $loggedUserId, $loggedRole, $loggedArea set by auth.php

require_once "permissions.php";
$userRole = strtolower(trim($loggedRole ?? ''));
if (in_array($userRole, ['member', 'trainee', 'representative'])) {
    header("Location: myReport.php");
    exit();
}

if (!hasPermission("reports")) {
    header("Location: dashboard.php");
    exit();
}

$canManage = in_array($userRole, ["md", "dg", "admin", "administrator", "committee"]);

// Fetch all users with resolved area names
$usersQuery = "SELECT u.id, u.firstName, u.lastName, u.username, u.email, u.phone, u.area, u.role, u.card, u.category, u.is_active, u.date_of_joining,
               COALESCE(da.areaName, u.area) AS area_display
               FROM users u
               LEFT JOIN dars_areas da ON (u.area REGEXP '^[0-9]+$' AND da.id = CAST(u.area AS UNSIGNED))
               ORDER BY u.id DESC";
$usersResult = mysqli_query($conn, $usersQuery);

$allUsers = [];
$roleCounts = [
    'total' => 0,
    'member' => 0,
    'committee' => 0,
    'trainee' => 0,
    'representative' => 0,
    'admin' => 0
];

if ($usersResult) {
    while ($row = mysqli_fetch_assoc($usersResult)) {
        $uRole = strtolower(trim($row['role'] ?? 'member'));
        $row['card'] = !empty($row['card']) ? $row['card'] : 'Diamond';
        $row['category'] = !empty($row['category']) ? $row['category'] : 'B';
        $row['fullName'] = trim(($row['firstName'] ?? '') . ' ' . ($row['lastName'] ?? ''));
        $row['areaName'] = !empty($row['area_display']) ? $row['area_display'] : '—';
        $rawPhone = trim((string)($row['phone'] ?? ''));
        $row['phoneDisplay'] = (!empty($rawPhone) && $rawPhone !== '2147483647' && $rawPhone !== '0') ? $rawPhone : '—';

        $allUsers[] = $row;

        $roleCounts['total']++;
        if (isset($roleCounts[$uRole])) {
            $roleCounts[$uRole]++;
        } else {
            $roleCounts['member']++;
        }
    }
}
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
/* ── Reports Theme & Styling ── */
.reports-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    flex-wrap: wrap;
    gap: 14px;
}

.reports-title {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 1.15rem;
    font-weight: 700;
    color: #f8fafc;
    margin: 0;
}

/* ── KPI Stat Cards ── */
.kpi-card {
    background: #101726;
    border: 1px solid #293647;
    border-radius: 12px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: transform 0.2s, border-color 0.2s;
    height: 100%;
}

.kpi-card:hover {
    transform: translateY(-2px);
    border-color: #3b82f6;
}

.kpi-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

.kpi-info h4 {
    margin: 0;
    font-size: 1.45rem;
    font-weight: 800;
    color: #ffffff;
    line-height: 1.2;
}

.kpi-info span {
    font-size: 0.78rem;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    font-weight: 600;
}

/* ── Filter Bar ── */
.filter-bar {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 20px;
    align-items: center;
}

.filter-bar .form-control,
.filter-bar .form-select {
    background: #101726;
    border: 1px solid #293647;
    color: #cbd5e1;
    font-size: 0.85rem;
    border-radius: 8px;
    padding: 8px 14px;
}

.filter-bar .form-control:focus,
.filter-bar .form-select:focus {
    border-color: #0d6efd;
    box-shadow: 0 0 0 2px rgba(13, 110, 253, 0.2);
    outline: none;
}

/* ── Table Styling ── */
.reports-table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.reports-table th {
    background: #101726;
    color: #94a3b8;
    font-size: 0.78rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    padding: 14px 16px;
    border-bottom: 1px solid #293647;
    white-space: nowrap;
}

.reports-table td {
    padding: 14px 16px;
    font-size: 0.85rem;
    color: #cbd5e1;
    border-bottom: 1px solid #233042;
    vertical-align: middle;
    white-space: nowrap;
}

.reports-table tr:hover td {
    background: rgba(30, 41, 59, 0.3);
}

.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%);
    color: #ffffff;
    font-weight: 700;
    font-size: 0.85rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    margin-right: 10px;
    flex-shrink: 0;
}

.badge-card {
    background: #1e293b;
    color: #38bdf8;
    border: 1px solid #334155;
    padding: 4px 12px;
    border-radius: 6px;
    font-size: 0.78rem;
    font-weight: 600;
}

.badge-category {
    background: rgba(139, 92, 246, 0.15);
    color: #a78bfa;
    border: 1px solid rgba(139, 92, 246, 0.3);
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.8rem;
}

.badge-role {
    font-size: 0.75rem;
    font-weight: 600;
    padding: 4px 10px;
    border-radius: 20px;
    text-transform: capitalize;
}
.badge-role-admin { background: rgba(239, 68, 68, 0.15); color: #f87171; border: 1px solid rgba(239, 68, 68, 0.3); }
.badge-role-representative { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
.badge-role-committee { background: rgba(59, 130, 246, 0.15); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.3); }
.badge-role-trainee { background: rgba(168, 85, 247, 0.15); color: #c084fc; border: 1px solid rgba(168, 85, 247, 0.3); }
.badge-role-member { background: rgba(16, 185, 129, 0.15); color: #34d399; border: 1px solid rgba(16, 185, 129, 0.3); }

.btn-view-report {
    background: linear-gradient(135deg, #0d6efd 0%, #0284c7 100%);
    color: #ffffff;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 6px 16px;
    border-radius: 8px;
    border: none;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    transition: all 0.2s ease;
}

.btn-view-report:hover {
    background: linear-gradient(135deg, #0b5ed7 0%, #0369a1 100%);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(13, 110, 253, 0.3);
}

/* ── Modal Performance Tabs & Cards ── */
.modal-perf-header {
    background: #101726;
    padding: 20px 24px;
    border-bottom: 1px solid #293647;
}

.perf-stat-box {
    background: #101726;
    border: 1px solid #293647;
    border-radius: 12px;
    padding: 16px;
    text-align: center;
    height: 100%;
}

.perf-stat-box .val {
    font-size: 1.6rem;
    font-weight: 800;
    line-height: 1.2;
    margin-bottom: 4px;
}

.perf-stat-box .lbl {
    font-size: 0.75rem;
    font-weight: 600;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.perf-tab-btn {
    background: transparent;
    border: none;
    color: #94a3b8;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 10px 18px;
    border-bottom: 2px solid transparent;
    cursor: pointer;
    transition: all 0.2s ease;
}

.perf-tab-btn.active {
    color: #38bdf8;
    border-bottom-color: #38bdf8;
}

.score-circle {
    width: 90px;
    height: 90px;
    border-radius: 50%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    border: 4px solid;
}

@media print {
    body * { visibility: hidden; }
    #userPerformanceModal, #userPerformanceModal * { visibility: visible; }
    #userPerformanceModal { position: absolute; left: 0; top: 0; width: 100%; }
    .no-print { display: none !important; }
}
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="breadcome-list single-page-breadcome">
                        <div class="row">
                            <div class="col-6">
                                <h6 class="mb-0" style="font-size:.9rem;color:#6c757d;">Users &bull; Performance Reports</h6>
                            </div>
                            <div class="col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="dashboard.php">Home</a> <span class="bread-slash">/</span></li>
                                    <li><a href="registeredUsers.php">Users</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Reports</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Start -->
    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <!-- Row 1: KPI Stats -->
            <div class="row mb-4 g-3">
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: rgba(13, 110, 253, 0.15); color: #60a5fa;">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <div class="kpi-info">
                            <h4><?php echo $roleCounts['total']; ?></h4>
                            <span>Total Users</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: rgba(16, 185, 129, 0.15); color: #34d399;">
                            <i class="bi bi-person-check-fill"></i>
                        </div>
                        <div class="kpi-info">
                            <h4><?php echo $roleCounts['member']; ?></h4>
                            <span>Members</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: rgba(59, 130, 246, 0.15); color: #93c5fd;">
                            <i class="bi bi-person-workspace"></i>
                        </div>
                        <div class="kpi-info">
                            <h4><?php echo $roleCounts['committee']; ?></h4>
                            <span>Committee Members</span>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                    <div class="kpi-card">
                        <div class="kpi-icon" style="background: rgba(168, 85, 247, 0.15); color: #c084fc;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div class="kpi-info">
                            <h4><?php echo $roleCounts['trainee']; ?></h4>
                            <span>Trainees</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: Main Users Table -->
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <!-- Header -->
                            <div class="reports-header">
                                <h5 class="reports-title">
                                    <i class="bi bi-file-earmark-bar-graph text-primary fs-5"></i>
                                    <span>User Performance Directory</span>
                                </h5>
                            </div>

                            <!-- Filters -->
                            <div class="filter-bar">
                                <div style="flex: 1 1 260px; min-width: 200px;">
                                    <input type="text" id="searchInput" class="form-control" placeholder="Search by name, username, phone, or area...">
                                </div>
                                <div class="d-flex gap-2 flex-wrap align-items-center ms-auto">
                                    <div>
                                        <select id="roleFilter" class="form-select">
                                            <option value="">All Roles</option>
                                            <option value="member">Member</option>
                                            <option value="committee">Committee</option>
                                            <option value="trainee">Trainee</option>
                                            <option value="representative">Representative</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    <div>
                                        <select id="cardFilter" class="form-select">
                                            <option value="">All Cards</option>
                                            <option value="Diamond">Diamond</option>
                                            <option value="Gold">Gold</option>
                                            <option value="Silver">Silver</option>
                                        </select>
                                    </div>
                                    <div>
                                        <select id="categoryFilter" class="form-select">
                                            <option value="">All Categories</option>
                                            <option value="A">A</option>
                                            <option value="B">B</option>
                                            <option value="C">C</option>
                                            <option value="D">D</option>
                                        </select>
                                    </div>
                                    <div>
                                        <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm d-flex align-items-center gap-1" style="border-color:#293647; color:#cbd5e1; padding:7px 14px; border-radius:8px; white-space:nowrap;" onclick="resetAllFilters()" title="Reset all filters">
                                            <i class="bi bi-arrow-counterclockwise"></i> <span>Reset</span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Active Filters Strip -->
                            <div id="activeFiltersStrip" class="d-flex align-items-center gap-2 flex-wrap mb-3 px-1" style="display:none !important; font-size:0.83rem;">
                                <span class="text-muted"><i class="bi bi-funnel-fill text-primary me-1"></i>Active filters:</span>
                                <span id="activeFilterBadges" class="d-flex gap-2 flex-wrap align-items-center"></span>
                                <a href="javascript:void(0)" onclick="resetAllFilters()" class="text-danger small ms-1 font-weight-bold" style="text-decoration:none;">Clear all</a>
                            </div>
                        </div>

                        <div class="sparkline12-graph">
                            <!-- Table -->
                            <div class="table-responsive">
                                <table class="reports-table" id="usersReportTable">
                                    <thead>
                                        <tr>
                                            <th style="width: 55px; text-align: center;">#</th>
                                            <th>FULL NAME</th>
                                            <th>CARD &amp; CATEGORY</th>
                                            <th>PHONE NUMBER</th>
                                            <th>AREA</th>
                                            <th>ROLE</th>
                                            <th>ACTIONS</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($allUsers)): ?>
                                            <?php foreach ($allUsers as $uIdx => $u): 
                                                $uId = $u['id'];
                                                $fullName = $u['fullName'] ?: ($u['username'] ?: 'User #' . $uId);
                                                $uInitials = strtoupper(substr($u['firstName'] ?? '', 0, 1) . substr($u['lastName'] ?? '', 0, 1));
                                                if (empty(trim($uInitials))) $uInitials = 'U';
                                                $uRole = strtolower(trim($u['role'] ?? 'member'));
                                                $uCard = $u['card'];
                                                $uCat = $u['category'];
                                                $uArea = $u['areaName'];
                                                $uPhone = $u['phoneDisplay'];
                                                $searchIndex = strtolower("{$fullName} {$u['username']} {$u['email']} {$uPhone} {$uArea} {$uRole} {$uCard} {$uCat} id:{$uId} #{$uId}");
                                            ?>
                                                <tr class="user-row"
                                                    data-user-id="<?php echo $uId; ?>"
                                                    data-role="<?php echo htmlspecialchars($uRole); ?>"
                                                    data-card="<?php echo htmlspecialchars(strtolower($uCard)); ?>"
                                                    data-category="<?php echo htmlspecialchars(strtolower($uCat)); ?>"
                                                    data-search="<?php echo htmlspecialchars($searchIndex); ?>">
                                                    <td class="text-center font-weight-bold row-serial-num" style="color: #94a3b8;">
                                                        <?php echo ($uIdx + 1); ?>
                                                    </td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="user-avatar"><?php echo htmlspecialchars($uInitials); ?></div>
                                                            <div style="line-height:1.2;">
                                                                <div class="d-flex align-items-center gap-2">
                                                                    <span class="text-white font-weight-bold"><?php echo htmlspecialchars($fullName); ?></span>
                                                                    <span class="badge bg-dark text-info border border-info border-opacity-25" style="font-size:0.75rem;">ID: #<?php echo $uId; ?></span>
                                                                </div>
                                                                <small class="text-muted">@<?php echo htmlspecialchars($u['username'] ?? ''); ?> &bull; Joined <?php echo !empty($u['date_of_joining']) ? date('M Y', strtotime($u['date_of_joining'])) : '—'; ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge-card me-1"><i class="bi bi-gem me-1"></i><?php echo htmlspecialchars($uCard); ?></span>
                                                        <span class="badge-category"><?php echo htmlspecialchars($uCat); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="text-light"><i class="bi bi-telephone text-muted me-1"></i><?php echo htmlspecialchars($uPhone); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="text-light"><i class="bi bi-geo-alt text-muted me-1"></i><?php echo htmlspecialchars($uArea); ?></span>
                                                    </td>
                                                    <td>
                                                        <span class="badge-role badge-role-<?php echo htmlspecialchars($uRole); ?>">
                                                            <?php echo htmlspecialchars(ucfirst($uRole)); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="btn-view-report" onclick="openPerformanceModal(<?php echo $uId; ?>, '<?php echo htmlspecialchars(addslashes($fullName)); ?>', '<?php echo htmlspecialchars($uCard); ?>', '<?php echo htmlspecialchars($uCat); ?>', '<?php echo htmlspecialchars(ucfirst($uRole)); ?>', '<?php echo htmlspecialchars(addslashes($uArea)); ?>', '<?php echo htmlspecialchars($uPhone); ?>')">
                                                            <i class="bi bi-file-earmark-bar-graph"></i> Report
                                                        </button>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <tr id="noUsersFoundRow" style="display:none;">
                                                <td colspan="7" class="text-center py-4" style="background: rgba(16, 23, 38, 0.4);">
                                                    <i class="bi bi-funnel text-warning" style="font-size: 1.8rem; display:block; margin-bottom:8px;"></i>
                                                    <div class="text-white font-weight-bold mb-1" style="font-size:0.95rem;">No users match your selected filter criteria.</div>
                                                    <button type="button" class="btn btn-sm btn-outline-warning font-weight-bold mt-2" onclick="resetAllFilters()">
                                                        <i class="bi bi-arrow-counterclockwise me-1"></i> Reset Filters
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="7" class="text-center text-muted py-4">No users found in database.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ── INTERACTIVE USER PERFORMANCE MODAL ── -->
    <div class="modal fade" id="userPerformanceModal" tabindex="-1" aria-labelledby="userPerformanceModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-xl">
            <div class="modal-content" style="background:#192436; border:1px solid #293647; color:#fff; box-shadow: 0 10px 40px rgba(0,0,0,0.6);">
                <!-- Modal Header -->
                <div class="modal-perf-header">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div id="modalUserAvatar" class="user-avatar" style="width:48px; height:48px; font-size:1.1rem;">U</div>
                            <div>
                                <h5 class="mb-1 text-white font-weight-bold d-flex align-items-center gap-2">
                                    <span id="modalUserName">User Performance</span>
                                    <span class="badge bg-dark text-info border border-info border-opacity-25" id="modalUserIdBadge" style="font-size:0.8rem;">ID: #</span>
                                </h5>
                                <div class="d-flex align-items-center gap-2 flex-wrap" style="font-size:0.78rem;">
                                    <span class="badge-role" id="modalUserRoleBadge">Member</span>
                                    <span class="badge-card" id="modalUserCardBadge">Diamond</span>
                                    <span class="badge-category" id="modalUserCatBadge">B</span>
                                    <span class="text-muted ms-2"><i class="bi bi-geo-alt me-1"></i><span id="modalUserArea">Area</span></span>
                                    <span class="text-muted ms-1"><i class="bi bi-telephone me-1"></i><span id="modalUserPhone">Phone</span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Filter by Time Period in Modal -->
                        <div class="d-flex align-items-center gap-2 ms-auto no-print">
                            <label class="text-muted small font-weight-bold mb-0 text-uppercase" style="letter-spacing:0.5px;">Period:</label>
                            <select id="modalPeriodFilter" class="form-select form-select-sm" style="background:#101726; border-color:#293647; color:#38bdf8; font-weight:600; min-width:160px;" onchange="onPeriodChange()">
                                <option value="all">All Time</option>
                                <option value="today">Daily / Today</option>
                                <option value="yesterday">Yesterday</option>
                                <option value="last_3_days">Last 3 Days</option>
                                <option value="this_week">This Week</option>
                                <option value="last_week">Last Week</option>
                                <option value="this_month">This Month</option>
                                <option value="last_month">Last Month</option>
                                <option value="yearly">Yearly</option>
                            </select>
                            <button type="button" class="btn btn-outline-secondary btn-sm text-white" onclick="window.print()" title="Print / Export Report">
                                <i class="bi bi-printer"></i>
                            </button>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <div class="d-flex gap-2 mt-3 pt-2 border-top border-secondary-subtle no-print" style="border-color: #293647 !important;">
                        <button class="perf-tab-btn active" id="tabBtnOverview" onclick="switchPerfTab('overview')"><i class="bi bi-speedometer2 me-1"></i> Overview</button>
                        <button class="perf-tab-btn" id="tabBtnTasks" onclick="switchPerfTab('tasks')"><i class="bi bi-card-checklist me-1"></i> Tasks (<span id="tabTaskCount">0</span>)</button>
                        <button class="perf-tab-btn" id="tabBtnAttendance" onclick="switchPerfTab('attendance')"><i class="bi bi-calendar-check me-1"></i> Attendance</button>
                        <button class="perf-tab-btn" id="tabBtnDawahStudy" onclick="switchPerfTab('dawahStudy')"><i class="bi bi-hourglass-split me-1"></i> Dawah &amp; Study</button>
                    </div>
                </div>

                <!-- Modal Body -->
                <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                    <!-- Loading Spinner -->
                    <div id="perfModalLoader" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status"></div>
                        <div class="text-muted small mt-2">Loading performance data...</div>
                    </div>

                    <!-- Main Report Content -->
                    <div id="perfModalContent" style="display:none;">

                        <!-- ── TAB 1: OVERVIEW ── -->
                        <div id="perfTabOverview">
                            <!-- Overall Score Card -->
                            <div class="p-3 mb-4 rounded" style="background:#101726; border:1px solid #293647;">
                                <div class="row align-items-center">
                                    <div class="col-md-3 text-center border-end border-secondary-subtle" style="border-color: #293647 !important;">
                                        <div class="score-circle" id="overallScoreCircle">
                                            <span class="fw-bold" style="font-size:1.6rem;" id="overallScoreVal">0%</span>
                                        </div>
                                        <div class="mt-2 font-weight-bold" id="overallScoreGrade">Rating</div>
                                        <small class="text-muted d-block" style="font-size:0.72rem;">Overall Performance Score</small>
                                    </div>
                                    <div class="col-md-9 mt-3 mt-md-0">
                                        <div class="row g-3">
                                            <div class="col-6 col-sm-4">
                                                <div class="perf-stat-box">
                                                    <div class="val text-primary" id="kpiNamazRate">0%</div>
                                                    <div class="lbl">Namaz Jamaat</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-sm-4">
                                                <div class="perf-stat-box">
                                                    <div class="val text-success" id="kpiDarsRate">0%</div>
                                                    <div class="lbl">Dars Attendance</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-sm-4">
                                                <div class="perf-stat-box">
                                                    <div class="val text-info" id="kpiQuranRate">0%</div>
                                                    <div class="lbl">Quran Attendance</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-sm-4">
                                                <div class="perf-stat-box">
                                                    <div class="val text-warning" id="kpiTaskRate">0%</div>
                                                    <div class="lbl">Task Completion</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-sm-4">
                                                <div class="perf-stat-box">
                                                    <div class="val text-purple" style="color:#c084fc;" id="kpiStudyTime">0m</div>
                                                    <div class="lbl">Study Logged</div>
                                                </div>
                                            </div>
                                            <div class="col-6 col-sm-4">
                                                <div class="perf-stat-box">
                                                    <div class="val text-teal" style="color:#2dd4bf;" id="kpiDawahCount">0</div>
                                                    <div class="lbl">Dawah Contacts</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Visual Progress Breakdown -->
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 rounded" style="background:#101726; border:1px solid #293647; height:100%;">
                                        <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-clock-history text-primary me-2"></i>PRAYER &amp; NAMAZ SUMMARY</h6>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>With Jamaat:</span>
                                                <span id="txtNamazJamaat">0 / 0</span>
                                            </div>
                                            <div class="progress" style="height: 8px; background:#1e293b;">
                                                <div class="progress-bar bg-primary" id="barNamazJamaat" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between gap-2 mt-3 pt-2 border-top border-secondary-subtle" style="border-color:#293647 !important; font-size:0.8rem;">
                                            <div>With Jamaat: <strong class="text-success" id="cntNamazJamaat">0</strong></div>
                                            <div>Without Jamaat: <strong class="text-info" id="cntNamazNoJamaat">0</strong></div>
                                            <div>Missed: <strong class="text-danger" id="cntNamazMissed">0</strong></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="p-3 rounded" style="background:#101726; border:1px solid #293647; height:100%;">
                                        <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-check2-circle text-success me-2"></i>TASKS &amp; DARS ATTENDANCE</h6>
                                        
                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>Dars Attendance:</span>
                                                <span id="txtDarsRate">0%</span>
                                            </div>
                                            <div class="progress" style="height: 8px; background:#1e293b;">
                                                <div class="progress-bar bg-success" id="barDarsRate" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>

                                        <div class="mb-2">
                                            <div class="d-flex justify-content-between small text-muted mb-1">
                                                <span>Task Completion:</span>
                                                <span id="txtTaskRate">0%</span>
                                            </div>
                                            <div class="progress" style="height: 8px; background:#1e293b;">
                                                <div class="progress-bar bg-warning" id="barTaskRate" role="progressbar" style="width: 0%"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ── TAB 2: TASKS ── -->
                        <div id="perfTabTasks" style="display:none;">
                            <div class="row g-3 mb-4">
                                <div class="col-md-3 col-6">
                                    <div class="perf-stat-box">
                                        <div class="val text-white" id="statTasksAssigned">0</div>
                                        <div class="lbl">Assigned Tasks</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="perf-stat-box">
                                        <div class="val text-primary" id="statTasksSubmitted">0</div>
                                        <div class="lbl">Submitted</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="perf-stat-box">
                                        <div class="val text-success" id="statTasksApproved">0</div>
                                        <div class="lbl">Approved</div>
                                    </div>
                                </div>
                                <div class="col-md-3 col-6">
                                    <div class="perf-stat-box">
                                        <div class="val text-danger" id="statTasksLate">0</div>
                                        <div class="lbl">Late Submissions</div>
                                    </div>
                                </div>
                            </div>

                            <h6 class="text-white font-weight-bold mb-2">Recent Task Submissions</h6>
                            <div class="table-responsive rounded border border-secondary-subtle" style="border-color:#293647 !important;">
                                <table class="table table-sm text-light mb-0" style="font-size:0.83rem;">
                                    <thead style="background:#101726; color:#94a3b8;">
                                        <tr>
                                            <th>Code</th>
                                            <th>Task Name</th>
                                            <th>Submitted At</th>
                                            <th>Status</th>
                                            <th>Timing</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblTaskSubmissions">
                                        <!-- Loaded via JS -->
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- ── TAB 3: ATTENDANCE ── -->
                        <div id="perfTabAttendance" style="display:none;">
                            <!-- Prayers Table -->
                            <h6 class="text-white font-weight-bold mb-2"><i class="bi bi-clock-history me-1 text-primary"></i>5 Daily Prayers Breakdown</h6>
                            <div class="table-responsive rounded border border-secondary-subtle mb-4" style="border-color:#293647 !important;">
                                <table class="table table-sm text-light mb-0 text-center" style="font-size:0.83rem;">
                                    <thead style="background:#101726; color:#94a3b8;">
                                        <tr>
                                            <th class="text-start ps-3">Prayer</th>
                                            <th>With Jamaat</th>
                                            <th>Without Jamaat</th>
                                            <th>Missed</th>
                                            <th>Jamaat %</th>
                                        </tr>
                                    </thead>
                                    <tbody id="tblPrayerBreakdown">
                                        <!-- Loaded via JS -->
                                    </tbody>
                                </table>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="p-3 rounded" style="background:#101726; border:1px solid #293647;">
                                        <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-book-half text-success me-2"></i>Dars Attendance</h6>
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
                                        <h6 class="text-white font-weight-bold mb-3"><i class="bi bi-book text-info me-2"></i>Quran Attendance</h6>
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
                        <div id="perfTabDawahStudy" style="display:none;">
                            <div class="row g-3 mb-4">
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

                <div class="modal-footer no-print" style="border-top:1px solid #293647;">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm font-weight-bold" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print Report
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Script dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
    // ── Global Filter & Search Logic ──
    var searchInput = document.getElementById('searchInput');
    var roleFilter = document.getElementById('roleFilter');
    var cardFilter = document.getElementById('cardFilter');
    var categoryFilter = document.getElementById('categoryFilter');
    var userRows = document.querySelectorAll('#usersReportTable tbody tr.user-row');
    var noUsersFoundRow = document.getElementById('noUsersFoundRow');

    function resetAllFilters() {
        if (searchInput) searchInput.value = '';
        if (roleFilter) roleFilter.value = '';
        if (cardFilter) cardFilter.value = '';
        if (categoryFilter) categoryFilter.value = '';
        filterUsersTable();
    }

    function updateActiveChips(searchVal, roleVal, cardVal, catVal) {
        var strip = document.getElementById('activeFiltersStrip');
        var container = document.getElementById('activeFilterBadges');
        if (!strip || !container) return;

        var chips = [];
        if (searchVal) {
            chips.push({ label: 'Search: "' + searchVal + '"', clear: function() { searchInput.value = ''; filterUsersTable(); } });
        }
        if (roleVal) {
            chips.push({ label: 'Role: ' + roleFilter.options[roleFilter.selectedIndex].text, clear: function() { roleFilter.value = ''; filterUsersTable(); } });
        }
        if (cardVal) {
            chips.push({ label: 'Card: ' + cardFilter.value, clear: function() { cardFilter.value = ''; filterUsersTable(); } });
        }
        if (catVal) {
            chips.push({ label: 'Category: ' + categoryFilter.value, clear: function() { categoryFilter.value = ''; filterUsersTable(); } });
        }

        if (chips.length > 0) {
            strip.style.setProperty('display', 'flex', 'important');
            container.innerHTML = '';
            chips.forEach(function(chip) {
                var badge = document.createElement('span');
                badge.className = 'badge bg-secondary d-inline-flex align-items-center gap-1';
                badge.style.cssText = 'background:#1e293b !important; border:1px solid #334155; font-size:0.78rem; padding:4px 8px; color:#cbd5e1;';
                badge.innerHTML = chip.label + ' <a href="javascript:void(0)" class="text-danger ms-1" style="text-decoration:none; font-weight:bold;">&times;</a>';
                badge.querySelector('a').addEventListener('click', function(e) {
                    e.preventDefault();
                    chip.clear();
                });
                container.appendChild(badge);
            });
        } else {
            strip.style.setProperty('display', 'none', 'important');
            container.innerHTML = '';
        }
    }

    function filterUsersTable() {
        var searchVal = searchInput ? searchInput.value.toLowerCase().trim() : '';
        var roleVal = roleFilter ? roleFilter.value.toLowerCase().trim() : '';
        var cardVal = cardFilter ? cardFilter.value.toLowerCase().trim() : '';
        var catVal = categoryFilter ? categoryFilter.value.toLowerCase().trim() : '';

        var visibleCount = 0;
        var serialCounter = 1;

        userRows.forEach(function(row) {
            var searchData = row.getAttribute('data-search') || '';
            var rowRole = row.getAttribute('data-role') || '';
            var rowCard = row.getAttribute('data-card') || '';
            var rowCat = row.getAttribute('data-category') || '';

            var matchesSearch = !searchVal || searchData.includes(searchVal);
            var matchesRole = !roleVal || rowRole === roleVal;
            var matchesCard = !cardVal || rowCard.includes(cardVal);
            var matchesCat = !catVal || rowCat.includes(catVal);

            if (matchesSearch && matchesRole && matchesCard && matchesCat) {
                row.style.display = '';
                var sNum = row.querySelector('.row-serial-num');
                if (sNum) sNum.textContent = serialCounter++;
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        updateActiveChips(searchVal, roleVal, cardVal, catVal);

        if (noUsersFoundRow) {
            noUsersFoundRow.style.display = (visibleCount === 0 && userRows.length > 0) ? '' : 'none';
        }
    }

    if (searchInput) searchInput.addEventListener('keyup', filterUsersTable);
    if (roleFilter) roleFilter.addEventListener('change', filterUsersTable);
    if (cardFilter) cardFilter.addEventListener('change', filterUsersTable);
    if (categoryFilter) categoryFilter.addEventListener('change', filterUsersTable);

    // ── Interactive Performance Report Modal Logic ──
    var activeModalUserId = null;
    var perfModal = null;

    function openPerformanceModal(userId, fullName, card, category, role, area, phone) {
        activeModalUserId = userId;

        document.getElementById('modalUserName').textContent = fullName;
        var idBadge = document.getElementById('modalUserIdBadge');
        if (idBadge) idBadge.textContent = 'ID: #' + userId;
        document.getElementById('modalUserAvatar').textContent = (fullName.charAt(0) || 'U').toUpperCase();
        document.getElementById('modalUserRoleBadge').textContent = role;
        document.getElementById('modalUserRoleBadge').className = 'badge-role badge-role-' + role.toLowerCase();
        document.getElementById('modalUserCardBadge').textContent = card;
        document.getElementById('modalUserCatBadge').textContent = category;
        document.getElementById('modalUserArea').textContent = area;
        document.getElementById('modalUserPhone').textContent = (!phone || phone === '2147483647' || phone === '0') ? '—' : phone;

        // Reset period to 'all'
        document.getElementById('modalPeriodFilter').value = 'all';

        switchPerfTab('overview');

        if (!perfModal) {
            perfModal = new bootstrap.Modal(document.getElementById('userPerformanceModal'));
        }
        perfModal.show();

        loadPerformanceReport(userId, 'all');
    }

    function onPeriodChange() {
        var period = document.getElementById('modalPeriodFilter').value;
        if (activeModalUserId) {
            loadPerformanceReport(activeModalUserId, period);
        }
    }

    function switchPerfTab(tabName) {
        var tabs = ['overview', 'tasks', 'attendance', 'dawahStudy'];
        tabs.forEach(function(t) {
            var btn = document.getElementById('tabBtn' + t.charAt(0).toUpperCase() + t.slice(1));
            var pane = document.getElementById('perfTab' + t.charAt(0).toUpperCase() + t.slice(1));
            if (btn && pane) {
                if (t === tabName) {
                    btn.classList.add('active');
                    pane.style.display = '';
                } else {
                    btn.classList.remove('active');
                    pane.style.display = 'none';
                }
            }
        });
    }

    function loadPerformanceReport(userId, period) {
        var loader = document.getElementById('perfModalLoader');
        var content = document.getElementById('perfModalContent');

        loader.style.display = '';
        content.style.display = 'none';

        fetch('fetchUserPerformance.php?user_id=' + userId + '&period=' + encodeURIComponent(period))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                loader.style.display = 'none';
                if (!data.success) {
                    alert(data.message || 'Failed to load report.');
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
    </script>
    <?php include "footer.php"; ?>
</body>
</html>
