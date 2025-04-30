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
    
    if (empty($cart)) {
        die("Cart is empty. Please add items to your cart before checkout.");
    }

    // cart items
    $product_details = [];
    foreach ($cart as $item) {
        $name = $conn->real_escape_string($item['name']);
        $quantity = intval($item['quantity']);
        $price = floatval($item['price']);
        $itemTotal = $price * $quantity;

        $totalQuantity += $quantity;
        $totalPrice += $itemTotal;
        
        $product_details[] = "{$quantity} x {$name}";
    }

    $product_details_str = implode(", ", $product_details);

    $gcashNumber = NULL;
    $gcashName = NULL;
    $proofOfPayment = NULL;

    if ($payment_method === "gcash") {
        $gcashNumber = $_POST['gcashNumber'];
        $gcashName = $_POST['gcashName'];

        $target_dir = "uploads/proof/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }

        if (!isset($_FILES['proofOfPayment']) || $_FILES['proofOfPayment']['error'] !== 0) {
            die("Proof of payment is required for GCash transactions.");
        }

        $proof_name = basename($_FILES["proofOfPayment"]["name"]);
        $proof_path = $target_dir . time() . "_" . $proof_name;

        if (move_uploaded_file($_FILES["proofOfPayment"]["tmp_name"], $proof_path)) {
            $proofOfPayment = $proof_path;
        } else {
            die("Failed to upload proof of payment.");
        }
    }

    // Insert order
    $stmt = $conn->prepare("INSERT INTO orders 
        (full_name, phone_number, street, city, province, zipcode, totalQuantity, product_details, totalPrice, payment_method, gcashNumber, gcashName, proofOfPayment, user_id) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    if (!$stmt) {
        die("Error processing order. Please try again later.");
    }

    $stmt->bind_param("ssssssisdssssi", 
        $full_name, $phone_number, $street, $city, $province, $zipcode,
        $totalQuantity, $product_details_str, $totalPrice, $payment_method,
        $gcashNumber, $gcashName, $proofOfPayment, $user_id
    );

    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;

        $orderItemSuccessCount = 0;
        
        foreach ($cart as $item) {
            $product_name = $conn->real_escape_string($item['name']);
            $quantity = intval($item['quantity']);
            $price = floatval($item['price']);
            
            $productQuery = $conn->prepare("SELECT product_id FROM products WHERE name = ?");
            if (!$productQuery) {
                continue;
            }
            
            $productQuery->bind_param("s", $product_name);
            $productQuery->execute();
            $result = $productQuery->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $product_id = $row['product_id'];
                
                $orderItemSql = "INSERT INTO order_items (order_id, product_id, product_name, quantity, price) 
                                 VALUES (?, ?, ?, ?, ?)";
                                 
                $orderItemStmt = $conn->prepare($orderItemSql);
                
                if (!$orderItemStmt) {
                    continue;
                }
                
                $orderItemStmt->bind_param("iisid", $order_id, $product_id, $product_name, $quantity, $price);
                
                if ($orderItemStmt->execute()) {
                    $orderItemSuccessCount++;
                    
                    // Update stock
                    $updateSql = "UPDATE products SET quantity = quantity - ? WHERE product_id = ? AND quantity >= ?";
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("iii", $quantity, $product_id, $quantity);
                    $updateStmt->execute();
                    $updateStmt->close();
                    
                    // Remove from cart
                    $clearCartSql = "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?";
                    $clearCartStmt = $conn->prepare($clearCartSql);
                    $clearCartStmt->bind_param("ii", $user_id, $product_id);
                    $clearCartStmt->execute();
                    $clearCartStmt->close();
                }
                
                $orderItemStmt->close();
            }
            
            $productQuery->close();
        }
       //change this maybe substitute lng 
        echo "<div style='text-align:center; margin-top:50px;'>";
        echo "<h2>✅ Order placed successfully!</h2>";
        echo "<p>Your order ID is: #$order_id</p>";
        echo "<p><a href='../pages/ewasteWeb.php' style='display:inline-block; padding:10px 20px; background-color:#4CAF50; color:white; text-decoration:none; border-radius:5px;'>Return to Home</a></p>";
        echo "</div>";
    } else {
        echo "<div style='text-align:center; margin-top:50px;'>";
        echo "<h2>❌ Failed to place order</h2>";
        echo "<p>Please try again later.</p>";
        echo "<p><a href='javascript:history.back()' style='display:inline-block; padding:10px 20px; background-color:#f44336; color:white; text-decoration:none; border-radius:5px;'>Go Back</a></p>";
        echo "</div>";
    }
    
    $stmt->close();
}

$conn->close();
?>