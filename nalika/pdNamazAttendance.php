<?php
include "connection.php";
include "auth.php";
// auth.php sets: $loggedUserId, $loggedRole, $loggedArea
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
/* ── Controls ── */
.att-top-bar {
    display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 10px;
    margin-bottom: 18px;
}
.att-top-bar h5 { margin: 0; font-size: 1.05rem; font-weight: 700; }

.att-controls-card {
    border: 1px solid #293647; border-radius: 12px;
    padding: 14px 18px; margin-bottom: 18px;
    display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
    background: transparent;
}

/* ── Week nav ── */
.week-nav {
    display: flex; align-items: center; gap: 10px; margin: 0;
}
.week-nav .week-label {
    font-size: .9rem; font-weight: 600;
    min-width: 200px; text-align: center;
}
.week-nav button {
    background: transparent; border: 1.5px solid #293647; border-radius: 8px;
    width: 34px; height: 34px;
    display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: inherit; transition: background .15s;
}
.week-nav button:hover { background: #0d6efd; border-color: #0d6efd; color: #fff; }
.week-nav button:disabled { opacity: .4; cursor: not-allowed; }

/* ── View toggle ── */
.view-toggle {
    display: flex; border: 1.5px solid #293647; border-radius: 9px; overflow: hidden;
}
.view-toggle button {
    background: transparent; border: none; padding: 7px 18px;
    font-size: .82rem; font-weight: 600; color: #6c757d;
    cursor: pointer; transition: background .15s, color .15s;
}
.view-toggle button.active { background: #0d6efd; color: #fff; }

/* ── Mark-all-today ── */
.btn-mark-today {
    background: transparent; border: 1.5px solid #198754; border-radius: 8px;
    color: #198754; padding: 7px 14px; font-size: .82rem; font-weight: 600;
    cursor: pointer; transition: background .15s, color .15s;
    display: flex; align-items: center; gap: 6px;
}
.btn-mark-today:hover { background: #198754; color: #fff; }

/* ── Stats pills ── */
.stats-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.stat-pill {
    display: flex; align-items: center; gap: 8px;
    border: 1px solid #293647; border-radius: 10px; padding: 8px 16px;
    font-size: .82rem;
}
.stat-pill .stat-val { font-size: 1.1rem; font-weight: 700; }
.stat-pill.green .stat-val { color: #198754; }
.stat-pill.yellow .stat-val { color: #ffc107; }
.stat-pill.red .stat-val   { color: #dc3545; }
.stat-pill.blue .stat-val  { color: #0d6efd; }
.stat-pill.gold .stat-val  { color: #fd7e14; }

/* ── Progress bar ── */
.prog-wrap { margin-bottom: 18px; }
.prog-wrap .prog-label {
    display: flex; justify-content: space-between;
    font-size: .78rem; color: #6c757d; margin-bottom: 5px;
}
.prog-wrap .progress { border-radius: 99px; height: 8px; background: #293647; }
.prog-wrap .progress-bar { border-radius: 99px; transition: width .4s; }
.prog-wrap .progress-bar.green  { background: #198754; }
.prog-wrap .progress-bar.yellow { background: #ffc107; }

/* ── Badges ── */
.badges-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
.badge-chip {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 20px; padding: 4px 12px;
    font-size: .75rem; font-weight: 700; border: 1.5px solid;
}

/* ── Weekly table card ── */
.namaz-table-wrap {
    overflow-x: auto; border-radius: 12px;
    border: 1px solid #293647;
}
.namaz-table {
    width: 100%; border-collapse: collapse;
    min-width: 680px;
}
.namaz-table th {
    padding: 10px 14px; font-size: .75rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .5px; text-align: center;
    border-bottom: 1px solid #293647;
}
.namaz-table th.day-col { text-align: left; min-width: 90px; }
.namaz-table td {
    padding: 8px 10px; border-bottom: 1px solid #1a2535; text-align: center;
    vertical-align: middle;
}
.namaz-table td.day-cell {
    text-align: left; font-weight: 700; font-size: .82rem;
    padding-left: 14px; min-width: 90px;
}
.namaz-table tr.today-row td { background: rgba(13,110,253,.06); }
.namaz-table tr:last-child td { border-bottom: none; }
.namaz-table tr.future-row { opacity: .4; pointer-events: none; }

/* ── Prayer status button (cycles on click) ── */
.prayer-btn {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 4px; border-radius: 8px; padding: 5px 10px;
    font-size: .72rem; font-weight: 700; cursor: pointer;
    border: 1.5px solid; transition: all .15s; white-space: nowrap;
    min-width: 90px; user-select: none;
}
.prayer-btn.jamaat {
    background: rgba(25,135,84,.12); border-color: #198754; color: #198754;
}
.prayer-btn.no-jamaat {
    background: rgba(255,193,7,.1); border-color: #ffc107; color: #b08800;
}
.prayer-btn.missed {
    background: rgba(220,53,69,.1); border-color: #dc3545; color: #dc3545;
}
.prayer-btn.unmarked {
    background: transparent; border-color: #293647; color: #6c757d;
}
.prayer-btn.saving { opacity: .5; pointer-events: none; }

/* ── Day summary dot ── */
.day-summary {
    display: flex; gap: 3px; margin-top: 3px; justify-content: flex-start;
}
.dot { width: 7px; height: 7px; border-radius: 50%; }
.dot.j  { background: #198754; }
.dot.nj { background: #ffc107; }
.dot.m  { background: #dc3545; }
.dot.u  { background: #293647; }

/* ── Monthly calendar ── */
.cal-header {
    display: flex; align-items: center; justify-content: space-between; margin-bottom: 12px;
}
.cal-header h6 { margin: 0; font-size: .95rem; font-weight: 700; }
.cal-header button {
    background: transparent; border: 1.5px solid #293647; border-radius: 8px;
    width: 34px; height: 34px; display: flex; align-items: center; justify-content: center;
    cursor: pointer; color: inherit;
}
.cal-header button:hover { background: #0d6efd; border-color: #0d6efd; color: #fff; }
.cal-grid {
    display: grid; grid-template-columns: repeat(7,1fr); gap: 6px; text-align: center;
}
.cal-dow { font-size: .68rem; font-weight: 700; text-transform: uppercase; color: #6c757d; padding: 4px 0; }
.cal-cell {
    aspect-ratio: 1; border-radius: 8px; border: 1.5px solid #293647;
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    font-size: .8rem; font-weight: 600; cursor: pointer; transition: all .15s; gap: 2px;
}
.cal-cell.empty  { border-color: transparent; cursor: default; }
.cal-cell.future { opacity: .35; cursor: not-allowed; pointer-events: none; }
.cal-cell.locked { opacity: .5; cursor: not-allowed; pointer-events: none; }
.cal-cell.cal-complete { border-color: #198754; background: rgba(25,135,84,.1); color: #198754; }
.cal-cell.cal-partial  { border-color: #ffc107; background: rgba(255,193,7,.08); }
.cal-cell.cal-missed   { border-color: #dc3545; background: rgba(220,53,69,.08); }
.cal-cell.cal-today    { box-shadow: 0 0 0 2px #0d6efd; }
.cal-cell .cal-icon    { font-size: .85rem; }
.cal-cell .cal-num     { font-size: .72rem; }

/* ── Streak badge ── */
.streak-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: linear-gradient(135deg,#ffc107,#fd7e14);
    color: #000; border-radius: 20px; padding: 4px 12px;
    font-size: .78rem; font-weight: 700; margin-left: auto;
}

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
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid"><div class="row"><div class="col-lg-12">
            <div class="breadcome-list single-page-breadcome"><div class="row">
                <div class="col-6">
                    <h6 class="mb-0" style="font-size:.9rem;color:#6c757d;">Namaz Attendance</h6>
                </div>
                <div class="col-6">
                    <ul class="breadcome-menu">
                        <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Namaz Attendance</span></li>
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
                        <i class="bi bi-moon-stars-fill me-2" style="color:#ffc107;font-size:1.1rem;"></i>
                        My Namaz Attendance
                    </h5>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <button class="btn-mark-today" onclick="markAllToday()">
                            <i class="bi bi-check-all"></i> Mark All Today (Jamaat)
                        </button>
                        <div class="streak-badge" id="streakBadge" style="display:none;">
                            🔥 <span id="streakCount">0</span>-day streak
                        </div>
                    </div>
                </div>

                <!-- CONTROLS -->
                <div class="att-controls-card">
                    <div style="display:flex;flex-direction:column;gap:4px;">
                        <label style="font-size:.72rem;font-weight:700;color:#6c757d;text-transform:uppercase;letter-spacing:.5px;margin:0;">View</label>
                        <div class="view-toggle">
                            <button class="active" id="btnWeekly" onclick="switchView('weekly')">Weekly</button>
                            <button id="btnMonthly" onclick="switchView('monthly')">Monthly</button>
                        </div>
                    </div>

                    <div class="week-nav" id="weekNavCtrl">
                        <button onclick="shiftWeek(-1)" title="Previous week">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <span class="week-label" id="weekLabel">—</span>
                        <button onclick="shiftWeek(1)" id="btnNextWeek" title="Next week">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                </div>

                <!-- BADGES -->
                <div class="badges-row" id="badgesRow"></div>

                <!-- STATS PILLS -->
                <div class="stats-row">
                    <div class="stat-pill green">
                        <i class="bi bi-check-circle-fill"></i>
                        <div>
                            <div class="stat-val" id="statCompleted">—</div>
                            <div>Prayers This Week</div>
                        </div>
                    </div>
                    <div class="stat-pill yellow">
                        <i class="bi bi-people-fill"></i>
                        <div>
                            <div class="stat-val" id="statJamaat">—</div>
                            <div>With Jamaat</div>
                        </div>
                    </div>
                    <div class="stat-pill blue">
                        <i class="bi bi-percent"></i>
                        <div>
                            <div class="stat-val" id="statJamaatPct">—</div>
                            <div>Jamaat Consistency</div>
                        </div>
                    </div>
                    <div class="stat-pill red">
                        <i class="bi bi-x-circle-fill"></i>
                        <div>
                            <div class="stat-val" id="statMissed">—</div>
                            <div>Missed This Week</div>
                        </div>
                    </div>
                    <div class="stat-pill gold">
                        <i class="bi bi-fire"></i>
                        <div>
                            <div class="stat-val" id="statStreak">—</div>
                            <div>Day Streak</div>
                        </div>
                    </div>
                </div>

                <!-- PROGRESS BARS -->
                <div class="prog-wrap">
                    <div class="prog-label">
                        <span>Weekly Prayer Completion</span>
                        <span id="progPrayerText">0 / 35</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar green" id="progPrayerBar"
                             role="progressbar" style="width:0%"></div>
                    </div>
                </div>
                <div class="prog-wrap">
                    <div class="prog-label">
                        <span>Jamaat Consistency</span>
                        <span id="progJamaatText">0%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar yellow" id="progJamaatBar"
                             role="progressbar" style="width:0%"></div>
                    </div>
                </div>

                <!-- WEEKLY VIEW -->
                <div id="weeklyView">
                    <div class="namaz-table-wrap">
                        <table class="namaz-table">
                            <thead>
                                <tr>
                                    <th class="day-col">Day</th>
                                    <th>🌅 Fajr</th>
                                    <th>☀️ Dhuhr</th>
                                    <th>🌤️ Asr</th>
                                    <th>🌇 Maghrib</th>
                                    <th>🌙 Isha</th>
                                    <th>Summary</th>
                                </tr>
                            </thead>
                            <tbody id="namazTableBody"></tbody>
                        </table>
                    </div>
                </div>

                <!-- MONTHLY VIEW -->
                <div id="monthlyView" style="display:none;">
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
        <i id="toastIcon" class="bi bi-check-circle-fill"></i>
        <span id="toastMsg">Saved</span>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>
    // ── PHP → JS ───────────────────────────────────────────
    const USER_ID      = <?= json_encode($loggedUserId) ?>;
    const AREA_ID      = <?= json_encode($loggedArea)   ?>;
    const MAX_PAST_DAYS = 30;

    // ── Constants ──────────────────────────────────────────
    const PRAYERS    = ['fajr','dhuhr','asr','maghrib','isha'];
    const PRAYER_LABELS = {
        fajr:'Fajr', dhuhr:'Dhuhr', asr:'Asr', maghrib:'Maghrib', isha:'Isha'
    };
    const STATUSES   = ['with_jamaat','without_jamaat','missed']; // cycle order
    const DAY_NAMES  = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

    // ── State ──────────────────────────────────────────────
    // namazMap[dateStr][prayer] = 'with_jamaat'|'without_jamaat'|'missed'|null
    let namazMap   = {};
    let weekOffset = 0;
    let monthOffset= 0;
    let currentView= 'weekly';

    // ── Helpers ────────────────────────────────────────────
    function today()    { return fmtDate(new Date()); }
    function fmtDate(d) { return d.toISOString().slice(0,10); }

    function getWeekDates(offset=0) {
        const now = new Date();
        const dow = (now.getDay() + 6) % 7;
        const mon = new Date(now);
        mon.setDate(now.getDate() - dow + offset * 7);
        return Array.from({length:7}, (_,i) => {
            const d = new Date(mon);
            d.setDate(mon.getDate() + i);
            return fmtDate(d);
        });
    }

    function isFuture(ds) { return ds > today(); }
    function isLocked(ds) {
        return (new Date(today()) - new Date(ds)) / 86400000 > MAX_PAST_DAYS;
    }

    function getStatus(dateStr, prayer) {
        return (namazMap[dateStr] && namazMap[dateStr][prayer]) || null;
    }

    // ── Load from backend ──────────────────────────────────
    async function loadNamaz(from, to) {
        try {
            const res  = await fetch(`fetchNamazAttendance.php?user_id=${USER_ID}&from=${from}&to=${to}`);
            if (!res.ok) throw new Error('Network error');
            const rows = await res.json();
            // rows = [{attendance_date, prayer_name, status}, ...]
            rows.forEach(r => {
                if (!namazMap[r.attendance_date]) namazMap[r.attendance_date] = {};
                namazMap[r.attendance_date][r.prayer_name] = r.status;
            });
        } catch(e) { console.error('Load error:', e); }
    }

    // ── Auto-save ──────────────────────────────────────────
    async function saveOnePrayer(dateStr, prayer, status) {
        const btn = document.getElementById(`btn-${dateStr}-${prayer}`);
        if (btn) btn.classList.add('saving');

        try {
            const res = await fetch('saveNamazAttendance.php', {
                method : 'POST',
                headers: {'Content-Type':'application/json'},
                body   : JSON.stringify({
                    user_id        : USER_ID,
                    area_id        : AREA_ID,
                    attendance_date: dateStr,
                    prayer_name    : prayer,
                    status         : status
                })
            });
            if (!res.ok) throw new Error('Save failed');
            const json = await res.json();
            if (!json.success) throw new Error(json.message ?? 'Error');
            showToast(`${PRAYER_LABELS[prayer]} → ${statusLabel(status)}`, 'success');
        } catch(e) {
            showToast(e.message, 'error');
            // rollback
            if (!namazMap[dateStr]) namazMap[dateStr] = {};
            renderCurrentView();
        } finally {
            if (btn) btn.classList.remove('saving');
        }
    }

    // ── Cycle status on click ──────────────────────────────
    function cycleStatus(dateStr, prayer) {
        if (isFuture(dateStr)) { showToast('Cannot mark future dates.','error'); return; }
        if (isLocked(dateStr)) { showToast('This date is locked (too old).','error'); return; }

        if (!namazMap[dateStr]) namazMap[dateStr] = {};
        const cur  = namazMap[dateStr][prayer] || null;
        const idx  = cur ? STATUSES.indexOf(cur) : -1;
        const next = STATUSES[(idx + 1) % STATUSES.length];
        namazMap[dateStr][prayer] = next;

        renderCurrentView();
        updateStats();
        saveOnePrayer(dateStr, prayer, next);
    }

    // ── Mark all today with Jamaat ─────────────────────────
    function markAllToday() {
        const t = today();
        if (!namazMap[t]) namazMap[t] = {};
        PRAYERS.forEach(p => { namazMap[t][p] = 'with_jamaat'; });
        renderCurrentView();
        updateStats();
        PRAYERS.forEach(p => saveOnePrayer(t, p, 'with_jamaat'));
        showToast('All prayers marked With Jamaat for today!', 'success');
    }

    // ── Status helpers ─────────────────────────────────────
    function statusLabel(s) {
        return s === 'with_jamaat'    ? 'With Jamaat'
             : s === 'without_jamaat' ? 'No Jamaat'
             : s === 'missed'         ? 'Missed'
             : 'Unmarked';
    }
    function statusClass(s) {
        return s === 'with_jamaat'    ? 'jamaat'
             : s === 'without_jamaat' ? 'no-jamaat'
             : s === 'missed'         ? 'missed'
             : 'unmarked';
    }
    function statusIcon(s) {
        return s === 'with_jamaat'    ? '<i class="bi bi-people-fill"></i>'
             : s === 'without_jamaat' ? '<i class="bi bi-person-fill"></i>'
             : s === 'missed'         ? '<i class="bi bi-x-lg"></i>'
             : '<i class="bi bi-dash"></i>';
    }

    // ── Weekly render ──────────────────────────────────────
    function renderWeekly() {
        const dates    = getWeekDates(weekOffset);
        const todayStr = today();

        const fmt = d => new Date(d).toLocaleDateString('en-GB',{day:'numeric',month:'short'});
        document.getElementById('weekLabel').textContent = `${fmt(dates[0])} – ${fmt(dates[6])}`;
        document.getElementById('btnNextWeek').disabled =
            new Date(dates[6]) >= new Date(todayStr);

        let html = '';
        dates.forEach((dateStr, i) => {
            const future  = isFuture(dateStr);
            const locked  = isLocked(dateStr);
            const isToday = dateStr === todayStr;
            const rowClass= future ? 'future-row' : isToday ? 'today-row' : '';

            // Summary dots
            const dots = PRAYERS.map(p => {
                const s = getStatus(dateStr, p);
                const c = s === 'with_jamaat' ? 'j' : s === 'without_jamaat' ? 'nj' : s === 'missed' ? 'm' : 'u';
                return `<div class="dot ${c}" title="${PRAYER_LABELS[p]}: ${statusLabel(s)}"></div>`;
            }).join('');

            // Prayer buttons
            const prayerBtns = PRAYERS.map(p => {
                const s   = getStatus(dateStr, p);
                const cls = statusClass(s);
                const ico = statusIcon(s);
                const lbl = statusLabel(s);
                const clickable = !future && !locked;
                return `<td>
                    <button class="prayer-btn ${cls}" id="btn-${dateStr}-${p}"
                        ${clickable ? `onclick="cycleStatus('${dateStr}','${p}')"` : 'disabled'}
                        title="Click to cycle: With Jamaat → No Jamaat → Missed">
                        ${ico} ${lbl}
                    </button>
                </td>`;
            }).join('');

            const dayLabel = `<strong>${DAY_NAMES[i]}</strong><br>
                <small style="color:#6c757d;font-size:.7rem;">${fmt(dateStr)}</small>
                ${isToday ? '<br><span style="font-size:.65rem;color:#0d6efd;font-weight:700;">TODAY</span>' : ''}`;

            html += `<tr class="${rowClass}">
                <td class="day-cell">${dayLabel}</td>
                ${prayerBtns}
                <td>
                    <div class="day-summary">${dots}</div>
                </td>
            </tr>`;
        });

        document.getElementById('namazTableBody').innerHTML = html;
    }

    // ── Monthly render ─────────────────────────────────────
    function renderMonthly() {
        const now  = new Date();
        const base = new Date(now.getFullYear(), now.getMonth() + monthOffset, 1);
        const year = base.getFullYear(), month = base.getMonth();

        document.getElementById('calMonthLabel').textContent =
            base.toLocaleDateString('en-GB',{month:'long',year:'numeric'});

        const daysInMonth = new Date(year, month+1, 0).getDate();
        const firstDow    = (new Date(year, month, 1).getDay() + 6) % 7;
        const todayStr    = today();

        let html = DAY_NAMES.map(d => `<div class="cal-dow">${d}</div>`).join('');

        for (let i=0; i<firstDow; i++) html += `<div class="cal-cell empty"></div>`;

        for (let day=1; day<=daysInMonth; day++) {
            const dateStr = `${year}-${String(month+1).padStart(2,'0')}-${String(day).padStart(2,'0')}`;
            const future  = isFuture(dateStr);
            const locked  = isLocked(dateStr);
            const isToday = dateStr === todayStr;
            const dayData = namazMap[dateStr] || {};

            // Summarise day
            let j=0, nj=0, m=0, total=0;
            PRAYERS.forEach(p => {
                const s = dayData[p];
                if (s==='with_jamaat')    { j++;  total++; }
                if (s==='without_jamaat') { nj++; total++; }
                if (s==='missed')         { m++;  total++; }
            });

            let calClass = 'cal-none';
            let icon = '';
            if (total === 5 && m === 0)      { calClass='cal-complete'; icon='✔️'; }
            else if (total > 0 && m < 5)     { calClass='cal-partial';  icon='⚠️'; }
            else if (m === 5)                { calClass='cal-missed';   icon='❌'; }

            const classes = ['cal-cell', calClass,
                future ? 'future' : '', locked ? 'locked' : '',
                isToday ? 'cal-today' : ''
            ].filter(Boolean).join(' ');

            const clickable = !future && !locked;
            const onclick   = clickable ? `onclick="openDayDetail('${dateStr}')"` : '';

            html += `<div class="${classes}" ${onclick} title="${dateStr}">
                <div class="cal-icon">${icon}</div>
                <div class="cal-num">${day}</div>
            </div>`;
        }

        document.getElementById('calGrid').innerHTML = html;
    }

    // Clicking a monthly day switches to weekly view at that week
    function openDayDetail(dateStr) {
        const now  = new Date();
        const d    = new Date(dateStr);
        const diffDays = Math.round((d - now) / 86400000);
        weekOffset = Math.floor(diffDays / 7);
        if (weekOffset > 0) weekOffset = 0;
        const dates = getWeekDates(weekOffset);
        loadNamaz(dates[0], dates[6]).then(() => {
            switchView('weekly');
            updateStats();
        });
    }

    // ── Stats & badges ─────────────────────────────────────
    function updateStats() {
        const weekDates = getWeekDates(0); // always stats for current week
        let total=0, jam=0, noJam=0, missed=0;

        weekDates.forEach(ds => {
            PRAYERS.forEach(p => {
                const s = getStatus(ds, p);
                if (s==='with_jamaat')    { total++; jam++;   }
                if (s==='without_jamaat') { total++; noJam++; }
                if (s==='missed')         { total++; missed++;}
            });
        });

        const pct     = total > 0 ? Math.round((total - missed) / 35 * 100) : 0;
        const jamPct  = (jam + noJam) > 0 ? Math.round(jam / (jam+noJam) * 100) : 0;
        const prayers = total - missed;

        document.getElementById('statCompleted').textContent = prayers;
        document.getElementById('statJamaat').textContent    = jam;
        document.getElementById('statJamaatPct').textContent = jamPct + '%';
        document.getElementById('statMissed').textContent    = missed;

        // Progress bars
        document.getElementById('progPrayerBar').style.width  = pct + '%';
        document.getElementById('progPrayerText').textContent = `${prayers} / 35`;
        document.getElementById('progJamaatBar').style.width  = jamPct + '%';
        document.getElementById('progJamaatText').textContent = jamPct + '%';

        // Streak (consecutive fully-completed days going back from today)
        let streak = 0;
        const check = new Date();
        for (let i=0; i<60; i++) {
            const ds  = fmtDate(check);
            const day = namazMap[ds] || {};
            const allDone = PRAYERS.every(p => day[p] && day[p] !== 'missed');
            if (allDone) { streak++; check.setDate(check.getDate()-1); }
            else break;
        }
        document.getElementById('statStreak').textContent = streak;
        const sb = document.getElementById('streakBadge');
        document.getElementById('streakCount').textContent = streak;
        sb.style.display = streak >= 3 ? 'inline-flex' : 'none';

        // Badges
        renderBadges({ prayers, jam, noJam, missed, streak, pct, jamPct });
    }

 function renderBadges({ prayers, jamPct, fajrIshaDays, fajrIshaJamaatDays }) {
    const earned = [];

    // 👑 Jamaat Champion (Top Priority)
    if (jamPct === 100 && prayers >= 20) {
        earned.push(['👑','Jamaat Champion','#6f42c1','#fff']);
    }
    // 🕌 90% Jamaat
    else if (jamPct >= 90 && prayers >= 20) {
        earned.push(['🕌','Jamaat Consistency','#0d6efd','#fff']);
    }

    // 🌅🌙 Fajr & Isha
    if (fajrIshaJamaatDays >= 3) {
        earned.push(['🌅🌙','Strong Discipline','#198754','#fff']);
    }
    else if (fajrIshaDays >= 3) {
        earned.push(['🌅🌙','Basic Discipline','#ffc107','#000']);
    }

    const row = document.getElementById('badgesRow');

    if (earned.length === 0) {
        row.innerHTML = '';
        return;
    }

    row.innerHTML = earned.map(([icon, label, bg, col]) =>
        `<span class="badge-chip" style="background:${bg}20;border-color:${bg};color:${col === '#fff' ? bg : col};">
            ${icon} ${label}
        </span>`
    ).join('');
}
    // ── View switch ────────────────────────────────────────
    function switchView(v) {
        currentView = v;
        document.getElementById('btnWeekly').classList.toggle('active', v==='weekly');
        document.getElementById('btnMonthly').classList.toggle('active', v==='monthly');
        document.getElementById('weeklyView').style.display  = v==='weekly'  ? '' : 'none';
        document.getElementById('monthlyView').style.display = v==='monthly' ? '' : 'none';
        document.getElementById('weekNavCtrl').style.display = v==='weekly'  ? 'flex' : 'none';
        if (v==='monthly') renderMonthly();
        else renderWeekly();
    }

    function renderCurrentView() {
        if (currentView === 'weekly') renderWeekly();
        else renderMonthly();
    }

    // ── Week / month navigation ────────────────────────────
    async function shiftWeek(dir) {
        const next = weekOffset + dir;
        if (next > 0) return;
        weekOffset = next;
        const dates = getWeekDates(weekOffset);
        await loadNamaz(dates[0], dates[6]);
        renderWeekly();
        updateStats();
    }

    function shiftMonth(dir) {
        const now  = new Date();
        const base = new Date(now.getFullYear(), now.getMonth() + monthOffset + dir, 1);
        if (base > now) return;
        monthOffset += dir;
        const year = base.getFullYear(), month = base.getMonth();
        const from = `${year}-${String(month+1).padStart(2,'0')}-01`;
        const last = new Date(year, month+1, 0).getDate();
        const to   = `${year}-${String(month+1).padStart(2,'0')}-${last}`;
        loadNamaz(from, to).then(() => { renderMonthly(); updateStats(); });
    }

    // ── Toast ──────────────────────────────────────────────
    let toastTimer;
    function showToast(msg, type='success') {
        const t  = document.getElementById('attToast');
        const ic = document.getElementById('toastIcon');
        document.getElementById('toastMsg').textContent = msg;
        t.className = `att-toast toast-${type}`;
        ic.className = type === 'success' ? 'bi bi-check-circle-fill'
                     : type === 'error'   ? 'bi bi-exclamation-circle-fill'
                     : 'bi bi-info-circle-fill';
        void t.offsetWidth;
        t.classList.add('show');
        clearTimeout(toastTimer);
        toastTimer = setTimeout(() => t.classList.remove('show'), 2800);
    }

    // ── Init ───────────────────────────────────────────────
    window.addEventListener('DOMContentLoaded', async () => {
        const now  = new Date();
        const year = now.getFullYear(), month = now.getMonth();

        // Load current month + current week
        const from = `${year}-${String(month+1).padStart(2,'0')}-01`;
        const last = new Date(year, month+1, 0).getDate();
        const to   = `${year}-${String(month+1).padStart(2,'0')}-${last}`;
        await loadNamaz(from, to);

        renderWeekly();
        updateStats();
    });
    </script>
</body>
</html>