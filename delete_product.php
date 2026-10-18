<!-- this is delete_product.php -->

<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

if (!isset($_GET["id"])) {
    header("Location: products.php");
    exit;
}

$product_id = $_GET["id"];

// Check whether this product has existing transaction history
$check = $conn->prepare("
    SELECT transaction_id
    FROM `transaction`
    WHERE product_id = ?
");

$check->bind_param("i", $product_id);
$check->execute();

$result = $check->get_result();

if ($result->num_rows > 0) {
    echo "<script>
        alert('Cannot delete this product because it has existing transaction history.');
        window.location.href = 'products.php';
    </script>";
    exit;
}

$stmt = $conn->prepare("
    DELETE FROM product
    WHERE product_id = ?
");

$stmt->bind_param("i", $product_id);

if ($stmt->execute()) {
    header("Location: products.php");
    exit;
} else {
    echo "Failed to delete product.";
}
?>