<?php

/*this is profile.php
contains name, email, role, 
account creation date and 
back to dashboard function */
session_start();
require_once "auth.php";
require_login();

require_once "database.php";

$user_id = $_SESSION["user_id"];

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = $_POST["current_password"] ?? "";
    $new_password = $_POST["new_password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    $password_stmt = $conn->prepare("SELECT password FROM user WHERE user_id = ?");
    $password_stmt->bind_param("i", $user_id);
    $password_stmt->execute();
    $password_row = $password_stmt->get_result()->fetch_assoc();

    if (!password_verify($current_password, $password_row["password"])) {
        $message = "Current password is incorrect.";
        $message_type = "error";
    } elseif (strlen($new_password) < 6) {
        $message = "New password must be at least 6 characters.";
        $message_type = "error";
    } elseif ($new_password !== $confirm_password) {
        $message = "New passwords do not match.";
        $message_type = "error";
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $update_password = $conn->prepare("UPDATE user SET password = ? WHERE user_id = ?");
        $update_password->bind_param("si", $hashed_password, $user_id);
        if ($update_password->execute()) {
            $message = "Password changed successfully.";
            $message_type = "success";
        } else {
            $message = "Failed to change password.";
            $message_type = "error";
        }
    }
}

$stmt = $conn->prepare("
    SELECT user.name, user.email, role.role_name, user.created_at
    FROM user
    JOIN role ON user.role_id = role.role_id
    WHERE user.user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html>
<head>
    <title>My Profile</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include "navbar.php"; ?>

<div class="profile">
    <h2>My Profile</h2>

    <p><strong>Name:</strong> <?php echo htmlspecialchars($user["name"]); ?></p>

    <p><strong>Email:</strong> <?php echo htmlspecialchars($user["email"]); ?></p>

    <p><strong>Role:</strong> <?php echo htmlspecialchars($user["role_name"]); ?></p>

    <p><strong>Member Since:</strong> <?php echo htmlspecialchars($user["created_at"]); ?></p>

    <hr class="profile-divider">

    <h3>Change Password</h3>
    <?php if ($message != ""): ?><div class="form-message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>
    <form method="POST" class="profile-password-form">
        <label>Current Password</label>
        <div class="password-field"><input type="password" name="current_password" autocomplete="current-password" required><button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)" aria-label="Show password"><svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div>
        <label>New Password</label>
        <div class="password-field"><input type="password" name="new_password" minlength="6" autocomplete="new-password" required><button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)" aria-label="Show password"><svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div>
        <label>Confirm New Password</label>
        <div class="password-field"><input type="password" name="confirm_password" minlength="6" autocomplete="new-password" required><button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)" aria-label="Show password"><svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg><svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg></button></div>
        <button type="submit">Change Password</button>
    </form>
</div>
<script src="password_toggle.js"></script>

</body>
</html>