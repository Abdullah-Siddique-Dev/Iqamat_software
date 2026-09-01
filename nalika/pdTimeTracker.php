<?php
include "connection.php";
include "auth.php";
// auth.php sets: $loggedUserId, $loggedRole, $loggedArea
?>
<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
/* ══════════════════════════════════════════════
   Time Tracker  ·  Styles
══════════════════════════════════════════════ */

/* ── Timer Card ── */
.timer-hero {
    display: flex; flex-direction: column; align-items: center;
    justify-content: center; padding: 40px 24px 32px;
    border: 1px solid #293647; border-radius: 16px;
    margin-bottom: 20px; position: relative;
    background: transparent;
}
.timer-ring-wrap {
    position: relative; width: 200px; height: 200px;
    margin-bottom: 24px;
}
.timer-ring-wrap svg { position: absolute; top:0; left:0; transform: rotate(-90deg); }
.timer-ring-bg   { fill: none; stroke: #293647; stroke-width: 8; }
.timer-ring-prog { fill: none; stroke-width: 8; stroke-linecap: round;
    transition: stroke-dashoffset .5s ease, stroke .5s ease; }
.timer-ring-prog.running { stroke: #198754; }
.timer-ring-prog.stopped { stroke: #0d6efd; }
.timer-ring-prog.done    { stroke: #ffc107; }

.timer-display {
    position: absolute; inset: 0;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center; gap: 2px;
}
.timer-digits {
    font-size: 2.6rem; font-weight: 800; font-variant-numeric: tabular-nums;
    letter-spacing: -1px; line-height: 1;
}
.timer-digits.running { color: #198754; }
.timer-digits.stopped { color: inherit; }
.timer-sub  { font-size: .7rem; color: #6c757d; font-weight: 600; text-transform: uppercase; letter-spacing: .5px; }

/* ── Buttons ── */
.timer-btns { display: flex; gap: 14px; margin-top: 0; }
.btn-start, .btn-stop, .btn-reset {
    display: inline-flex; align-items: center; justify-content: center;
    gap: 8px; border-radius: 12px; padding: 12px 28px;
    font-size: .9rem; font-weight: 700; border: 2px solid;
    cursor: pointer; transition: all .18s; user-select: none;
    min-width: 130px;
}
.btn-start {
    background: rgba(25,135,84,.15); border-color: #198754; color: #198754;
}
.btn-start:hover:not(:disabled) {
    background: #198754; color: #fff;
}
.btn-stop {
    background: rgba(220,53,69,.12); border-color: #dc3545; color: #dc3545;
}
.btn-stop:hover:not(:disabled) {
    background: #dc3545; color: #fff;
}
.btn-reset {
    background: transparent; border-color: #293647; color: #6c757d;
    min-width: auto; padding: 12px 16px;
}
.btn-reset:hover { border-color: #6c757d; color: #fff; }
.btn-start:disabled, .btn-stop:disabled {
    opacity: .35; cursor: not-allowed;
}

/* ── Session badge ── */
.session-info {
    margin-top: 14px; font-size: .8rem; color: #6c757d;
    display: flex; align-items: center; gap: 6px;
}
.session-dot {
    width: 8px; height: 8px; border-radius: 50%;
    background: #6c757d; transition: background .3s;
}
.session-dot.live { background: #198754; animation: pulse-dot 1.2s infinite; }
@keyframes pulse-dot {
    0%, 100% { opacity: 1; } 50% { opacity: .3; }
}

/* ── Stats pills (same as Namaz) ── */
.stats-row { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 18px; }
.stat-pill {
    display: flex; align-items: center; gap: 8px;
    border: 1px solid #293647; border-radius: 10px; padding: 8px 16px;
    font-size: .82rem;
}
.stat-pill .stat-val { font-size: 1.1rem; font-weight: 700; }
.stat-pill.green  .stat-val { color: #198754; }
.stat-pill.yellow .stat-val { color: #ffc107; }
.stat-pill.red    .stat-val { color: #dc3545; }
.stat-pill.blue   .stat-val { color: #0d6efd; }
.stat-pill.gold   .stat-val { color: #fd7e14; }

/* ── Progress bar ── */
.prog-wrap { margin-bottom: 14px; }
.prog-wrap .prog-label {
    display: flex; justify-content: space-between;
    font-size: .78rem; color: #6c757d; margin-bottom: 5px;
}
.prog-wrap .progress { border-radius: 99px; height: 10px; background: #293647; }
.prog-wrap .progress-bar { border-radius: 99px; transition: width .5s ease; }
.prog-wrap .progress-bar.green  { background: #198754; }
.prog-wrap .progress-bar.yellow { background: #ffc107; }
.prog-wrap .progress-bar.blue   { background: #0d6efd; }

/* ── Weekly row ── */
.week-row {
    display: grid; grid-template-columns: repeat(7, 1fr); gap: 8px;
    margin-bottom: 18px;
}
.week-cell {
    display: flex; flex-direction: column; align-items: center;
    gap: 5px; padding: 10px 4px; border-radius: 10px;
    border: 1.5px solid #293647; font-size: .72rem; font-weight: 700;
    text-align: center; transition: all .2s;
}
.week-cell.complete { border-color: #198754; background: rgba(25,135,84,.1); color: #198754; }
.week-cell.partial  { border-color: #ffc107; background: rgba(255,193,7,.08); color: #b08800; }
.week-cell.today    { box-shadow: 0 0 0 2px #0d6efd; }
.week-cell.future   { opacity: .35; }
.week-cell .wc-icon { font-size: 1.1rem; line-height: 1; }
.week-cell .wc-day  { font-size: .65rem; color: #6c757d; margin-top: 1px; }
.week-cell .wc-min  { font-size: .68rem; }

/* ── Badges ── */
.badges-row { display: flex; gap: 8px; flex-wrap: wrap; margin-bottom: 18px; }
.badge-chip {
    display: inline-flex; align-items: center; gap: 5px;
    border-radius: 20px; padding: 4px 12px;
    font-size: .75rem; font-weight: 700; border: 1.5px solid;
}

/* ── Streak badge ── */
.streak-badge {
    display: inline-flex; align-items: center; gap: 5px;
    background: linear-gradient(135deg,#ffc107,#fd7e14);
    color: #000; border-radius: 20px; padding: 4px 12px;
    font-size: .78rem; font-weight: 700;
}

/* ── Controls card ── */
.att-controls-card {
    border: 1px solid #293647; border-radius: 12px;
    padding: 14px 18px; margin-bottom: 18px;
    display: flex; flex-wrap: wrap; gap: 14px; align-items: center;
    background: transparent;
}

/* ── Top bar ── */
.att-top-bar {
    display: flex; align-items: center;
    justify-content: space-between; flex-wrap: wrap; gap: 10px;
    margin-bottom: 18px;
}
.att-top-bar h5 { margin: 0; font-size: 1.05rem; font-weight: 700; }

/* ── Sessions list ── */
.sessions-list { list-style: none; margin: 0; padding: 0; }
.sessions-list li {
    display: flex; justify-content: space-between; align-items: center;
    padding: 8px 14px; border-bottom: 1px solid #1a2535; font-size: .82rem;
}
.sessions-list li:last-child { border-bottom: none; }
.sessions-list .sess-badge {
    background: rgba(25,135,84,.12); color: #198754;
    border: 1px solid #198754; border-radius: 6px;
    padding: 2px 8px; font-size: .72rem; font-weight: 700;
}
.sessions-list .sess-time { color: #6c757d; font-size: .75rem; }

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

/* ── Goal achieved flash ── */
@keyframes goal-flash {
    0%   { box-shadow: 0 0 0 0 rgba(255,193,7,.6); }
    70%  { box-shadow: 0 0 0 16px rgba(255,193,7,0); }
    100% { box-shadow: 0 0 0 0 rgba(255,193,7,0); }
}
.goal-achieved-anim { animation: goal-flash 1s ease 3; }
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid"><div class="row"><div class="col-lg-12">
            <div class="breadcome-list single-page-breadcome"><div class="row">
                <div class="col-6">
                    <h6 class="mb-0" style="font-size:.9rem;color:#6c757d;">Time Tracker</h6>
                </div>
                <div class="col-6">
                    <ul class="breadcome-menu">
                        <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                        <li><span class="bread-blod">Time Tracker</span></li>
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
                        <i class="bi bi-stopwatch-fill me-2" style="color:#0d6efd;font-size:1.1rem;"></i>
                        Daily Focus Tracker
                    </h5>
                    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                        <div class="streak-badge" id="streakBadge" style="display:none;">
                            🔥 <span id="streakCount">0</span>-day streak
                        </div>
                    </div>
                </div>

                <!-- ── TIMER HERO ─────────────────────────── -->
                <div class="timer-hero">
                    <!-- Ring -->
                    <div class="timer-ring-wrap">
                        <svg viewBox="0 0 200 200" width="200" height="200">
                            <circle class="timer-ring-bg"   cx="100" cy="100" r="88"/>
                            <circle class="timer-ring-prog stopped" id="ringProg"
                                    cx="100" cy="100" r="88"
                                    stroke-dasharray="553"
                                    stroke-dashoffset="553"/>
                        </svg>
                        <div class="timer-display">
                            <div class="timer-digits stopped" id="timerDigits">00:00:00</div>
                            <div class="timer-sub"    id="timerSub">Ready</div>
                        </div>
                    </div>

                    <!-- Buttons -->
                    <div class="timer-btns">
                        <button class="btn-start" id="btnStart" onclick="startTimer()">
                            <i class="bi bi-play-fill"></i> Start
                        </button>
                        <button class="btn-stop" id="btnStop" onclick="stopTimer()" disabled>
                            <i class="bi bi-stop-fill"></i> Stop
                        </button>
                        <button class="btn-reset" id="btnReset" onclick="resetDisplay()" title="Reset display">
                            <i class="bi bi-arrow-counterclockwise"></i>
                        </button>
                    </div>

                    <!-- Session pulse -->
                    <div class="session-info" id="sessionInfo">
                        <div class="session-dot" id="sessionDot"></div>
                        <span id="sessionInfoText">No active session</span>
                    </div>
                </div>

                <!-- ── STATS ─────────────────────────────── -->
                <div class="stats-row">
                    <div class="stat-pill green">
                        <i class="bi bi-clock-fill"></i>
                        <div>
                            <div class="stat-val" id="statToday">0 min</div>
                            <div>Today</div>
                        </div>
                    </div>
                    <div class="stat-pill blue">
                        <i class="bi bi-calendar-week"></i>
                        <div>
                            <div class="stat-val" id="statWeek">0 min</div>
                            <div>This Week</div>
                        </div>
                    </div>
                    <div class="stat-pill yellow">
                        <i class="bi bi-trophy-fill"></i>
                        <div>
                            <div class="stat-val" id="statDays">0 / 7</div>
                            <div>Goals This Week</div>
                        </div>
                    </div>
                    <div class="stat-pill gold">
                        <i class="bi bi-fire"></i>
                        <div>
                            <div class="stat-val" id="statStreak">0</div>
                            <div>Day Streak</div>
                        </div>
                    </div>
                </div>

                <!-- ── PROGRESS BARS ──────────────────────── -->
                <div class="prog-wrap">
                    <div class="prog-label">
                        <span>Today's Goal</span>
                        <span id="progTodayText">0 / 60 min</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar green" id="progTodayBar"
                             role="progressbar" style="width:0%"></div>
                    </div>
                </div>
                <div class="prog-wrap">
                    <div class="prog-label">
                        <span>Weekly Completion</span>
                        <span id="progWeekText">0 / 7 days</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar blue" id="progWeekBar"
                             role="progressbar" style="width:0%"></div>
                    </div>
                </div>

                <!-- ── BADGES ────────────────────────────── -->
                <div class="badges-row" id="badgesRow"></div>

                <!-- ── WEEKLY DOTS ───────────────────────── -->
                <div style="font-size:.78rem;font-weight:700;color:#6c757d;text-transform:uppercase;
                            letter-spacing:.5px;margin-bottom:10px;">
                    This Week
                </div>
                <div class="week-row" id="weekRow"></div>

                <!-- ── TODAY'S SESSIONS ──────────────────── -->
                <div style="border:1px solid #293647;border-radius:12px;overflow:hidden;margin-bottom:18px;">
                    <div style="padding:12px 18px;border-bottom:1px solid #293647;
                                font-size:.82rem;font-weight:700;display:flex;
                                align-items:center;justify-content:space-between;">
                        <span><i class="bi bi-list-ul me-2" style="color:#0d6efd;"></i>Today's Sessions</span>
                        <span id="todaySessionCount" style="font-size:.75rem;color:#6c757d;">—</span>
                    </div>
                    <ul class="sessions-list" id="sessionsList">
                        <li style="justify-content:center;color:#6c757d;padding:18px;">
                            No sessions yet today
                        </li>
                    </ul>
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
    const USER_ID = <?= json_encode($loggedUserId) ?>;
    const AREA_ID = <?= json_encode($loggedArea)   ?>;

    // ── Constants ──────────────────────────────────────────
    const GOAL_MINUTES = 60;
    const RING_CIRC    = 553; // 2π × 88
    const DAY_NAMES    = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];

    // ── State ──────────────────────────────────────────────
    let isRunning    = false;
    let sessionId    = null;   // DB session id
    let sessionStart = null;   // Date object (JS)
    let tickInterval = null;
    let dailyData    = {};     // { 'YYYY-MM-DD': { total_minutes, goal_achieved } }
    let todaySessions= [];     // from localStorage for display

    // ── Helpers ────────────────────────────────────────────
    function today()    { return fmtDate(new Date()); }
    function fmtDate(d) { return d.toISOString().slice(0,10); }
    function padZ(n)    { return String(Math.floor(n)).padStart(2,'0'); }

    function fmtDuration(sec) {
        const h = Math.floor(sec / 3600);
        const m = Math.floor((sec % 3600) / 60);
        const s = sec % 60;
        return `${padZ(h)}:${padZ(m)}:${padZ(s)}`;
    }
    function fmtMins(m) {
        if (m >= 60) return `${Math.floor(m)}h ${Math.round((m%1)*60)}m`;
        return `${Math.round(m)} min`;
    }
    function fmtTime(dt) {
        return new Date(dt).toLocaleTimeString('en-GB',{hour:'2-digit',minute:'2-digit'});
    }

    function getWeekDates() {
        const now = new Date();
        const dow = (now.getDay() + 6) % 7;
        const mon = new Date(now);
        mon.setDate(now.getDate() - dow);
        return Array.from({length:7}, (_,i) => {
            const d = new Date(mon);
            d.setDate(mon.getDate() + i);
            return fmtDate(d);
        });
    }

    // ── Ring progress ──────────────────────────────────────
    function setRing(pct, state='stopped') {
        const offset = RING_CIRC - (Math.min(pct, 1) * RING_CIRC);
        const ring   = document.getElementById('ringProg');
        ring.style.strokeDashoffset = offset;
        ring.className = `timer-ring-prog ${state}`;
    }

    // ── Tick (every second) ────────────────────────────────
    function tick() {
        if (!sessionStart) return;
        const elapsed = Math.floor((Date.now() - sessionStart.getTime()) / 1000);
        document.getElementById('timerDigits').textContent = fmtDuration(elapsed);

        // Ring: current session relative to remaining goal
        const todayDone  = (dailyData[today()]?.total_minutes || 0);
        const totalSecs  = todayDone * 60 + elapsed;
        const pct        = totalSecs / (GOAL_MINUTES * 60);
        setRing(pct, 'running');
    }

    // ── Load data from backend ─────────────────────────────
    async function loadData() {
        const dates = getWeekDates();
        const from  = dates[0], to = dates[6];
        try {
            const res  = await fetch(`fetchTimeData.php?user_id=${USER_ID}&from=${from}&to=${to}`);
            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            // Build dailyData map
            json.days.forEach(d => { dailyData[d.date] = d; });

            // Resume open session if any
            if (json.open_session && !isRunning) {
                sessionId    = parseInt(json.open_session.id);
                let rawStart = json.open_session.start_time;
                let isoStr   = (rawStart && rawStart.includes('T')) ? rawStart : (rawStart ? rawStart.replace(' ', 'T') + 'Z' : null);
                let parsedStart = isoStr ? new Date(isoStr) : new Date();
                let elapsedSec  = Math.floor((Date.now() - parsedStart.getTime()) / 1000);
                if (elapsedSec < 0 || elapsedSec > 43200) {
                    parsedStart = new Date();
                }
                sessionStart = parsedStart;
                isRunning    = true;
                startTick();
                setUIRunning(true);
                showToast('Resuming active session…', 'info');
            }

            renderWeekRow();
            updateStats();
            loadTodaySessions();
        } catch(e) {
            console.error('Load error:', e);
        }
    }

    // ── Start ──────────────────────────────────────────────
    async function startTimer() {
        if (isRunning) return;
        document.getElementById('btnStart').disabled = true;

        try {
            const res  = await fetch('startTimeSession.php', {
                method : 'POST',
                headers: {'Content-Type':'application/json'},
                body   : JSON.stringify({ user_id: USER_ID, area_id: AREA_ID })
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            sessionId    = json.session_id;
            sessionStart = new Date();
            isRunning    = true;
            startTick();
            setUIRunning(true);

            // Persist in localStorage for cross-tab resume
            localStorage.setItem('ts_session', JSON.stringify({
                id: sessionId, start: sessionStart.toISOString()
            }));

            // Log locally for session list
            const sessions = getLocalSessions();
            sessions.push({ id: sessionId, start: sessionStart.toISOString(), end: null });
            setLocalSessions(sessions);
            renderSessionsList();

            showToast('Session started!', 'success');
        } catch(e) {
            showToast(e.message || 'Could not start session', 'error');
            document.getElementById('btnStart').disabled = false;
        }
    }

    // ── Stop ───────────────────────────────────────────────
    async function stopTimer() {
        if (!isRunning || !sessionId) return;
        document.getElementById('btnStop').disabled = true;

        stopTick();

        try {
            const res  = await fetch('stopTimeSession.php', {
                method : 'POST',
                headers: {'Content-Type':'application/json'},
                body   : JSON.stringify({ user_id: USER_ID, session_id: sessionId })
            });
            const json = await res.json();
            if (!json.success) throw new Error(json.message);

            // Update local state
            const prevGoal = dailyData[today()]?.goal_achieved || false;
            dailyData[today()] = {
                date          : today(),
                total_minutes : json.daily_total,
                goal_achieved : json.goal_achieved,
            };

            // Update local session list with end time
            const sessions = getLocalSessions();
            const s = sessions.find(x => x.id === sessionId);
            if (s) s.end = new Date().toISOString();
            setLocalSessions(sessions);

            isRunning    = false;
            sessionStart = null;
            sessionId    = null;
            localStorage.removeItem('ts_session');

            setUIRunning(false);
            renderWeekRow();
            updateStats();
            renderSessionsList();

            // Goal achieved animation
            if (json.goal_achieved && !prevGoal) {
                document.querySelector('.timer-hero').classList.add('goal-achieved-anim');
                setTimeout(() => document.querySelector('.timer-hero').classList.remove('goal-achieved-anim'), 3500);
                showToast('🎉 Daily goal achieved! 60 min done!', 'success');
            } else {
                showToast(`Stopped. Session: ${fmtMins(json.duration_minutes)}`, 'success');
            }

        } catch(e) {
            showToast(e.message || 'Could not stop session', 'error');
            // Re-enable stop so user can retry
            isRunning = true;
            startTick();
            setUIRunning(true);
        }
    }

    function resetDisplay() {
        if (isRunning) { showToast('Stop the timer first.', 'error'); return; }
        document.getElementById('timerDigits').textContent = '00:00:00';
        document.getElementById('timerSub').textContent = 'Ready';
        setRing(0, 'stopped');
    }

    // ── Tick helpers ───────────────────────────────────────
    function startTick() {
        tick();
        tickInterval = setInterval(tick, 1000);
    }
    function stopTick() {
        clearInterval(tickInterval);
        tickInterval = null;
    }

    // ── UI state ───────────────────────────────────────────
    function setUIRunning(running) {
        document.getElementById('btnStart').disabled = running;
        document.getElementById('btnStop').disabled  = !running;
        document.getElementById('timerDigits').className = `timer-digits ${running ? 'running' : 'stopped'}`;
        document.getElementById('timerSub').textContent  = running ? 'Running…' : 'Stopped';
        document.getElementById('sessionDot').className  = `session-dot ${running ? 'live' : ''}`;
        document.getElementById('sessionInfoText').textContent = running
            ? `Started at ${sessionStart ? fmtTime(sessionStart) : '—'}`
            : 'No active session';
        if (!running) setRing(dailyPct(), 'stopped');
    }

    function dailyPct() {
        const m = dailyData[today()]?.total_minutes || 0;
        return m / GOAL_MINUTES;
    }

    // ── Render weekly dots ─────────────────────────────────
    function renderWeekRow() {
        const dates    = getWeekDates();
        const todayStr = today();
        let html = '';

        dates.forEach((ds, i) => {
            const d    = dailyData[ds];
            const mins = d?.total_minutes || 0;
            const done = d?.goal_achieved || false;
            const future = ds > todayStr;
            const isToday = ds === todayStr;

            let cls  = 'week-cell';
            let icon = '○';
            if (future)      { cls += ' future'; icon = '—'; }
            else if (done)   { cls += ' complete'; icon = '✔'; }
            else if (mins>0) { cls += ' partial';  icon = '⏳'; }

            if (isToday) cls += ' today';

            html += `<div class="${cls}">
                <div class="wc-icon">${icon}</div>
                <div class="wc-day">${DAY_NAMES[i]}</div>
                <div class="wc-min">${future ? '' : (mins > 0 ? Math.round(mins)+'m' : '0m')}</div>
            </div>`;
        });

        document.getElementById('weekRow').innerHTML = html;
    }

    // ── Update stats ───────────────────────────────────────
    function updateStats() {
        const dates = getWeekDates();
        const todayMins = dailyData[today()]?.total_minutes || 0;

        let weekTotal = 0, weekDays = 0, streak = 0;
        dates.forEach(ds => {
            const d = dailyData[ds];
            if (d) {
                weekTotal += d.total_minutes || 0;
                if (d.goal_achieved) weekDays++;
            }
        });

        // Streak: consecutive goal days going back from today
        const check = new Date();
        for (let i = 0; i < 60; i++) {
            const ds  = fmtDate(check);
            const done = dailyData[ds]?.goal_achieved || false;
            if (done) { streak++; check.setDate(check.getDate()-1); }
            else break;
        }

        document.getElementById('statToday').textContent  = fmtMins(todayMins);
        document.getElementById('statWeek').textContent   = fmtMins(weekTotal);
        document.getElementById('statDays').textContent   = `${weekDays} / 7`;
        document.getElementById('statStreak').textContent = streak;

        // Progress bars
        const todayPct = Math.min(todayMins / GOAL_MINUTES * 100, 100);
        document.getElementById('progTodayBar').style.width   = todayPct + '%';
        document.getElementById('progTodayText').textContent  = `${Math.round(todayMins)} / ${GOAL_MINUTES} min`;
        document.getElementById('progWeekBar').style.width    = (weekDays / 7 * 100) + '%';
        document.getElementById('progWeekText').textContent   = `${weekDays} / 7 days`;

        // Ring (static, when not running)
        if (!isRunning) setRing(todayPct / 100, todayPct >= 100 ? 'done' : 'stopped');

        // Streak badge
        const sb = document.getElementById('streakBadge');
        document.getElementById('streakCount').textContent = streak;
        sb.style.display = streak >= 3 ? 'inline-flex' : 'none';

        renderBadges({ todayMins, weekDays, streak });
    }

    // ── Badges ─────────────────────────────────────────────
    function renderBadges({ todayMins, weekDays, streak }) {
        const earned = [];

        if (streak >= 7)
            earned.push(['🏆', '7-Day Discipline', '#fd7e14', '#fff']);
        else if (streak >= 3)
            earned.push(['🔥', '3-Day Streak', '#dc3545', '#fff']);

        if (weekDays === 7)
            earned.push(['🌟', 'Perfect Week', '#6f42c1', '#fff']);
        else if (weekDays >= 5)
            earned.push(['💪', 'Strong Week', '#0d6efd', '#fff']);

        if (todayMins >= 60)
            earned.push(['🟢', 'Focused Day', '#198754', '#fff']);

        const row = document.getElementById('badgesRow');
        row.innerHTML = earned.map(([icon, label, bg]) =>
            `<span class="badge-chip" style="background:${bg}20;border-color:${bg};color:${bg};">
                ${icon} ${label}
            </span>`
        ).join('');
    }

    // ── Today's Sessions list ──────────────────────────────
    function getLocalSessions() {
        try { return JSON.parse(localStorage.getItem('ts_today_sessions') || '[]'); }
        catch { return []; }
    }
    function setLocalSessions(arr) {
        localStorage.setItem('ts_today_sessions', JSON.stringify(arr));
    }
    function loadTodaySessions() {
        // Clear stale (yesterday's)
        const sessions = getLocalSessions().filter(s => {
            const d = new Date(s.start);
            return fmtDate(d) === today();
        });
        setLocalSessions(sessions);
        renderSessionsList();
    }
    function renderSessionsList() {
        const sessions = getLocalSessions().filter(s => fmtDate(new Date(s.start)) === today());
        const count    = sessions.length;
        document.getElementById('todaySessionCount').textContent =
            count ? `${count} session${count>1?'s':''}` : '—';

        if (!count) {
            document.getElementById('sessionsList').innerHTML =
                `<li style="justify-content:center;color:#6c757d;padding:18px;">No sessions yet today</li>`;
            return;
        }

        document.getElementById('sessionsList').innerHTML = sessions.map((s,i) => {
            const start = fmtTime(new Date(s.start));
            const end   = s.end ? fmtTime(new Date(s.end)) : '…';
            const dur   = s.end
                ? Math.round((new Date(s.end) - new Date(s.start)) / 60000)
                : null;
            return `<li>
                <span>Session ${i+1} &nbsp;
                    <span class="sess-time">${start} → ${end}</span>
                </span>
                <span class="sess-badge">${dur !== null ? dur + ' min' : '⏱ Live'}</span>
            </li>`;
        }).join('');
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
        toastTimer = setTimeout(() => t.classList.remove('show'), 3000);
    }

    // ── Init ───────────────────────────────────────────────
    window.addEventListener('DOMContentLoaded', async () => {
        // Check localStorage for interrupted session
        try {
            const saved = JSON.parse(localStorage.getItem('ts_session') || 'null');
            if (saved && saved.id && saved.start) {
                sessionId    = saved.id;
                sessionStart = new Date(saved.start);
                isRunning    = true;
            }
        } catch {}

        await loadData();

        // If we detected a local session but server says none open, clear it
        if (isRunning) {
            setUIRunning(true);
            startTick();
        }

        renderWeekRow();
        updateStats();
        loadTodaySessions();
    });
    </script>
</body>
</html>