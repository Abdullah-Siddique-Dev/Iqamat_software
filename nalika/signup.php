<?php include "connection.php"; ?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <title>Register | Nalika - Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <link rel="shortcut icon" type="image/x-icon" href="../graphics/Logo/Iqamat Logo Transparent.png" />
  <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css" />
  <style>
    body {
      background: #152036;
      font-family: "Roboto", sans-serif;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 15px;
      margin: 0;
    }

    .nk-auth-card {
      background: #1b2a47;
      border: 1px solid rgba(255, 255, 255, 0.06);
      border-radius: 12px;
      width: 100%;
      max-width: 520px;
      padding: 18px 24px;
    }

    .nk-auth-logo {
      font-size: 24px;
      font-weight: 700;
      color: #fff;
      letter-spacing: 1px;
      margin-bottom: 8px !important;
    }

    .nk-auth-logo img {
      width: 110px !important;
    }

    .nk-auth-card .form-control,
    .nk-auth-card .form-select {
      background: #152036;
      border: 1px solid #253a5c;
      color: #fff;
      border-radius: 6px;
      padding: 6px 12px;
      font-size: 13px;
      height: 36px;
    }

    .nk-auth-card .form-control:focus,
    .nk-auth-card .form-select:focus {
      border-color: #03a9f4;
      box-shadow: 0 0 0 2px rgba(3, 169, 244, 0.15);
      background: #152036;
      color: #fff;
    }

    .nk-auth-card .form-control::placeholder {
      color: #6c7a8d;
    }

    .nk-auth-card .form-label {
      color: #8a9bb5;
      font-size: 12px;
      font-weight: 500;
      margin-bottom: 2px;
    }

    .nk-auth-card .mb-3 {
      margin-bottom: 8px !important;
    }

    .nk-auth-card .mb-4 {
      margin-bottom: 10px !important;
    }

    .nk-auth-card .form-check-label {
      color: #8a9bb5;
      font-size: 12px;
    }

    .nk-auth-card .form-check-input {
      background-color: #253a5c;
      border-color: #253a5c;
    }

    .nk-auth-card .form-check-input:checked {
      background-color: #03a9f4;
      border-color: #03a9f4;
    }

    .btn-nk-primary {
      background: #03a9f4;
      border: none;
      color: #fff;
      border-radius: 6px;
      padding: 8px;
      font-weight: 600;
      font-size: 14px;
      transition: background 0.2s;
    }

    .btn-nk-primary:hover {
      background: #0290d1;
      color: #fff;
    }

    .btn-nk-outline {
      background: transparent;
      border: 1px solid #253a5c;
      color: #8a9bb5;
      border-radius: 6px;
      padding: 6px;
      font-size: 13px;
      transition: all 0.2s;
    }

    .btn-nk-outline:hover {
      border-color: #03a9f4;
      color: #03a9f4;
    }

    .nk-auth-footer {
      color: #5a6a7f;
      font-size: 12px;
      margin-top: 10px !important;
    }

    .nk-auth-footer a {
      color: #03a9f4;
      text-decoration: none;
    }

    .nk-auth-footer a:hover {
      text-decoration: underline;
    }

    .nk-divider {
      display: flex;
      align-items: center;
      color: #5a6a7f;
      font-size: 11px;
      margin: 8px 0;
    }

    .nk-divider::before,
    .nk-divider::after {
      content: "";
      flex: 1;
      height: 1px;
      background: #253a5c;
    }

    .nk-divider span {
      padding: 0 12px;
    }

    /* ── MODAL OVERLAY ── */
    #photoModal {
      display: none;
      position: fixed;
      inset: 0;
      background: rgba(10, 16, 28, 0.82);
      z-index: 9999;
      align-items: center;
      justify-content: center;
      animation: fadeIn 0.2s ease;
    }

    #photoModal.active {
      display: flex;
    }

    @keyframes fadeIn {
      from {
        opacity: 0;
      }

      to {
        opacity: 1;
      }
    }

    .photo-modal-card {
      background: #1b2a47;
      border: 1px solid rgba(255, 255, 255, 0.08);
      border-radius: 16px;
      width: 100%;
      max-width: 440px;
      padding: 36px 32px 28px;
      margin: 16px;
      animation: slideUp 0.25s ease;
      position: relative;
    }

    @keyframes slideUp {
      from {
        transform: translateY(24px);
        opacity: 0;
      }

      to {
        transform: translateY(0);
        opacity: 1;
      }
    }

    .photo-modal-title {
      color: #fff;
      font-size: 18px;
      font-weight: 700;
      margin-bottom: 4px;
    }

    .photo-modal-sub {
      color: #8a9bb5;
      font-size: 13px;
      margin-bottom: 24px;
    }

    .drop-zone {
      border: 2px dashed #253a5c;
      border-radius: 12px;
      padding: 32px 20px;
      text-align: center;
      cursor: pointer;
      transition: all 0.2s ease;
      position: relative;
      background: #152036;
    }

    .drop-zone:hover,
    .drop-zone.dragover {
      border-color: #03a9f4;
      background: rgba(3, 169, 244, 0.04);
    }

    .drop-zone input[type="file"] {
      position: absolute;
      inset: 0;
      opacity: 0;
      cursor: pointer;
      width: 100%;
      height: 100%;
    }

    .drop-zone-icon {
      font-size: 36px;
      color: #03a9f4;
      margin-bottom: 8px;
    }

    .drop-zone-title {
      color: #c8d6e8;
      font-size: 14px;
      font-weight: 500;
      margin-bottom: 4px;
    }

    .drop-zone-hint {
      color: #5a6a7f;
      font-size: 12px;
    }

    /* Preview state */
    .preview-wrap {
      display: none;
      flex-direction: column;
      align-items: center;
      gap: 12px;
    }

    .preview-wrap.active {
      display: flex;
    }

    .drop-zone.has-preview {
      padding: 20px;
    }

    .preview-avatar {
      width: 96px;
      height: 96px;
      border-radius: 50%;
      object-fit: cover;
      border: 3px solid #03a9f4;
      display: block;
    }

    .preview-filename {
      color: #8a9bb5;
      font-size: 12px;
      word-break: break-all;
      text-align: center;
    }

    .btn-remove-photo {
      background: rgba(255, 77, 77, 0.12);
      border: 1px solid rgba(255, 77, 77, 0.25);
      color: #ff6b6b;
      border-radius: 6px;
      padding: 4px 12px;
      font-size: 12px;
      cursor: pointer;
      transition: background 0.2s;
    }

    .btn-remove-photo:hover {
      background: rgba(255, 77, 77, 0.22);
    }

    /* Error message */
    .upload-error {
      display: none;
      color: #ff6b6b;
      font-size: 12px;
      margin-top: 8px;
      padding: 8px 12px;
      background: rgba(255, 77, 77, 0.08);
      border: 1px solid rgba(255, 77, 77, 0.2);
      border-radius: 6px;
    }

    .upload-error.active {
      display: block;
    }

    /* Modal action buttons */
    .modal-actions {
      display: flex;
      gap: 12px;
      margin-top: 24px;
    }

    .btn-modal-skip {
      flex: 1;
      background: transparent;
      border: 1px solid #253a5c;
      color: #8a9bb5;
      border-radius: 8px;
      padding: 11px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s;
    }

    .input-group .form-control {
      border-radius: 8px 0 0 8px !important;
    }

    .btn-modal-skip:hover {
      border-color: #8a9bb5;
      color: #c8d6e8;
    }

    .btn-modal-register {
      flex: 2;
      background: #03a9f4;
      border: none;
      color: #fff;
      border-radius: 8px;
      padding: 11px;
      font-size: 14px;
      font-weight: 500;
      cursor: pointer;
      transition: background 0.2s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 6px;
    }

    .btn-modal-register:hover {
      background: #0290d1;
    }

    .btn-modal-register:disabled {
      background: #1e4060;
      color: #4a6a80;
      cursor: not-allowed;
    }
  </style>
