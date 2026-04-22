<?php
/**
 * Client Logout
 */

session_start();
session_destroy();
header('Location: /auth/client-login.php');
exit;
?>
