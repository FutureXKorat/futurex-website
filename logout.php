<?php
include 'database.php'; // registers MySQL handler + starts session
session_destroy();
header("Location: index.php");
exit();
?>
