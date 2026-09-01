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
            "registeredUsers",
            "committee",
            "userTeams"
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
            "userTeams"
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
            "registeredUsers",
            "committee",
            "userTeams"
        ],
        "features" => []
    ],

];

function hasPermission($page)
{
    global $permissions, $loggedRole;

    if (empty($loggedRole)) {
        return false;
    }

    $role = trim($loggedRole);

    $matchedKey = null;
    foreach ($permissions as $key => $val) {
        if (strcasecmp($key, $role) === 0) {
            $matchedKey = $key;
            break;
        }
    }

    if (!$matchedKey) {
        return false;
    }

    $pages = $permissions[$matchedKey]['pages'];

    return in_array("*", $pages) || in_array($page, $pages);
}

function hasFeature($feature)
{
    global $permissions, $loggedRole;

    if (empty($loggedRole)) {
        return false;
    }

    $role = trim($loggedRole);

    $matchedKey = null;
    foreach ($permissions as $key => $val) {
        if (strcasecmp($key, $role) === 0) {
            $matchedKey = $key;
            break;
        }
    }

    if (!$matchedKey) {
        return false;
    }

    $features = $permissions[$matchedKey]['features'];

    return in_array("*", $features) || in_array($feature, $features);
}