<?php
session_start();
require_once "database.php";

$message = "";
$message_type = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($email) || empty($password)) {
        $message = "Please enter your email and password.";
        $message_type = "error";
    } else {
        $stmt = $conn->prepare("SELECT user.user_id, user.name, user.email, user.password, user.role_id, user.is_active, role.role_name FROM user JOIN role ON user.role_id = role.role_id WHERE user.email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $user = $result->fetch_assoc();

            if (!$user["is_active"]) {
                $message = "This account has been deactivated. Please contact the Store Owner.";
                $message_type = "error";
            } elseif (password_verify($password, $user["password"])) {
                session_regenerate_id(true);
                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["name"] = $user["name"];
                $_SESSION["role_id"] = $user["role_id"];
                $_SESSION["role_name"] = $user["role_name"];
                header("Location: index.php");
                exit;
            } else {
                $message = "Incorrect email or password.";
                $message_type = "error";
            }
        } else {
            $message = "Incorrect email or password.";
            $message_type = "error";
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign In - StockRecord</title>
    <link rel="stylesheet" href="style.css">
</head>
<body class="auth-page">
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>StockRecord</h1>
                <p>Inventory Management System</p>
            </div>

            <h2>Sign In</h2>

            <?php if ($message != ""): ?>
                <div class="form-message <?php echo $message_type; ?>"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <form method="POST">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>" autocomplete="email" required>

                <label for="password">Password</label>
                <div class="password-field">
                    <input type="password" id="password" name="password" autocomplete="current-password" required>
                    <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)" aria-label="Show password">
                        <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"/><path d="M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"/><path d="M14.12 14.12a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
                    </button>
                </div>

                <button type="submit">Sign In</button>
            </form>

            <div class="auth-note">Account access is managed by the Store Owner.</div>
        </div>
    </div>
    <script src="password_toggle.js"></script>
</body>
</html>