<?php
session_start();
require_once 'includes/server-conf.php';

session_destroy();
header("Location: index.php");
exit();
?>