<?php
session_start();
include 'db_connect.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/ewasteWeb.php#loginSection");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {
   
    $full_name = $_POST['full_name'];
    $phone_number = $_POST['phone_number'];
    $street = $_POST['street'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $zipcode = $_POST['zipcode'];
    $payment_method = $_POST['payment'];

    $user_id = $_SESSION['user_id'];

    $totalQuantity = 0;
    $totalPrice = 0;

    $cart = isset($_POST['cartData']) ? json_decode(urldecode($_POST['cartData']), true) : [];

    $product_details = [];

    // Calculate cart
    foreach ($cart as $item) {
        $name = $conn->real_escape_string($item['name']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);
        $itemTotal = $price * $quantity;

        $totalQuantity += $quantity;
        $totalPrice += $itemTotal;

        
        $product_details[] = "{$quantity} x {$item['name']}";
    }


    $product_details_str = implode(", ", $product_details);

    // Updates stock
    if (is_array($cart)) {
        foreach ($cart as $item) {
            $name = $conn->real_escape_string($item['name']);
            $quantity = intval($item['quantity']);

            $updateSql = "UPDATE products SET quantity = quantity - $quantity WHERE name = '$name' AND quantity >= $quantity";
            $conn->query($updateSql);
        }
    }

    $gcashNumber = NULL;
    $gcashName = NULL;
    $proofOfPayment = NULL;

    if ($payment_method === "gcash") {
        $gcashNumber = $_POST['gcashNumber'];
        $gcashName = $_POST['gcashName'];

        if (!isset($_FILES['proofOfPayment']) || $_FILES['proofOfPayment']['error'] !== 0) {
            die("❌ Proof of payment is required for GCash transactions.");
        }


        $target_dir = "uploads/proof/";
        $proof_name = basename($_FILES["proofOfPayment"]["name"]);
        $proof_path = $target_dir . time() . "_" . $proof_name;

        if (move_uploaded_file($_FILES["proofOfPayment"]["tmp_name"], $proof_path)) {
            $proofOfPayment = $proof_path;
        } else {
            die("❌ Failed to upload proof of payment.");
        }
    }


    $stmt = $conn->prepare("INSERT INTO orders 
        (full_name, phone_number, street, city, province, zipcode, totalQuantity,product_details, totalPrice, payment_method, gcashNumber, gcashName, proofOfPayment, user_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $stmt->bind_param("ssssssisdssssi", 
        $full_name, $phone_number, $street, $city, $province, $zipcode,
        $totalQuantity, $product_details_str, $totalPrice, $payment_method,
        $gcashNumber, $gcashName, $proofOfPayment, $user_id
    );

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
       
   
        $orderItemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, quantity, price) VALUES (?, ?, ?, ?, ?)");

        foreach ($cart as $item) {
            $product_name = $conn->real_escape_string($item['name']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);

            $productQuery = $conn->prepare("SELECT product_id FROM products WHERE name = ?");
            $productQuery->bind_param("s", $product_name);
            $productQuery->execute();
            $result = $productQuery->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $product_id = $row['product_id'];

                $orderItemStmt->bind_param("iisid", $order_id, $product_id, $product_name, $quantity, $price);
                $orderItemStmt->execute();
            } else {
                echo "⚠️ Warning: Product '{$product_name}' not found in database.<br>";
            }
            
            $productQuery->close();
        }

        $orderItemStmt->close();

        echo "✅ Order placed successfully!<br>";
        echo "<a href='ewasteWeb.php'>Go back to home</a>";
       
        exit;
    } else {
        echo "❌ DB Error: " . $stmt->error;
    }

    $stmt->close();
}

$conn->close();
?>