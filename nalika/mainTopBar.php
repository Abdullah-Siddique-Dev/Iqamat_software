  <?php

  ?>
  <div class="all-content-wrapper">
    <div class="container-fluid">
      <div class="row">
        <div class="col-lg-12 col-md-12 col-sm-12 col-12">
          <div class="logo-pro">
            <a href="dashboard.php"><img class="main-logo" src="img/logo/logo.png" alt="" /></a>
          </div>
        </div>
      </div>
    </div>
    <div class="header-advance-area">
      <div class="header-top-area">
        <div class="container-fluid">
          <div class="nk-header-bar">
            <!-- Sidebar Toggle -->
            <button
              type="button"
              id="sidebarCollapse"
              class="btn text-white p-0 me-3 border-0 text-decoration-none"
              style="font-size: 20px">
              <i class="icon nalika-menu-task"></i>
            </button>

            <!-- Search -->
            <div class="nk-header-search d-none d-lg-block">
              <input
                type="text"
                class="nk-search-input"
                placeholder="Search..." />
              <i class="bi bi-search nk-search-icon"></i>
            </div>

            <!-- Right Icons -->
            <div class="nk-header-right ms-auto">
              <!-- Messages -->
              <!-- <div class="dropdown">
                <a
                  href="#"
                  class="nk-header-icon"
                  data-bs-toggle="dropdown"
                  data-bs-auto-close="outside"
                  aria-expanded="false">
                  <i class="icon nalika-mail"></i>
                  <span class="nk-badge"></span>
                </a>
                <div class="dropdown-menu dropdown-menu-end">
                  <h6 class="nk-dd-title">Messages</h6>
                  <div class="nk-dd-body">
                    <a href="#" class="nk-dd-item">
                      <img src="img/contact/1.svg" alt="" />
                      <div class="nk-dd-item-body">
                        <div class="d-flex justify-content-between">
                          <h6>
                            <?php
                              echo htmlspecialchars($_SESSION['user']['username'] ?? 'Guest');
                            ?>
                            <span class="min-dtn"></span>
                          </h6>
                          <span class="nk-dd-date">16 Sept</span>
                        </div>
                        <p>Please done this project as soon possible.</p>
                      </div>
                    </a>
                    <a href="#" class="nk-dd-item">
                      <img src="img/contact/4.svg" alt="" />
                      <div class="nk-dd-item-body">
                        <div class="d-flex justify-content-between">
                          <h6>Sulaiman din</h6>
                          <span class="nk-dd-date">16 Sept</span>
                        </div>
                        <p>Please done this project as soon possible.</p>
                      </div>
                    </a>
                    <a href="#" class="nk-dd-item">
                      <img src="img/contact/3.svg" alt="" />
                      <div class="nk-dd-item-body">
                        <div class="d-flex justify-content-between">
                          <h6>Victor Jara</h6>
                          <span class="nk-dd-date">16 Sept</span>
                        </div>
                        <p>Please done this project as soon possible.</p>
                      </div>
                    </a>
                    <a href="#" class="nk-dd-item">
                      <img src="img/contact/2.svg" alt="" />
                      <div class="nk-dd-item-body">
                        <div class="d-flex justify-content-between">
                          <h6>Victor Jara</h6>
                          <span class="nk-dd-date">16 Sept</span>
                        </div>
                        <p>Please done this project as soon possible.</p>
                      </div>
                    </a>
                  </div>
                  <div class="nk-dd-footer">
                    <a href="#">View All Messages</a>
                  </div>
                </div>
              </div> -->

              <!-- Notifications -->
<div class="dropdown">
  <a href="#" class="nk-header-icon" id="notifBell"
     data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
    <i class="icon nalika-alarm"></i>
    <span class="nk-badge" id="notifBadge" style="display:none;"></span>
  </a>
  <div class="dropdown-menu dropdown-menu-end">
    <h6 class="nk-dd-title">Notifications</h6>
    <div class="nk-dd-body" id="notifBody">
      <!-- filled dynamically -->
    </div>
    <div class="nk-dd-footer">
      <a href="#">View All Notifications</a>
    </div>
  </div>
