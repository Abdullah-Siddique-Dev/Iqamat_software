<?php

$permissions = [

    "MD" => [
        "pages"    => ["*"],
        "features" => ["*"]
    ],

    "DG" => [
        "pages"    => ["*"],
        "features" => ["*"]
    ],

    "admin" => [
        "pages"    => ["*"],
        "features" => ["*"]
    ],

    "administrator" => [
        "pages"    => ["*"],
        "features" => ["*"]
    ],

    "representative" => [
        "pages" => ["*"],
        "features" => [
            "addDars", "editDars", "deleteDars",
            "addWorkshop", "editWorkshop", "deleteWorkshop",
            "addEventDawah", "editEventDawah", "deleteEventDawah",
            "addResearch", "editResearch", "deleteResearch",
            "addDawah", "editDawah", "deleteDawah",
            "addQuranAttendance", "editQuranAttendance", "deleteQuranAttendance",
            "addNamazAttendance", "editNamazAttendance", "deleteNamazAttendance",
            "addDarsAttendance", "editDarsAttendance", "deleteDarsAttendance",
            "addTimeTracker", "editTimeTracker", "deleteTimeTracker",
            "addJob", "editJob", "deleteJob",
            "addTechnicalWorkshop", "editTechnicalWorkshop", "deleteTechnicalWorkshop",
            "addArea", "editArea", "deleteArea",
            "addTeam", "addTeamMember", "changeTeamLeader", "deleteTeam", "removeTeamMember",
            "addCommittee", "editCommittee", "deleteCommittee",
            "editUser",
            "editRole",
            "addTask", "editTask", "deleteTask",
        ]
    ],

    "committee" => [
        "pages" => [
            "dashboard",
            "upcomingEvents",
            "pdDarsAttendance",
            "pdQuranAttendance",
            "pdDawah",
            "pdNamazAttendance",
            "pdTimeTracker",
            "pdTask",
            "adminTasks",
            "eventDars",
            "eventWorkshop",
            "eventDawah",
            "eventResearch",
            "darsAreasInfo",
            "opportunityJobs",
            "opportunityTechWorkshops",
            "newUsers",
            "registeredUsers",
            "committee",
            "userTeams",
            "reports",
            "myReport"
        ],
        "features" => [
            "addDars", "editDars",
            "addWorkshop", "editWorkshop",
            "addEventDawah", "editEventDawah",
            "addResearch", "editResearch",
            "addDawah", "editDawah",
            "addQuranAttendance", "editQuranAttendance",
            "addNamazAttendance", "editNamazAttendance",
            "addDarsAttendance", "editDarsAttendance",
            "addTimeTracker", "editTimeTracker",
            "addJob", "editJob",
            "addTechnicalWorkshop", "editTechnicalWorkshop",
            "addArea", "editArea",
            "addTeam", "addTeamMember", "changeTeamLeader",
            "addTask", "editTask",
        ]
    ],

    "member" => [
        "pages" => [
            "dashboard",
            "upcomingEvents",
            "pdDarsAttendance",
            "pdQuranAttendance",
            "pdDawah",
            "pdNamazAttendance",
            "pdTimeTracker",
            "pdTask",
            "adminTasks",
            "eventDars",
            "eventWorkshop",
            "eventDawah",
            "eventResearch",
            "darsAreasInfo",
            "opportunityJobs",
            "opportunityTechWorkshops",
            "registeredUsers",
            "committee",
            "userTeams",
            "myReport"
        ],
        "features" => []
    ],

    "trainee" => [
        "pages" => [
            "dashboard",
            "upcomingEvents",
            "pdQuranAttendance",
            "pdNamazAttendance",
            "pdTimeTracker",
            "pdTask",
            "adminTasks",
            "eventDars",
            "eventWorkshop",
            "eventDawah",
            "eventResearch",
            "darsAreasInfo",
            "opportunityJobs",
            "opportunityTechWorkshops",
            "myReport"
        ],
        "features" => []
    ],

];

function hasPermission($page)
{
    global $permissions, $loggedRole, $loggedUsername;

    if (stripos($loggedUsername ?? '', 'admin') !== false) {
        return true;
    }

    if (empty($loggedRole)) {
        return false;
    }

    $role = strtolower(trim($loggedRole));

    // Full access for admin, administrator, adminsir, md, dg
    if (in_array($role, ['md', 'dg', 'admin', 'administrator', 'adminsir']) || stripos($role, 'admin') !== false) {
        return true;
    }

    $perms = array_change_key_case($permissions, CASE_LOWER);

    if (!isset($perms[$role])) {
        return false;
    }

    $pages = $perms[$role]['pages'];

    return in_array("*", $pages) || in_array($page, $pages);
}

function hasFeature($feature)
{
    global $permissions, $loggedRole, $loggedUsername;

    if (stripos($loggedUsername ?? '', 'admin') !== false) {
        return true;
    }

    if (empty($loggedRole)) {
        return false;
    }

    $role = strtolower(trim($loggedRole));

    // Full access for admin, administrator, adminsir, md, dg
    if (in_array($role, ['md', 'dg', 'admin', 'administrator', 'adminsir']) || stripos($role, 'admin') !== false) {
        return true;
    }

    $perms = array_change_key_case($permissions, CASE_LOWER);

    if (!isset($perms[$role])) {
        return false;
    }

    $features = $perms[$role]['features'];

    return in_array("*", $features) || in_array($feature, $features);
}