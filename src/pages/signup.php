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

            $check_email = $conn->prepare("SELECT * FROM users WHERE email = ?");
            $check_email->bind_param("s", $email);
            $check_email->execute();
            $result = $check_email->get_result();

            if ($result->num_rows > 0) {
                echo "Email already registered! Try logging in.";
            } else {
                $stmt = $conn->prepare("INSERT INTO users (full_name, email, password) VALUES (?, ?, ?)");
                $stmt->bind_param("sss", $full_name, $email, $password);

                if ($stmt->execute()) {

                    $user_id = $stmt->insert_id;
                
                    session_start();
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['temp_name'] = $_POST['name']; 
                    $_SESSION['temp_email'] = $_POST['email']; 
                    header("Location: predashboard.php");
                    exit();
                }
                 else {
                    echo "Error: " . $stmt->error;
                }
            }
        }
        ?>


    <body>
</html>