</div>

              <!-- User Profile -->
              <div class="dropdown">
                <a
                  href="#"
                  class="nk-header-icon nk-header-user"
                  data-bs-toggle="dropdown"
                  aria-expanded="false">
                  <i class="icon nalika-user"></i>
                  <span class="nk-user-name d-none d-md-inline">
                    <div class="d-flex justify-content-between">
                      <?php
                      echo isset($_SESSION['user']) ? htmlspecialchars($_SESSION['user']['username']) : "Guest";
                      ?>
                      <span class="min-dtn"></span>

                    </div>
                  </span>
                  <i class="bi bi-chevron-down" style="font-size: 12px"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end nk-user-menu">
                  <li class="px-3 py-2 text-center" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <span style="background:rgba(13,110,253,.18);border:1px solid rgba(13,110,253,.35);color:#60a5fa;border-radius:20px;padding:3px 10px;font-size:0.75rem;font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                        <i class="bi bi-credit-card-2-front-fill"></i> <?= htmlspecialchars($loggedCard ?? 'Diamond') ?> | <?= htmlspecialchars($loggedCategory ?? 'B') ?>
                    </span>
                  </li>
                  <li>
                    <a class="dropdown-item" href="userProfile.php"><i class="icon nalika-user me-2"></i>My Profile</a>
                  </li>
                  <!-- <li>
                    <a class="dropdown-item" href="lock.html"><i class="icon nalika-diamond me-2"></i>Lock</a>
                  </li> -->
                  <li>
                    <a class="dropdown-item" href="userProfile.php#settings"><i class="icon nalika-settings me-2"></i>Settings</a>
                  </li> 
                  <li>
                    <hr class="dropdown-divider" />
                  </li>
                  <li>
                    <a class="dropdown-item" href="logout.php">
                      <i class="icon nalika-unlocked me-2"></i>Log Out
                    </a>
                  </li>
                </ul>
              </div>

              <!-- Settings Panel -->
              <!-- <div class="dropdown">
                <a
                  href="#"
                  class="nk-header-icon"
                  data-bs-toggle="dropdown"
                  data-bs-auto-close="outside"
                  aria-expanded="false">
                  <i class="icon nalika-menu-task"></i>
                </a>
                <div
                  class="dropdown-menu dropdown-menu-end nk-settings-panel">
                  <ul class="nav nav-tabs nav-fill" role="tablist">
                    <li class="nav-item">
                      <button
                        class="nav-link active"
                        data-bs-toggle="tab"
                        data-bs-target="#nkNews"
                        type="button">
                        News
                      </button>
                    </li>
                    <li class="nav-item">
                      <button
                        class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#nkActivity"
                        type="button">
                        Activity
                      </button>
                    </li>
                    <li class="nav-item">
                      <button
                        class="nav-link"
                        data-bs-toggle="tab"
                        data-bs-target="#nkSettings"
                        type="button">
                        Settings
                      </button>
                    </li>
                  </ul>
                  <div class="tab-content">
                   
                    <div class="tab-pane fade show active" id="nkNews">
                      <div class="nk-panel-heading">
                        <h6>
                          <i class="icon nalika-chat me-1"></i> Latest News
                        </h6>
                        <p>You have 10 New News.</p>
                      </div>
                      <a href="#" class="nk-panel-item">
                        <img src="img/contact/4.svg" alt="" />
                        <div class="nk-panel-item-body">
                          <p>
                            The point of using Lorem Ipsum is that it has a
                            more-or-less normal.
                          </p>
                          <small>Yesterday 2:45 pm</small>
                        </div>
                      </a>
                      <a href="#" class="nk-panel-item">
                        <img src="img/contact/1.svg" alt="" />
                        <div class="nk-panel-item-body">
                          <p>
                            The point of using Lorem Ipsum is that it has a
                            more-or-less normal.
                          </p>
                          <small>Yesterday 2:45 pm</small>
                        </div>
                      </a>
                      <a href="#" class="nk-panel-item">
                        <img src="img/contact/2.svg" alt="" />
                        <div class="nk-panel-item-body">
                          <p>
                            The point of using Lorem Ipsum is that it has a
                            more-or-less normal.
                          </p>
                          <small>Yesterday 2:45 pm</small>
                        </div>
                      </a>
                      <a href="#" class="nk-panel-item">
                        <img src="img/contact/3.svg" alt="" />
                        <div class="nk-panel-item-body">
                          <p>
                            The point of using Lorem Ipsum is that it has a
                            more-or-less normal.
                          </p>
                          <small>Yesterday 2:45 pm</small>
                        </div>
                      </a>
                      <a href="#" class="nk-panel-item">
                        <img src="img/contact/4.svg" alt="" />
                        <div class="nk-panel-item-body">
                          <p>
                            The point of using Lorem Ipsum is that it has a
                            more-or-less normal.
                          </p>
                          <small>Yesterday 2:45 pm</small>
                        </div>
                      </a>
                    </div>
                  
                    <div class="tab-pane fade" id="nkActivity">
                      <div class="nk-panel-heading">
                        <h6>
                          <i class="icon nalika-happiness me-1"></i> Recent
                          Activity
                        </h6>
                        <p>You have 20 Recent Activity.</p>
                      </div>
                      <div class="nk-activity-item">
                        <h6>New User Registered</h6>
                        <p>
                          The point of using Lorem Ipsum is that it has a more
                          or less normal.
                        </p>
                        <small>1 hours ago</small>
                      </div>
                      <div class="nk-activity-item">
                        <h6>New Order Received</h6>
                        <p>
                          The point of using Lorem Ipsum is that it has a more
                          or less normal.
                        </p>
                        <small>2 hours ago</small>
                      </div>
                      <div class="nk-activity-item">
                        <h6>New Order Received</h6>
                        <p>
                          The point of using Lorem Ipsum is that it has a more
                          or less normal.
                        </p>
                        <small>3 hours ago</small>
                      </div>
                      <div class="nk-activity-item">
                        <h6>New Order Received</h6>
                        <p>
                          The point of using Lorem Ipsum is that it has a more
                          or less normal.
                        </p>
                        <small>4 hours ago</small>
                      </div>
                      <div class="nk-activity-item">
                        <h6>New User Registered</h6>
                        <p>
                          The point of using Lorem Ipsum is that it has a more
                          or less normal.
                        </p>
                        <small>5 hours ago</small>
                      </div>
                    </div>
                 
                    <div class="tab-pane fade" id="nkSettings">
                      <div class="nk-panel-heading">
                        <h6>
                          <i class="icon nalika-gear me-1"></i> Settings Panel
                        </h6>
                        <p>You have 20 Settings. 5 not completed.</p>
                      </div>
                      <div class="nk-setting-item">
                        <label for="nkOpt1">Show notifications</label>
                        <div class="form-check form-switch mb-0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            id="nkOpt1" />
                        </div>
                      </div>
                      <div class="nk-setting-item">
                        <label for="nkOpt2">Disable Chat</label>
                        <div class="form-check form-switch mb-0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            id="nkOpt2" />
                        </div>
                      </div>
                      <div class="nk-setting-item">
                        <label for="nkOpt3">Enable history</label>
                        <div class="form-check form-switch mb-0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            id="nkOpt3" />
                        </div>
                      </div>
                      <div class="nk-setting-item">
                        <label for="nkOpt4">Show charts</label>
                        <div class="form-check form-switch mb-0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            id="nkOpt4" />
                        </div>
                      </div>
                      <div class="nk-setting-item">
                        <label for="nkOpt5">Update everyday</label>
                        <div class="form-check form-switch mb-0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            id="nkOpt5"
                            checked />
                        </div>
                      </div>
                      <div class="nk-setting-item">
                        <label for="nkOpt6">Global search</label>
                        <div class="form-check form-switch mb-0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            id="nkOpt6"
                            checked />
                        </div>
                      </div>
                      <div class="nk-setting-item">
                        <label for="nkOpt7">Offline users</label>
                        <div class="form-check form-switch mb-0">
                          <input
                            class="form-check-input"
                            type="checkbox"
                            id="nkOpt7"
                            checked />
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div> -->
            </div>
          </div>
        </div>
      </div>

      <script>

