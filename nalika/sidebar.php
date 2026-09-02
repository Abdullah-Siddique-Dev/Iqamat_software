<style>
    .comment-scrollbar,
    .timeline-scrollbar,
    .messages-scrollbar,
    .project-list-scrollbar {
        height: calc(100vh - 350px);
        overflow-y: auto;
    }
</style>

<nav id="sidebar" class="">
    <div class="sidebar-header">
        <a href="dashboard.php">
            <img class="main-logo" src="../graphics/Logo/Iqamat Logo Transparent.png" alt="" style="width: 100px;" />
        </a>
        <strong><img src="../graphics/Logo/Iqamat Logo Transparen.png" alt="" /></strong>
    </div>

    <div class="nalika-profile">
        <div class="profile-dtl">
            <?php
             require_once "permissions.php";
            // auth.php is already included on every page before sidebar.php is included.
            // $loggedUser, $loggedFirstName, $loggedLastName, $loggedUsername are all set.
            $profilePic = (!empty($loggedImage) && file_exists($loggedImage))
                ? htmlspecialchars($loggedImage)
                : 'https://ui-avatars.com/api/?name='
                . urlencode($loggedFirstName . '+' . $loggedLastName)
                . '&background=03a9f4&color=fff&size=200';
            ?>
            <a href="userProfile.php">
                <img src="<?php echo $profilePic; ?>"
                    alt="<?php echo htmlspecialchars($loggedUsername); ?>" />
            </a>
            <h2><?php echo htmlspecialchars($loggedUsername); ?> <span class="min-dtn"></span></h2>
        </div>
        <div class="profile-social-dtl">
            <ul class="dtl-social">
                <!-- <li><a href="#"><i class="bi bi-facebook"></i></a></li>
                <li><a href="#"><i class="bi bi-twitter-x"></i></a></li>
                <li><a href="#"><i class="bi bi-linkedin"></i></a></li> -->
            </ul>
        </div>
    </div>

    <div class="left-custom-menu-adp-wrap comment-scrollbar">
        <nav class="sidebar-nav left-sidebar-menu-pro">
            <ul class="metismenu" id="menu1">
                <?php if(hasPermission("pdDarsAttendance")): ?>
                <li class="active">
                    <a href="dashboard.php">
                        <i class="bi big-icon bi-speedometer2 icon-wrap"></i>
                        <span>Dashboards</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(hasPermission("adminTasks")): ?>
                <li>
                    <a href="adminTasks.php">
                        <i class="bi big-icon bi-card-checklist icon-wrap"></i>
                        <span class="mini-click-non">Card System</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(hasPermission("pdDarsAttendance")): ?>
                <li>
                    <a href="upcomingEvents.php">
                        <i class="bi big-icon bi-bag icon-wrap"></i>
                        <span class="mini-click-non">Upcoming Events</span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if(hasPermission("pdDarsAttendance")): ?>

                    <li>
                        <a href="pdDarsAttendance.php">
                            <i class="bi bi-book-half big-icon icon-wrap"></i>
                            <span>Dars Attendance</span>
                        </a>
                    </li>

                <?php endif; ?>
                <!-- <li>
                    <a href="pdDarsAttendance.php">
                        <i class="bi big-icon bi-bag icon-wrap"></i>
                        <span class="mini-click-non">Dars Attendance</span>
                    </a>
                </li> -->
                <?php if(hasPermission("pdQuranAttendance") || hasPermission("pdDawah") || hasPermission("pdNamazAttendance") || hasPermission("pdTimeTracker")): ?>
                <li>
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="bi bi-person-heart big-icon icon-wrap"></i>
                        <span class="mini-click-non">Personal Development</span>
                    </a>
                    <ul class="submenu-angle" aria-expanded="false">
                        <li><a href="pdQuranAttendance.php"><span class="mini-sub-pro"><i class="bi bi-book"></i>  Quran Attendance</span></a></li>
                        <li><a href="pdDawah.php"><span class="mini-sub-pro"><i class="bi bi-megaphone"></i>  Dawah</span></a></li>
                        <li><a href="pdNamazAttendance.php"><span class="mini-sub-pro"><i class="bi bi-clock-history"></i>  Namaz Attendance</span></a></li>
                        <li><a href="pdTimeTracker.php"><span class="mini-sub-pro"><i class="bi bi-stopwatch"></i>  Time Tracker</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if(hasPermission("eventDars") || hasPermission("eventWorkshop") || hasPermission("eventDawah") || hasPermission("eventResearch")): ?>
                <li>
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="bi bi-calendar2-week big-icon icon-wrap"></i>
                        <span class="mini-click-non">Events</span>
                    </a>
                    <ul class="submenu-angle" aria-expanded="false">
                        <li><a href="eventDars.php"><span class="mini-sub-pro"><i class="bi bi-journal-richtext"></i>  Upcoming Dars</span></a></li>
                        <li><a href="eventWorkshop.php"><span class="mini-sub-pro"><i class="bi bi-easel"></i>  Workshops</span></a></li>
                        <li><a href="eventDawah.php"><span class="mini-sub-pro"><i class="bi bi-megaphone-fill"></i>  Collective Dawah</span></a></li>
                        <li><a href="research.php"><span class="mini-sub-pro"><i class="bi bi-search"></i>  Research</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if(hasPermission("darsAreasInfo")): ?>
                <li>
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="bi bi-geo-alt-fill big-icon icon-wrap"></i>
                        <span class="mini-click-non">Dars Areas</span>
                    </a>
                    <ul class="submenu-angle" aria-expanded="false">
                        <li><a href="darsAreasInfo.php"><span class="mini-sub-pro"><i class="bi bi-map"></i>  Areas Info</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if(hasPermission("opportunityJobs") || hasPermission("opportunityTechWorkshops")): ?>
                <li>
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="bi bi-briefcase-fill big-icon icon-wrap"></i>
                        <span class="mini-click-non">Opportunities</span>
                    </a>
                    <ul class="submenu-angle" aria-expanded="false">
                        <li><a href="opportunityJobs.php"><span class="mini-sub-pro"><i class="bi bi-briefcase"></i>  Jobs / Internships</span></a></li>
                        <li><a href="opportunityTechWorkshops.php"><span class="mini-sub-pro"><i class="bi bi-laptop"></i>  Technical Workshops</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
                <?php if(hasPermission("registeredUsers") || hasPermission("committee") || hasPermission("userTeams")): ?>
                <li>
                    <a class="has-arrow" href="#" aria-expanded="false">
                        <i class="bi bi-people-fill big-icon icon-wrap"></i>
                        <span class="mini-click-non">Users</span>
                    </a>
                    <ul class="submenu-angle" aria-expanded="false">
                        <li><a href="newUsers.php"><span class="mini-sub-pro"><i class="bi bi-person-plus"></i>  New Users</span></a></li>
                        <li><a href="registeredUsers.php"><span class="mini-sub-pro"><i class="bi bi-person-badge"></i>  Registered Users</span></a></li>
                        <li><a href="committee.php"><span class="mini-sub-pro"><i class="bi bi-person-workspace"></i>  Committee</span></a></li>
                        <li><a href="userTeams.php"><span class="mini-sub-pro"><i class="bi bi-people"></i>  Teams</span></a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</nav>