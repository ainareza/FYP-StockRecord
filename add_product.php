<!--add_product.php functions to allow any items  -->

<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_name = trim($_POST["product_name"]);
    $category_id = $_POST["category_id"] ?? "";
    $quantity = $_POST["quantity"];
    $minimum_stock = $_POST["minimum_stock"];
    $price = $_POST["price"];

    if (empty($product_name) || empty($category_id)) {

        $message = "Please fill in the required fields and select a category.";

    } else {

        $conn->begin_transaction();

        $stmt = $conn->prepare("
            INSERT INTO product
            (product_name, quantity, minimum_stock, price)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->bind_param(
            "siid",
            $product_name,
            $quantity,
            $minimum_stock,
            $price
        );

        $success = $stmt->execute();

        if ($success) {

            $product_id = $conn->insert_id;

            $pc_stmt = $conn->prepare("
                INSERT INTO product_category (product_id, category_id)
                VALUES (?, ?)
            ");
            $pc_stmt->bind_param("ii", $product_id, $category_id);
            $success = $pc_stmt->execute();
        }

        if ($success) {

            $conn->commit();
            header("Location: products.php");
            exit;

        } else {

            $conn->rollback();
            $message = "Failed to add product.";

        }
    }
}


/* Get categories for dropdown */

$categories = $conn->query("
    SELECT category_id, category_name
    FROM category
    ORDER BY category_name
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Product</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>


    <h2>Add Product</h2>

    <?php if ($message != ""): ?>
        <p><?php echo htmlspecialchars($message); ?></p>
    <?php endif; ?>

    <form method="POST">

        <label>Product Name</label>
        <input type="text" name="product_name" required>

        <br><br>

        <label>Category</label>

        <select name="category_id" required>
            <option value="">Select Category</option>

            <?php while ($category = $categories->fetch_assoc()): ?>
                <option value="<?php echo $category["category_id"]; ?>"
                    <?php echo (($_POST["category_id"] ?? "") == $category["category_id"]) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($category["category_name"]); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <br><br>

        <label>Quantity</label>
        <input type="number" name="quantity" min="0" value="0" required>

        <br><br>

        <label>Minimum Stock</label>
        <input type="number" name="minimum_stock" min="0" value="0" required>

        <br><br>

        <label>Price</label>
        <input type="number" name="price" min="0" step="0.01" value="0.00" required>

        <br><br>

        <button type="submit">Add Product</button>

    </form>


</body>

</html>