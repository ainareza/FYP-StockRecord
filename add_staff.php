<!-- this is add_staff.php -->
<?php
/* Owner-only. Creates a Storekeeper or Sales Associate account.
   The initial Store Owner account is created during system setup; this page creates staff accounts only. */

session_start();
require_once "auth.php";
require_role([ROLE_OWNER]);

require_once "database.php";

$message = "";

// Roles the Owner may assign (everything except Store Owner)
$allowed_roles = [ROLE_STOREKEEPER, ROLE_SALES_ASSOCIATE];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $role_id = (int) $_POST["role_id"];
    $password = $_POST["password"];

    if (empty($name) || empty($email) || empty($password) || empty($role_id)) {

        $message = "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (!in_array($role_id, $allowed_roles)) {

        $message = "Please choose a valid role.";

    } elseif (strlen($password) < 6) {

        $message = "Password must be at least 6 characters.";

    } else {

        $check = $conn->prepare("SELECT user_id FROM user WHERE email = ?");
        $check->bind_param("s", $email);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {

            $message = "Email is already registered.";

        } else {

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("
                INSERT INTO user (role_id, name, email, password)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->bind_param("isss", $role_id, $name, $email, $hashed_password);

            if ($stmt->execute()) {

                header("Location: manage_staff.php");
                exit;

            } else {

                $message = "Failed to add staff account.";

            }
        }
    }
}

// Roles for the dropdown
$roles = $conn->query("
    SELECT role_id, role_name
    FROM role
    WHERE role_id != 1
    ORDER BY role_id
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Staff</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>

    <h2>Add Staff</h2>

    <?php if ($message != ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Name</label>
        <input type="text" name="name" required
               value="<?php echo htmlspecialchars($_POST["name"] ?? ""); ?>">

        <label>Email</label>
        <input type="email" name="email" required
               value="<?php echo htmlspecialchars($_POST["email"] ?? ""); ?>">

        <label>Role</label>
        <select name="role_id" required>
            <option value="">Select Role</option>

            <?php while ($role = $roles->fetch_assoc()): ?>
                <option value="<?php echo $role["role_id"]; ?>"
                    <?php echo (($_POST["role_id"] ?? "") == $role["role_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($role["role_name"]); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>Initial Password</label>
        <div class="password-field">
            <input type="password" name="password" minlength="6" required>
        <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)" aria-label="Show password">
            <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
        </div>

        <button type="submit">Add Staff</button>

    </form>


<script src="password_toggle.js"></script>
</body>
</html>