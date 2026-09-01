<?php
include "connection.php";
include "auth.php";

if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}

// Get logged-in user's area
$userId   = (int)$_SESSION['user']['id'];
$userArea = (int)($_SESSION['user']['area'] ?? 0);

// ── 1. Dars sessions for user's area only ─────────────────────────────────
$events = [];

if ($userArea) {
    $r = mysqli_query($conn, "
        SELECT areaName, darsType, dayTime, startDate, location
        FROM dars_areas
        WHERE id = $userArea
        LIMIT 1
    ");
    $area = mysqli_fetch_assoc($r);

    if ($area) {
        // Generate next 8 upcoming dars dates based on dayTime + darsType
        $dayMap = [
            'Monday' => 1,
            'Tuesday' => 2,
            'Wednesday' => 3,
            'Thursday' => 4,
            'Friday' => 5,
            'Saturday' => 6,
            'Sunday' => 0
        ];

        // Extract day name from dayTime e.g. "Friday 7:00 PM"
        $parts   = explode(' ', $area['dayTime']);
        $dayName = $parts[0] ?? 'Friday';
        $timeStr = isset($parts[1]) ? $parts[1] . ' ' . $parts[2] : '7:00 PM';
        $targetDow = $dayMap[$dayName] ?? 5;

        // How many days between occurrences
        $interval = 7; // weekly default
        if ($area['darsType'] === 'Monthly')   $interval = 30;
        if ($area['darsType'] === 'Bi-weekly') $interval = 14;

        $now = new DateTime();
        $d   = new DateTime();

        // Find next occurrence of the target day
        $dow = (int)$d->format('w');
        $diff = ($targetDow - $dow + 7) % 7;
        if ($diff === 0) $diff = $interval; // already today → next cycle
        $d->modify("+$diff days");

        for ($i = 0; $i < 8; $i++) {
            $dateStr = $d->format('Y-m-d');
            $events[] = [
                'date'  => $dateStr,
                'time'  => date('g:i A', strtotime($timeStr)),
                'title' => $area['areaName'] . ' — Dars',
                'type'  => 'Dars',
                'loc'   => $area['location'],
                'by'    => $area['contactName'] ?? '',
            ];
            $d->modify("+$interval days");
        }
    }
}

// ── 2. All upcoming Events from events table ───────────────────────────────
$r = mysqli_query($conn, "
    SELECT topic, organisier, dateTime, location, type
    FROM events
    WHERE dateTime >= NOW()
    ORDER BY dateTime ASC
    LIMIT 20
");
while ($row = mysqli_fetch_assoc($r)) {
    $events[] = [
        'date'  => substr($row['dateTime'], 0, 10),
        'time'  => date('g:i A', strtotime($row['dateTime'])),
        'title' => $row['topic'],
        'type'  => $row['type'], // Dars, Workshop, Dawah etc.
        'loc'   => $row['location'],
        'by'    => $row['organisier'],
    ];
}

// ── 3. All upcoming Technical Workshops ───────────────────────────────────
$r = mysqli_query($conn, "
    SELECT topic, organisier, dateTime, location, feeType
    FROM technical_workshops
    WHERE dateTime >= NOW()
    ORDER BY dateTime ASC
    LIMIT 10
");
while ($row = mysqli_fetch_assoc($r)) {
    $events[] = [
        'date'  => substr($row['dateTime'], 0, 10),
        'time'  => date('g:i A', strtotime($row['dateTime'])),
        'title' => $row['topic'],
        'type'  => 'TechWorkshop',
        'loc'   => $row['location'],
        'by'    => $row['organisier'],
        'fee'   => $row['feeType'],
    ];
}

// Sort all events by date
usort($events, fn($a, $b) => strcmp($a['date'], $b['date']));

$eventsJson = json_encode($events);
?>

<!doctype html>
<html lang="en">
<?php include "header.php"; ?>

<style>
    /* Calendar cell (desktop month view) */
    .ev-cal-cell {
        min-height: 82px;
        background: rgba(255, 255, 255, .03);
        border-radius: 8px;
        border: 1px solid rgba(255, 255, 255, .05);
        padding: 7px 8px;
        display: flex;
        flex-direction: column;
        gap: 3px;
        cursor: default;
        transition: border-color .15s;
    }

    .ev-cal-cell.has-ev {
        border-color: rgba(255, 255, 255, .09);
        cursor: pointer;
    }

    .ev-cal-cell.has-ev:hover {
        border-color: rgba(255, 255, 255, .2);
    }

    .ev-cal-cell.ev-today {
        border-color: #0d6efd;
        background: rgba(13, 110, 253, .07);
    }

    .ev-cal-cell.ev-empty {
        background: transparent;
        border-color: transparent;
        cursor: default;
    }

    .ev-cal-num {
        font-size: .75rem;
        font-weight: 600;
        color: rgba(255, 255, 255, .5);
        line-height: 1;
    }

    .ev-cal-cell.ev-today .ev-cal-num {
        color: #0d6efd;
        font-weight: 800;
    }

    .ev-chip-cal {
        border-radius: 4px;
        padding: 2px 5px;
        font-size: .58rem;
        font-weight: 700;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        line-height: 1.4;
    }

    .chip-Dars {
        background: rgba(0, 227, 150, .18);
        color: #00e396;
    }

    .chip-Workshop {
        background: rgba(0, 143, 251, .18);
        color: #74b4ff;
    }

    .chip-TechWorkshop {
        background: rgba(248, 172, 89, .18);
        color: #f8ac59;
    }

    .chip-Dawah {
        background: rgba(119, 93, 208, .18);
        color: #b08ef0;
    }

    .chip-Event {
        background: rgba(254, 176, 25, .18);
        color: #feb019;
    }

    /* List item */
    .ev-list-item {
        display: flex;
        align-items: flex-start;
        gap: 12px;
        padding: 13px 15px;
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .06);
        border-radius: 10px;
        transition: border-color .15s;
    }

    .ev-list-item:hover {
        border-color: rgba(255, 255, 255, .15);
    }

    .ev-date-box {
        text-align: center;
        flex-shrink: 0;
        width: 46px;
        padding: 7px 4px;
        background: #1e3154;
        border-radius: 9px;
    }

    .ev-date-day {
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1;
    }

    .ev-date-mon {
        font-size: .58rem;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: rgba(255, 255, 255, .4);
        margin-top: 2px;
    }

    .ev-item-title {
        font-size: .83rem;
        font-weight: 600;
        color: rgba(255, 255, 255, .88);
        margin-bottom: 3px;
        line-height: 1.3;
    }

    .ev-item-meta {
        font-size: .7rem;
        color: rgba(255, 255, 255, .4);
        margin-bottom: 6px;
    }

    .ev-type-badge {
        display: inline-flex;
        padding: 2px 9px;
        border-radius: 20px;
        font-size: .62rem;
        font-weight: 700;
    }

    .badge-Dars {
        background: rgba(0, 227, 150, .15);
        color: #00e396;
    }

    .badge-Workshop {
        background: rgba(0, 143, 251, .15);
        color: #74b4ff;
    }

    .badge-TechWorkshop {
        background: rgba(248, 172, 89, .15);
        color: #f8ac59;
    }

    .badge-Dawah {
        background: rgba(119, 93, 208, .15);
        color: #b08ef0;
    }

    .badge-Event {
        background: rgba(254, 176, 25, .15);
        color: #feb019;
    }

    /* ── Weekly agenda view (mobile calendar) ── */
    #evWeekGrid {
        display: none;
        flex-direction: column;
        gap: 0;
    }

    .ev-week-row {
        display: flex;
        gap: 12px;
        padding: 12px 4px;
        border-bottom: 1px solid rgba(255, 255, 255, .06);
    }

    .ev-week-row:last-child {
        border-bottom: none;
    }

    .ev-week-daylabel {
        width: 46px;
        flex-shrink: 0;
        text-align: center;
        padding-top: 2px;
    }

    .ev-week-dow {
        font-size: .6rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .5px;
        color: rgba(255, 255, 255, .35);
    }

    .ev-week-datenum {
        font-size: 1.15rem;
        font-weight: 800;
        color: #fff;
        margin-top: 2px;
    }

    .ev-week-row.ev-week-today .ev-week-datenum {
        color: #0d6efd;
    }

    .ev-week-row.ev-week-today .ev-week-dow {
        color: #0d6efd;
    }

    .ev-week-events {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .ev-week-chip {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 7px 10px;
        border-radius: 8px;
        min-width: 0;
    }

    .ev-week-chip .ev-week-chip-title {
        font-size: .78rem;
        font-weight: 700;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
        flex: 1;
    }

    .ev-week-chip .ev-week-chip-time {
        font-size: .64rem;
        font-weight: 600;
        opacity: .75;
        flex-shrink: 0;
    }

    .ev-week-empty {
        font-size: .72rem;
        color: rgba(255, 255, 255, .25);
        font-style: italic;
        padding-top: 4px;
    }

    /* ── Mobile breakpoint ── */
    @media(max-width: 640px) {
        #evListGrid {
            grid-template-columns: 1fr !important;
        }

        #evMonthDow {
            display: none;
        }

        /* Mon/Tue header row not needed in week rows */
        #evCalGrid {
            display: none;
        }

        /* hide month grid */
        #evWeekGrid {
            display: flex;
        }

        /* show week agenda instead */
        #evCalLabel {
            min-width: auto !important;
            font-size: .8rem !important;
        }

        #evCalNav button {
            width: 28px !important;
            height: 28px !important;
        }

        .ev-list-item {
            padding: 11px 12px;
            gap: 10px;
        }

        .ev-date-box {
            width: 40px;
            padding: 6px 3px;
        }

        .ev-date-day {
            font-size: 1.1rem;
        }

        .ev-item-title {
            font-size: .8rem;
        }
    }
