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

<?php
include "header.php";
?>


<style>
    th[onclick] {
        user-select: none;
    }

    th[onclick]:hover {
        background-color: #f1f1f1;
    }
</style>

<style>
.iq-toast {
    position: fixed; top: 20px; right: 20px; z-index: 9999;
    background: #1b2a47; border: 1px solid rgba(255,255,255,.1);
    border-radius: 10px; padding: 14px 18px; color: #fff;
    font-size: .85rem; display: flex; align-items: center; gap: 10px;
    box-shadow: 0 8px 24px rgba(0,0,0,.35); max-width: 340px;
    animation: iqToastIn .25s ease;
}
.iq-toast.iq-toast-out { animation: iqToastOut .25s ease forwards; }
@keyframes iqToastIn  { from{transform:translateX(30px);opacity:0;} to{transform:translateX(0);opacity:1;} }
@keyframes iqToastOut { from{transform:translateX(0);opacity:1;} to{transform:translateX(30px);opacity:0;} }
.iq-toast-icon { font-size: 1.1rem; flex-shrink: 0; }
.iq-toast-icon.success { color: #00e396; }
.iq-toast-icon.error   { color: #ff4560; }
</style>

<body>

    <?php include "auth.php"; ?>
    <?php include "sidebar.php"; ?>


    <!-- Start Welcome area -->
    <?php
    include "mainTopBar.php";
    ?>
    <div class="breadcome-area">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="breadcome-list single-page-breadcome">
                        <div class="row">
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <div class="breadcome-heading">
                                    <?php if (hasFeature("addWorkshop")) { ?>
                                        <button class="btn btn-success mb-3" id="addNewWorkshopBtn">New Workshop Event</button>
                                    <?php } ?>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a> <span class="bread-slash">/</span>
                                    </li>
                                    <li><span class="bread-blod">Static Table</span>
                                    </li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>
    <!-- Static Table Start -->
    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="padding-bottom: 20px;">

                                <!-- Left: Heading -->
                                <div>
                                    <h5 class="mb-0">Upcoming Workshops</h5>
                                </div>

                                <!-- Right: Search -->
                                <div class="mt-2 mt-lg-0" style="width: 300px;">
                                    <div class="nk-header-search">
                                        <input type="text" id="searchInput" class="nk-search-input form-control" placeholder="Search...">
                                        <i class="bi bi-search nk-search-icon"></i>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="sparkline12-graph">
                            <div class="table-responsive static-table-list">
                                <table class="table table-hover table-bordered">
                                    <thead class="thead-dark">
                                        <tr>
                                            <th>#</th>
                                            <th onclick="sortTable('topic')" style="cursor:pointer;">Topic</th>
                                            <th onclick="sortTable('organisier')" style="cursor:pointer;">Organisier</th>
                                            <th onclick="sortTable('dateTime')" style="cursor:pointer;">Date/Time</th>
                                            <th onclick="sortTable('phone')" style="cursor:pointer;">Phone</th>
                                            <th onclick="sortTable('location')" style="cursor:pointer;">Location</th>
                                            <?php if (hasFeature("editWorkshop") || hasFeature("deleteWorkshop")) { ?>
                                                <th style="width:180px;">Actions</th>
                                            <?php } ?>
                                        </tr>
                                    </thead>
                                    <tbody id="eventWorkshopTableBody">
                                        <!-- Rows dynamically rendered here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php
    include "footer.php"; ?>

    <!-- Add Event Workshop Modal -->
    <div class="modal fade" id="addEventWorkshopModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Create New Event Workshop</h5>
                </div>

                <div class="modal-body">
                    <form id="addEventWorkshopForm">
                        <div class="form-group mb-3">
                            <label for="add-topic">Topic</label>
                            <input type="text" name="topic" id="add-topic" class="form-control" placeholder="Enter Topic" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="add-organisier">Organizer</label>
                            <select name="organisier" id="add-organisier" class="form-control" required>
                                <option value="">-- Select Organizer --</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="add-dateTime">Date/Time</label>
                            <input type="datetime-local" name="dateTime" id="add-dateTime" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label for="add-phone">Phone</label>
                            <input type="text" name="phone" id="add-phone" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label for="add-location">Location</label>
                            <input type="text" name="location" id="add-location" class="form-control">
                        </div>
                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="addEventWorkshop()">Add</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Edit Event Workshop Modal -->
    <div class="modal fade" id="editEventWorkshopModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Edit Event Workshop</h5>
                </div>

                <div class="modal-body">
                    <form id="editEventWorkshopForm">
                        <input type="hidden" id="editEventWorkshopId" name="id">

                        <div class="form-group mb-3">
                            <label for="edit-topic">Topic</label>
                            <input type="text" name="topic" id="edit-topic" class="form-control" placeholder="Enter Topic" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit-organisier">Organizer</label>
                            <select name="organisier" id="edit-organisier" class="form-control" required>
                                <option value="">-- Select Organizer --</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit-dateTime">Date/Time</label>
                            <input type="datetime-local" name="dateTime" id="edit-dateTime" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit-phone">Phone</label>
                            <input type="text" name="phone" id="edit-phone" class="form-control">
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit-location">Location</label>
                            <input type="text" name="location" id="edit-location" class="form-control">
                        </div>

                    </form>
                </div>

                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="updateEventWorkshop()">Update</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>

            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>

                <div class="modal-body">
                    <p id="deleteMessage">Are you sure you want to delete this workshop?</p>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        Delete
                    </button>
                </div>

            </div>
        </div>
    </div>




    <!-- sparkline JS
		============================================ -->
    <!-- Bootstrap 5.3.8 Bundle (includes Popper)
		============================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <!-- AOS 2.3.4
		============================================ -->
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <!-- SimpleBar 6.2.7
		============================================ -->
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <!-- main JS
		============================================ -->
    <script src="js/main.js"></script>

    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>


    <script>

const canEditWorkshop   = <?= hasFeature("editWorkshop")   ? "true" : "false" ?>;
const canDeleteWorkshop = <?= hasFeature("deleteWorkshop") ? "true" : "false" ?>;

let addEventWorkshopModal  = new bootstrap.Modal(document.getElementById('addEventWorkshopModal'));
let editEventWorkshopModal = new bootstrap.Modal(document.getElementById('editEventWorkshopModal'));
let deleteEventWorkshopModal = new bootstrap.Modal(document.getElementById('deleteModal'));

let tomSelectAddWorkshop  = null;
let tomSelectEditWorkshop = null;
let eventWorkshopList = [];
let selectedEventWorkshopId = null;
let sortDirection = {};

async function loadUsers() {
    try {
        const res   = await fetch('fetchUsersComboBox.php');
        const users = await res.json();

        ['add-organisier', 'edit-organisier'].forEach(id => {
            const sel = document.getElementById(id);
            while (sel.options.length > 1) sel.remove(1);
            users.forEach(u => {
                const opt = document.createElement('option');
                opt.value       = u.id;      // ✅ store ID, not name
                opt.textContent = u.name;
                sel.appendChild(opt);
            });
        });

        if (tomSelectAddWorkshop)  tomSelectAddWorkshop.destroy();
        if (tomSelectEditWorkshop) tomSelectEditWorkshop.destroy();

        tomSelectAddWorkshop  = new TomSelect('#add-organisier',  { placeholder: '-- Select Organizer --', allowEmptyOption: true });
        tomSelectEditWorkshop = new TomSelect('#edit-organisier', { placeholder: '-- Select Organizer --', allowEmptyOption: true });

    } catch (err) {
        console.error('Error loading users:', err);
    }
}

const addNewWorkshopBtn = document.getElementById('addNewWorkshopBtn');
if (addNewWorkshopBtn) {
    addNewWorkshopBtn.addEventListener('click', () => {
        document.getElementById('addEventWorkshopForm').reset();
        if (tomSelectAddWorkshop) tomSelectAddWorkshop.clear();
        addEventWorkshopModal.show();
    });
}

loadUsers();

function addEventWorkshop() {
    const form = document.getElementById('addEventWorkshopForm');
    const data = new FormData(form);
    data.append("type", "Workshop");

    const addBtn = document.querySelector('#addEventWorkshopModal .btn-primary');
    addBtn.disabled = true;
    const originalText = addBtn.textContent;
    addBtn.innerHTML = `<span class="spinner-border spinner-border-sm me-2"></span>Adding...`;

    fetch('addEvent.php', { method: 'POST', body: data })
        .then(res => res.json())
        .then(result => {
            addEventWorkshopModal.hide();
            showSuccess(result.message, loadEventWorkshop);

            // ✅ Fire off email sending separately
            if (result.success && result.eventId) {
                const emailData = new FormData();
                emailData.append('eventId', result.eventId);

                fetch('sendEventEmails.php', { method: 'POST', body: emailData })
                    .then(res => res.json())
                    .then(emailResult => {
                        if (emailResult.success) {
                            showToast(`📧 Notification emails sent to ${emailResult.sent} member${emailResult.sent === 1 ? '' : 's'}.`, 'success');
                        } else {
                            showToast(`⚠️ Could not send notification emails.`, 'error');
                        }
                    })
                    .catch(() => showToast('⚠️ Could not send notification emails.', 'error'));
            }
        })
        .catch(err => {
            console.error("Error adding Workshop:", err);
            showToast('❌ Something went wrong while adding the event.', 'error');
        })
        .finally(() => {
            addBtn.disabled = false;
            addBtn.textContent = originalText;
        });
}

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
        this.remove();
        if (callback) callback();
    });
}

