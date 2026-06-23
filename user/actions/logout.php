<?php
session_start();

session_unset();
session_destroy();

header("Location: ../../login.php"); // 🔥 THIS IS THE FIX
exit();
?>