</head>

<body>
  <div class="text-center px-3">
    <div class="nk-auth-card mx-auto mb-4">
      <div class="nk-auth-logo mb-2">
        <img src="../graphics/Logo/Logo Iqamat-19.png" alt="" style="width:110px;" />
      </div>

      <form action="register.php" method="POST" id="registerForm" enctype="multipart/form-data">
        <div class="row">
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-firstName">First Name</label>
            <input type="text" name="firstName" id="reg-firstName" class="form-control" placeholder="Enter first name" required />
          </div>
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-lastName">Last Name</label>
            <input type="text" name="lastName" id="reg-lastName" class="form-control" placeholder="Enter last name" required />
          </div>
        </div>

        <div class="row">
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-password">Password</label>
            <div class="input-group">
              <input type="password" name="password" id="reg-password" class="form-control" placeholder="Create password" required />
              <button type="button" class="btn" id="togglePass"
                style="background:#152036;border:1px solid #253a5c;border-left:none;color:#6c7a8d;border-radius:0 8px 8px 0;">
                <i class="bi bi-eye" id="togglePassIcon"></i>
              </button>
            </div>
            <small id="password-strength" class="mt-1 d-block"></small>
          </div>
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-password2">Confirm Password</label>
            <div class="input-group">
              <input type="password" name="confirm_password" id="reg-password2" class="form-control" placeholder="Repeat password" required />
              <button type="button" class="btn" id="togglePass2"
                style="background:#152036;border:1px solid #253a5c;border-left:none;color:#6c7a8d;border-radius:0 8px 8px 0;">
                <i class="bi bi-eye" id="togglePass2Icon"></i>
              </button>
            </div>
            <small id="password-match" class="mt-1 d-block"></small>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-email">Email</label>
            <input type="email" name="email" id="reg-email" class="form-control" placeholder="you@example.com" required />
          </div>
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label">Contact</label>
            <input type="text" name="phone" class="form-control" placeholder="Phone Number" required />
          </div>
        </div>

        <div class="row">
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label">Age</label>
            <input type="number" name="age" class="form-control" placeholder="Enter Your Age" required />
          </div>
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label">Gender</label>
            <div class="d-flex gap-3 mt-2">
              <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="male" value="Male" required />
                <label class="form-check-label" for="male">Male</label>
              </div>
              <div class="form-check">
                <input class="form-check-input" type="radio" name="gender" id="female" value="Female" />
                <label class="form-check-label" for="female">Female</label>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-card">Card <span style="color:#dc3545;">*</span></label>
            <select name="card" id="reg-card" class="form-control" required>
              <option value="" disabled selected>Select Card</option>
              <option value="Diamond">Diamond</option>
              <option value="Gold">Gold</option>
              <option value="Silver">Silver</option>
              <option value="Metal">Metal</option>
            </select>
          </div>
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-category">Category <span style="color:#dc3545;">*</span></label>
            <select name="category" id="reg-category" class="form-control" required>
              <option value="" disabled selected>Select Category</option>
              <option value="A">A</option>
              <option value="B">B</option>
              <option value="C">C</option>
              <option value="D">D</option>
            </select>
          </div>
        </div>

        <div class="row">
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-role">Type <span style="color:#dc3545;">*</span></label>
            <select name="type" id="reg-role" class="form-control" required>
              <option value="" disabled selected>Select type</option>
              <option value="member">Member</option>
              <option value="trainee">Trainee</option>
              <option value="committee">Committee Member</option>
              <option value="representative" id="repOption">Representative</option>
            </select>
          </div>
          <div class="col-sm-6 mb-3 text-start">
            <label class="form-label" for="reg-area">Dars Area <span style="color:#dc3545;">*</span></label>
            <select name="area" id="reg-area" class="form-control" required>
              <option value="" disabled selected>Select your Dars Area</option>
              <?php
              $areasList = [];
              if (isset($conn) && $conn) {
                  $areasResult = @mysqli_query($conn, "SELECT areaName FROM dars_areas ORDER BY areaName ASC");
                  if ($areasResult && mysqli_num_rows($areasResult) > 0) {
                      while ($row = mysqli_fetch_assoc($areasResult)) {
                          $areasList[] = $row['areaName'];
                      }
                  }
              }
              if (empty($areasList)) {
                  $areasList = [
                      "Gulshan Colony",
                      "PM Colony",
                      "Asifabad Colony",
                      "Anwar Chowk",
                      "Rawalpindi"
                  ];
              }
              foreach ($areasList as $aName):
              ?>
                <option value="<?php echo htmlspecialchars($aName); ?>"><?php echo htmlspecialchars($aName); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

        <div class="mb-3 text-start mt-2">
          <div class="form-check">
            <input type="checkbox" class="form-check-input" id="newsletter" checked />
            <label class="form-check-label" for="newsletter">Sign up for our newsletter</label>
          </div>
        </div>

        <!-- Hidden file input that gets populated from the modal -->
        <input type="file" name="profile_image" id="hiddenFileInput" style="display:none;" accept="image/*" />

        <button type="button" class="btn btn-nk-primary w-100 mb-2" id="openModalBtn">
          Create Account
        </button>
        <div class="nk-divider"><span>already have an account?</span></div>
        <a href="../index.php" class="btn btn-nk-outline w-100">Sign In</a>
      </form>
    </div>

    <a href="../index.php" class="text-decoration-none" style="color:#5a6a7f;font-size:13px;">
      <i class="bi bi-arrow-left me-1"></i>Back to Sign In
    </a>
    <div class="nk-auth-footer mt-2">
      <?php include "footer.php"; ?>
    </div>
  </div>

  <!-- ── PHOTO UPLOAD MODAL ── -->
  <div id="photoModal">
    <div class="photo-modal-card">
      <p class="photo-modal-title">Add a Profile Photo</p>
      <p class="photo-modal-sub">Help others recognize you — upload a photo or skip to continue.</p>

      <!-- Drop Zone (default state) -->
      <div class="drop-zone" id="dropZone">
        <input type="file" id="modalFileInput" accept="image/jpeg,image/png,image/gif,image/webp" />

        <div id="dropPlaceholder">
          <div class="drop-zone-icon">
            <i class="bi bi-cloud-arrow-up"></i>
          </div>
          <p class="drop-zone-title">Drag &amp; drop your photo here</p>
          <p class="drop-zone-hint">or <span style="color:#03a9f4;">click to browse</span> &nbsp;·&nbsp; JPG, PNG, WEBP</p>
        </div>

        <!-- Preview state (shown after file chosen) -->
        <div class="preview-wrap" id="previewWrap">
          <img id="previewImg" class="preview-avatar" src="" alt="Profile preview" />
          <p class="preview-filename" id="previewName"></p>
          <button type="button" class="btn-remove-photo" id="removePhotoBtn">
            <i class="bi bi-x me-1"></i>Remove
          </button>
        </div>
      </div>

      <div class="upload-error" id="uploadError"></div>

      <!-- Action Buttons -->
      <div class="modal-actions">
        <button type="button" class="btn-modal-skip" id="modalSkipBtn">Skip for now</button>
        <button type="button" class="btn-modal-register" id="modalSubmitBtn">
          Complete Registration <i class="bi bi-arrow-right"></i>
        </button>
      </div>
    </div>
  </div>

  <script>
    // Password toggle
    const togglePass = document.getElementById("togglePass");
    const passInput = document.getElementById("reg-password");
    const toggleIcon = document.getElementById("togglePassIcon");

    if (togglePass && passInput) {
      togglePass.addEventListener("click", () => {
        const type = passInput.type === "password" ? "text" : "password";
        passInput.type = type;
        toggleIcon.className = type === "password" ? "bi bi-eye" : "bi bi-eye-slash";
      });
    }

    const togglePass2 = document.getElementById("togglePass2");
    const passInput2 = document.getElementById("reg-password2");
    const toggleIcon2 = document.getElementById("togglePass2Icon");

    if (togglePass2 && passInput2) {
      togglePass2.addEventListener("click", () => {
        const type = passInput2.type === "password" ? "text" : "password";
        passInput2.type = type;
        toggleIcon2.className = type === "password" ? "bi bi-eye" : "bi bi-eye-slash";
      });
    }

    // Modal elements
    const openModalBtn = document.getElementById("openModalBtn");
    const photoModal = document.getElementById("photoModal");
    const registerForm = document.getElementById("registerForm");
    const modalFileInput = document.getElementById("modalFileInput");
    const hiddenFileInput = document.getElementById("hiddenFileInput");
    const modalSkipBtn = document.getElementById("modalSkipBtn");
    const modalSubmitBtn = document.getElementById("modalSubmitBtn");
    const dropZone = document.getElementById("dropZone");
    const dropPlaceholder = document.getElementById("dropPlaceholder");
    const previewWrap = document.getElementById("previewWrap");
    const previewImg = document.getElementById("previewImg");
    const previewName = document.getElementById("previewName");
    const removePhotoBtn = document.getElementById("removePhotoBtn");
    const uploadError = document.getElementById("uploadError");

    let selectedFile = null;

    // Real-time input filters (letters-only & phone-only)
    function setupInputFilters() {
      function showNotice(el, msg) {
        let parent = el.parentElement;
        let existing = parent.querySelector('.input-filter-notice');
        if (existing) {
          existing.textContent = msg;
          existing.style.display = 'block';
          clearTimeout(existing._t);
          existing._t = setTimeout(() => existing.style.display = 'none', 2500);
          return;
        }
        let note = document.createElement('small');
        note.className = 'input-filter-notice text-danger d-block mt-1';
        note.style.fontSize = '0.75rem';
        note.style.fontWeight = '500';
        note.textContent = msg;
        parent.appendChild(note);
        note._t = setTimeout(() => note.style.display = 'none', 2500);
      }

      ['reg-firstName', 'reg-lastName'].forEach(id => {
        const inp = document.getElementById(id);
        if (!inp) return;
        inp.addEventListener('keypress', e => {
          if (/[0-9]/.test(e.key)) {
            e.preventDefault();
            showNotice(inp, 'Only letters allowed. No digits permitted.');
          }
        });
        inp.addEventListener('input', () => {
          if (/[0-9]/.test(inp.value)) {
            inp.value = inp.value.replace(/[0-9]/g, '');
            showNotice(inp, 'Only letters allowed. No digits permitted.');
          }
        });
      });

      const phoneInp = document.querySelector('input[name="phone"]');
      if (phoneInp) {
        phoneInp.addEventListener('keypress', e => {
          if (!/[0-9+\-\s]/.test(e.key) && e.key.length === 1) {
            e.preventDefault();
            showNotice(phoneInp, 'Only numbers allowed. No letters permitted.');
          }
        });
        phoneInp.addEventListener('input', () => {
          if (/[a-zA-Z]/.test(phoneInp.value)) {
            phoneInp.value = phoneInp.value.replace(/[a-zA-Z]/g, '');
            showNotice(phoneInp, 'Only numbers allowed. No letters permitted.');
          }
        });
      }
    }
    setupInputFilters();

    // Validate form before opening modal
    openModalBtn.addEventListener("click", function() {
      if (!registerForm.checkValidity()) {
        registerForm.reportValidity();
        return;
      }
      const fn = document.getElementById("reg-firstName").value.trim();
      const ln = document.getElementById("reg-lastName").value.trim();
      const ph = document.querySelector('input[name="phone"]').value.trim();

      if (/[0-9]/.test(fn)) {
        alert("First Name: Only letters allowed. No digits permitted.");
        document.getElementById("reg-firstName").focus();
        return;
      }
      if (/[0-9]/.test(ln)) {
        alert("Last Name: Only letters allowed. No digits permitted.");
        document.getElementById("reg-lastName").focus();
        return;
      }
      if (/[a-zA-Z]/.test(ph)) {
        alert("Phone: Only numbers allowed. No letters permitted.");
        document.querySelector('input[name="phone"]').focus();
        return;
      }
      photoModal.classList.add("active");
    });

    // Drag & Drop
    ["dragenter", "dragover"].forEach(eventName => {
      dropZone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.add("dragover");
      });
    });

    ["dragleave", "drop"].forEach(eventName => {
      dropZone.addEventListener(eventName, e => {
        e.preventDefault();
        e.stopPropagation();
        dropZone.classList.remove("dragover");
      });
    });

    dropZone.addEventListener("drop", e => {
      const dt = e.dataTransfer;
      const files = dt.files;
      if (files && files.length > 0) {
        handleFile(files[0]);
      }
    });

    modalFileInput.addEventListener("change", function() {
      if (this.files && this.files.length > 0) {
        handleFile(this.files[0]);
      }
    });

    function handleFile(file) {
      uploadError.classList.remove("active");
      uploadError.textContent = "";

      const validTypes = ["image/jpeg", "image/png", "image/gif", "image/webp"];
      if (!validTypes.includes(file.type)) {
        uploadError.textContent = "Only JPG, PNG, GIF, or WEBP images are allowed.";
        uploadError.classList.add("active");
        return;
      }

      if (file.size > 5 * 1024 * 1024) {
        uploadError.textContent = "File size exceeds 5MB limit.";
        uploadError.classList.add("active");
        return;
      }

      selectedFile = file;

      const reader = new FileReader();
      reader.onload = function(e) {
        previewImg.src = e.target.result;
        previewName.textContent = file.name;
        dropPlaceholder.style.display = "none";
        previewWrap.classList.add("active");
        dropZone.classList.add("has-preview");
      };
      reader.readAsDataURL(file);
    }

    removePhotoBtn.addEventListener("click", e => {
      e.stopPropagation();
      clearPhotoSelection();
    });

    function clearPhotoSelection() {
      selectedFile = null;
      modalFileInput.value = "";
      previewImg.src = "";
      previewName.textContent = "";
      previewWrap.classList.remove("active");
      dropZone.classList.remove("has-preview");
      dropPlaceholder.style.display = "block";
      uploadError.classList.remove("active");
    }

    // Skip photo -> submit form without photo
    modalSkipBtn.addEventListener("click", () => {
      hiddenFileInput.value = "";
      registerForm.submit();
    });

    // Complete registration with photo
    modalSubmitBtn.addEventListener("click", () => {
      if (selectedFile) {
        const dt = new DataTransfer();
        dt.items.add(selectedFile);
        hiddenFileInput.files = dt.files;
      }
      registerForm.submit();
    });

    // Close modal on click outside
    photoModal.addEventListener("click", e => {
      if (e.target === photoModal) {
        photoModal.classList.remove("active");
      }
    });
  </script>
</body>

</html>