function updateEventWorkshop() {
    const form = document.getElementById('editEventWorkshopForm');
    fetch('updateEventDars.php', { method: 'POST', body: new FormData(form) })
        .then(res => res.text())
        .then(msg => { editEventWorkshopModal.hide(); showSuccess(msg, loadEventWorkshop); })
        .catch(console.error);
}

// ---- Fetch / render ----

async function loadEventWorkshop() {
    try {
        const res = await fetch("fetchEvents.php?type=Workshop");
        eventWorkshopList = await res.json();
        renderEventWorkshop(eventWorkshopList);
    } catch (err) {
        console.error("Error fetching Event Workshop:", err);
    }
}

function renderEventWorkshop(list) {
    const tableBody = document.getElementById("eventWorkshopTableBody");
    tableBody.innerHTML = "";

    if (!list || list.length === 0) {
        const cols = (canEditWorkshop || canDeleteWorkshop) ? 7 : 6;
        tableBody.innerHTML = `<tr><td colspan="${cols}" class="text-center">No Event Workshop found</td></tr>`;
        return;
    }

    list.forEach((event, i) => {
        const actionsCell = (canEditWorkshop || canDeleteWorkshop) ? `
        <td>
            ${canEditWorkshop   ? `<button class="btn btn-sm btn-primary" onclick="editEventWorkshop(${event.id})">Edit</button>`   : ''}
            ${canDeleteWorkshop ? `<button class="btn btn-sm btn-danger"  onclick="deleteEventWorkshop(${event.id})">Delete</button>` : ''}
        </td>` : '';

        tableBody.innerHTML += `
        <tr>
            <td>${i + 1}</td>
            <td>${event.topic}</td>
            <td>${event.organisierName ?? '-'}</td>
            <td>${formatDateTime(event.dateTime)}</td>
            <td>${event.phone ?? '-'}</td>
            <td>${event.location ?? '-'}</td>
            ${actionsCell}
        </tr>`;
    });
}

