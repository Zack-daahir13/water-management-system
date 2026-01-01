<?php
require_once 'config.php';
require_once 'auth.php';

$auth->logout();
header('Location: ' . APP_URL . '/login.php');
exit;
