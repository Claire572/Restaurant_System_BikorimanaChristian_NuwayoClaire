<?php
/**
 * Logout Handler
 * Restaurant Order Management System
 */

require_once 'config.php';

// Destroy all session data
session_unset();
session_destroy();

// Redirect to login with logout message
header('Location: login.php?logout=1');
exit;
?>