<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/ewasteWeb.php#Login");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $street = $_POST['street'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $zipcode = $_POST['zipcode'];
    $payment_method = $_POST['payment_method'];
    $user_id = $_SESSION['user_id'];

    $pfp = null;
    if (isset($_FILES['pfp']) && $_FILES['pfp']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/pfp/';
        $pfp = $uploadDir . basename($_FILES['pfp']['name']);
        move_uploaded_file($_FILES['pfp']['tmp_name'], $pfp);
    }

    $stmt = $conn->prepare("INSERT INTO user_details (user_id, full_name, email, phone_number, street, city, province, zipcode, pfp, payment_method) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isssssssss", $user_id, $full_name, $email, $phone_number, $street, $city, $province, $zipcode, $pfp, $payment_method);

    if ($stmt->execute()) {
        echo "<div class='registration-success-container'>
                <div class='registration-success-container1'>
                    <h2 class='success-message'>Profile completed successfully!</h2> 
                    <a href='userdash.php'>Visit your Profile!</a>
                </div>
              </div>";
    } else {
        echo "<p style='color:red;'>Error: " . $stmt->error . "</p>";
    }
}
?>