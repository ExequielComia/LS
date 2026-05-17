  <?php
  include 'db.php';

  // Query the database for the store address and contact
  $footer_info_query = "SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('store_address', 'store_contact')";
  $footer_info_result = $conn->query($footer_info_query);

  // Set default fallback text
  $store_address = "123 Baker Street, Manila";
  $store_contact = "+63 912 345 6789";

  if ($footer_info_result && $footer_info_result->num_rows > 0) {
    while ($row = $footer_info_result->fetch_assoc()) {
      if ($row['setting_key'] === 'store_address' && !empty($row['setting_value'])) {
        $store_address = $row['setting_value'];
      }
      if ($row['setting_key'] === 'store_contact' && !empty($row['setting_value'])) {
        $store_contact = $row['setting_value'];
      }
    }
  }

  // Query the database for the store name
  $store_name = "La Seanale"; // Default fallback if nothing is saved yet

  // Check if $conn exists (assuming db.php is included before this)
  if (isset($conn)) {
    $title_query = "SELECT setting_value FROM settings WHERE setting_key = 'store_name'";
    $title_result = $conn->query($title_query);

    if ($title_result && $title_result->num_rows > 0) {
      $row = $title_result->fetch_assoc();
      if (!empty($row['setting_value'])) {
        $store_name = $row['setting_value'];
      }
    }
  }
  ?>

  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($store_name); ?></title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link
      href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@700;800&family=Lato:wght@400;700&display=swap"
      rel="stylesheet">
    <link rel="stylesheet" href="./css/style3.css">
    <link rel="icon" type="image/png" href="./assets/logodash.png">
  </head>

  <body>

    <header class="header">
      <nav class="nav-links">
        <a href="#">Home</a>
        <a href="#">Our Products</a>
      </nav>

      <div class="logo">
        <img src="./assets/hero.png" alt="Logo" class="logo-img">
      </div>

      <div class="header-right">
        <button class="order-btn" onclick="document.getElementById('loginModal').classList.add('active')">
          Order Online
        </button>
        <button class="rider-btn" onclick="document.getElementById('riderLoginModal').classList.add('active')">
          Become a rider?
        </button>
      </div>
    </header>

    <!-- Rider Modal -->
    <div id="riderModal" class="modal-overlay">
      <div class="modal-card" style="max-height: 90vh; display: flex; flex-direction: column;">
        <button class="modal-close" onclick="document.getElementById('riderModal').classList.remove('active')">&#x2715;</button>

        <div class="modal-header" style="flex-shrink: 0;">
          <h2>Become a Rider!</h2>
          <p>Join our delivery team and earn on your schedule</p>
        </div>
        <div style="overflow-y: auto; flex: 1; padding: 0 1.5rem 1.5rem;">

          <div class="form-row">
            <div class="form-group">
              <label for="riderFirstName">First Name</label>
              <input type="text" id="riderFirstName" placeholder="Juan">
            </div>
            <div class="form-group">
              <label for="riderLastName">Last Name</label>
              <input type="text" id="riderLastName" placeholder="Dela Cruz">
            </div>
          </div>

          <div class="form-group">
            <label for="riderEmail">Email</label>
            <input type="email" id="riderEmail" placeholder="you@example.com">
          </div>

          <div class="form-group">
            <label for="riderPhone">Phone Number</label>
            <input type="tel" id="riderPhone" placeholder="+63 9XX XXX XXXX">
          </div>

          <div class="form-group">
            <label for="riderVehicle">Vehicle Type</label>
            <select id="riderVehicle">
              <option value="" disabled selected>Select vehicle</option>
              <option>Bicycle</option>
              <option>Motorcycle</option>
              <option>Car</option>
              <option>E-bike</option>
            </select>
          </div>

          <div class="form-group">
            <label for="riderPassword">Password</label>
            <input type="password" id="riderPassword" placeholder="••••••••">
          </div>

          <div class="form-group">
            <label for="confirmriderPassword">Confirm Password</label>
            <input type="password" id="confirmriderPassword" placeholder="••••••••">
          </div>

          <div class="form-group">
            <label>ID Verification</label>
            <p style="font-size: 12px; color: #8b6340; font-family: sans-serif; margin-bottom: 8px; line-height: 1.5;">
              Upload a valid government-issued ID (e.g. PhilSys, Driver's License, Passport, UMID). File must be JPG, PNG,
              or PDF — max 5MB.
            </p>

            <div id="id-drop-zone"
              style="border: 2px dashed #c8b89a; border-radius: 10px; padding: 1.5rem; text-align: center; cursor: pointer; background: #fdf6ed; transition: border-color 0.2s;"
              onclick="document.getElementById('riderIdFile').click()"
              ondragover="event.preventDefault(); this.style.borderColor='saddlebrown';"
              ondragleave="this.style.borderColor='#c8b89a';" ondrop="handleIdDrop(event)">
              <div style="font-size: 28px; margin-bottom: 8px; color: #c8b89a;">🪪</div>
              <p style="font-size: 13px; font-family: sans-serif; color: #8b6340; margin: 0;">
                <span style="color: saddlebrown; font-weight: 500;">Click to upload</span> or drag and drop
              </p>
              <p style="font-size: 11px; font-family: sans-serif; color: #c8b89a; margin: 4px 0 0;">JPG, PNG, PDF up to 5MB</p>
            </div>
            <input type="file" id="riderIdFile" accept="image/*,.pdf" style="display: none;" onchange="handleIdUpload(this)">

            <div id="id-preview-wrap" style="display: none; margin-top: 10px; position: relative;">
              <img id="id-preview-img" src="" alt="ID preview"
                style="width: 100%; max-height: 160px; object-fit: cover; border-radius: 8px; border: 1.5px solid #e8dcc8; display: block;">
              <div id="id-preview-file"
                style="display: none; padding: 10px 12px; background: #fdf6ed; border: 1.5px solid #e8dcc8; border-radius: 8px; font-size: 13px; font-family: sans-serif; color: #3b2208;">
              </div>
              <button onclick="clearIdUpload()"
                style="position: absolute; top: 6px; right: 6px; width: 24px; height: 24px; border-radius: 50%; background: #c0392b; color: white; border: none; cursor: pointer; font-size: 14px; display: flex; align-items: center; justify-content: center; line-height: 1;">×</button>
            </div>
            <span class="field-hint" id="id-hint" style="margin-top: 6px; display: block;"></span>
          </div>

          <div class="form-check">
            <label>
              <input type="checkbox" id="riderTerms">
              I agree to the <a href="#">Terms &amp; Conditions</a>
            </label>
          </div>

          <button class="submit-btn" onclick="handleRiderSubmit()">APPLY NOW</button>

          <p class="modal-footer-text">
            Already a rider? <a href="#"
              onclick="document.getElementById('riderModal').classList.remove('active'); document.getElementById('riderLoginModal').classList.add('active'); return false;">Sign
              in here</a>
          </p>

        </div>
      </div>
    </div>


    <div class="modal-overlay" id="riderLoginModal" onclick="if(event.target===this)this.classList.remove('active')">
      <div class="modal">
        <button class="modal-close" onclick="document.getElementById('riderLoginModal').classList.remove('active')" aria-label="Close">&times;</button>
        <div class="modal-header">
          <h2 class="modal-title" style="color: saddlebrown;"><i class="fa-solid fa-motorcycle"></i> Rider Portal</h2>
          <p class="modal-subtitle">Sign in to your delivery account</p>
        </div>
        <div class="modal-body">
          <div class="input-group">
            <label for="rider-login-email">Email</label>
            <input type="email" id="rider-login-email" placeholder="rider@example.com" autocomplete="email">
          </div>
          <div class="input-group">
            <label for="rider-login-password">Password</label>
            <input type="password" id="rider-login-password" placeholder="••••••••" autocomplete="current-password">
          </div>

          <button class="modal-submit" onclick="handleRiderLogin()">Sign In as Rider</button>

          <p class="modal-register" style="margin-top: 15px;">
            Not a rider yet? <a href="#" onclick="document.getElementById('riderLoginModal').classList.remove('active'); document.getElementById('riderModal').classList.add('active'); return false;">Apply Now</a>
          </p>
        </div>
      </div>
    </div>
    </div>


    <!-- Login Modal -->
    <div class="modal-overlay" id="loginModal" onclick="if(event.target===this)this.classList.remove('active')">
      <div class="modal">
        <button class="modal-close" onclick="document.getElementById('loginModal').classList.remove('active')"
          aria-label="Close">&times;</button>
        <div class="modal-header">
          <h2 class="modal-title">Welcome!</h2>
          <p class="modal-subtitle">Sign in now!</p>
        </div>
        <div class="modal-body">
          <div class="input-group">
            <label for="email">Email</label>
            <input type="email" id="email" placeholder="you@example.com" autocomplete="email">
          </div>
          <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" placeholder="••••••••" autocomplete="current-password">
          </div>
          <div class="modal-options">
            <label class="remember-me">
              <input type="checkbox" id="remember"> Remember me
            </label>
            <a href="#" class="forgot-link" onclick="switchToForgot()">Forgot password?</a>
          </div>
          <button class="modal-submit" onclick="handleLogin()">Sign In</button>
          <p class="modal-register">Don't have an account? <a href="#" onclick="switchToRegister()">Register</a></p>
        </div>
      </div>
    </div>

    <!-- Register Modal -->
    <div class="modal-overlay" id="registerModal" onclick="if(event.target===this)this.classList.remove('active')">
      <div class="modal" style="max-width: 520px;">
        <button class="modal-close" onclick="document.getElementById('registerModal').classList.remove('active')"
          aria-label="Close">&times;</button>
        <div class="modal-header">
          <h2 class="modal-title">Let's Get You Started</h2>
          <p class="modal-subtitle">Create an account to manage your orders, save your favorites, and get your snacks
            delivered straight to you.</p>
        </div>
        <div class="modal-body">

          <div style="display: flex; gap: 10px;">
            <div class="input-group" style="flex: 1;">
              <label>First Name</label>
              <input type="text" id="reg-fname" placeholder="Juan" class="settings-input"
                style="border-radius: 8px; width: 100%;">
            </div>

            <div class="input-group" style="flex: 1;">
              <label>Last Name</label>
              <input type="text" id="reg-lname" placeholder="Dela Cruz" class="settings-input"
                style="border-radius: 8px; width: 100%;">
            </div>
          </div>

          <div class="input-group" style="flex: 1;">
            <label>Date of Birth</label>
            <input type="date" id="reg-dob" class="settings-input" style="border-radius: 8px; width: 100%;">
          </div>

          <div class="input-group">
            <label>Address</label>
            <input type="text" id="reg-address" placeholder="House No., Street, Barangay, City" maxlength="150"
              class="settings-input" style="border-radius: 8px; width: 100%;" oninput="regCharCount(this)">
            <span class="field-hint" id="reg-address-count">0/150</span>
          </div>

          <div style="display: flex; gap: 10px;">
            <div class="input-group" style="flex: 1;">
              <label>Email</label>
              <input type="email" id="reg-email" placeholder="example@email.com" class="settings-input"
                style="border-radius: 8px; width: 100%;">
            </div>
            <div class="input-group" style="flex: 1;">
              <label>Phone Number</label>
              <input type="tel" id="reg-phone" placeholder="09XX-XXX-XXXX" maxlength="13" class="settings-input"
                style="border-radius: 8px; width: 100%;" oninput="regFormatPhone(this)">
              <span class="field-hint" id="reg-phone-hint"></span>
            </div>
          </div>

          <div style="display: flex; gap: 10px;">
            <div class="input-group" style="flex: 1;">
              <label>Password</label>
              <input type="password" id="reg-password" placeholder="••••••••" class="settings-input"
                style="border-radius: 8px; width: 100%;" oninput="checkRegPassStrength(this.value)">
              <span class="field-hint" id="reg-pass-hint"></span>
            </div>
            <div class="input-group" style="flex: 1;">
              <label>Confirm Password</label>
              <input type="password" id="reg-confirm" placeholder="••••••••" class="settings-input"
                style="border-radius: 8px; width: 100%;">
            </div>
          </div>

          <button class="modal-submit" onclick="handleRegister()">Create</button>
          <p class="modal-footer-text" style="text-align: center; margin-top: 15px;">
            Already have an account? <a href="#"
              onclick="document.getElementById('registerModal').classList.remove('active'); document.getElementById('loginModal').classList.add('active'); return false;">Sign in</a>
          </p>

        </div>
      </div>
    </div>

    <!-- Forgot Password Modal -->
    <div class="modal-overlay" id="forgotModal" onclick="if(event.target===this)this.classList.remove('active')">
      <div class="modal" style="max-width: 420px;">
        <button class="modal-close" onclick="closeForgot()" aria-label="Close">&times;</button>

        <!-- Step 1: Enter Phone -->
        <div id="forgot-step-1">
          <div class="modal-header">
            <h2 class="modal-title">Forgot Password?</h2>
            <p class="modal-subtitle">Enter your registered mobile number and we'll send you a verification code.</p>
          </div>
          <div class="modal-body">
            <div class="input-group">
              <label>Phone Number</label>
              <input type="tel" id="forgot-phone" placeholder="09XX-XXX-XXXX" maxlength="13" class="settings-input"
                style="border-radius: 8px; width: 100%;" oninput="forgotFormatPhone(this)">
              <span class="field-hint" id="forgot-phone-hint"></span>
            </div>
            <button class="modal-submit" onclick="sendOTP()">Send Verification Code</button>
            <p class="modal-register">Remembered it? <a href="#" onclick="switchToLogin()">Sign In</a></p>
          </div>
        </div>

        <div id="forgot-step-2" style="display:none; text-align: center; padding: 0.5rem 0;">

          <div style="text-align: left;">
            <button class="otp-back-btn" onclick="showForgotStep(1)">
              <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round">
                <polyline points="15 18 9 12 15 6" />
              </svg>
            </button>
          </div>

          <div class="otp-icon-wrap">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="saddlebrown" stroke-width="1.8"
              stroke-linecap="round" stroke-linejoin="round">
              <rect x="5" y="2" width="14" height="20" rx="2" />
              <path d="M12 18h.01" />
              <path d="M9 7h6M9 11h4" />
            </svg>
          </div>

          <p style="font-size: 20px; font-weight: 500; color: #3b2208; font-family: sans-serif; margin-bottom: 8px;">Enter
            OTP</p>
          <p style="font-size: 13px; color: #8b6340; font-family: sans-serif; margin-bottom: 4px;">We sent a 6-digit code
            to</p>
          <p style="font-size: 14px; font-weight: 500; color: #3b2208; font-family: sans-serif; margin-bottom: 1.8rem;"
            id="forgot-phone-display"></p>

          <div class="otp-row" id="gcash-otp-row">
            <div style="display:flex; gap:10px; justify-content:center; margin-bottom:8px;" id="gcash-otp-row">
              <input
                style="width:48px;height:54px;border:2px solid #e8dcc8;border-radius:10px;background:#fff;font-size:26px;font-weight:500;text-align:center;color:#3b2208;font-family:sans-serif;outline:none;padding:0;box-shadow:none;-webkit-appearance:none;appearance:none;"
                type="text" inputmode="numeric" maxlength="1" id="gb0" onkeydown="gcashKey(this,0,event)"
                oninput="gcashInput(this,0)">
              <input
                style="width:48px;height:54px;border:2px solid #e8dcc8;border-radius:10px;background:#fff;font-size:26px;font-weight:500;text-align:center;color:#3b2208;font-family:sans-serif;outline:none;padding:0;box-shadow:none;-webkit-appearance:none;appearance:none;"
                type="text" inputmode="numeric" maxlength="1" id="gb1" onkeydown="gcashKey(this,1,event)"
                oninput="gcashInput(this,1)">
              <input
                style="width:48px;height:54px;border:2px solid #e8dcc8;border-radius:10px;background:#fff;font-size:26px;font-weight:500;text-align:center;color:#3b2208;font-family:sans-serif;outline:none;padding:0;box-shadow:none;-webkit-appearance:none;appearance:none;"
                type="text" inputmode="numeric" maxlength="1" id="gb2" onkeydown="gcashKey(this,2,event)"
                oninput="gcashInput(this,2)">
              <input
                style="width:48px;height:54px;border:2px solid #e8dcc8;border-radius:10px;background:#fff;font-size:26px;font-weight:500;text-align:center;color:#3b2208;font-family:sans-serif;outline:none;padding:0;box-shadow:none;-webkit-appearance:none;appearance:none;"
                type="text" inputmode="numeric" maxlength="1" id="gb3" onkeydown="gcashKey(this,3,event)"
                oninput="gcashInput(this,3)">
              <input
                style="width:48px;height:54px;border:2px solid #e8dcc8;border-radius:10px;background:#fff;font-size:26px;font-weight:500;text-align:center;color:#3b2208;font-family:sans-serif;outline:none;padding:0;box-shadow:none;-webkit-appearance:none;appearance:none;"
                type="text" inputmode="numeric" maxlength="1" id="gb4" onkeydown="gcashKey(this,4,event)"
                oninput="gcashInput(this,4)">
              <input
                style="width:48px;height:54px;border:2px solid #e8dcc8;border-radius:10px;background:#fff;font-size:26px;font-weight:500;text-align:center;color:#3b2208;font-family:sans-serif;outline:none;padding:0;box-shadow:none;-webkit-appearance:none;appearance:none;"
                type="text" inputmode="numeric" maxlength="1" id="gb5" onkeydown="gcashKey(this,5,event)"
                oninput="gcashInput(this,5)">
            </div>
          </div>

          <div style="font-size: 13px; color: #8b6340; font-family: sans-serif; margin: 10px 0 4px;">
            Code expires in <span id="otp-timer" style="font-weight:500; color: saddlebrown;">2:00</span>
          </div>
          <p class="field-hint" id="otp-error" style="text-align:center; min-height:16px; margin-bottom: 12px;"></p>

          <button class="modal-submit" id="verify-main-btn" onclick="verifyOTP()" disabled
            style="opacity:0.5;">Verify</button>

          <p style="font-size: 13px; color: #8b6340; font-family: sans-serif; margin-top: 1rem;">
            Didn't receive it?
            <button class="otp-resend-btn" id="resend-link" onclick="resendOTP()" disabled>
              Resend (<span id="resend-timer">30</span>s)
            </button>
          </p>

        </div>

        <!-- Step 3: New Password -->
        <div id="forgot-step-3" style="display:none;">
          <div class="modal-header">
            <h2 class="modal-title">Set New Password</h2>
            <p class="modal-subtitle">Choose a strong password for your account.</p>
          </div>
          <div class="modal-body">
            <div class="input-group">
              <label>New Password</label>
              <input type="password" id="forgot-newpass" placeholder="••••••••" class="settings-input"
                style="border-radius: 8px; width: 100%;" oninput="checkForgotPassStrength(this.value)">
              <span class="field-hint" id="forgot-pass-hint"></span>
            </div>
            <div class="input-group">
              <label>Confirm New Password</label>
              <input type="password" id="forgot-confirmpass" placeholder="••••••••" class="settings-input"
                style="border-radius: 8px; width: 100%;">
            </div>
            <button class="modal-submit" onclick="resetPassword()">Reset Password</button>
          </div>
        </div>

        <!-- Step 4: Success -->
        <div id="forgot-step-4" style="display:none;">
          <div class="modal-header" style="text-align: center; padding: 2rem 1rem;">
            <div style="font-size: 52px; margin-bottom: 12px;">✅</div>
            <h2 class="modal-title">Password Reset!</h2>
            <p class="modal-subtitle">Your password has been updated successfully. You can now sign in with your new
              password.</p>
          </div>
          <div class="modal-body">
            <button class="modal-submit" onclick="switchToLogin()">Back to Sign In</button>
          </div>
        </div>

      </div>
    </div>

    <main class="main">
      <h2 class="section-title">Our Products</h2>
      <p class="section-subtitle">Fresh from the oven — handcrafted with love</p>
      <div class="product-grid">

        <div class="product-card">
          <div class="product-img">
            <img src="./assets/product1.jpg" alt="Product 1">
            <div class="product-overlay"><span>Ensaymada</span></div>
          </div>
          <p class="product-label">Soft, buttery, and delightfully fluffy. Our ensaymada is baked to golden perfection and
            topped with a rich layer of creamy butter, sugar, and grated cheese for that perfect balance of sweet and
            savory.</p>
        </div>

        <div class="product-card">
          <div class="product-img">
            <img src="./assets/product2.png" alt="Product 2">
            <div class="product-overlay"><span>Sandwiches</span></div>
          </div>
          <p class="product-label">Fresh, flavorful, and made to satisfy. Our sandwiches are stacked with quality
            ingredients, from savory fillings to crisp veggies, all tucked between perfectly baked bread—ideal for a quick
            bite or a hearty meal any time of day.</p>
        </div>

        <div class="product-card">
          <div class="product-img">
            <img src="./assets/product3.png" alt="Product 3">
            <div class="product-overlay"><span>Coffee</span></div>
          </div>
          <p class="product-label">Bold, aromatic, and perfectly brewed—our coffee delivers a smooth, satisfying sip in
            every cup.</p>
        </div>

      </div>
    </main>

    <section class="devs-section">
      <h3 class="devs-title">About us</h3>
      <p class="devs-subtitle">The team behind this project</p>
      <div class="devs-grid">

        <div class="dev-card">
          <div class="dev-avatar">JD</div>
          <p class="dev-name">Juan Dela Cruz</p>
          <p class="dev-role">Frontend Developer</p>
        </div>

        <div class="dev-card">
          <div class="dev-avatar">MA</div>
          <p class="dev-name">Maria Andres</p>
          <p class="dev-role">UI/UX Designer</p>
        </div>

        <div class="dev-card">
          <div class="dev-avatar">KR</div>
          <p class="dev-name">Karl Reyes</p>
          <p class="dev-role">Backend Developer</p>
        </div>

      </div>
    </section>

    <footer class="footer">
      <div class="social-icons">
        <a href="#" class="social-icon" aria-label="Facebook">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z" />
          </svg>
        </a>
        <a href="#" class="social-icon" aria-label="Instagram">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
            stroke-linecap="round" stroke-linejoin="round">
            <rect x="2" y="2" width="20" height="20" rx="5" ry="5" />
            <circle cx="12" cy="12" r="4" />
            <circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none" />
          </svg>
        </a>
        <a href="#" class="social-icon" aria-label="TikTok">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="currentColor">
            <path
              d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-2.88 2.5 2.89 2.89 0 0 1-2.89-2.89 2.89 2.89 0 0 1 2.89-2.89c.28 0 .54.04.79.1V9.01a6.33 6.33 0 0 0-.79-.05 6.34 6.34 0 0 0-6.34 6.34 6.34 6.34 0 0 0 6.34 6.34 6.34 6.34 0 0 0 6.33-6.34V8.69a8.19 8.19 0 0 0 4.78 1.52V6.75a4.85 4.85 0 0 1-1.01-.06z" />
          </svg>
        </a>
      </div>

      <div class="footer-info">
        <span><?php echo htmlspecialchars($store_address); ?></span>
        <span><?php echo htmlspecialchars($store_contact); ?></span>
      </div>
    </footer>

    <script src="./js/script.js"> </script>

    <script>

function handleRegister() {
    const fname = document.getElementById('reg-fname').value.trim();
    const lname = document.getElementById('reg-lname').value.trim();
    const dob = document.getElementById('reg-dob').value;
    const address = document.getElementById('reg-address').value.trim();
    const email = document.getElementById('reg-email').value.trim();
    const phone = document.getElementById('reg-phone').value.trim().replace(/-/g, '');
    const password = document.getElementById('reg-password').value;
    const confirm = document.getElementById('reg-confirm').value;

    const formData = new FormData();
    formData.append('fname', fname);
    formData.append('lname', lname);
    formData.append('dob', dob);
    formData.append('address', address);
    formData.append('email', email);
    formData.append('phone', phone);
    formData.append('password', password);
    formData.append('confirm', confirm);

    fetch('register.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('✅ ' + data.message);
                document.getElementById('registerModal').classList.remove('active');
                document.getElementById('loginModal').classList.add('active');
            } else {
                alert('❌ ' + data.message);
            }
        })
        .catch(() => alert('Network error. Please try again.'));
}

      // --- SEPARATED RIDER LOGIN ---
      function handleRiderLogin() {
        const email = document.getElementById('rider-login-email').value.trim();
        const password = document.getElementById('rider-login-password').value;

        if (!email || !password) {
          alert('Please fill in all fields.');
          return;
        }

        const btn = document.querySelector('#riderLoginModal .modal-submit');
        btn.disabled = true;
        btn.textContent = 'Signing in...';

        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);

        // This safely targets the rider backend we created earlier
        fetch('login_rider.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              // Redirect straight to the rider dashboard
              window.location.href = './rider/index.php';
            } else {
              alert(data.message);
            }
          })
          .catch(() => alert('Network error. Please try again.'))
          .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Sign In as Rider';
          });
      }
      // --- RIDER REGISTRATION ---
      function handleRiderSubmit() {
        const fname = document.getElementById('riderFirstName').value.trim();
        const lname = document.getElementById('riderLastName').value.trim();
        const email = document.getElementById('riderEmail').value.trim();
        const phone = document.getElementById('riderPhone').value.trim();
        const vehicle = document.getElementById('riderVehicle').value;
        const password = document.getElementById('riderPassword').value;
        const confirm = document.getElementById('confirmriderPassword').value;
        const idFile = document.getElementById('riderIdFile').files[0];
        const terms = document.getElementById('riderTerms').checked;

        // 1. MERGE FIRST NAME AND LAST NAME HERE
        const mergedFullName = fname + " " + lname;

        // Client-side Validation
        if (!fname || !lname || !email || !phone || !vehicle || !password) {
          alert('Please fill in all required fields.');
          return;
        }
        if (password !== confirm) {
          alert('Passwords do not match.');
          return;
        }
        if (!idFile) {
          alert('Please upload a valid ID.');
          return;
        }
        if (!terms) {
          alert('You must agree to the Terms & Conditions.');
          return;
        }

        const btn = document.querySelector('#riderModal .submit-btn');
        btn.disabled = true;
        btn.textContent = 'SUBMITTING...';

        // 2. APPEND THE MERGED NAME AND CORRECT DB COLUMNS
        const formData = new FormData();
        formData.append('fullname', mergedFullName);
        formData.append('email', email);
        formData.append('contact', phone); // Changed to match your 'contact' DB column
        formData.append('vehicle_type', vehicle);
        formData.append('password', password);
        formData.append('id_image', idFile);

        fetch('register_rider.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              alert(data.message);
              document.getElementById('riderModal').classList.remove('active');
              // Reset form
              document.querySelectorAll('#riderModal input').forEach(inp => inp.value = '');
            } else {
              alert(data.message);
            }
          })
          .catch(() => alert('Network error. Please try again.'))
          .finally(() => {
            btn.disabled = false;
            btn.textContent = 'APPLY NOW';
          });
      }

      function showRegError(msg) {
        let errEl = document.getElementById('reg-error-msg');
        if (!errEl) {
          errEl = document.createElement('p');
          errEl.id = 'reg-error-msg';
          errEl.className = 'field-hint error';
          errEl.style.cssText = 'text-align:center; margin-bottom: 8px;';
          const btn = document.querySelector('#registerModal .modal-submit');
          btn.parentNode.insertBefore(errEl, btn);
        }
        errEl.textContent = msg;
      }

      function handleLogin() {
        const email = document.getElementById('email').value.trim();
        const password = document.getElementById('password').value;

        if (!email || !password) {
          alert('Please fill in all fields.');
          return;
        }

        const btn = document.querySelector('#loginModal .modal-submit');
        btn.disabled = true;
        btn.textContent = 'Signing in...';

        const formData = new FormData();
        formData.append('email', email);
        formData.append('password', password);

        fetch('login.php', {
            method: 'POST',
            body: formData
          })
          .then(res => res.json())
          .then(data => {
            if (data.success) {
              window.location.href = 'dashboard.php';
            } else {
              alert(data.message);
            }
          })
          .catch(() => alert('Network error. Please try again.'))
          .finally(() => {
            btn.disabled = false;
            btn.textContent = 'Sign In';
          });
      }
    </script>


  </body>

  </html>