function loadNotifications() {
  fetch('get_notifications.php')
    .then(res => res.json())
    .then(data => {
      const badge = document.getElementById('notifBadge');
      const body = document.getElementById('notifBody');

      if (data.unread > 0) {
        badge.style.display = 'block';
      } else {
        badge.style.display = 'none';
      }

      body.innerHTML = '';
      if (data.notifications.length === 0) {
        body.innerHTML = '<p class="text-center p-2">No notifications</p>';
        return;
      }

      data.notifications.forEach(n => {
        const item = document.createElement('a');
        item.href = '#';
        item.className = 'nk-dd-item';
        item.innerHTML = `
          <div class="nk-dd-item-body">
            <div class="d-flex justify-content-between">
              <h6>${n.type}</h6>
              <span class="nk-dd-date">${n.created_at}</span>
            </div>
            <p>${n.message}</p>
          </div>`;
        body.appendChild(item);
      });
    });
}

// Mark as read when dropdown is opened
document.getElementById('notifBell').addEventListener('click', () => {
  fetch('mark_notifications_read.php').then(() => {
    setTimeout(() => document.getElementById('notifBadge').style.display = 'none', 500);
  });
});

// Poll every 30s + on page load
loadNotifications();
setInterval(loadNotifications, 30000);

      </script>