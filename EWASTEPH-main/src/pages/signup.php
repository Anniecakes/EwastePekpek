<!DOCTYPE html>
<html lang="en">
    <head>
        <title>Sign Up</title>
        <link rel="stylesheet" href="../../src/styles/ewasteWeb.css"> 
    </head>

    <body>
        <?php
        include 'db_connect.php';

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $full_name = $_POST['full_name'];
            $email = $_POST['email'];
            $password = password_hash($_POST['password'], PASSWORD_BCRYPT); // Secure password hashing

            // Check if email already exists
            $check_email = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $result = $check_email->get_result();

            if ($result->num_rows > 0) {
                echo "Email already registered! Try logging in.";
            } else {
                // Insert new user
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $full_name, $email, $password);

                if ($stmt->execute()) {
                    // Get the inserted user's ID
                    $user_id = $stmt->insert_id;
                
                    // Start session and store user ID
                    session_start();
                    $_SESSION['user_id'] = $user_id;
                
                    // Redirect to preDashboard.php
                    header("Location: preDashboard.php");
                    exit();
                } else {
                    echo "Error: " . $stmt->error;
                }
            }
        }
        ?>


    <body>
</html>
