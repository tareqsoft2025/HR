<?php
require_once 'config.php';
logActivity('تسجيل الخروج');
session_destroy();
header('Location: login.php');
exit;
?>