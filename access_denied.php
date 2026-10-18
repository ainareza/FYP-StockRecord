<?php
session_start();
require_once "auth.php";
require_login();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Access Denied</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>

    <h2>Access Denied</h2>

    <p>Your account role does not have permission to view this page.</p>

    <p>
        <a href="index.php">Back to Dashboard</a>
    </p>

</body>
</html>