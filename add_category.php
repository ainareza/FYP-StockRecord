<!-- this is add_category.php function buy connecting to category.php-->

<?php
session_start();
require_once "auth.php";
require_role([ROLE_OWNER, ROLE_STOREKEEPER]);

require_once "database.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $category_name = trim($_POST["category_name"]);

    if (empty($category_name)) {

        $message = "Please enter a category name.";

    } else {

        // Check if category already exists
        $check = $conn->prepare("
            SELECT category_id
            FROM category
            WHERE category_name = ?
        ");

        $check->bind_param("s", $category_name);
        $check->execute();

        $result = $check->get_result();

        if ($result->num_rows > 0) {

            $message = "Category already exists.";

        } else {

            $stmt = $conn->prepare("
                INSERT INTO category (category_name)
                VALUES (?)
            ");

            $stmt->bind_param("s", $category_name);

            if ($stmt->execute()) {

                header("Location: categories.php");
                exit;

            } else {

                $message = "Failed to add category.";

            }
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Add Category</title>
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <?php include "navbar.php"; ?>


    <h2>Add Category</h2>

    <?php if ($message != ""): ?>

        <p><?php echo htmlspecialchars($message); ?></p>

    <?php endif; ?>


    <form method="POST">

        <label>Category Name</label>

        <input
            type="text"
            name="category_name"
            required
        >

        <br><br>

        <button type="submit">Add Category</button>

    </form>



</body>

</html>