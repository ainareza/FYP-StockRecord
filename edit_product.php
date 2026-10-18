<!-- this is edit_product.php
one of the CRUD function -->

<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

$message = "";

// Get product ID from URL
if (!isset($_GET["id"])) {
    header("Location: products.php");
    exit;
}

$product_id = $_GET["id"];

// Get product information
$stmt = $conn->prepare("
    SELECT product_name, quantity, minimum_stock, price
    FROM product
    WHERE product_id = ?
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows != 1) {
    header("Location: products.php");
    exit;
}

$product = $result->fetch_assoc();

// Get this product's currently linked category (at most one row now)
$linked_stmt = $conn->prepare("
    SELECT category_id
    FROM product_category
    WHERE product_id = ?
");
$linked_stmt->bind_param("i", $product_id);
$linked_stmt->execute();
$linked_row = $linked_stmt->get_result()->fetch_assoc();

$current_category_id = $linked_row["category_id"] ?? "";


// Update product
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $product_name = trim($_POST["product_name"]);
    $category_id = $_POST["category_id"] ?? "";
    $quantity = $_POST["quantity"];
    $minimum_stock = $_POST["minimum_stock"];
    $price = $_POST["price"];

    if (empty($product_name) || empty($category_id)) {

        $message = "Please fill in the required fields and select a category.";
        // Keep the just-submitted selection on redisplay
        $current_category_id = $category_id;

    } else {

        $conn->begin_transaction();

        $update = $conn->prepare("
            UPDATE product
            SET product_name = ?,
                quantity = ?,
                minimum_stock = ?,
                price = ?
            WHERE product_id = ?
        ");

        $update->bind_param(
            "siidi",
            $product_name,
            $quantity,
            $minimum_stock,
            $price,
            $product_id
        );

        $success = $update->execute();

        if ($success) {

            // Replace this product's single category link.
            // DELETE + INSERT keeps this correct whether or not a
            // product_category row already existed for it.
            $delete_link = $conn->prepare("
                DELETE FROM product_category WHERE product_id = ?
            ");
            $delete_link->bind_param("i", $product_id);
            $success = $delete_link->execute();
        }

        if ($success) {

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
            $message = "Failed to update product.";
            $current_category_id = $category_id;

        }
    }
}


// Get categories
$categories = $conn->query("
    SELECT category_id, category_name
    FROM category
    ORDER BY category_name
");
?>

<!DOCTYPE html>
<html>

<head>
    <title>Edit Product</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>


    <h2>Edit Product</h2>

    <?php if ($message != ""): ?>

        <p><?php echo htmlspecialchars($message); ?></p>

    <?php endif; ?>


    <form method="POST">

        <label>Product Name</label>

        <input
            type="text"
            name="product_name"
            value="<?php echo htmlspecialchars($product["product_name"]); ?>"
            required
        >

        <br><br>


        <label>Category</label>

        <select name="category_id" required>
            <option value="">Select Category</option>

            <?php while ($category = $categories->fetch_assoc()): ?>
                <option value="<?php echo $category["category_id"]; ?>"
                    <?php echo ($category["category_id"] == $current_category_id) ? "selected" : ""; ?>>
                    <?php echo htmlspecialchars($category["category_name"]); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <br><br>


        <label>Quantity</label>

        <input
            type="number"
            name="quantity"
            min="0"
            value="<?php echo htmlspecialchars($product["quantity"]); ?>"
            required
        >

        <br><br>


        <label>Minimum Stock</label>

        <input
            type="number"
            name="minimum_stock"
            min="0"
            value="<?php echo htmlspecialchars($product["minimum_stock"]); ?>"
            required
        >

        <br><br>


        <label>Price</label>

        <input
            type="number"
            name="price"
            min="0"
            step="0.01"
            value="<?php echo htmlspecialchars($product["price"]); ?>"
            required
        >

        <br><br>


        <button type="submit">Update Product</button>

    </form>



</body>

</html>