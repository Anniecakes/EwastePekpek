<?php
session_start();
include 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/ewasteWeb.php#Login");
    exit();
}

$user_id = $_SESSION['user_id'];
$profile_completed = false;
$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['full_name'];
    $email = $_POST['email'];
    $phone_number = $_POST['phone_number'];
    $street = $_POST['street'];
    $city = $_POST['city'];
    $province = $_POST['province'];
    $zipcode = $_POST['zipcode'];
    $payment_method = $_POST['payment_method'];


    $pfp = null;
    if (isset($_FILES['pfp']) && $_FILES['pfp']['error'] == UPLOAD_ERR_OK) {
        $uploadDir = 'uploads/pfp/';

        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $fileExtension = pathinfo($_FILES['pfp']['name'], PATHINFO_EXTENSION);
        $uniqueFilename = uniqid('profile_') . '.' . $fileExtension;
        $pfp = $uploadDir . $uniqueFilename;
        
        move_uploaded_file($_FILES['pfp']['tmp_name'], $pfp);
    }

    $checkStmt = $conn->prepare("SELECT user_id FROM user_details WHERE user_id = ?");
    $checkStmt->bind_param("i", $user_id);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        $updateStmt = $conn->prepare("UPDATE user_details SET full_name = ?, email = ?, phone_number = ?, 
                                      street = ?, city = ?, province = ?, zipcode = ?, payment_method = ? 
                                      " . ($pfp ? ", pfp = ?" : "") . " WHERE user_id = ?");
        
        if ($pfp) {
            $updateStmt->bind_param("sssssssssi", $full_name, $email, $phone_number, $street, $city, 
                                  $province, $zipcode, $payment_method, $pfp, $user_id);
        } else {
            $updateStmt->bind_param("ssssssssi", $full_name, $email, $phone_number, $street, $city, 
                                  $province, $zipcode, $payment_method, $user_id);
        }
        
        if ($updateStmt->execute()) {
            $profile_completed = true;
            $message = "Profile updated successfully!";
        } else {
            $message = "Error updating profile: " . $updateStmt->error;
        }
    } else {
        $insertStmt = $conn->prepare("INSERT INTO user_details (user_id, full_name, email, phone_number, 
                                     street, city, province, zipcode, pfp, payment_method) 
                                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $insertStmt->bind_param("isssssssss", $user_id, $full_name, $email, $phone_number, 
                               $street, $city, $province, $zipcode, $pfp, $payment_method);
        
        if ($insertStmt->execute()) {
            $profile_completed = true;
            $message = "Profile completed successfully!";
        } else {
            $message = "Error: " . $insertStmt->error;
        }
    }
}

