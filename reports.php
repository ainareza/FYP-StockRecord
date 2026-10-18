<?php
/*
this is reports.php
purpose: to allow user get csv report
*/
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

if (isset($_GET["export"]) && $_GET["export"] == "csv") {

    // Only the Store Owner can export; Storekeeper can view but not export
    if (!current_user_has_role([ROLE_OWNER])) {
        header("Location: access_denied.php");
        exit;
    }

    $result = $conn->query("
        SELECT
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
        ORDER BY product.product_name
    ");

    header("Content-Type: text/csv");
    header("Content-Disposition: attachment; filename=inventory_report.csv");

    $output = fopen("php://output", "w");

    fputcsv($output, [
        "Product",
        "Category",
        "Quantity",
        "Minimum Stock",
        "Price"
    ]);

    while ($product = $result->fetch_assoc()) {
        fputcsv($output, [
            $product["product_name"],
            $product["category_names"] ?? "",
            $product["quantity"],
            $product["minimum_stock"],
            $product["price"]
        ]);
    }

    fclose($output);
    exit;
}

$products = $conn->query("
    SELECT
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
    ORDER BY product.product_name
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Inventory Report</title>
    <link rel="stylesheet" href="style.css">
    
</head>

<body>

<?php include "navbar.php"; ?>

<h2>Inventory Report</h2>
<p>
    <?php if (current_user_has_role([ROLE_OWNER])): ?>
        <a href="reports.php?export=csv">Export CSV</a>
    <?php endif; ?>
</p>

<table>

    <tr>
        <th>Product</th>
        <th>Category</th>
        <th>Quantity</th>
        <th>Minimum Stock</th>
        <th>Price</th>
    </tr>

    <?php if ($products->num_rows > 0): ?>

        <?php while ($product = $products->fetch_assoc()): ?>

            <tr>
                <td>
                    <?php echo htmlspecialchars($product["product_name"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($product["category_names"] ?? "—"); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($product["quantity"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($product["minimum_stock"]); ?>
                </td>

                <td>
                    RM <?php echo number_format($product["price"], 2); ?>
                </td>
            </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr>
            <td colspan="5">No products available.</td>
        </tr>

    <?php endif; ?>

</table>

</body>
</html>