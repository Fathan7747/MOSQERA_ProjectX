<?php
require_once __DIR__ . '/config.php';
if (empty($_SESSION['user'])) {
    header('Location: login.php');
    exit;
}
header('Location: admin.php?tab=kas');
exit;