$stmt = $conn->prepare("SELECT * FROM user_details WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$user_details = null;
if ($result->num_rows > 0) {
    $user_details = $result->fetch_assoc();
}

if ($user_details && $_SERVER["REQUEST_METHOD"] != "POST") {
    header("Location: userdash.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Completion</title>
  <link rel="stylesheet" href="../../src/styles/ewasteWeb.css">
  <style>
    .registration-success-container {
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      width: 100%;
      position: fixed;
      top: 0;
      left: 0;
      background-color: rgba(0, 0, 0, 0.7);
      z-index: 1000;
    }
    
    .registration-success-container1 {
      background-color: white;
      padding: 30px;
      border-radius: 10px;
      text-align: center;
      max-width: 500px;
    }
    
    .success-message {
      color: green;
      margin-bottom: 20px;
    }
    
    .registration-success-container1 a {
      display: inline-block;
      padding: 10px 20px;
      background-color: #4CAF50;
      color: white;
      text-decoration: none;
      border-radius: 5px;
      margin-top: 10px;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <?php if ($profile_completed): ?>
    <div class='registration-success-container'>
      <div class='registration-success-container1'>
        <h2 class='success-message'><?php echo $message; ?></h2> 
        <a href='userdash.php'>Visit your Profile!</a>
      </div>
    </div>
  <?php else: ?>
    <div class="mainmodal">
      <div class="modal">
        <div class="modal-header">
          <h2 class="modal-title">Complete Your Profile</h2>
          <div class="close-button">
            <a href="userdash.php"><button type="button">X</button></a>
          </div>
        </div>

        <?php if (!empty($message)): ?>
          <div class="alert <?php echo strpos($message, 'Error') !== false ? 'alert-danger' : 'alert-success'; ?>">
            <?php echo $message; ?>
          </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" enctype="multipart/form-data">
          <div class="modal-body">
            <p class="instruction-text">Please complete your profile information to continue</p>

            <!-- Full Name -->
            <div class="form-group">
              <label class="form-label">Full Name *</label>
              <input type="text" name="full_name" id="full_name" class="form-control" 
                    placeholder="Enter your full name" required
                    value="<?php echo isset($user_details['full_name']) ? htmlspecialchars($user_details['full_name']) : ''; ?>">
            </div>

            <!-- Email Address-->
            <div class="form-group">
              <label class="form-label">Email Address *</label>
              <input type="email" name="email" id="email" class="form-control" 
                    placeholder="Enter your email" required
                    value="<?php echo isset($user_details['email']) ? htmlspecialchars($user_details['email']) : ''; ?>">
              <span class="verified-badge">(verified)</span>
            </div>

            <!-- Phone Number -->
            <div class="form-group">
              <label class="form-label">Phone Number *</label>
              <input type="tel" name="phone_number" class="form-control" 
                    placeholder="Enter your phone number" required
                    value="<?php echo isset($user_details['phone_number']) ? htmlspecialchars($user_details['phone_number']) : ''; ?>">
            </div>

            <!-- Shipping Address -->
            <div class="form-group">
              <label class="form-label">Street</label>
              <input type="text" name="street" class="form-control" 
                    placeholder="Enter your street" required
                    value="<?php echo isset($user_details['street']) ? htmlspecialchars($user_details['street']) : ''; ?>">
            </div>
            <div class="form-group">
              <label class="form-label">City</label>
              <input type="text" name="city" class="form-control" 
                    placeholder="Enter your city" required
                    value="<?php echo isset($user_details['city']) ? htmlspecialchars($user_details['city']) : ''; ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Province</label>
              <input type="text" name="province" class="form-control" 
                    placeholder="Enter your province" required
                    value="<?php echo isset($user_details['province']) ? htmlspecialchars($user_details['province']) : ''; ?>">
            </div>
            <div class="form-group">
              <label class="form-label">Zipcode</label>
              <input type="text" name="zipcode" class="form-control" 
                    placeholder="Enter your zipcode" required
                    value="<?php echo isset($user_details['zipcode']) ? htmlspecialchars($user_details['zipcode']) : ''; ?>">
            </div>

            <!-- Profile Picture -->
            <div class="form-group">
              <label class="form-label">Profile Picture (optional)</label>
              <?php if (isset($user_details['pfp']) && !empty($user_details['pfp'])): ?>
                <div class="current-pfp">
                  <p>Current profile picture: <?php echo basename($user_details['pfp']); ?></p>
                </div>
              <?php endif; ?>
              <input type="file" name="pfp" accept="image/*">
            </div>

            <!-- Payment Method -->
            <div class="form-group">
              <label class="form-label">Payment Methods *</label>
              <select name="payment_method" required>
                <option value="">--- Select a Method ---</option>
                <option value="Gcash" <?php echo (isset($user_details['payment_method']) && $user_details['payment_method'] == 'Gcash') ? 'selected' : ''; ?>>Gcash</option>
                <option value="Card" <?php echo (isset($user_details['payment_method']) && $user_details['payment_method'] == 'Card') ? 'selected' : ''; ?>>Card</option>
                <option value="Cash-on-delivery" <?php echo (isset($user_details['payment_method']) && $user_details['payment_method'] == 'Cash-on-delivery') ? 'selected' : ''; ?>>Cash-on-delivery</option>
              </select>
            </div>
          </div>

          <div class="modal-footer">
            <p class="required-fields-note">* Required fields</p>
            <button type="reset">Reset</button>
            <button type="submit" name="submit" class="submit-button">Save & Continue</button>
          </div>
        </form>
      </div>
    </div>
  <?php endif; ?>
</body>
</html>