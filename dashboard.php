<?php
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cards_printer';
$db_table = 'users';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Handle card selection form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['card_quantity'])) {
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $user_id = $_SESSION['user_id'];
    $card_quantity = $_POST['card_quantity'];

    // Check if user exists
    $stmt = $conn->prepare("SELECT id FROM $db_table WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // User found, update the card quantity directly
        $stmt = $conn->prepare("UPDATE $db_table SET number_cards = ? WHERE id = ?");
        $stmt->bind_param("si", $card_quantity, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows > 0) {
            $_SESSION['success_message'] = "تم تحديث عدد الكروت بنجاح!";
        } else {
            $_SESSION['error_message'] = "حدث خطأ أثناء التحديث";
        }
    } else {
        // User not found
        $_SESSION['error_message'] = "المستخدم غير موجود!";
    }

    $stmt->close();
    $conn->close();

    // Redirect to avoid form resubmission
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get user details from database
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$stmt = $conn->prepare("SELECT first_name, last_name, number_cards, status, role FROM $db_table WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    $first_name = $user['first_name'];
    $last_name = $user['last_name'];
    $full_name = trim("$first_name $last_name");
    $number_cards = $user['number_cards'];
    $status = $user['status'] ?? 'inactive';
    $role = $user['role'] ?? 'user';
} else {
    // Default values if user not found
    $first_name = 'User';
    $last_name = '';
    $full_name = 'User';
    $number_cards = 1000;
}

$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8"/>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport"/>
    <title>لوحة التحكم - طابعة الكروت</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css"/>

    <!-- CSS Libraries -->
    <link rel="stylesheet" href="assets/modules/jqvmap/dist/jqvmap.min.css"/>
    <link rel="stylesheet" href="assets/modules/weather-icon/css/weather-icons.min.css"/>
    <link rel="stylesheet" href="assets/modules/weather-icon/css/weather-icons-wind.min.css"/>
    <link rel="stylesheet" href="assets/modules/summernote/summernote-bs4.css"/>

    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/components.css"/>

    <style>
        .btn-card {
            width: 100%;
            padding: 15px;
            font-size: 18px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }

        .btn-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>
<body>
<div id="app">
    <div class="main-wrapper main-wrapper-1">
        <!-- Main Content -->
        <div class="main-content">
            <section class="section">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show">
                        <?php echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show">
                        <?php echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                <?php endif; ?>

                <div class="section">
                    <h1>مرحباً <?php echo htmlspecialchars($first_name); ?>!</h1>
                </div>

                <div class="row" style="direction: ltr;">
                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-primary">
                                <i class="far fa-user"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>الاسم</h4>
                                </div>
                                <div class="card-body"><?php echo htmlspecialchars($full_name); ?></div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                        <div class="card card-statistic-1">
                            <div class="card-icon bg-danger">
                                <i class="fas fa-credit-card"></i>
                            </div>
                            <div class="card-wrap">
                                <div class="card-header">
                                    <h4>عدد الكروت</h4>
                                </div>
                                <div class="card-body"><?php echo htmlspecialchars($number_cards); ?></div>
                            </div>
                        </div>
                    </div>

                    <?php if ($role === 'admin') { ?>
                        <div class="col-lg-3 col-md-6 col-sm-6 col-12">
                            <div class="card card-statistic-1">
                                <div class="card-icon bg-warning">
                                    <i class="fas fa-desktop"></i>
                                </div>
                                <div class="card-wrap">
                                    <div class="card-header">
                                        <h4>Control Panel of users</h4>
                                    </div>
                                    <a href="control-panel.php" class="card-body">لوحة التحكم</a>
                                </div>
                            </div>
                        </div>
                    <?php } ?>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>الأسعار</h4>
                            </div>
                            <div class="card-body">
                                <div class="empty-state" data-height="450">
                                    <div class="empty-state-icon">
                                        <i class="fas fa-dollar-sign"></i>
                                    </div>
                                    <h2>أسعار طباعة الكروت</h2>
                                    <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                                        <button type="submit" name="card_quantity" value="1000"
                                                class="btn btn-primary btn-card">
                                            1000 كرت بـ 20 شيكل
                                        </button>
                                        <button type="submit" name="card_quantity" value="2000"
                                                class="btn btn-primary btn-card">
                                            2000 كرت بـ 40 شيكل
                                        </button>
                                        <button type="submit" name="card_quantity" value="3000"
                                                class="btn btn-primary btn-card">
                                            3000 كرت بـ 55 شيكل
                                        </button>
                                        <button type="submit" name="card_quantity" value="4000"
                                                class="btn btn-primary btn-card">
                                            4000 كرت بـ 70 شيكل
                                        </button>
                                        <button type="submit" name="card_quantity" value="5000"
                                                class="btn btn-primary btn-card">
                                            5000 كرت بـ 85 شيكل
                                        </button>
                                    </form>

                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 col-sm-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>اتصل بنا</h4>
                            </div>
                            <div class="card-body">
                                <div class="empty-state" data-height="400">
                                    <div class="empty-state-icon bg-success">
                                        <i class="fas fa-phone"></i>
                                    </div>
                                    <h2>جهـات الاتصـال</h2>
                                    <p class="lead">
                                        بعد اختيار عدد الكروت المناسبة لك تواصل معنا عبر الواتس اب لتأكيد عملية الدفع
                                        لفتح لك رابط الموقع
                                        <a href="https://wa.me/970592328271" class="bb">+970592328271</a>
                                    </p>
                                    <!--                                    <a href="#" name="link_of_printer" class="btn btn-warning mt-4">رابـط الموقع</a>-->
                                    <?php if ($status === 'active') { ?>
                                        <a href="index.php" class="btn btn-success mt-4">رابـط الموقع</a>
                                    <?php } else { ?>
                                        <button class="btn btn-warning mt-4" disabled>في انتظار التفعيل</button>
                                    <?php } ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>

        <footer class="main-footer">
            <div class="footer-left">
                حقوق النشر &copy; عثمان 2025
                <div class="bullet"></div>
            </div>
            <div class="footer-right"></div>
        </footer>
    </div>
</div>

<!-- General JS Scripts -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/popper.js"></script>
<script src="assets/modules/tooltip.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script src="assets/modules/nicescroll/jquery.nicescroll.min.js"></script>
<script src="assets/modules/moment.min.js"></script>
<script src="assets/js/stisla.js"></script>

<!-- JS Libraries -->
<script src="assets/modules/simple-weather/jquery.simpleWeather.min.js"></script>
<script src="assets/modules/chart.min.js"></script>
<script src="assets/modules/jqvmap/dist/jquery.vmap.min.js"></script>
<script src="assets/modules/jqvmap/dist/maps/jquery.vmap.world.js"></script>
<script src="assets/modules/summernote/summernote-bs4.js"></script>
<script src="assets/modules/chocolat/dist/js/jquery.chocolat.min.js"></script>

<!-- Page Specific JS File -->
<script src="assets/js/page/index-0.js"></script>

<!-- Template JS File -->
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>
</body>
</html>
