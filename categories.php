<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";
require_once "pagination.php";

$search_name = trim($_GET["search"] ?? "");
$filter_from = $_GET["from"] ?? "";
$filter_to   = $_GET["to"] ?? "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_from)) $filter_from = "";
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $filter_to))   $filter_to = "";

$filters_active = ($search_name !== "" || $filter_from !== "" || $filter_to !== "");

$where = ["1=1"];
$types = "";
$params = [];

if ($search_name !== "") {
    $where[]  = "category_name LIKE ?";
    $types   .= "s";
    $params[] = "%" . $search_name . "%";
}
if ($filter_from !== "") {
    $where[]  = "DATE(created_at) >= ?";
    $types   .= "s";
    $params[] = $filter_from;
}
if ($filter_to !== "") {
    $where[]  = "DATE(created_at) <= ?";
    $types   .= "s";
    $params[] = $filter_to;
}

$where_sql = implode(" AND ", $where);

$count_stmt = $conn->prepare("SELECT COUNT(*) AS total FROM category WHERE $where_sql");
if ($types !== "") {
    $count_stmt->bind_param($types, ...$params);
}
$count_stmt->execute();
$total_rows = $count_stmt->get_result()->fetch_assoc()["total"];
$total_pages = max(1, (int) ceil($total_rows / ROWS_PER_PAGE));

$current_page_num = min(get_current_page(), $total_pages);
$offset = ($current_page_num - 1) * ROWS_PER_PAGE;

$sql = "
    SELECT category_id, category_name, created_at
    FROM category
    WHERE $where_sql
    ORDER BY category_name
    LIMIT ? OFFSET ?
";

$all_types = $types . "ii";
$all_params = array_merge($params, [ROWS_PER_PAGE, $offset]);

$stmt = $conn->prepare($sql);
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$categories = $stmt->get_result();
?>

<!DOCTYPE html>
<html>

<head>
    <title>Categories</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Navigation Bar -->
    <?php include "navbar.php"; ?>


    <h2>Categories</h2>

    <form method="GET" action="categories.php" class="filter-bar">

        <div class="filter-field">
            <label for="filter-search">Search</label>
            <input type="text" name="search" id="filter-search" placeholder="Category name"
                   value="<?php echo htmlspecialchars($search_name); ?>">
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
            <a class="reset-link" href="categories.php">Reset</a>
        <?php endif; ?>

    </form>

    <table>

        <tr>
            <th>#</th>
            <th>Category Name</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>

        <?php if ($categories->num_rows > 0): ?>

            <?php $row_number = 1; ?>
            <?php while ($category = $categories->fetch_assoc()): ?>

                <tr>
                    <td><?php echo $offset + $row_number++; ?></td>

                    <td>
                        <?php echo htmlspecialchars($category["category_name"]); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($category["created_at"]); ?>
                    </td>

                    <td>
                        <a href="edit_category.php?id=<?php echo $category["category_id"]; ?>">Edit</a>

                        <a href="delete_category.php?id=<?php echo $category["category_id"]; ?>"
                        onclick="return confirm('Are you sure you want to delete this category?');">
                        Delete</a>

                    </td>
                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="4"><?php echo $filters_active ? "No categories match your filters." : "No categories yet."; ?></td>
            </tr>

        <?php endif; ?>

    </table>

    <?php render_pagination($current_page_num, $total_pages); ?>

</body>

</html>