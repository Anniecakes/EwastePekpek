<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Profile Completion Popup</title>
  <link rel="stylesheet" href="../../src/styles/ewasteWeb.css">
</head>
<body>
  <div class="mainmodal">
    <div class="modal">
      <div class="modal-header">
        <h2 class="modal-title">Complete Your Profile</h2>
        <div class="close-button">
          <a href="userdash.php"><button type="button">X</button></a>
        </div>
      </div>

      <form action="completeprofile.php" method="POST" enctype="multipart/form-data">
        <div class="modal-body">
          <p class="instruction-text">Please complete your profile information to continue</p>

          <!-- Full Name -->
          <div class="form-group">
            <label class="form-label">Full Name *</label>
            <input type="text" name="full_name" id="full_name" class="form-control" placeholder="Enter your full name" required>
          </div>

          <!-- Email Address-->
          <div class="form-group">
            <label class="form-label">Email Address *</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="Enter your email">
            <span class="verified-badge">(verified)</span>
          </div>

          <!-- Phone Number -->
          <div class="form-group">
            <label class="form-label">Phone Number *</label>
            <input type="tel" name="phone_number" class="form-control" placeholder="Enter your phone number" required>
          </div>

          <!-- Shipping Address -->
          <div class="form-group">
            <label class="form-label">Street</label>
            <input type="text" name="street" class="form-control" placeholder="Enter your street" required>
          </div>
          <div class="form-group">
            <label class="form-label">City</label>
            <input type="text" name="city" class="form-control" placeholder="Enter your city" required>
          </div>
          <div class="form-group">
            <label class="form-label">Province</label>
            <input type="text" name="province" class="form-control" placeholder="Enter your province" required>
          </div>
          <div class="form-group">
            <label class="form-label">Zipcode</label>
            <input type="text" name="zipcode" class="form-control" placeholder="Enter your zipcode" required>
          </div>

          <!-- Profile Picture -->
          <div class="form-group">
            <label class="form-label">Profile Picture (optional)</label>
            <input type="file" name="pfp" accept="image/*">
          </div>

          <!-- Payment Method -->
          <div class="form-group">
            <label class="form-label">Payment Methods *</label>
            <select name="payment_method" required>
              <option value="">--- Select a Method ---</option>
              <option value="Gcash">Gcash</option>
              <option value="Card">Card</option>
              <option value="Cash-on-delivery">Cash-on-delivery</option>
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
</body>
</html>