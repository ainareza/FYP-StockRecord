<!-- this is activity_log.php -->

<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";
require_once "pagination.php";

$filter_user_id = $_GET["user_id"] ?? "";
$filter_action  = $_GET["action"] ?? "";
$filter_from    = $_GET["from"] ?? "";
$filter_to      = $_GET["to"] ?? "";
if (!ctype_digit($filter_user_id)) $filter_user_id = "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_from)) $filter_from = "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_to))   $filter_to = "";

$filters_active = ($filter_user_id !== "" || $filter_action !== "" || $filter_from !== "" || $filter_to !== "");

$where = ["1=1"];
$types = "";
$params = [];

if ($filter_user_id !== "") {
    $where[]  = "activity_log.user_id = ?";
    $types   .= "i";
    $params[] = (int) $filter_user_id;
}
if ($filter_action !== "") {
    $where[]  = "activity_log.action = ?";
    $types   .= "s";
    $params[] = $filter_action;
}
if ($filter_from !== "") {
    $where[]  = "DATE(activity_log.activity_date) >= ?";
    $types   .= "s";
    $params[] = $filter_from;
}
if ($filter_to !== "") {
    $where[]  = "DATE(activity_log.activity_date) <= ?";
    $types   .= "s";
    $params[] = $filter_to;
}

$where_sql = implode(" AND ", $where);

$count_stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM activity_log
    JOIN user ON activity_log.user_id = user.user_id
    WHERE $where_sql
");
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
        activity_log.log_id,
        user.name,
        activity_log.action,
        activity_log.description,
        activity_log.activity_date
    FROM activity_log
    JOIN user
        ON activity_log.user_id = user.user_id
    WHERE $where_sql
    ORDER BY activity_log.activity_date DESC
    LIMIT ? OFFSET ?
";

$all_types = $types . "ii";
$all_params = array_merge($params, [ROWS_PER_PAGE, $offset]);

$stmt = $conn->prepare($sql);
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$logs = $stmt->get_result();

// Users for the filter dropdown (only users who actually have log entries)
$filter_users = $conn->query("
    SELECT DISTINCT user.user_id, user.name
    FROM activity_log
    JOIN user ON activity_log.user_id = user.user_id
    ORDER BY user.name
");

// Distinct action values that currently exist, for the filter dropdown
$filter_actions = $conn->query("
    SELECT DISTINCT action FROM activity_log ORDER BY action
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Activity Log</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include "navbar.php"; ?>

<h2>Activity Log</h2>

<form method="GET" action="activity_log.php" class="filter-bar">

    <div class="filter-field">
        <label for="filter-user">User</label>
        <select name="user_id" id="filter-user">
            <option value="">All</option>
            <?php while ($u = $filter_users->fetch_assoc()): ?>
                <option value="<?php echo $u["user_id"]; ?>"
                    <?php echo ((string) $u["user_id"] === $filter_user_id) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($u["name"]); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="filter-action">Action</label>
        <select name="action" id="filter-action">
            <option value="">All</option>
            <?php while ($a = $filter_actions->fetch_assoc()): ?>
                <option value="<?php echo htmlspecialchars($a["action"]); ?>"
                    <?php echo ($filter_action === $a["action"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($a["action"]); ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>

    <div class="filter-field">
        <label for="filter-from">From</label>
        <input type="date" name="from" id="filter-from"
               value="<?php echo htmlspecialchars($filter_from); ?>">
    </div>

    <div class="filter-field">
        <label for="filter-to">To</label>
        <input type="date" name="to" id="filter-to"
               value="<?php echo htmlspecialchars($filter_to); ?>">
    </div>

    <button type="submit">Filter</button>

    <?php if ($filters_active): ?>
        <a class="reset-link" href="activity_log.php">Reset</a>
    <?php endif; ?>

</form>

<table>

    <tr>
        <th>#</th>
        <th>User</th>
        <th>Action</th>
        <th>Description</th>
        <th>Date</th>
    </tr>

    <?php if ($logs->num_rows > 0): ?>

        <?php $row_number = 1; ?>
        <?php while ($log = $logs->fetch_assoc()): ?>

            <tr>
                <td><?php echo $offset + $row_number++; ?></td>
                <td><?php echo htmlspecialchars($log["name"]); ?></td>

                <td><?php echo htmlspecialchars($log["action"]); ?></td>

                <td><?php echo htmlspecialchars($log["description"]); ?></td>

                <td><?php echo htmlspecialchars($log["activity_date"]); ?></td>
            </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr>
            <td colspan="5"><?php echo $filters_active ? "No activity matches your filters." : "No activities yet."; ?></td>
        </tr>

    <?php endif; ?>

</table>

<?php render_pagination($current_page_num, $total_pages); ?>

</body>
</html>