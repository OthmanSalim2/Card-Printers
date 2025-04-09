<?php
session_start();

// Database configuration
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
$email = $password = '';
$errors = [];

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize inputs
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    // Validation
    if (empty($email)) {
        $errors['email'] = 'Please fill in your email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    }

    if (empty($password)) {
        $errors['password'] = 'Please fill in your password';
    }

    // If no errors, proceed with login
    if (empty($errors)) {
        // Prepare SQL statement to get user data including role
        $stmt = $conn->prepare("SELECT id, email, password, role FROM $db_table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Verify password (using password_verify for hashed passwords)
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_role'] = !empty($user['role']) ? $user['role'] : 'user'; // Default to 'user' if role not set
                $_SESSION['logged_in'] = true;

                // Remember me functionality (secure implementation)
                if ($remember) {
                    $token = bin2hex(random_bytes(32)); // Generate secure token
                    $hashed_token = hash('sha256', $token);

                    // Store token in database
                    $update_stmt = $conn->prepare("UPDATE $db_table SET remember_token = ? WHERE id = ?");
                    $update_stmt->bind_param("si", $hashed_token, $user['id']);
                    $update_stmt->execute();

                    // Set cookie (HttpOnly, Secure in production)
                    $cookie_value = $user['id'] . ':' . $token;
                    setcookie(
                        'remember_me',
                        $cookie_value,
                        time() + (86400 * 30), // 30 days
                        "/",
                        "",
                        false, // Set to true if using HTTPS
                        true // HttpOnly flag
                    );
                }

                // Redirect to dashboard
                header("Location: dashboard.php");
                exit();
            } else {
                $errors['login'] = 'Invalid email or password';
            }
        } else {
            $errors['login'] = 'Invalid email or password';
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
    <title>Login &mdash; Stisla</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="assets/modules/bootstrap-social/bootstrap-social.css">

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
</head>

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
                        <div class="card-header"><h4>Login</h4></div>

                        <div class="card-body">
                            <?php if (!empty($errors['login'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($errors['login']); ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="" class="needs-validation" novalidate>
                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input id="email" type="email"
                                           class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                           name="email" tabindex="1" value="<?php echo htmlspecialchars($email); ?>"
                                           required autofocus>
                                    <div class="invalid-feedback">
                                        <?php echo isset($errors['email']) ? htmlspecialchars($errors['email']) : 'Please fill in your email'; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="d-block">
                                        <label for="password" class="control-label">Password</label>
                                        <div class="float-right">
                                            <a href="auth-forgot-password.php" class="text-small">
                                                Forgot Password?
                                            </a>
                                        </div>
                                    </div>
                                    <input id="password" type="password"
                                           class="form-control <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                           name="password" tabindex="2" required>
                                    <div class="invalid-feedback">
                                        <?php echo isset($errors['password']) ? htmlspecialchars($errors['password']) : 'Please fill in your password'; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox" name="remember" class="custom-control-input" tabindex="3"
                                               id="remember-me">
                                        <label class="custom-control-label" for="remember-me">Remember Me</label>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block" tabindex="4">
                                        Login
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="mt-5 text-muted text-center">
                        Don't have an account? <a href="register.php">Create One</a>
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

<!-- Template JS File -->
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>