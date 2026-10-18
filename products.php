<!-- this is product.php-->
<?php
session_start();
require_once "auth.php";
require_login();

require_once "database.php";
require_once "pagination.php";

$search_name = trim($_GET["search"] ?? "");
$filter_category_id = $_GET["category_id"] ?? "";
if (!ctype_digit($filter_category_id)) {
    $filter_category_id = "";
}

$where = ["1=1"];
$types = "";
$params = [];

if ($search_name !== "") {
    $where[]  = "product.product_name LIKE ?";
    $types   .= "s";
    $params[] = "%" . $search_name . "%";
}
if ($filter_category_id !== "") {
    $where[]  = "EXISTS (
        SELECT 1 FROM product_category
        WHERE product_category.product_id = product.product_id
        AND product_category.category_id = ?
    )";
    $types   .= "i";
    $params[] = (int) $filter_category_id;
}

$where_sql = implode(" AND ", $where);

// Count matching products (for pagination), independent of LIMIT
$count_sql = "SELECT COUNT(*) AS total FROM product WHERE $where_sql";
$count_stmt = $conn->prepare($count_sql);
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
        product.product_id,
        product.product_name,
        category.category_name AS category_names,
        product.quantity,
        product.minimum_stock,
        product.price
    FROM product
    LEFT JOIN product_category
        ON product.product_id = product_category.product_id
    LEFT JOIN category
        ON product_category.category_id = category.category_id
    WHERE $where_sql
    ORDER BY product.product_id DESC
    LIMIT ? OFFSET ?
";

$all_types = $types . "ii";
$all_params = array_merge($params, [ROWS_PER_PAGE, $offset]);

$stmt = $conn->prepare($sql);
$stmt->bind_param($all_types, ...$all_params);
$stmt->execute();
$products = $stmt->get_result();

// Categories for the filter dropdown
$filter_categories = $conn->query("
    SELECT category_id, category_name
    FROM category
    ORDER BY category_name
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Products</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- Navigation Bar -->
    <?php include "navbar.php"; ?>


    <h2>Products</h2>

    <form method="GET" action="products.php" class="filter-bar">

        <div class="filter-field">
            <label for="filter-search">Search</label>
            <input type="text" name="search" id="filter-search" placeholder="Product name"
                   value="<?php echo htmlspecialchars($search_name); ?>">
        </div>

        <div class="filter-field">
            <label for="filter-category">Category</label>
            <select name="category_id" id="filter-category">
                <option value="">All</option>
                <?php while ($c = $filter_categories->fetch_assoc()): ?>
                    <option value="<?php echo $c["category_id"]; ?>"
                        <?php echo ((string) $c["category_id"] === $filter_category_id) ? "selected" : ""; ?>>
                        <?php echo htmlspecialchars($c["category_name"]); ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <button type="submit">Filter</button>

        <?php if ($search_name !== "" || $filter_category_id !== ""): ?>
            <a class="reset-link" href="products.php">Reset</a>
        <?php endif; ?>

    </form>

    <table>

        <tr>
            <th>#</th>
            <th>Product</th>
            <th>Category</th>
            <th>Quantity</th>
            <th>Minimum Stock</th>
            <th>Price</th>
            <?php if (current_user_has_role([ROLE_OWNER, ROLE_STOREKEEPER])): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>

        <?php if ($products->num_rows > 0): ?>

            <?php $row_number = 1; ?>
            <?php while ($product = $products->fetch_assoc()): ?>

                <tr>
                    <td><?php echo $offset + $row_number++; ?></td>
                    <td><?php echo htmlspecialchars($product["product_name"]); ?></td>

                    <td><?php echo htmlspecialchars($product["category_names"] ?? "—"); ?></td>

                    <td><?php echo htmlspecialchars($product["quantity"]); ?></td>

                    <td><?php echo htmlspecialchars($product["minimum_stock"]); ?></td>

                    <td>RM <?php echo htmlspecialchars($product["price"]); ?></td>

                    <?php if (current_user_has_role([ROLE_OWNER, ROLE_STOREKEEPER])): ?>
                    <td>
                        <a href="edit_product.php?id=<?php echo $product["product_id"]; ?>">
                        Edit
                        </a>

                        <a href="delete_product.php?id=<?php echo $product["product_id"]; ?>"
                        onclick="return confirm('Are you sure you want to delete this product?');">
                        Delete
                    
                        </a>
                
                    </td>
                    <?php endif; ?>

                </tr>

            <?php endwhile; ?>

        <?php else: ?>

            <tr>
                <td colspan="7"><?php echo ($search_name !== "" || $filter_category_id !== "") ? "No products match your filters." : "No products yet."; ?></td>
            </tr>

        <?php endif; ?>

    </table>

    <?php render_pagination($current_page_num, $total_pages); ?>

</body>

</html>