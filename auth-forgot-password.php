<?php
session_start();

// Database configuration (should match your login/register pages)
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cards_printer';
$db_table = 'users';

// Establish database connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize variables
$email = $password = $confirm_password = '';
$errors = [];
$success_message = '';

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $email = filter_var(trim(isset($_POST['email']) ? $_POST['email'] : ''), FILTER_SANITIZE_EMAIL);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm-password']) ? $_POST['confirm-password'] : '';

    // Validation
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if ($password !== $confirm_password) {
        $errors['confirm-password'] = 'Passwords do not match';
    }

    // If no errors, proceed with password reset
    if (empty($errors)) {
        // Check if email exists in database
        $stmt = $conn->prepare("SELECT id FROM $db_table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Email exists, update password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $update_stmt = $conn->prepare("UPDATE $db_table SET password = ? WHERE email = ?");
            $update_stmt->bind_param("ss", $hashed_password, $email);

            if ($update_stmt->execute()) {
                $success_message = 'Password has been reset successfully!';

                // Clear form
                $email = $password = $confirm_password = '';

                // Optionally send email notification
                // $this->sendPasswordResetEmail($email);
            } else {
                $errors['database'] = 'Failed to reset password. Please try again.';
            }

            $update_stmt->close();
        } else {
            // Email doesn't exist, but we don't reveal this to prevent email enumeration
            $success_message = 'If the email exists in our system, you will receive a password reset link.';
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport">
    <title>Reset Password &mdash; Stisla</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">

    <!-- CSS Libraries -->

    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <!-- Start GA -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=UA-94034622-3"></script>
    <script>
        window.dataLayer = window.dataLayer || [];

        function gtag() {
            dataLayer.push(arguments);
        }

        gtag('js', new Date());

        gtag('config', 'UA-94034622-3');
    </script>
    <!-- /END GA --></head>

<body>
<div id="app">
    <section class="section">
        <div class="container mt-5">
            <div class="row">
                <div class="col-12 col-sm-8 offset-sm-2 col-md-6 offset-md-3 col-lg-6 offset-lg-3 col-xl-4 offset-xl-4">
                    <div class="login-brand">
                        <img src="assets/img/stisla-fill.svg" alt="logo" width="100"
                             class="shadow-light rounded-circle">
                    </div>

                    <div class="card card-primary">
                        <div class="card-header"><h4>Reset Password</h4></div>

                        <div class="card-body">
                            <?php if (!empty($success_message)): ?>
                                <div class="alert alert-success">
                                    <?php echo htmlspecialchars($success_message); ?>
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($errors['database'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($errors['database']); ?>
                                </div>
                            <?php endif; ?>

                            <!--                            <p class="text-muted">We will send a link to reset your password</p>-->
                            <form method="POST" action="">
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input id="email" type="email"
                                           class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                           name="email" value="<?php echo htmlspecialchars($email); ?>" tabindex="1"
                                           required autofocus>
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo htmlspecialchars($errors['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <label for="password">New Password</label>
                                    <input id="password" type="password"
                                           class="form-control pwstrength <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                           data-indicator="pwindicator" name="password" tabindex="2" required>
                                    <?php if (isset($errors['password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo htmlspecialchars($errors['password']); ?>
                                        </div>
                                    <?php endif; ?>
                                    <div id="pwindicator" class="pwindicator">
                                        <div class="bar"></div>
                                        <div class="label"></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="password-confirm">Confirm Password</label>
                                    <input id="password-confirm" type="password"
                                           class="form-control <?php echo isset($errors['confirm-password']) ? 'is-invalid' : ''; ?>"
                                           name="confirm-password" tabindex="3" required>
                                    <?php if (isset($errors['confirm-password'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo htmlspecialchars($errors['confirm-password']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                                        Reset Password
                                    </button>
                                    <a href="Login.php" type="submit" class="btn btn-outline-primary btn-lg btn-block"
                                       tabindex="4">
                                        Login
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="simple-footer">
                        Copyright &copy; Othman 2025
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>

<!-- General JS Scripts -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/tooltip.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>

<!-- JS Libraies -->

<!-- Page Specific JS File -->

<!-- Template JS File -->
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>