</style>

<body>
    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>
    <?php include "mainTopBar.php"; ?>

    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12">
                    <div class="breadcome-list single-page-breadcome">
                        <div class="row">
                            <div class="col-6">
                                <h6 class="mb-0" style="font-size:.9rem;color:#6c757d;">Dars Attendance</h6>
                            </div>
                            <div class="col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Dars Attendance</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <div class="container-fluid mt-4">
        <div class="row g-3">
            <div class="col-12">
                <div class="white-box" style="padding:0;overflow:hidden;">

                    <!-- ── Header ── -->
                    <div style="display:flex;align-items:center;justify-content:space-between;
                            flex-wrap:wrap;gap:10px;padding:16px 20px;
                            border-bottom:1px solid rgba(255,255,255,.07);">
                        <h3 class="box-title mb-0" style="display:flex;align-items:center;gap:8px;">
                            <i class="bi bi-calendar-event-fill" style="color:#0d6efd;font-size:1rem;"></i>
                            Events &amp; Sessions
                        </h3>
                        <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">

                            <!-- Month nav (hidden in list view) -->
                            <div id="evCalNav" style="display:flex;align-items:center;gap:6px;">
                                <button onclick="evShiftMonth(-1)" style="
                                background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
                                color:rgba(255,255,255,.7);width:32px;height:32px;border-radius:8px;
                                cursor:pointer;font-size:16px;display:flex;align-items:center;
                                justify-content:center;transition:background .15s;"
                                    onmouseover="this.style.background='rgba(255,255,255,.13)'"
                                    onmouseout="this.style.background='rgba(255,255,255,.06)'">
                                    <i class="bi bi-chevron-left"></i>
                                </button>
                                <span id="evCalLabel" style="font-size:.85rem;font-weight:700;color:#fff;
                                  min-width:130px;text-align:center;">—</span>
                                <button onclick="evShiftMonth(1)" style="
                                background:rgba(255,255,255,.06);border:1px solid rgba(255,255,255,.1);
                                color:rgba(255,255,255,.7);width:32px;height:32px;border-radius:8px;
                                cursor:pointer;font-size:16px;display:flex;align-items:center;
                                justify-content:center;transition:background .15s;"
                                    onmouseover="this.style.background='rgba(255,255,255,.13)'"
                                    onmouseout="this.style.background='rgba(255,255,255,.06)'">
                                    <i class="bi bi-chevron-right"></i>
                                </button>
                            </div>

                            <!-- View toggle -->
                            <div style="display:flex;background:rgba(255,255,255,.06);border-radius:8px;
                                    overflow:hidden;border:1px solid rgba(255,255,255,.1);">
                                <button id="evBtnCal" onclick="evSetView('cal')"
                                    style="background:#0d6efd;border:none;color:#fff;padding:7px 16px;
                                       font-size:.78rem;font-weight:600;cursor:pointer;
                                       display:flex;align-items:center;gap:6px;font-family:inherit;
                                       transition:all .15s;">
                                    <i class="bi bi-calendar3"></i> Calendar
                                </button>
                                <button id="evBtnList" onclick="evSetView('list')"
                                    style="background:transparent;border:none;color:rgba(255,255,255,.45);
                                       padding:7px 16px;font-size:.78rem;font-weight:600;cursor:pointer;
                                       display:flex;align-items:center;gap:6px;font-family:inherit;
                                       transition:all .15s;">
                                    <i class="bi bi-list-ul"></i> List
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- ── Calendar View ── -->
                    <div id="evCalView" style="padding:20px;">

                        <!-- Day-of-week headers (desktop month view only) -->
                        <div id="evMonthDow" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;margin-bottom:6px;">
                            <?php foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $d): ?>
                                <div style="font-size:.63rem;font-weight:700;text-transform:uppercase;
                        letter-spacing:.6px;color:rgba(255,255,255,.3);text-align:center;padding:4px 0;">
                                    <?= $d ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Month grid (desktop) -->
                        <div id="evCalGrid" style="display:grid;grid-template-columns:repeat(7,1fr);gap:4px;"></div>

                        <!-- Week agenda (mobile) -->
                        <div id="evWeekGrid"></div>

                    </div>

                    <!-- ── List View ── -->
                    <div id="evListView" style="padding:20px;display:none;">
                        <div id="evListGrid" style="display:grid;grid-template-columns:1fr 1fr;gap:12px;"></div>
                    </div>

                </div>
            </div>
        </div>
    </div>


    <?php
    include "footer.php"; ?>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

