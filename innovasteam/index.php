<?php
require_once __DIR__ . '/includes/config.php';

if (!isLoggedIn()) {
    redirect(BASE_URL . '/login.php');
}

redirect(dashboardUrl());
