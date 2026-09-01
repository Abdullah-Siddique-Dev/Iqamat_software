<?php
include "connection.php";
session_start();
if (!isset($_SESSION['user'])) {
    header("Location: ../index.php");
    exit();
}
?>

<!doctype html>
<html lang="en">

<?php include "header.php"; ?>

<style>
  @import url('https://fonts.googleapis.com/css2?family=Lora:wght@500;600&family=DM+Sans:wght@400;500&display=swap');

  /* ── Grid ── */
  .cards-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
    padding: 16px 0;
    font-family: 'DM Sans', sans-serif;
  }

  /* ── Card Shell ── */
  .job-card {
    border-radius: 14px;
    overflow: hidden;
    border: 1px solid #e2e8f0;
    background: #fff;
    transition: transform .22s ease, box-shadow .22s ease;
  }
  .job-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 32px rgba(0,0,0,0.13);
  }

  /* ── Card Header Visual ── */
  .card-visual {
    padding: 20px 18px 16px;
    position: relative;
    overflow: hidden;
    min-height: 115px;
  }
  /* Job  → navy blue */
  .card-visual.type-job         { background: #1a3a5c; }
  /* Internship → rich teal */
  .card-visual.type-internship  { background: #0d4a3a; }

  .geo-bg {
    position: absolute; top: 0; right: 0;
    width: 170px; height: 115px;
    opacity: .12; pointer-events: none;
  }

  .card-initial-circle {
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.13);
    border: 1.5px solid rgba(255,255,255,0.24);
    display: flex; align-items: center; justify-content: center;
    font-family: 'Lora', serif; font-size: 18px; font-weight: 600;
    color: #fff; margin-bottom: 10px; flex-shrink: 0;
  }

  .card-visual h5 {
    font-family: 'Lora', serif; font-size: 1rem; font-weight: 600;
    color: #fff; margin: 0 0 2px; position: relative;
  }
  .card-visual .company-sub {
    font-size: 12px; color: rgba(255,255,255,.5);
    position: relative; font-weight: 400;
  }

  /* ── Chips ── */
  .chip-row {
    position: absolute; top: 12px; right: 12px;
    display: flex; flex-direction: column; align-items: flex-end; gap: 5px;
  }
  .chip {
    padding: 3px 10px; border-radius: 20px;
    font-size: 10px; font-weight: 600; letter-spacing: .4px;
    white-space: nowrap;
  }
  .chip-job          { background: #dbeafe; color: #1e40af; }
  .chip-internship   { background: #d1fae5; color: #065f46; }
  .chip-online       { background: #ede9fe; color: #5b21b6; }
  .chip-physical     { background: #fee2e2; color: #991b1b; }
  .chip-hybrid       { background: #fef3c7; color: #92400e; }
  .chip-active       { background: #d1fae5; color: #065f46; }
  .chip-closed       { background: #f3f4f6; color: #6b7280; }

  /* ── Info Rows ── */
  .card-body-inner { padding: 14px 16px 8px; }
  .info-item {
    display: flex; align-items: flex-start; gap: 10px;
    padding: 7px 0; border-bottom: 1px solid #f1f3f5; font-size: 13px;
  }
  .info-item:last-child { border-bottom: none; }
  .info-icon {
    width: 28px; height: 28px; border-radius: 8px;
    background: #f8f9fa;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0; margin-top: 1px;
  }
  .info-icon svg {
    width: 13px; height: 13px;
    stroke: #6c757d; fill: none;
    stroke-width: 1.8; stroke-linecap: round; stroke-linejoin: round;
  }
  .info-label { font-size: 10px; color: #adb5bd; display: block; margin-bottom: 1px; }
  .info-val   { color: #212529; font-weight: 500; }
  .info-val a { color: #0d6efd; text-decoration: none; }
  .info-val a:hover { text-decoration: underline; }

  /* ── Action Buttons ── */
  .card-actions {
    padding: 10px 14px 12px;
    display: flex; gap: 8px;
  }
  .btn-card-edit {
    flex: 1; padding: 7px 0; border-radius: 8px;
    color: #fff; border: none;
    font-size: 13px; font-weight: 500; cursor: pointer;
    font-family: 'DM Sans', sans-serif;
    display: flex; align-items: center; justify-content: center; gap: 6px;
    transition: opacity .18s;
  }
  .btn-card-edit:hover { opacity: .82; }
  .btn-card-del {
    width: 36px; height: 36px; border-radius: 8px;
    background: #fff1f1; border: 1px solid #f5c6c6;
    color: #c0392b; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background .18s;
  }
  .btn-card-del:hover { background: #ffe0e0; }

  /* ── Empty ── */
  .empty-state {
    text-align: center; padding: 50px 20px;
    color: #adb5bd; grid-column: 1 / -1;
  }
  .empty-state i { font-size: 3rem; margin-bottom: 12px; display: block; }

  .modal-content { border-radius: 10px; overflow: hidden; }

  @media(max-width: 576px) {
    .cards-grid { grid-template-columns: 1fr; }
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
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <div class="breadcome-heading">
                                <?php if (hasFeature("addJob")) { ?>
                                    <button class="btn btn-success mb-3" id="addNewJobBtn">New Listing</button>
                                <?php } ?>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Jobs &amp; Internships</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="padding-bottom:20px;">
                                <h5 class="mb-0">Jobs &amp; Internships</h5>
                                <div class="d-flex gap-2 flex-wrap mt-2 mt-lg-0">
                                    <select id="filterType" class="form-control" style="width:140px;font-size:.9rem;">
                                        <option value="">All Types</option>
                                        <option value="Job">Job</option>
                                        <option value="Internship">Internship</option>
                                    </select>
                                    <select id="filterStatus" class="form-control" style="width:130px;font-size:.9rem;">
                                        <option value="">All Status</option>
                                        <option value="Active">Active</option>
                                        <option value="Closed">Closed</option>
                                    </select>
                                    <div class="nk-header-search">
                                        <input type="text" id="searchInput" class="nk-search-input form-control" placeholder="Search…">
                                        <i class="bi bi-search nk-search-icon"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="cards-grid" id="jobsGrid">
                            <!-- Cards rendered here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php
include "footer.php"; ?>

    <!-- ══ ADD MODAL ══ -->
    <div class="modal fade" id="addJobModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Job / Internship</h5>
                </div>
                <div class="modal-body">
                    <form id="addJobForm">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label>Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder='e.g. "Frontend Developer"' required>
                            </div>
                            <div class="col-md-4">
                                <label>Type <span class="text-danger">*</span></label>
                                <select name="type" class="form-control" required>
                                    <option value="">Select…</option>
                                    <option value="Job">Job</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Company Name</label>
                                <input type="text" name="company_name" class="form-control" placeholder="Company name">
                            </div>
                            <div class="col-md-6">
                                <label>Company Contact</label>
                                <input type="text" name="company_contact" class="form-control" placeholder="Email / Phone / Website">
                            </div>
                            <div class="col-md-4">
                                <label>Location Type</label>
                                <select name="location_type" class="form-control">
                                    <option value="Physical">Physical</option>
                                    <option value="Online">Online</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label>Location Address</label>
                                <input type="text" name="location_address" class="form-control" placeholder="City / Area (leave blank if Online)">
                            </div>
                            <div class="col-md-6">
                                <label>Salary / Stipend</label>
                                <input type="text" name="salary" class="form-control" placeholder='e.g. "PKR 50,000/mo" or "Unpaid"'>
                            </div>
                            <div class="col-md-6">
                                <label>Timings</label>
                                <input type="text" name="timings" class="form-control" placeholder='e.g. "9 AM – 5 PM, Mon–Fri"'>
                            </div>
                            <div class="col-md-6">
                                <label>Referred By (Name)</label>
                                <input type="text" name="referred_by_name" class="form-control" placeholder="Who referred this?">
                            </div>
                            <div class="col-md-6">
                                <label>Referred By (Contact)</label>
                                <input type="text" name="referred_by_contact" class="form-control" placeholder="Phone / WhatsApp">
                            </div>
                            <div class="col-md-6">
                                <label>Posted Date</label>
                                <input type="date" name="posted_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="Active">Active</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="addJob()">Add</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ EDIT MODAL ══ -->
    <div class="modal fade" id="editJobModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Job / Internship</h5>
                </div>
                <div class="modal-body">
                    <form id="editJobForm">
                        <input type="hidden" id="edit-id" name="id">
                        <div class="row g-3">
                            <div class="col-md-8">
                                <label>Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="edit-title" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label>Type <span class="text-danger">*</span></label>
                                <select name="type" id="edit-type" class="form-control" required>
                                    <option value="Job">Job</option>
                                    <option value="Internship">Internship</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Company Name</label>
                                <input type="text" name="company_name" id="edit-company_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Company Contact</label>
                                <input type="text" name="company_contact" id="edit-company_contact" class="form-control">
                            </div>
                            <div class="col-md-4">
                                <label>Location Type</label>
                                <select name="location_type" id="edit-location_type" class="form-control">
                                    <option value="Physical">Physical</option>
                                    <option value="Online">Online</option>
                                    <option value="Hybrid">Hybrid</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label>Location Address</label>
                                <input type="text" name="location_address" id="edit-location_address" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Salary / Stipend</label>
                                <input type="text" name="salary" id="edit-salary" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Timings</label>
                                <input type="text" name="timings" id="edit-timings" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Referred By (Name)</label>
                                <input type="text" name="referred_by_name" id="edit-referred_by_name" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Referred By (Contact)</label>
                                <input type="text" name="referred_by_contact" id="edit-referred_by_contact" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Posted Date</label>
                                <input type="date" name="posted_date" id="edit-posted_date" class="form-control">
                            </div>
                            <div class="col-md-6">
                                <label>Status</label>
                                <select name="status" id="edit-status" class="form-control">
                                    <option value="Active">Active</option>
                                    <option value="Closed">Closed</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="updateJob()">Update</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ══ DELETE MODAL ══ -->
    <div class="modal fade" id="deleteJobModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteJobMessage">Are you sure you want to delete this listing?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteJobBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    <script>

    const canAddJob    = <?= hasFeature("addJob")    ? "true" : "false" ?>;
    const canEditJob   = <?= hasFeature("editJob")   ? "true" : "false" ?>;
    const canDeleteJob = <?= hasFeature("deleteJob") ? "true" : "false" ?>;

        const addJobModal    = new bootstrap.Modal(document.getElementById('addJobModal'));
        const editJobModal   = new bootstrap.Modal(document.getElementById('editJobModal'));
        const deleteJobModal = new bootstrap.Modal(document.getElementById('deleteJobModal'));

        let jobsList        = [];
        let selectedDeleteId = null;

   const addNewJobBtn = document.getElementById('addNewJobBtn');
if (addNewJobBtn) {
    addNewJobBtn.addEventListener('click', () => {
        document.getElementById('addJobForm').reset();
        addJobModal.show();
    });
}


        /* ── ADD ── */
        function addJob() {
            const form = document.getElementById('addJobForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            fetch('addOpportunityJob.php', { method: 'POST', body: new FormData(form) })
                .then(r => r.text())
                .then(msg => { addJobModal.hide(); showSuccess(msg, loadJobs); })
                .catch(console.error);
        }

        /* ── LOAD ── */
        async function loadJobs() {
            try {
                const res = await fetch('fetchOpportunities.php');
                jobsList  = await res.json();
                applyFilters();
            } catch(e) { console.error('Error loading jobs:', e); }
        }

        /* ══ SVG background patterns ══ */
        const GEO_JOB = `<svg class="geo-bg" viewBox="0 0 170 115" xmlns="http://www.w3.org/2000/svg">
          <rect x="10" y="10" width="150" height="95" fill="none" stroke="#fff" stroke-width=".8"/>
          <rect x="25" y="25" width="120" height="65" fill="none" stroke="#fff" stroke-width=".5"/>
          <line x1="10" y1="57" x2="160" y2="57" stroke="#fff" stroke-width=".5"/>
          <line x1="85" y1="10" x2="85" y2="105" stroke="#fff" stroke-width=".5"/>
          <circle cx="85" cy="57" r="22" fill="none" stroke="#fff" stroke-width=".7"/>
          <circle cx="85" cy="57" r="10" fill="none" stroke="#fff" stroke-width=".5"/>
          <line x1="10" y1="10" x2="160" y2="105" stroke="#fff" stroke-width=".4"/>
          <line x1="160" y1="10" x2="10" y2="105" stroke="#fff" stroke-width=".4"/>
        </svg>`;

        const GEO_INTERN = `<svg class="geo-bg" viewBox="0 0 170 115" xmlns="http://www.w3.org/2000/svg">
          <polygon points="85,8 162,50 162,95 85,108 8,95 8,50" fill="none" stroke="#fff" stroke-width=".9"/>
          <polygon points="85,22 145,55 145,85 85,95 25,85 25,55" fill="none" stroke="#fff" stroke-width=".6"/>
          <polygon points="85,36 128,58 128,78 85,84 42,78 42,58" fill="none" stroke="#fff" stroke-width=".5"/>
          <line x1="8"  y1="50" x2="162" y2="50" stroke="#fff" stroke-width=".4"/>
          <line x1="8"  y1="95" x2="162" y2="95" stroke="#fff" stroke-width=".4"/>
          <line x1="85" y1="8"  x2="85"  y2="108" stroke="#fff" stroke-width=".4"/>
        </svg>`;

        /* ── SVG Icons ── */
        const IC = {
          briefcase: `<svg viewBox="0 0 24 24"><rect x="2" y="7" width="20" height="14" rx="2"/><path d="M16 7V5a2 2 0 00-2-2h-4a2 2 0 00-2 2v2"/><line x1="12" y1="12" x2="12" y2="12"/></svg>`,
          building:  `<svg viewBox="0 0 24 24"><rect x="3" y="3" width="18" height="18" rx="1"/><path d="M9 3v18M15 3v18M3 9h18M3 15h18"/></svg>`,
          pin:       `<svg viewBox="0 0 24 24"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/></svg>`,
          money:     `<svg viewBox="0 0 24 24"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>`,
          clock:     `<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>`,
          phone:     `<svg viewBox="0 0 24 24"><path d="M22 16.92v3a2 2 0 01-2.18 2A19.79 19.79 0 013.09 4.18 2 2 0 015.07 2h3a2 2 0 012 1.72c.127.96.361 1.903.7 2.81a2 2 0 01-.45 2.11L9.09 9.91a16 16 0 006.08 6.08l1.27-1.27a2 2 0 012.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0122 16.92z"/></svg>`,
          user:      `<svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>`,
          edit:      `<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>`,
          trash:     `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>`,
        };

        /* ── RENDER ── */
        function renderCards(list) {
            const grid = document.getElementById('jobsGrid');
            grid.innerHTML = '';

            if (!list || list.length === 0) {
                grid.innerHTML = `<div class="empty-state"><i class="bi bi-briefcase"></i><p>No listings found.</p></div>`;
                return;
            }

            list.forEach(item => {
                const isJob      = item.type === 'Job';
                const bgColor    = isJob ? '#1a3a5c' : '#0d4a3a';
                const typeClass  = isJob ? 'type-job' : 'type-internship';
                const chipType   = isJob ? 'chip-job' : 'chip-internship';
                const geoPat     = isJob ? GEO_JOB : GEO_INTERN;
                const initial    = (item.title || '?')[0].toUpperCase();
                const safeName   = (item.title || '').replace(/'/g, "\\'");

                const locChipCls = item.location_type === 'Online'  ? 'chip-online'
                                 : item.location_type === 'Hybrid'  ? 'chip-hybrid'
                                 : 'chip-physical';
                const statusCls  = item.status === 'Active' ? 'chip-active' : 'chip-closed';

                const postedFmt = item.posted_date
                    ? new Date(item.posted_date).toLocaleDateString('en-GB', { day:'2-digit', month:'short', year:'numeric' })
                    : '—';

                const locationDisplay = item.location_type === 'Online'
                    ? 'Online'
                    : (item.location_address || '—');

                grid.innerHTML += `
                <div class="job-card">
                    <div class="card-visual ${typeClass}">
                        ${geoPat}
                        <div class="chip-row">
                            <span class="chip ${chipType}">${item.type}</span>
                            <span class="chip ${locChipCls}">${item.location_type}</span>
                            <span class="chip ${statusCls}">${item.status}</span>
                        </div>
                        <div class="card-initial-circle">${initial}</div>
                        <h5>${item.title}</h5>
                        <span class="company-sub">${item.company_name || 'Company not specified'}</span>
                    </div>
                    <div class="card-body-inner">
                        <div class="info-item">
                            <div class="info-icon">${IC.building}</div>
                            <div><span class="info-label">Company Contact</span><span class="info-val">${item.company_contact || '—'}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">${IC.pin}</div>
                            <div><span class="info-label">Location</span><span class="info-val">${locationDisplay}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">${IC.money}</div>
                            <div><span class="info-label">Salary / Stipend</span><span class="info-val">${item.salary || '—'}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">${IC.clock}</div>
                            <div><span class="info-label">Timings</span><span class="info-val">${item.timings || '—'}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">${IC.user}</div>
                            <div><span class="info-label">Referred By</span><span class="info-val">${item.referred_by_name || '—'} ${item.referred_by_contact ? `<small style="color:#6c757d;font-weight:400;">${item.referred_by_contact}</small>` : ''}</span></div>
                        </div>
                        <div class="info-item">
                            <div class="info-icon">${IC.briefcase}</div>
                            <div><span class="info-label">Posted Date</span><span class="info-val">${postedFmt}</span></div>
                        </div>
                    </div>
<div class="card-actions">
    ${canEditJob ? `
    <button class="btn-card-edit" style="background:${bgColor};" onclick="editJob(${item.id})">
        ${IC.edit} Edit
    </button>` : ''}
    ${canDeleteJob ? `
    <button class="btn-card-del" onclick="confirmDeleteJob(${item.id}, '${safeName}')">
        ${IC.trash}
    </button>` : ''}
</div>
                </div>`;
            });
        }

        /* ── FILTER ── */
        function applyFilters() {
            const query  = document.getElementById('searchInput').value.toLowerCase().trim();
            const type   = document.getElementById('filterType').value;
            const status = document.getElementById('filterStatus').value;

            const filtered = jobsList.filter(j => {
                const matchSearch = !query ||
                    (j.title              || '').toLowerCase().includes(query) ||
                    (j.company_name       || '').toLowerCase().includes(query) ||
                    (j.location_address   || '').toLowerCase().includes(query) ||
                    (j.referred_by_name   || '').toLowerCase().includes(query);
                const matchType   = !type   || j.type   === type;
                const matchStatus = !status || j.status === status;
                return matchSearch && matchType && matchStatus;
            });

            renderCards(filtered);
        }

        document.getElementById('searchInput').addEventListener('input', applyFilters);
        document.getElementById('filterType').addEventListener('change', applyFilters);
        document.getElementById('filterStatus').addEventListener('change', applyFilters);

        /* ── EDIT ── */
        function editJob(id) {
            const item = jobsList.find(j => j.id == id);
            if (!item) return;

            document.getElementById('edit-id').value                  = item.id;
            document.getElementById('edit-title').value               = item.title               || '';
            document.getElementById('edit-type').value                = item.type                || 'Job';
            document.getElementById('edit-company_name').value        = item.company_name        || '';
            document.getElementById('edit-company_contact').value     = item.company_contact     || '';
            document.getElementById('edit-location_type').value       = item.location_type       || 'Physical';
            document.getElementById('edit-location_address').value    = item.location_address    || '';
            document.getElementById('edit-salary').value              = item.salary              || '';
            document.getElementById('edit-timings').value             = item.timings             || '';
            document.getElementById('edit-referred_by_name').value    = item.referred_by_name    || '';
            document.getElementById('edit-referred_by_contact').value = item.referred_by_contact || '';
            document.getElementById('edit-posted_date').value         = item.posted_date         || '';
            document.getElementById('edit-status').value              = item.status              || 'Active';

            editJobModal.show();
        }

        /* ── UPDATE ── */
        function updateJob() {
            const form = document.getElementById('editJobForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }
            fetch('updateOpportunity.php', { method: 'POST', body: new FormData(form) })
                .then(r => r.text())
                .then(msg => { editJobModal.hide(); showSuccess(msg, loadJobs); })
                .catch(console.error);
        }

        /* ── DELETE CONFIRM ── */
        function confirmDeleteJob(id, name) {
            selectedDeleteId = id;
            document.getElementById('deleteJobMessage').innerText =
                `Are you sure you want to delete "${name}"?`;
            deleteJobModal.show();
        }

        document.getElementById('confirmDeleteJobBtn').addEventListener('click', function() {
            if (!selectedDeleteId) return;
            fetch(`deleteOpportunity.php?id=${selectedDeleteId}`)
                .then(r => r.text())
                .then(() => {
                    deleteJobModal.hide();
                    selectedDeleteId = null;
                    loadJobs();
                })
                .catch(console.error);
        });

        /* ── SUCCESS MODAL ── */
        function showSuccess(msg, callback) {
            const html = `
                <div class="modal fade" id="successModal" tabindex="-1">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-body text-center p-4">
                                <h5>${msg}</h5>
                                <button class="btn btn-primary mt-3" data-bs-dismiss="modal">OK</button>
                            </div>
                        </div>
                    </div>
                </div>`;
            const div = document.createElement('div');
            div.innerHTML = html;
            document.body.appendChild(div.firstElementChild);
            const m = new bootstrap.Modal(document.getElementById('successModal'));
            m.show();
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function() {
                document.getElementById('successModal').remove();
                if (callback) callback();
            });
        }

        /* ── INIT ── */
        loadJobs();
    </script>

</body>
</html>