function editEventWorkshop(id) {
    const event = eventWorkshopList.find(e => e.id == id);
    if (!event) return;

    document.getElementById('editEventWorkshopId').value = event.id;
    document.getElementById('edit-topic').value           = event.topic;
    if (tomSelectEditWorkshop) tomSelectEditWorkshop.setValue(event.organisier); // ✅ ID via Tom Select
    document.getElementById('edit-dateTime').value = event.dateTime
        ? event.dateTime.replace(' ', 'T').slice(0, 16) : '';
    document.getElementById('edit-phone').value    = event.phone    ?? '';
    document.getElementById('edit-location').value = event.location ?? '';

    editEventWorkshopModal.show();
}

function deleteEventWorkshop(id) {
    selectedEventWorkshopId = id;
    const ev = eventWorkshopList.find(e => e.id == id);
    document.getElementById("deleteMessage").innerText =
        `Are you sure you want to delete "${ev ? ev.topic : 'this event'}"?`;
    deleteEventWorkshopModal.show();
}

document.getElementById("confirmDeleteBtn").addEventListener("click", function() {
    if (!selectedEventWorkshopId) return;
    fetch(`deleteEvent.php?id=${selectedEventWorkshopId}`)
        .then(res => res.text())
        .then(() => {
            loadEventWorkshop();
            deleteEventWorkshopModal.hide();
            selectedEventWorkshopId = null;
        });
});

function formatDateTime(dateTime) {
    if (!dateTime) return '-';
    const date = new Date(dateTime);
    const day = String(date.getDate()).padStart(2, '0');
    const month = String(date.getMonth() + 1).padStart(2, '0');
    const year = date.getFullYear();
    let hours = date.getHours();
    const minutes = String(date.getMinutes()).padStart(2, '0');
    const ampm = hours >= 12 ? 'PM' : 'AM';
    hours = hours % 12 || 12;
    return `${day}-${month}-${year} ${hours}:${minutes} ${ampm}`;
}

document.getElementById('searchInput').addEventListener('input', function() {
    const query = this.value.toLowerCase().trim();

    const filtered = eventWorkshopList.filter(event => {
        return event.topic.toLowerCase().startsWith(query) ||
            (event.organisierName ?? '').toLowerCase().startsWith(query) ||
            (event.phone ?? '').startsWith(query) ||
            (event.location ?? '').toLowerCase().startsWith(query);
    });

    renderEventWorkshop(filtered);
});

function sortTable(column) {
    sortDirection[column] = !sortDirection[column];

    eventWorkshopList.sort((a, b) => {
        let valA = a[column] ?? '';
        let valB = b[column] ?? '';

        if (column === 'dateTime') {
            valA = new Date(valA).getTime() || 0;
            valB = new Date(valB).getTime() || 0;
        } else {
            valA = valA.toString().toLowerCase();
            valB = valB.toString().toLowerCase();
        }

        if (valA < valB) return sortDirection[column] ? -1 : 1;
        if (valA > valB) return sortDirection[column] ? 1 : -1;
        return 0;
    });

    renderEventWorkshop(eventWorkshopList);
}

function showToast(message, type = 'success', duration = 15000) {
    const toast = document.createElement('div');
    toast.className = 'iq-toast';
    const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-circle-fill';
    toast.innerHTML = `
        <i class="bi ${icon} iq-toast-icon ${type}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(toast);

    setTimeout(() => {
        toast.classList.add('iq-toast-out');
        setTimeout(() => toast.remove(), 250);
    }, duration);
}

// Initial load
loadEventWorkshop();

    </script>


</body>

</html>