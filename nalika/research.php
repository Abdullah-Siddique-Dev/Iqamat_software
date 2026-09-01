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

    .badge-islamic {
        background-color: #1a7a4a;
        color: #fff;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-science {
        background-color: #1565c0;
        color: #fff;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .badge-other {
        background-color: #6c757d;
        color: #fff;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }

    .file-upload-wrapper {
        position: relative;
        border: 2px dashed #ced4da;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        cursor: pointer;
        transition: border-color 0.2s;
    }

    .file-upload-wrapper:hover {
        border-color: #0d6efd;
        background: #e8f0fe;
    }

    .file-upload-wrapper input[type="file"] {
        position: absolute;
        inset: 0;
        opacity: 0;
        cursor: pointer;
        width: 100%;
        height: 100%;
    }

    .file-upload-label {
        pointer-events: none;
        font-size: 14px;
        color: #6c757d;
    }

    .file-upload-label i {
        font-size: 28px;
        display: block;
        margin-bottom: 6px;
        color: #adb5bd;
    }

    #fileName {
        font-size: 13px;
        margin-top: 6px;
        color: #0d6efd;
        font-weight: 500;
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
                                 <?php if (hasFeature("addResearch")) { ?>
                                    <button class="btn btn-success mb-3" id="addNewResearchBtn">New Research</button>
                                <?php } ?>
                                </div>
                            </div>
                            <div class="col-lg-6 col-md-6 col-sm-6 col-6">
                                <ul class="breadcome-menu">
                                    <li><a href="#">Home</a> <span class="bread-slash">/</span></li>
                                    <li><span class="bread-blod">Research</span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Research Table -->
    <div class="static-table-area mg-t-15">
        <div class="container-fluid">
            <div class="row">
                <div class="col-lg-12 col-md-12 col-sm-12 col-12">
                    <div class="sparkline12-list mg-b-15">
                        <div class="sparkline12-hd">
                            <div class="d-flex justify-content-between align-items-center flex-wrap" style="padding-bottom: 20px;">
                                <div>
                                    <h5 class="mb-0">Research Papers</h5>
                                </div>
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
                                            <th onclick="sortTable('title')" style="cursor:pointer;">Title</th>
                                            <th onclick="sortTable('author_name')" style="cursor:pointer;">Author</th>
                                            <th onclick="sortTable('research_type')" style="cursor:pointer;">Type</th>
                                            <th onclick="sortTable('created_at')" style="cursor:pointer;">Date Added</th>
                                            <th style="width:200px;">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="researchTableBody">
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

    <?php include "footer.php"; ?>

    <!-- Add Research Modal -->
    <div class="modal fade" id="addResearchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Research</h5>
                </div>
                <div class="modal-body">
                    <form id="addResearchForm" enctype="multipart/form-data">

                        <div class="form-group mb-3">
                            <label for="add-title">Title of Research <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="add-title" class="form-control" placeholder="Enter research title" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="add-author">Author (User) <span class="text-danger">*</span></label>
                            <select name="author_id" id="add-author" class="form-control" required>
                                <option value="">-- Select Author --</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="add-type">Type of Research <span class="text-danger">*</span></label>
                            <select name="research_type" id="add-type" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <option value="Islamic Topic">Islamic Topic</option>
                                <option value="Science & Technology">Science &amp; Technology</option>
                                <option value="Social Studies">Social Studies</option>
                                <option value="History">History</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label>Upload PDF <span class="text-danger">*</span></label>
                            <div class="file-upload-wrapper" id="addFileWrapper">
                                <input type="file" name="pdf_file" id="add-pdf" accept="application/pdf" required onchange="showFileName(this, 'addFileWrapper', 'addFileName')">
                                <div class="file-upload-label">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                    Click or drag to upload a PDF
                                </div>
                            </div>
                            <div id="addFileName"></div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="addResearch()">Add Research</button>
                    <button class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Research Modal -->
    <div class="modal fade" id="editResearchModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Research</h5>
                </div>
                <div class="modal-body">
                    <form id="editResearchForm" enctype="multipart/form-data">
                        <input type="hidden" id="edit-research-id" name="id">

                        <div class="form-group mb-3">
                            <label for="edit-title">Title of Research <span class="text-danger">*</span></label>
                            <input type="text" name="title" id="edit-title" class="form-control" required>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit-author">Author (User) <span class="text-danger">*</span></label>
                            <select name="author_id" id="edit-author" class="form-control" required>
                                <option value="">-- Select Author --</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit-type">Type of Research <span class="text-danger">*</span></label>
                            <select name="research_type" id="edit-type" class="form-control" required>
                                <option value="">-- Select Type --</option>
                                <option value="Islamic Topic">Islamic Topic</option>
                                <option value="Science & Technology">Science &amp; Technology</option>
                                <option value="Social Studies">Social Studies</option>
                                <option value="History">History</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label>Replace PDF <span class="text-muted" style="font-size:12px;">(optional – leave blank to keep existing)</span></label>
                            <div class="file-upload-wrapper" id="editFileWrapper">
                                <input type="file" name="pdf_file" id="edit-pdf" accept="application/pdf" onchange="showFileName(this, 'editFileWrapper', 'editFileName')">
                                <div class="file-upload-label">
                                    <i class="bi bi-file-earmark-pdf"></i>
                                    Click or drag to replace PDF
                                </div>
                            </div>
                            <div id="editFileName"></div>
                            <div id="currentPdfInfo" class="mt-2" style="font-size:13px; color:#555;"></div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="updateResearch()">Update</button>
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
                    <p id="deleteMessage">Are you sure you want to delete this research?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/aos@2.3.4/dist/aos.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/simplebar@6.2.7/dist/simplebar.min.js"></script>
    <script src="js/main.js"></script>

    
    <script>

const canAddResearch = <?= hasFeature("addResearch") ? "true" : "false" ?>;
const canEditResearch = <?= hasFeature("editResearch") ? "true" : "false" ?>;
const canDeleteResearch = <?= hasFeature("deleteResearch") ? "true" : "false" ?>;

        // ── Modals ──────────────────────────────────────────────
        const addResearchModal   = new bootstrap.Modal(document.getElementById('addResearchModal'));
        const editResearchModal  = new bootstrap.Modal(document.getElementById('editResearchModal'));
        const deleteResearchModal = new bootstrap.Modal(document.getElementById('deleteModal'));

        // ── Open Add Modal ──────────────────────────────────────
const addResearchBtn = document.getElementById("addNewResearchBtn");

if (addResearchBtn) {
    addResearchBtn.addEventListener("click", function () {
        document.getElementById('addResearchForm').reset();
        document.getElementById('addFileName').textContent = '';
        addResearchModal.show();
    });
}

        // ── File name display ───────────────────────────────────
        function showFileName(input, wrapperId, labelId) {
            const name = input.files[0] ? input.files[0].name : '';
            document.getElementById(labelId).textContent = name ? '📄 ' + name : '';
        }

        // ── Load users into author dropdowns ────────────────────
        async function loadUsers() {
            try {
                const res  = await fetch('fetchUsersComboBox.php');
                const users = await res.json();

                ['add-author', 'edit-author'].forEach(id => {
                    const sel = document.getElementById(id);
                    // keep first placeholder option
                    while (sel.options.length > 1) sel.remove(1);
                    users.forEach(u => {
                        const opt = document.createElement('option');
                        opt.value       = u.id;
                        opt.textContent = u.name;
                        sel.appendChild(opt);
                    });
                });
            } catch (err) {
                console.error('Error loading users:', err);
            }
        }

        // ── Research list (global) ──────────────────────────────
        let researchList = [];

        async function loadResearch() {
            try {
                const res   = await fetch('fetchResearch.php');
                researchList = await res.json();
                renderResearch(researchList);
            } catch (err) {
                console.error('Error fetching research:', err);
            }
        }

        // ── Render table ────────────────────────────────────────
function renderResearch(list) {

    const tbody = document.getElementById('researchTableBody');
    tbody.innerHTML = '';

    if (!list || list.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="text-center">
                    No research found
                </td>
            </tr>`;
        return;
    }

    list.forEach((r, i) => {

        const badgeClass =
            r.research_type === 'Islamic Topic'
                ? 'badge-islamic'
                : r.research_type === 'Science & Technology'
                    ? 'badge-science'
                    : 'badge-other';

        let actionButtons = `
            <button class="btn btn-sm btn-info text-white"
                onclick="viewResearch(${r.id})">
                View
            </button>
        `;

        if (canEditResearch) {
            actionButtons += `
                <button class="btn btn-sm btn-primary"
                    onclick="editResearch(${r.id})">
                    Edit
                </button>
            `;
        }

        if (canDeleteResearch) {
            actionButtons += `
                <button class="btn btn-sm btn-danger"
                    onclick="deleteResearch(${r.id}, this)">
                    Delete
                </button>
            `;
        }

        tbody.innerHTML += `
            <tr>
                <td>${i + 1}</td>
                <td>${r.title}</td>
                <td>${r.author_name ?? '-'}</td>
                <td>
                    <span class="${badgeClass}">
                        ${r.research_type}
                    </span>
                </td>
                <td>${formatDate(r.created_at)}</td>
                <td>${actionButtons}</td>
            </tr>
        `;
    });

}
        function formatDate(dt) {
            if (!dt) return '-';
            const d = new Date(dt);
            return `${String(d.getDate()).padStart(2,'0')}-${String(d.getMonth()+1).padStart(2,'0')}-${d.getFullYear()}`;
        }

        // ── Search ──────────────────────────────────────────────
        document.getElementById('searchInput').addEventListener('input', function () {
            const q = this.value.toLowerCase().trim();
            const filtered = researchList.filter(r =>
                r.title.toLowerCase().includes(q) ||
                (r.author_name ?? '').toLowerCase().includes(q) ||
                r.research_type.toLowerCase().includes(q)
            );
            renderResearch(filtered);
        });

        // ── Sort ────────────────────────────────────────────────
        let sortDir = {};
        function sortTable(col) {
            sortDir[col] = !sortDir[col];
            researchList.sort((a, b) => {
                let A = (a[col] ?? '').toString().toLowerCase();
                let B = (b[col] ?? '').toString().toLowerCase();
                if (col === 'created_at') { A = new Date(a[col]).getTime(); B = new Date(b[col]).getTime(); }
                return A < B ? (sortDir[col] ? -1 : 1) : A > B ? (sortDir[col] ? 1 : -1) : 0;
            });
            renderResearch(researchList);
        }

        // ── Add Research ────────────────────────────────────────
        function addResearch() {
            const form = document.getElementById('addResearchForm');
            if (!form.checkValidity()) { form.reportValidity(); return; }

            const data = new FormData(form);

            fetch('addResearch.php', { method: 'POST', body: data })
                .then(r => r.text())
                .then(msg => {
                    addResearchModal.hide();
                    showSuccessModal(msg, loadResearch);
                })
                .catch(err => console.error(err));
        }

        // ── View PDF ────────────────────────────────────────────
        function viewResearch(id) {
            window.open(`viewResearchPDF.php?id=${id}`, '_blank');
        }

        // ── Edit Research ────────────────────────────────────────
        function editResearch(id) {
            const r = researchList.find(x => x.id == id);
            if (!r) return;

            document.getElementById('edit-research-id').value = r.id;
            document.getElementById('edit-title').value        = r.title;
            document.getElementById('edit-type').value         = r.research_type;
            document.getElementById('editFileName').textContent = '';

            // set author after users loaded
            const sel = document.getElementById('edit-author');
            sel.value = r.author_id;

            document.getElementById('currentPdfInfo').innerHTML =
                r.pdf_path ? `Current PDF: <strong>${r.pdf_path.split('/').pop()}</strong>` : 'No PDF uploaded';

            editResearchModal.show();
        }

        function updateResearch() {
            const form = document.getElementById('editResearchForm');
            const data = new FormData(form);

            fetch('updateResearch.php', { method: 'POST', body: data })
                .then(r => r.text())
                .then(msg => {
                    editResearchModal.hide();
                    showSuccessModal(msg, loadResearch);
                })
                .catch(err => console.error(err));
        }

        // ── Delete Research ──────────────────────────────────────
        let selectedResearchId = null;

        function deleteResearch(id, btn) {
            selectedResearchId = id;
            const row   = btn.closest('tr');
            const title = row.children[1].innerText;
            document.getElementById('deleteMessage').textContent =
                `Are you sure you want to delete "${title}"?`;
            deleteResearchModal.show();
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
            if (!selectedResearchId) return;
            fetch(`deleteResearch.php?id=${selectedResearchId}`)
                .then(r => r.text())
                .then(() => {
                    deleteResearchModal.hide();
                    selectedResearchId = null;
                    loadResearch();
                });
        });

        // ── Success Modal helper ─────────────────────────────────
        function showSuccessModal(msg, callback) {
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
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
            document.getElementById('successModal').addEventListener('hidden.bs.modal', function () {
                if (callback) callback();
                this.remove();
            });
        }

        // ── Init ────────────────────────────────────────────────
        loadUsers();
        loadResearch();
    </script>

</body>
</html>