<script>
(function(){
const EVENTS   = <?= $eventsJson ?>;
const MONTHS   = ['January','February','March','April','May','June',
                  'July','August','September','October','November','December'];
const DOW3     = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
const DATECOLOR = {
    Dars:         '#00e396',
    Workshop:     '#74b4ff',
    TechWorkshop: '#f8ac59',
    Dawah:        '#b08ef0',
    Event:        '#feb019',
};

const todayObj  = new Date();
const todayStr  = todayObj.toISOString().slice(0,10);
let   viewMonth = todayObj.getMonth();
let   viewYear  = todayObj.getFullYear();

function getMonday(d){
    const dt = new Date(d);
    const day = dt.getDay(); // 0=Sun..6=Sat
    const diff = (day === 0 ? -6 : 1) - day;
    dt.setDate(dt.getDate() + diff);
    dt.setHours(0,0,0,0);
    return dt;
}
let weekStart = getMonday(todayObj);

const isMobile = () => window.innerWidth <= 640;
const toDS = (d) => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

// ── MONTH GRID (desktop) ─────────────────────────────────────
function renderMonth(){
    document.getElementById('evCalLabel').textContent = MONTHS[viewMonth]+' '+viewYear;
    const firstDow    = (new Date(viewYear, viewMonth, 1).getDay() + 6) % 7;
    const daysInMonth = new Date(viewYear, viewMonth+1, 0).getDate();
    let html = '';

    for(let i=0;i<firstDow;i++)
        html += '<div class="ev-cal-cell ev-empty"></div>';

    for(let d=1;d<=daysInMonth;d++){
        const ds  = `${viewYear}-${String(viewMonth+1).padStart(2,'0')}-${String(d).padStart(2,'0')}`;
        const evs = EVENTS.filter(e=>e.date===ds);
        const isToday = ds===todayStr;
        const cls = ['ev-cal-cell', isToday?'ev-today':'', evs.length?'has-ev':''].filter(Boolean).join(' ');
        const chips = evs.slice(0,3).map(e=>
            `<div class="ev-chip-cal chip-${e.type}">${e.title.split('—')[0].trim()}</div>`
        ).join('');
        const more = evs.length>3
            ? `<div style="font-size:.56rem;color:rgba(255,255,255,.3);">+${evs.length-3} more</div>` : '';

        html += `<div class="${cls}">
            <div class="ev-cal-num">${d}</div>
            ${chips}${more}
        </div>`;
    }
    document.getElementById('evCalGrid').innerHTML = html;
}

// ── WEEK AGENDA (mobile) ──────────────────────────────────────
function renderWeek(){
    const weekEnd = new Date(weekStart);
    weekEnd.setDate(weekEnd.getDate()+6);

    const sameMonth = weekStart.getMonth() === weekEnd.getMonth();
    document.getElementById('evCalLabel').textContent = sameMonth
        ? `${MONTHS[weekStart.getMonth()].slice(0,3)} ${weekStart.getDate()}–${weekEnd.getDate()}`
        : `${MONTHS[weekStart.getMonth()].slice(0,3)} ${weekStart.getDate()} – ${MONTHS[weekEnd.getMonth()].slice(0,3)} ${weekEnd.getDate()}`;

    let html = '';
    for(let i=0;i<7;i++){
        const d  = new Date(weekStart);
        d.setDate(d.getDate()+i);
        const ds = toDS(d);
        const evs = EVENTS.filter(e=>e.date===ds);
        const isToday = ds===todayStr;

        const chips = evs.length ? evs.map(e=>{
            const clr = DATECOLOR[e.type]||'#feb019';
            return `<div class="ev-week-chip chip-${e.type}">
                <span class="ev-week-chip-title">${e.title}</span>
                <span class="ev-week-chip-time">${e.time}</span>
            </div>`;
        }).join('') : `<div class="ev-week-empty">No events</div>`;

        html += `<div class="ev-week-row ${isToday?'ev-week-today':''}">
            <div class="ev-week-daylabel">
                <div class="ev-week-dow">${DOW3[d.getDay()]}</div>
                <div class="ev-week-datenum">${d.getDate()}</div>
            </div>
            <div class="ev-week-events">${chips}</div>
        </div>`;
    }
    document.getElementById('evWeekGrid').innerHTML = html;
}

function renderCal(){
    if (isMobile()) renderWeek(); else renderMonth();
}

window.evShiftMonth = function(dir){
    if (isMobile()) {
        weekStart.setDate(weekStart.getDate() + dir*7);
    } else {
        viewMonth += dir;
        if(viewMonth>11){viewMonth=0;viewYear++;}
        if(viewMonth<0) {viewMonth=11;viewYear--;}
    }
    renderCal();
};

// ── LIST ───────────────────────────────────────────────────
function renderList(){
    const upcoming = EVENTS.filter(e=>e.date>=todayStr).slice(0,12);
    document.getElementById('evListGrid').innerHTML = upcoming.map(e=>{
        const dt  = new Date(e.date+'T00:00:00');
        const clr = DATECOLOR[e.type]||'#feb019';
        const feeBadge = e.fee ? `<span style="font-size:.6rem;padding:1px 7px;border-radius:10px;
            background:${e.fee==='Free'?'rgba(0,227,150,.15)':'rgba(254,176,25,.15)'};
            color:${e.fee==='Free'?'#00e396':'#feb019'};font-weight:700;margin-left:5px;">${e.fee}</span>` : '';
        return `<div class="ev-list-item">
            <div class="ev-date-box">
                <div class="ev-date-day" style="color:${clr};">${dt.getDate()}</div>
                <div class="ev-date-mon">${MONTHS[dt.getMonth()].slice(0,3)}</div>
            </div>
            <div style="flex:1;min-width:0;">
                <div class="ev-item-title">${e.title}</div>
                <div class="ev-item-meta">
                    <i class="bi bi-clock me-1"></i>${e.time}
                    &nbsp;·&nbsp;
                    <i class="bi bi-geo-alt me-1"></i>${e.loc}
                    ${e.by ? `&nbsp;·&nbsp;<i class="bi bi-person me-1"></i>${e.by}` : ''}
                </div>
                <span class="ev-type-badge badge-${e.type}">${e.type==='TechWorkshop'?'Tech Workshop':e.type}</span>
                ${feeBadge}
            </div>
        </div>`;
    }).join('') || '<p style="color:rgba(255,255,255,.3);text-align:center;padding:20px;">No upcoming events</p>';
}

// ── VIEW TOGGLE ────────────────────────────────────────────
window.evSetView = function(v){
    const isCal = v==='cal';
    document.getElementById('evCalView').style.display  = isCal ? '' : 'none';
    document.getElementById('evListView').style.display = isCal ? 'none' : '';
    document.getElementById('evCalNav').style.display   = isCal ? 'flex' : 'none';
    const bCal  = document.getElementById('evBtnCal');
    const bList = document.getElementById('evBtnList');
    bCal.style.background  = isCal ? '#0d6efd' : 'transparent';
    bCal.style.color       = isCal ? '#fff'    : 'rgba(255,255,255,.45)';
    bList.style.background = isCal ? 'transparent' : '#0d6efd';
    bList.style.color      = isCal ? 'rgba(255,255,255,.45)' : '#fff';
};

// ── INIT ───────────────────────────────────────────────────
renderCal();
renderList();

// Re-render on resize/rotate so switching month↔week mode adapts live
let resizeTimer;
window.addEventListener('resize', function(){
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(renderCal, 150);
});
})();
</script>
</body>

</html>