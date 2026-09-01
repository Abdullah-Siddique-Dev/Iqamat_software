<?php
include "connection.php";
include "auth.php";
// $loggedUserId, $loggedRole, $loggedArea set by auth.php
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
/* ── Google Font ── */
@import url('https://fonts.googleapis.com/css2?family=Amiri:wght@400;700&display=swap');

/* ── Layout shells (same as dars page) ── */
.att-top-bar {
    display: flex; align-items: center;
    justify-content: space-between;
    flex-wrap: wrap; gap: 10px; margin-bottom: 18px;
}
.att-top-bar h5 { margin: 0; font-size: 1.05rem; font-weight: 700; }

.att-controls-card {
    background: transparent; border: 1px solid #293647; border-radius: 12px;
    padding: 16px 20px; margin-bottom: 18px;
    display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
}
.ctrl-group { display: flex; flex-direction: column; gap: 5px; }
.ctrl-group label {
    font-size: .72rem; font-weight: 700; color: #6c757d;
    text-transform: uppercase; letter-spacing: .5px; margin: 0;
}
.ctrl-group .form-control,
.ctrl-group .form-select {
    font-size: .88rem; border-radius: 8px; border: 1.5px solid #dee2e6;
    padding: 7px 12px; min-width: 160px;
    transition: border-color .18s, box-shadow .18s;
    background: transparent; color: inherit;
}
.ctrl-group .form-control:focus,
.ctrl-group .form-select:focus {
    border-color: #0d6efd; box-shadow: 0 0 0 2px rgba(13,110,253,.12); outline: none;
}

