<!-- this is edit_staff.php -->
<?php
/* Owner-only. Edits a Storekeeper / Sales Associate account.
   Leaving the password blank keeps the current one; entering a new one
   resets it. Store Owner accounts cannot be edited from here. */

session_start();
require_once "auth.php";
require_role([ROLE_OWNER]);

require_once "database.php";

if (!isset($_GET["id"])) {
    header("Location: manage_staff.php");
    exit;
}

$staff_id = (int) $_GET["id"];

$allowed_roles = [ROLE_STOREKEEPER, ROLE_SALES_ASSOCIATE];

// Load the staff member (never an Owner)
$stmt = $conn->prepare("
    SELECT name, email, role_id
    FROM user
    WHERE user_id = ? AND role_id != 1
");
$stmt->bind_param("i", $staff_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: manage_staff.php");
    exit;
}

$staff = $result->fetch_assoc();

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $role_id = (int) $_POST["role_id"];
    $password = $_POST["password"];

    // Keep what was just submitted on screen if validation fails
    $staff["name"] = $name;
    $staff["email"] = $email;
    $staff["role_id"] = $role_id;

    if (empty($name) || empty($email) || empty($role_id)) {

        $message = "Please fill in all required fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        $message = "Please enter a valid email address.";

    } elseif (!in_array($role_id, $allowed_roles)) {

        $message = "Please choose a valid role.";

    } elseif ($password !== "" && strlen($password) < 6) {

        $message = "New password must be at least 6 characters.";

    } else {

        // Email must not belong to a different account
        $check = $conn->prepare("
            SELECT user_id FROM user
            WHERE email = ? AND user_id != ?
        ");
        $check->bind_param("si", $email, $staff_id);
        $check->execute();

        if ($check->get_result()->num_rows > 0) {

            $message = "Email is already used by another account.";

        } else {

            if ($password !== "") {

                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $update = $conn->prepare("
                    UPDATE user
                    SET name = ?, email = ?, role_id = ?, password = ?
                    WHERE user_id = ? AND role_id != 1
                ");
                $update->bind_param("ssisi", $name, $email, $role_id, $hashed_password, $staff_id);

            } else {

                $update = $conn->prepare("
                    UPDATE user
                    SET name = ?, email = ?, role_id = ?
                    WHERE user_id = ? AND role_id != 1
                ");
                $update->bind_param("ssii", $name, $email, $role_id, $staff_id);

            }

            if ($update->execute()) {

                header("Location: manage_staff.php");
                exit;

            } else {

                $message = "Failed to update staff account.";

            }
        }
    }
}

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
    <title>Edit Staff</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>

    <h2>Edit Staff</h2>

    <?php if ($message != ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Name</label>
        <input type="text" name="name" required
               value="<?php echo htmlspecialchars($staff["name"]); ?>">

        <label>Email</label>
        <input type="email" name="email" required
               value="<?php echo htmlspecialchars($staff["email"]); ?>">

        <label>Role</label>
        <select name="role_id" required>

            <?php while ($role = $roles->fetch_assoc()): ?>
                <option value="<?php echo $role["role_id"]; ?>"
                    <?php echo ($staff["role_id"] == $role["role_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($role["role_name"]); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label>New Password (leave blank to keep current)</label>
        <div class="password-field">
            <input type="password" name="password" minlength="6">
        <button type="button" class="toggle-password" onclick="togglePasswordVisibility(this)" aria-label="Show password">
            <svg class="icon-eye" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
            <svg class="icon-eye-off" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.94 10.94 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A10.94 10.94 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>
        </button>
        </div>

        <button type="submit">Save Changes</button>

    </form>


<script src="password_toggle.js"></script>
</body>
</html>