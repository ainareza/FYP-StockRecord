<!-- this in low_stock.php-->

<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

$products = $conn->query("
    SELECT
        product_id,
        product_name,
        quantity,
        minimum_stock
    FROM product
    WHERE quantity <= minimum_stock
    ORDER BY quantity ASC
");
?>

<!DOCTYPE html>
<html>
<head>
    <title>Low Stock</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

<?php include "navbar.php"; ?>

<h2>Low Stock</h2>

<table>

    <tr>
        <th>Product</th>
        <th>Quantity</th>
        <th>Minimum Stock</th>
        <th>Status</th>
    </tr>

    <?php if ($products->num_rows > 0): ?>

    <?php while ($product = $products->fetch_assoc()): ?>
            <tr>
                <td>
                    <?php echo htmlspecialchars($product["product_name"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($product["quantity"]); ?>
                </td>

                <td>
                    <?php echo htmlspecialchars($product["minimum_stock"]); ?>
                </td>

                <td>
                    <span class="<?php echo ($product["quantity"] <= $product["minimum_stock"]) ? "low-stock" : "normal-stock"; ?>">
                        <?php
                        if ($product["quantity"] <= $product["minimum_stock"]) {
                            echo "Low Stock";
                        } else {
                            echo "Normal Stock";
                        }
                        ?>
                    </span>
                </td>
            </tr>

        <?php endwhile; ?>

    <?php else: ?>

        <tr>
            <td colspan="4">No low stock products.</td>
        </tr>

    <?php endif; ?>

</table>

</body>
</html>