/* ── View toggle pill ── */
.view-toggle {
    display: flex; border: 1.5px solid #293647; border-radius: 9px; overflow: hidden;
}
.view-toggle button {
    background: transparent; border: none; padding: 7px 18px;
    font-size: .82rem; font-weight: 600; color: #6c757d;
    cursor: pointer; transition: background .15s, color .15s;
}
.view-toggle button.active { background: #0d6efd; color: #fff; }

/* ── Week navigator ── */
.week-nav {
    display: flex; align-items: center; gap: 10px; margin-bottom: 18px;
}
.week-nav .week-label {
    font-size: .9rem; font-weight: 600; min-width: 210px; text-align: center;
}
.week-nav button {
    background: transparent; border: 1.5px solid #293647; border-radius: 8px;
    width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: inherit; transition: background .15s, border-color .15s;
}
.week-nav button:hover { background: #0d6efd; border-color: #0d6efd; color: #fff; }

/* ── Stats pill row ── */
.stats-row {
    display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px;
}
.stat-pill {
    display: flex; align-items: center; gap: 8px;
    border: 1px solid #293647; border-radius: 10px; padding: 8px 16px;
    font-size: .82rem;
}
.stat-pill .stat-val { font-size: 1.1rem; font-weight: 700; }
.stat-pill.green .stat-val { color: #198754; }
.stat-pill.red   .stat-val { color: #dc3545; }
.stat-pill.blue  .stat-val { color: #0d6efd; }
.stat-pill.gold  .stat-val { color: #ffc107; }

/* ── Progress bar ── */
.week-progress-wrap { margin-bottom: 18px; }
.week-progress-wrap .prog-label {
    display: flex; justify-content: space-between;
    font-size: .78rem; color: #6c757d; margin-bottom: 5px;
}
.week-progress-wrap .progress { border-radius: 99px; height: 8px; background: #293647; }
.week-progress-wrap .progress-bar { background: #198754; border-radius: 99px; transition: width .4s; }

/* ── Weekly day cards ── */
.days-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: 10px;
    margin-bottom: 20px;
}
@media (max-width: 768px) {
    .days-grid { grid-template-columns: repeat(4, 1fr); }
}
@media (max-width: 480px) {
    .days-grid { grid-template-columns: repeat(2, 1fr); }
}

.day-card {
    border: 1.5px solid #293647; border-radius: 12px;
    padding: 14px 8px; text-align: center;
    cursor: pointer; transition: transform .15s, border-color .2s, box-shadow .2s;
    position: relative; user-select: none;
}
.day-card:hover:not(.future):not(.locked) { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,.25); }
.day-card.future { opacity: .4; cursor: not-allowed; }
.day-card.locked { opacity: .6; cursor: not-allowed; }

.day-card .day-name {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    letter-spacing: .6px; color: #6c757d; margin-bottom: 6px;
}
.day-card .day-num {
    font-size: 1.35rem; font-weight: 700; line-height: 1;
    margin-bottom: 8px;
}
.day-card .day-status {
    font-size: .7rem; font-weight: 600; border-radius: 6px;
    padding: 2px 8px; display: inline-block;
}

/* Status colour states */
.day-card.status-present { border-color: #198754; background: rgba(25,135,84,.08); }
.day-card.status-present .day-num  { color: #198754; }
.day-card.status-present .day-status { background: rgba(25,135,84,.15); color: #198754; }

.day-card.status-absent { border-color: #dc3545; background: rgba(220,53,69,.07); }
.day-card.status-absent .day-num  { color: #dc3545; }
.day-card.status-absent .day-status { background: rgba(220,53,69,.13); color: #dc3545; }

.day-card.status-none .day-status { background: #293647; color: #6c757d; }

/* Today highlight ring */
.day-card.today { box-shadow: 0 0 0 2px #0d6efd; }
.day-card.today .day-name { color: #0d6efd; }

/* Saving spinner overlay */
.day-card .save-spin {
    display: none; position: absolute; inset: 0; border-radius: 12px;
    background: rgba(0,0,0,.35); align-items: center; justify-content: center;
}
.day-card.saving .save-spin { display: flex; }

/* ── Monthly calendar ── */
#monthCalendar { display: none; }
.cal-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 12px;
}
.cal-header h6 { margin: 0; font-size: .95rem; font-weight: 700; }
.cal-header button {
    background: transparent; border: 1.5px solid #293647; border-radius: 8px;
    width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: inherit; transition: background .15s;
}
.cal-header button:hover { background: #0d6efd; border-color: #0d6efd; color: #fff; }

.cal-grid {
    display: grid; grid-template-columns: repeat(7, 1fr);
    gap: 5px; text-align: center;
}
.cal-grid .cal-dow {
    font-size: .68rem; font-weight: 700; text-transform: uppercase;
    color: #6c757d; padding: 4px 0;
}
.cal-cell {
    aspect-ratio: 1; border-radius: 8px; border: 1.5px solid transparent;
    display: flex; align-items: center; justify-content: center;
    font-size: .83rem; font-weight: 600; cursor: pointer;
    transition: background .15s, border-color .2s;
}
.cal-cell.empty { cursor: default; }
.cal-cell.future { opacity: .35; cursor: not-allowed; }
.cal-cell.locked { opacity: .55; cursor: not-allowed; }
.cal-cell.cal-present { background: rgba(25,135,84,.15); border-color: #198754; color: #198754; }
.cal-cell.cal-absent  { background: rgba(220,53,69,.10); border-color: #dc3545; color: #dc3545; }
.cal-cell.cal-none    { border-color: #293647; color: inherit; }
.cal-cell.cal-none:hover:not(.future):not(.locked) { background: rgba(13,110,253,.1); border-color: #0d6efd; }
.cal-cell.cal-today   { box-shadow: 0 0 0 2px #0d6efd; }

/* ── Toast ── */
.att-toast {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    background: #1a2332; border: 1px solid #293647; border-radius: 10px;
    padding: 12px 20px; font-size: .85rem; font-weight: 500;
    display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 32px rgba(0,0,0,.4);
    transform: translateY(80px); opacity: 0;
    transition: transform .3s cubic-bezier(.22,1,.36,1), opacity .3s;
    pointer-events: none; min-width: 220px;
}
.att-toast.show { transform: translateY(0); opacity: 1; }
.att-toast.toast-success i { color: #198754; }
.att-toast.toast-error   i { color: #dc3545; }
.att-toast.toast-info    i { color: #0d6efd; }

/* ── Streak badge ── */
.streak-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: linear-gradient(135deg,#ffc107,#fd7e14);
    color: #000; border-radius: 20px; padding: 4px 12px;
    font-size: .78rem; font-weight: 700; margin-left: auto;
}
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid"><div class="row"><div class="col-lg-12">
            <div class="breadcome-list single-page-breadcome"><div class="row">
                <div class="col-6">
                    <h6 class="mb-0" style="font-size:.9rem;color:#6c757d;">Quran Attendance</h6>
                </div>
                <div class="col-6">
                    <ul class="breadcome-menu">
                        <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Quran Attendance</span></li>
                    </ul>
                </div>
            </div></div>
        </div></div></div>
    </div>
    </div>

    <div class="static-table-area mg-t-15">
        <div class="container-fluid"><div class="row"><div class="col-lg-12">
            <div class="sparkline12-list mg-b-15"><div class="sparkline12-hd">

                <!-- TOP BAR -->
                <div class="att-top-bar">
                    <h5>
                        <i class="bi bi-book me-2 text-success" style="font-size:larger;"></i>
                        My Quran Attendance
                    </h5>
                    <div class="streak-badge" id="streakBadge" style="display:none;">
                        🔥 <span id="streakCount" style="color: #000;">0</span>-day streak
                    </div>
                </div>

                <!-- CONTROLS -->
                <div class="att-controls-card">
                    <div class="ctrl-group">
                        <label><i class="bi bi-eye me-1"></i>View</label>
                        <div class="view-toggle">
                            <button class="active" id="btnWeekly" onclick="switchView('weekly')">Weekly</button>
                            <!-- <button id="btnMonthly" onclick="switchView('monthly')">Monthly</button> -->
                        </div>
                    </div>

                    <div class="ctrl-group" id="monthPickerWrap" style="display:none;">
                        <label><i class="bi bi-calendar3 me-1"></i>Month</label>
                        <input type="month" id="monthPicker" class="form-control"
                               value="" onchange="renderMonthFromPicker()">
                    </div>

                    <!-- week nav lives here in weekly mode -->
                    <div id="weekNavInline" style="display:flex;align-items:center;gap:10px;">
                        <!-- <label style="font-size:.72rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;margin:0;">
                            <i class="bi bi-calendar-week me-1"></i>Week
                        </label> -->
                        <div class="week-nav" style="margin:0;">
                            <button onclick="shiftWeek(-1)" title="Previous week">
                                <i class="bi bi-chevron-left"></i>
                            </button>
                            <span class="week-label" id="weekLabel">—</span>
                            <button onclick="shiftWeek(1)" title="Next week" id="btnNextWeek">
                                <i class="bi bi-chevron-right"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- STATS ROW -->
                <div class="stats-row" id="statsRow">
                    <div class="stat-pill green">
                        <i class="bi bi-check-circle-fill"></i>
                        <div><div class="stat-val" id="statPresent">—</div><div>This Week Present</div></div>
                    </div>
                    <div class="stat-pill red">
                        <i class="bi bi-x-circle-fill"></i>
                        <div><div class="stat-val" id="statAbsent">—</div><div>This Week Absent</div></div>
                    </div>
                    <div class="stat-pill blue">
                        <i class="bi bi-calendar-check"></i>
                        <div><div class="stat-val" id="statMonthPresent">—</div><div>This Month</div></div>
                    </div>
                    <div class="stat-pill gold">
                        <i class="bi bi-fire"></i>
                        <div><div class="stat-val" id="statStreak">—</div><div>Day Streak</div></div>
                    </div>
                </div>

                <!-- PROGRESS BAR -->
                <div class="week-progress-wrap" id="weekProgressWrap">
                    <div class="prog-label">
                        <span>Weekly Completion</span>
                        <span id="progText">0 / 7</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" id="progressBar" role="progressbar" style="width:0%"></div>
                    </div>
                </div>

                <!-- THRESHOLD NOTICE ALERT (Doc Section 1.2.1) -->
                <div id="quranThresholdAlert" class="alert alert-warning py-2 px-3 mb-3" style="display:none; font-size:.84rem; border-radius:10px; background:rgba(255,193,7,0.12); color:#ffc107; border:1px solid rgba(255,193,7,0.3);">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><strong>Weekly Threshold Reminder:</strong> You have read Quran for <span id="threshDays">0</span> day(s) this week. Aim to maintain at least 4 days of Quran recitation per week.
                </div>

                <!-- WEEKLY VIEW -->
                <div id="weeklyView">
                    <div class="days-grid" id="daysGrid"></div>
                </div>

                <!-- MONTHLY VIEW -->
                <div id="monthCalendar">
                    <div class="cal-header">
                        <button onclick="shiftMonth(-1)"><i class="bi bi-chevron-left"></i></button>
                        <h6 id="calMonthLabel">—</h6>
                        <button onclick="shiftMonth(1)"><i class="bi bi-chevron-right"></i></button>
                    </div>
                    <div class="cal-grid" id="calGrid"></div>
                </div>

            </div></div>
        </div></div></div>
    </div>

<?php
include "footer.php"; ?>

    <!-- Toast -->
    <div class="att-toast" id="attToast">
        <i class="bi bi-check-circle-fill" id="toastIcon"></i>
        <span id="toastMsg">Saved</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
    // ─── Constants injected from PHP ───────────────────────────────────────────
    const USER_ID   = <?= json_encode($loggedUserId) ?>;
    const AREA_ID   = <?= json_encode($loggedArea)   ?>;
    const MAX_PAST_DAYS = 30;   // how many days back user can mark
    const DAYS = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

    // ─── State ─────────────────────────────────────────────────────────────────
    let currentView   = 'weekly';
    let weekOffset    = 0;        // 0 = current week
    let monthOffset   = 0;        // 0 = current month
    let attendanceMap = {};       // { 'YYYY-MM-DD': 'Present'|'Absent'|'none' }
    let statsData     = { weekPresent:0, weekAbsent:0, monthPresent:0, streak:0 };

    // ─── Date helpers ──────────────────────────────────────────────────────────
    function today()   { return fmtDate(new Date()); }
    function fmtDate(d){ return d.toISOString().slice(0,10); }

    function getWeekDates(offset = 0) {
        const now = new Date();
        const dow = (now.getDay() + 6) % 7;          // Mon=0 … Sun=6
        const mon = new Date(now);
        mon.setDate(now.getDate() - dow + offset * 7);
        return Array.from({length:7}, (_,i) => {
            const d = new Date(mon);
            d.setDate(mon.getDate() + i);
            return fmtDate(d);
        });
    }

    function isFuture(dateStr)  { return dateStr > today(); }
    function isLocked(dateStr)  {
        const diff = (new Date(today()) - new Date(dateStr)) / 86400000;
        return diff > MAX_PAST_DAYS;
    }

    // ─── Load attendance from backend ─────────────────────────────────────────
    async function loadAttendance(from, to) {
        try {
            const res  = await fetch(`fetchQuranAttendance.php?user_id=${USER_ID}&from=${from}&to=${to}`);
            if (!res.ok) throw new Error('Network error');
            const data = await res.json();
            // data = [{ attendance_date:'YYYY-MM-DD', status:'Present'|'Absent' }, ...]
            data.forEach(r => { attendanceMap[r.attendance_date] = r.status; });
        } catch(e) {
            console.error(e);
        }
    }

    // ─── Auto-save (toggle) ────────────────────────────────────────────────────
    async function toggleDay(dateStr) {
        if (isFuture(dateStr)) { showToast('Cannot mark future dates.','error'); return; }
        if (isLocked(dateStr)) { showToast('This date is locked (too old).','error'); return; }

        const cur    = attendanceMap[dateStr] ?? 'none';
        const next   = cur === 'Present' ? 'Absent' : 'Present';
        attendanceMap[dateStr] = next;

        // optimistic UI
        renderCurrentView();
        updateStats();

        try {
            const res  = await fetch('saveQuranAttendance.php', {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify({
                    user_id        : USER_ID,
                    area_id        : AREA_ID,
                    attendance_date: dateStr,
                    status         : next
                })
            });
            if (!res.ok) throw new Error('Save failed');
            const json = await res.json();
            if (!json.success) throw new Error(json.message ?? 'Unknown error');
            showToast(`${dateStr} marked as ${next}`, 'success');
        } catch(e) {
            // rollback
            attendanceMap[dateStr] = cur;
            renderCurrentView();
            updateStats();
            showToast(e.message, 'error');
        }
    }

    // ─── Weekly render ─────────────────────────────────────────────────────────
    function renderWeekly() {
        const dates = getWeekDates(weekOffset);
        const todayStr = today();

        // Update week label
        const fmt = d => new Date(d).toLocaleDateString('en-GB',{day:'numeric',month:'short'});
        document.getElementById('weekLabel').textContent =
            `${fmt(dates[0])} – ${fmt(dates[6])}`;

        // Disable next-week btn if it would go into the future
        document.getElementById('btnNextWeek').disabled =
            new Date(dates[6]) >= new Date(todayStr);

        let html = '';
        dates.forEach(dateStr => {
            const d        = new Date(dateStr);
            const dayName  = DAYS[( d.getDay()+6)%7];
            const dayNum   = d.getDate();
            const status   = attendanceMap[dateStr] ?? 'none';
            const future   = isFuture(dateStr);
            const locked   = isLocked(dateStr);
            const isToday  = dateStr === todayStr;

            const classes = [
                'day-card',
                `status-${status}`,
                future ? 'future' : '',
                locked ? 'locked' : '',
                isToday ? 'today' : ''
            ].filter(Boolean).join(' ');

            const statusLabel = status === 'Present' ? '✓ Present'
                              : status === 'Absent'  ? '✗ Absent'
                              : '— Unmarked';

            const clickAttr = (!future && !locked)
                ? `onclick="toggleDay('${dateStr}')"` : '';

            html += `<div class="${classes}" ${clickAttr} title="${dateStr}">
                <div class="day-name">${dayName}</div>
                <div class="day-num">${dayNum}</div>
                <div class="day-status">${statusLabel}</div>
                <div class="save-spin"><span class="spinner-border spinner-border-sm text-light"></span></div>
            </div>`;
        });

        document.getElementById('daysGrid').innerHTML = html;

        // Progress
        const presentCount = dates.filter(d => attendanceMap[d] === 'Present').length;
        const pct = Math.round((presentCount / 7) * 100);
        document.getElementById('progressBar').style.width = pct + '%';
        document.getElementById('progText').textContent = `${presentCount} / 7`;
    }

    // ─── Monthly calendar render ────────────────────────────────────────────────
    function renderMonthly() {
        const now  = new Date();
        const base = new Date(now.getFullYear(), now.getMonth() + monthOffset, 1);
        const year = base.getFullYear(), month = base.getMonth();

        document.getElementById('calMonthLabel').textContent =
            base.toLocaleDateString('en-GB',{month:'long',year:'numeric'});

        // sync month picker
        document.getElementById('monthPicker').value =
            `${year}-${String(month+1).padStart(2,'0')}`;

        const daysInMonth = new Date(year, month+1, 0).getDate();
        const firstDow    = (new Date(year, month, 1).getDay() + 6) % 7; // Mon=0
        const todayStr    = today();

        let html = DAYS.map(d => `<div class="cal-dow">${d}</div>`).join('');

        // blank cells
        for(let i=0;i<firstDow;i++) html += `<div class="cal-cell empty"></div>`;

        for(let day=1; day<=daysInMonth; day++){
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const future  = isFuture(dateStr);
            const locked  = isLocked(dateStr);
            const status  = attendanceMap[dateStr] ?? 'none';
            const isToday = dateStr === todayStr;

            const classes = [
                'cal-cell',
                status === 'Present' ? 'cal-present' : status === 'Absent' ? 'cal-absent' : 'cal-none',
                future ? 'future' : '',
                locked ? 'locked' : '',
                isToday ? 'cal-today' : ''
            ].filter(Boolean).join(' ');

            const clickAttr = (!future && !locked)
                ? `onclick="toggleDay('${dateStr}')"` : '';

            html += `<div class="${classes}" ${clickAttr} title="${dateStr}">${day}</div>`;
        }

        document.getElementById('calGrid').innerHTML = html;
    }

    // ─── Stats update ──────────────────────────────────────────────────────────
    function updateStats() {
        // Week present/absent
        const weekDates = getWeekDates(0);
        const wp = weekDates.filter(d => attendanceMap[d] === 'Present').length;
        const wa = weekDates.filter(d => attendanceMap[d] === 'Absent').length;
        document.getElementById('statPresent').textContent = wp;
        document.getElementById('statAbsent').textContent  = wa;

        // Month present
        const now = new Date();
        const daysInMonth = new Date(now.getFullYear(), now.getMonth()+1, 0).getDate();
        let mp = 0;
        for(let d=1; d<=daysInMonth; d++){
            const key = `${now.getFullYear()}-${String(now.getMonth()+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
            if(attendanceMap[key] === 'Present') mp++;
        }
        document.getElementById('statMonthPresent').textContent = mp;

        // Streak (consecutive present days going backwards from today)
        let streak = 0, check = new Date();
        while(true){
            const key = fmtDate(check);
            if(attendanceMap[key] === 'Present'){ streak++; check.setDate(check.getDate()-1); }
            else break;
        }
        document.getElementById('statStreak').textContent = streak;
        const sb = document.getElementById('streakBadge');
        const sc = document.getElementById('streakCount');
        sc.textContent = streak;
        sb.style.display = streak >= 3 ? 'inline-flex' : 'none';

        // Weekly Threshold Reminder (Doc Section 1.2.1: Alert if < 4 days)
        const alertEl = document.getElementById('quranThresholdAlert');
        if (alertEl) {
            if (wp < 4 && weekOffset === 0) {
                document.getElementById('threshDays').textContent = wp;
                alertEl.style.display = 'block';
            } else {
                alertEl.style.display = 'none';
            }
        }
    }

    // ─── View switch ───────────────────────────────────────────────────────────
    function switchView(v) {
        currentView = v;
        document.getElementById('btnWeekly').classList.toggle('active', v==='weekly');
        document.getElementById('btnMonthly').classList.toggle('active', v==='monthly');
        document.getElementById('weeklyView').style.display   = v==='weekly'  ? '' : 'none';
        document.getElementById('weekNavInline').style.display= v==='weekly'  ? 'flex' : 'none';
        document.getElementById('weekProgressWrap').style.display = v==='weekly' ? '' : 'none';
        document.getElementById('monthCalendar').style.display    = v==='monthly' ? '' : 'none';
        document.getElementById('monthPickerWrap').style.display  = v==='monthly' ? '' : 'none';
        if(v==='monthly') renderMonthly();
        else renderWeekly();
    }

    function renderCurrentView() {
        if(currentView==='weekly') renderWeekly();
        else renderMonthly();
    }

    // ─── Week / Month navigation ───────────────────────────────────────────────
    async function shiftWeek(dir) {
        const next = weekOffset + dir;
        if(next > 0) return;           // can't go to future weeks
        weekOffset = next;
        const dates = getWeekDates(weekOffset);
        await loadAttendance(dates[0], dates[6]);
        renderWeekly();
        updateStats();
    }

    function shiftMonth(dir) {
        const now = new Date();
        const newBase = new Date(now.getFullYear(), now.getMonth() + monthOffset + dir, 1);
        if(newBase > now) return;      // can't go future
        monthOffset += dir;
        const year  = newBase.getFullYear(), month = newBase.getMonth();
        const from  = `${year}-${String(month+1).padStart(2,'0')}-01`;
        const last  = new Date(year, month+1, 0).getDate();
        const to    = `${year}-${String(month+1).padStart(2,'0')}-${last}`;
        loadAttendance(from, to).then(() => { renderMonthly(); updateStats(); });
    }

    function renderMonthFromPicker() {
        const val = document.getElementById('monthPicker').value;
        if(!val) return;
        const [y,m] = val.split('-').map(Number);
        const now   = new Date();
        monthOffset = (y - now.getFullYear())*12 + (m-1 - now.getMonth());
        const from  = `${y}-${String(m).padStart(2,'0')}-01`;
        const last  = new Date(y, m, 0).getDate();
        const to    = `${y}-${String(m).padStart(2,'0')}-${last}`;
        loadAttendance(from, to).then(() => { renderMonthly(); updateStats(); });
    }

    // ─── Toast ─────────────────────────────────────────────────────────────────
    let toastTimer;
    function showToast(msg, type='success') {
        const t  = document.getElementById('attToast');
        const ic = document.getElementById('toastIcon');
        const tx = document.getElementById('toastMsg');
        t.className = `att-toast toast-${type}`;
        ic.className = type==='success' ? 'bi bi-check-circle-fill'
                     : type==='error'   ? 'bi bi-exclamation-circle-fill'
                     :                    'bi bi-info-circle-fill';
        tx.textContent = msg;
        void t.offsetWidth;
        t.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
    }

    // ─── Init ──────────────────────────────────────────────────────────────────
    window.addEventListener('DOMContentLoaded', async () => {
        // pre-load current week + this month
        const now  = new Date();
        const year = now.getFullYear(), month = now.getMonth();
        const from = `${year}-${String(month+1).padStart(2,'0')}-01`;
        const last = new Date(year, month+1, 0).getDate();
        const to   = `${year}-${String(month+1).padStart(2,'0')}-${last}`;
        await loadAttendance(from, to);

        document.getElementById('monthPicker').value =
            `${year}-${String(month+1).padStart(2,'0')}`;

        renderWeekly();
        updateStats();
    });
    </script>
</body>
</html>