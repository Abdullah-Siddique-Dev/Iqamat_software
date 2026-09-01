<?php

include "auth.php";
require_once "permissions.php";

$currentPage = basename($_SERVER['PHP_SELF'], ".php");

if (!hasPermission($currentPage)) {

    header("Location: dashboard.php");
    exit();

}