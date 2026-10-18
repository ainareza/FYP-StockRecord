<!-- this is manage_staff.php -->
<?php
/* Owner-only page. Lists Storekeeper and Sales Associate accounts and
   lets the Store Owner add, edit, deactivate or reactivate them.
   Accounts are deactivated rather than deleted so that transaction and
   activity history keep pointing at a real user. */

session_start();
require_once "auth.php";
require_role([ROLE_OWNER]);

require_once "database.php";

// Deactivate / reactivate (POST only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["toggle_user_id"])) {

    $toggle_id = (int) $_POST["toggle_user_id"];

    // role_id != 1 so an Owner account can never be toggled from here
    $stmt = $conn->prepare("
        UPDATE user
        SET is_active = 1 - is_active
        WHERE user_id = ? AND role_id != 1
    ");
    $stmt->bind_param("i", $toggle_id);
    $stmt->execute();

    header("Location: manage_staff.php");
    exit;
}

require_once "pagination.php";

$search = trim($_GET["search"] ?? "");
$filter_role_id = $_GET["role_id"] ?? "";
$filter_status  = $_GET["status"] ?? "";
if (!ctype_digit($filter_role_id)) $filter_role_id = "";
if (!in_array($filter_status, ["active", "inactive"])) $filter_status = "";

$filters_active = ($search !== "" || $filter_role_id !== "" || $filter_status !== "");

$where = ["user.role_id != 1"];
$types = "";
$params = [];

if ($search !== "") {
    $where[]  = "(user.name LIKE ? OR user.email LIKE ?)";
    $types   .= "ss";
    $params[] = "%" . $search . "%";
    $params[] = "%" . $search . "%";
}
if ($filter_role_id !== "") {
    $where[]  = "user.role_id = ?";
    $types   .= "i";
    $params[] = (int) $filter_role_id;
}
if ($filter_status !== "") {
    $where[]  = "user.is_active = ?";
    $types   .= "i";
    $params[] = ($filter_status === "active") ? 1 : 0;
}

$where_sql = implode(" AND ", $where);

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM user WHERE $where_sql");
if ($types !== "") {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()["total"];
$total_pages = max(1, (int) ceil($total_rows / ROWS_PER_PAGE));

$current_page_num = min(get_current_page(), $total_pages);
$offset = ($current_page_num - 1) * ROWS_PER_PAGE;

$sql = "
    SELECT
        user.user_id,
        user.name,
        user.email,
        role.role_name,
        user.is_active,
        user.created_at
    FROM user
    JOIN role ON user.role_id = role.role_id
    WHERE $where_sql
    ORDER BY user.name
    LIMIT ? OFFSET ?
";

$all_types = $types . "ii";
$all_params = array_merge($params, [ROWS_PER_PAGE, $offset]);

$stmt = $conn->prepare($sql);
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$staff = $stmt->get_result();

// Roles for the filter dropdown (exclude Store Owner)
$filter_roles = $conn->query("SELECT role_id, role_name FROM role WHERE role_id != 1 ORDER BY role_id");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Manage Staff</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>

    <h2>Manage Staff</h2>

    <form method="GET" action="manage_staff.php" class="filter-bar">

        <div class="filter-field">
            <label for="filter-search">Search</label>
            <input type="text" name="search" id="filter-search" placeholder="Name or email"
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>

        <div class="filter-field">
            <label for="filter-role">Role</label>
            <select name="role_id" id="filter-role">
                <option value="">All</option>
                <?php while ($r = $filter_roles->fetch_assoc()): ?>
                    <option value="<?php echo $r["role_id"]; ?>"
                        <?php echo ((string) $r["role_id"] === $filter_role_id) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($r["role_name"]); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="filter-field">
            <label for="filter-status">Status</label>
            <select name="status" id="filter-status">
                <option value="">All</option>
                <option value="active" <?php echo $filter_status === "active" ? "selected" : ""; ?>>Active</option>
                <option value="inactive" <?php echo $filter_status === "inactive" ? "selected" : ""; ?>>Deactivated</option>
            </select>
        </div>

        <button type="submit">Filter</button>

        <?php if ($filters_active): ?>
            <a class="reset-link" href="manage_staff.php">Reset</a>
        <?php endif; ?>

    </form>

    <table>

        <tr>
            <th>#</th>
            <th>Name</th>
            <th>Email</th>
            <th>Role</th>
            <th>Status</th>
            <th>Created</th>
            <th>Actions</th>
        </tr>

        <?php if ($staff->num_rows > 0): ?>

            <?php $row_number = 1; ?>
            <?php while ($member = $staff->fetch_assoc()): ?>

                <tr>
                    <td><?php echo $offset + $row_number++; ?></td>
                    <td><?php echo htmlspecialchars($member["name"]); ?></td>
                    <td><?php echo htmlspecialchars($member["email"]); ?></td>
                    <td><?php echo htmlspecialchars($member["role_name"]); ?></td>

                    <td>
                        <?php if ($member["is_active"]): ?>
                            <span class="status-active">Active</span>
                        <?php else: ?>
                            <span class="status-inactive">Deactivated</span>
                        <?php endif; ?>
                    </td>

                    <td><?php echo htmlspecialchars($member["created_at"]); ?></td>

                    <td>
                        <a href="edit_staff.php?id=<?php echo $member["user_id"]; ?>">Edit</a>

                        <form method="POST"
                              onsubmit="return confirm('<?php echo $member["is_active"] ? "Deactivate this account? They will no longer be able to sign in." : "Reactivate this account?"; ?>');">
                            <input type="hidden" name="toggle_user_id" value="<?php echo $member["user_id"]; ?>">
                            <button type="submit">
                                <?php echo $member["is_active"] ? "Deactivate" : "Reactivate"; ?>
                            </button>
                        </form>
                    </td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="7"><?php echo $filters_active ? "No staff match your filters." : "No staff accounts yet."; ?></td>
            </tr>

        <?php endif; ?>

    </table>

    <?php render_pagination($current_page_num, $total_pages); ?>

</body>
</html>