<?php
session_start();

// Database configuration (should match your login page)
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
$first_name = $last_name = $email = $password = $password_confirm = '';
$errors = [];
$registration_success = false;

// Check if form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $first_name = htmlspecialchars(trim(isset($_POST['frist_name']) ? $_POST['frist_name'] : ''));
    $last_name = htmlspecialchars(trim(isset($_POST['last_name']) ? $_POST['last_name'] : ''));
    $email = filter_var(trim(isset($_POST['email']) ? $_POST['email'] : ''), FILTER_SANITIZE_EMAIL);
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $password_confirm = isset($_POST['password-confirm']) ? $_POST['password-confirm'] : '';

    // Validation
    if (empty($first_name)) {
        $errors['first_name'] = 'First name is required';
    } elseif (strlen($first_name) < 2) {
        $errors['first_name'] = 'First name must be at least 2 characters';
    }

    if (empty($last_name)) {
        $errors['last_name'] = 'Last name is required';
    } elseif (strlen($last_name) < 2) {
        $errors['last_name'] = 'Last name must be at least 2 characters';
    }

    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Please enter a valid email address';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM $db_table WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors['email'] = 'Email is already registered';
        }
        $stmt->close();
    }

    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 8) {
        $errors['password'] = 'Password must be at least 8 characters';
    }

    if ($password !== $password_confirm) {
        $errors['password-confirm'] = 'Passwords do not match';
    }

    // If no errors, proceed with registration
    if (empty($errors)) {
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Combine first and last name
        $full_name = $first_name . ' ' . $last_name;

        // Insert into database
        $stmt = $conn->prepare("INSERT INTO $db_table (first_name, last_name, email, password) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $first_name, $last_name, $email, $hashed_password);

        if ($stmt->execute()) {
            $registration_success = true;

            // Optionally log the user in immediately after registration
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['user_email'] = $email;
//            $_SESSION['user_name'] = $full_name;
            $_SESSION['logged_in'] = true;

            // Redirect to dashboard or welcome page
            header("Location: dashboard.php");
            exit();
        } else {
            $errors['database'] = 'Registration failed. Please try again.';
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
    <title>Register &mdash; Stisla</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="assets/modules/jquery-selectric/selectric.css">

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
                <div class="col-12 col-sm-10 offset-sm-1 col-md-8 offset-md-2 col-lg-8 offset-lg-2 col-xl-8 offset-xl-2">
                    <div class="login-brand">
                        <img src="assets/img/stisla-fill.svg" alt="logo" width="100"
                             class="shadow-light rounded-circle">
                    </div>

                    <div class="card card-primary">
                        <div class="card-header"><h4>Register</h4></div>

                        <div class="card-body">
                            <?php if (!empty($errors['database'])): ?>
                                <div class="alert alert-danger">
                                    <?php echo htmlspecialchars($errors['database']); ?>
                                </div>
                            <?php endif; ?>

                            <?php if ($registration_success): ?>
                                <div class="alert alert-success">
                                    Registration successful! Redirecting...
                                </div>
                            <?php endif; ?>

                            <form method="POST" action="">
                                <div class="row">
                                    <div class="form-group col-6">
                                        <label for="frist_name">First Name</label>
                                        <input id="frist_name" type="text"
                                               class="form-control <?php echo isset($errors['first_name']) ? 'is-invalid' : ''; ?>"
                                               name="frist_name" value="<?php echo htmlspecialchars($first_name); ?>"
                                               autofocus>
                                        <?php if (isset($errors['first_name'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo htmlspecialchars($errors['first_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="form-group col-6">
                                        <label for="last_name">Last Name</label>
                                        <input id="last_name" type="text"
                                               class="form-control <?php echo isset($errors['last_name']) ? 'is-invalid' : ''; ?>"
                                               name="last_name" value="<?php echo htmlspecialchars($last_name); ?>">
                                        <?php if (isset($errors['last_name'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo htmlspecialchars($errors['last_name']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label for="email">Email</label>
                                    <input id="email" type="email"
                                           class="form-control <?php echo isset($errors['email']) ? 'is-invalid' : ''; ?>"
                                           name="email" value="<?php echo htmlspecialchars($email); ?>">
                                    <?php if (isset($errors['email'])): ?>
                                        <div class="invalid-feedback">
                                            <?php echo htmlspecialchars($errors['email']); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="row">
                                    <div class="form-group col-6">
                                        <label for="password" class="d-block">Password</label>
                                        <input id="password" type="password"
                                               class="form-control pwstrength <?php echo isset($errors['password']) ? 'is-invalid' : ''; ?>"
                                               data-indicator="pwindicator" name="password">
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
                                    <div class="form-group col-6">
                                        <label for="password2" class="d-block">Password Confirmation</label>
                                        <input id="password2" type="password"
                                               class="form-control <?php echo isset($errors['password-confirm']) ? 'is-invalid' : ''; ?>"
                                               name="password-confirm">
                                        <?php if (isset($errors['password-confirm'])): ?>
                                            <div class="invalid-feedback">
                                                <?php echo htmlspecialchars($errors['password-confirm']); ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-lg btn-block">
                                        Register
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
<script src="assets/modules/jquery-pwstrength/jquery.pwstrength.min.js"></script>
<script src="assets/modules/jquery-selectric/jquery.selectric.min.js"></script>

<!-- Page Specific JS File -->
<script src="assets/js/page/auth-register.js"></script>

<!-- Template JS File -->
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>