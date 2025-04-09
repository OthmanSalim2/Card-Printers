<?php
session_start();

// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'cards_printer');

// Create database connection
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Authentication check
function isAuthenticated()
{
    return isset($_SESSION['user_id']);
}

// Enhanced admin check with database verification
function isAdmin($conn)
{
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    $user_id = $_SESSION['user_id'];
    $query = "SELECT role FROM users WHERE id = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $_SESSION['user_role'] = $user['role']; // Update session with current role
        return $user['role'] === 'admin';
    }

    return false;
}

// Redirect to login if not authenticated
if (!isAuthenticated()) {
    header("Location: login.php");
    exit();
}

// Check if user is admin, if not redirect to unauthorized page
if (!isAdmin($conn)) {
    header("Location: unauthorized.php");
    exit();
}

// Handle status update and PDF reset
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['user_id'])) {
    $user_id = $_POST['user_id'];

    // Initialize variables
    $new_status = null;
    $pdf_generated = null;

    // Determine which action to take based on the button clicked
    if (isset($_POST['activate_user'])) {
        $new_status = 'active';
        $pdf_generated = 0; // Reset PDF generated status when activating user
    } elseif (isset($_POST['deactivate_user'])) {
        $new_status = 'inactive';
        $pdf_generated = 1; // Set PDF generated to 1 when deactivating
    } elseif (isset($_POST['reset_pdf'])) {
        // Only reset PDF generated without changing status
        $pdf_generated = 0;
    }

    // Prepare the appropriate SQL query based on what needs to be updated
    if ($new_status !== null && $pdf_generated !== null) {
        // Update both status and pdf_generated
        $stmt = $conn->prepare("UPDATE users SET status = ?, pdf_generated = ? WHERE id = ?");
        $stmt->bind_param("sii", $new_status, $pdf_generated, $user_id);
    } elseif ($pdf_generated !== null) {
        // Only update pdf_generated
        $stmt = $conn->prepare("UPDATE users SET pdf_generated = ? WHERE id = ?");
        $stmt->bind_param("si", $pdf_generated, $user_id);
    } else {
        $_SESSION['error_message'] = "Invalid action requested";
        header("Location: " . $_SERVER['PHP_SELF']);
        exit();
    }

    if ($stmt->execute()) {
        $_SESSION['success_message'] = "User status updated successfully!";
    } else {
        $_SESSION['error_message'] = "Error updating user status: " . $conn->error;
    }

    $stmt->close();
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Get current user's details
$stmt = $conn->prepare("SELECT first_name, last_name FROM users WHERE id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();

$user = $result->fetch_assoc();
$first_name = $user['first_name'];
$last_name = $user['last_name'];
$full_name = $first_name . " " . $last_name;

// Pagination configuration
$records_per_page = 10;
$current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($current_page < 1) $current_page = 1;
$offset = ($current_page - 1) * $records_per_page;

// Function to get users with search filter and pagination
function getUsers($conn, $search = '', $offset = 0, $records_per_page = 10)
{
    $users = [];
    $query = "SELECT SQL_CALC_FOUND_ROWS id, first_name, last_name, email, role, status, number_cards, pdf_generated FROM users";

    // Add search condition if provided
    if (!empty($search)) {
        $search = "%$search%";
        $query .= " WHERE first_name LIKE ? OR last_name LIKE ? OR email LIKE ? OR role LIKE ?";
    }

    // Add pagination
    $query .= " ORDER BY first_name ASC LIMIT ?, ?";

    $stmt = $conn->prepare($query);

    if (!empty($search)) {
        $stmt->bind_param("sssiii", $search, $search, $search, $search, $offset, $records_per_page);
    } else {
        $stmt->bind_param("ii", $offset, $records_per_page);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }

    // Get total records for pagination
    $total_rows = $conn->query("SELECT FOUND_ROWS()")->fetch_row()[0];

    return [
        'users' => $users,
        'total_rows' => $total_rows
    ];
}

// Check if search form was submitted
$search_term = '';
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $search_term = trim($_GET['search']);
}

// Get users (filtered if search term exists)
$users_data = getUsers($conn, $search_term, $offset, $records_per_page);
$users = $users_data['users'];
$total_rows = $users_data['total_rows'];

// Calculate total pages
$total_pages = ceil($total_rows / $records_per_page);

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"/>
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, shrink-to-fit=no" name="viewport"/>
    <title>Admin Dashboard &mdash; User Management</title>

    <!-- General CSS Files -->
    <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css"/>
    <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css"/>

    <!-- Template CSS -->
    <link rel="stylesheet" href="assets/css/style.css"/>
    <link rel="stylesheet" href="assets/css/components.css"/>

    <style>
        .badge-pdf-used {
            background-color: #dc3545;
            color: white;
        }

        .badge-pdf-available {
            background-color: #28a745;
            color: white;
        }

        .action-buttons .btn {
            margin-right: 5px;
        }

        .dropdown-menu form {
            margin-bottom: 0;
        }
    </style>
</head>

<body>
<div id="app">
    <div class="main-wrapper main-wrapper-1">
        <!-- Admin Navigation -->
        <div class="navbar-bg"></div>
        <nav class="navbar navbar-expand-lg main-navbar">
            <div class="mr-auto">
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                            <i class="fas fa-user-shield"></i> Admin Panel
                        </a>
                        <div class="dropdown-menu">
                            <a href="control-panel.php" class="dropdown-item has-icon active">
                                <i class="fas fa-users"></i> User Management
                            </a>
                        </div>
                    </li>
                </ul>
            </div>
            <ul class="navbar-nav navbar-right">
                <li class="nav-item dropdown">
                    <a href="#" class="nav-link dropdown-toggle" data-toggle="dropdown">
                        <i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($full_name); ?>
                    </a>
                    <div class="dropdown-menu">
                        <a href="logout.php" class="dropdown-item has-icon text-danger">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </div>
                </li>
            </ul>
        </nav>

        <!-- Main Content -->
        <div class="main-content">
            <section class="section">
                <div class="section-header">
                    <h1>User Management</h1>
                    <div class="section-header-breadcrumb">
                        <div class="breadcrumb-item active"><a href="dashboard.php">Dashboard</a></div>
                        <div class="breadcrumb-item">User Management</div>
                    </div>
                </div>

                <div class="section-body">
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

                    <h2 class="section-title">System Users</h2>
                    <p class="section-lead">Manage all registered users in the system</p>

                    <div class="row">
                        <div class="col-12 col-md-6 col-lg-12">
                            <div class="card">
                                <div class="card-header">
                                    <h4>Users Table</h4>
                                    <div class="card-header-action">
                                        <form class="form-inline" method="GET" action="">
                                            <div class="input-group">
                                                <input type="text" class="form-control" name="search"
                                                       placeholder="Search users..."
                                                       value="<?php echo htmlspecialchars($search_term); ?>">
                                                <div class="input-group-append">
                                                    <button class="btn btn-primary" type="submit">
                                                        <i class="fas fa-search"></i>
                                                    </button>
                                                    <?php if (!empty($search_term)): ?>
                                                        <a href="control-panel.php" class="btn btn-danger">
                                                            <i class="fas fa-times"></i> Clear
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-md">
                                            <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Name</th>
                                                <th>Role</th>
                                                <th>Email</th>
                                                <th>Status</th>
                                                <th>Cards</th>
                                                <th>PDF Generated</th>
                                                <th>Actions</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <?php if (empty($users)): ?>
                                                <tr>
                                                    <td colspan="8" class="text-center">No users found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($users as $index => $user): ?>
                                                    <tr>
                                                        <td><?php echo $index + 1 + $offset; ?></td>
                                                        <td><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></td>
                                                        <td>
                                                            <?php echo htmlspecialchars($user['role']); ?>
                                                            <?php if ($user['role'] === 'admin'): ?>
                                                                <i class="fas fa-crown text-warning ml-1"></i>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                        <td>
                                                            <div class="badge badge-<?php echo $user['status'] === 'active' ? 'success' : 'danger'; ?>">
                                                                <?php echo ucfirst($user['status']); ?>
                                                            </div>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($user['number_cards']); ?></td>
                                                        <td>
                                                            <?php if ($user['pdf_generated'] == 1): ?>
                                                                <span class="badge badge-pdf-used">
                                                                    <i class="fas fa-check-circle"></i> Yes (Used)
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge badge-pdf-available">
                                                                    <i class="fas fa-times-circle"></i> No (Available)
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <div class="action-buttons">
                                                                <div class="btn-group">
                                                                    <button class="btn btn-secondary dropdown-toggle"
                                                                            type="button" data-toggle="dropdown"
                                                                            aria-haspopup="true" aria-expanded="false">
                                                                        <i class="fas fa-cog"></i> Actions
                                                                    </button>
                                                                    <div class="dropdown-menu">
                                                                        <form method="POST" action="" class="mb-0">
                                                                            <input type="hidden" name="user_id"
                                                                                   value="<?php echo $user['id']; ?>">
                                                                            <button type="submit" name="activate_user"
                                                                                    class="dropdown-item <?php echo $user['status'] === 'active' ? 'active' : ''; ?>">
                                                                                <i class="fas fa-check-circle text-success mr-2"></i>
                                                                                Activate User
                                                                            </button>
                                                                        </form>
                                                                        <form method="POST" action="" class="mb-0">
                                                                            <input type="hidden" name="user_id"
                                                                                   value="<?php echo $user['id']; ?>">
                                                                            <button type="submit" name="deactivate_user"
                                                                                    class="dropdown-item <?php echo $user['status'] === 'inactive' ? 'active' : ''; ?>">
                                                                                <i class="fas fa-times-circle text-danger mr-2"></i>
                                                                                Deactivate User
                                                                            </button>
                                                                        </form>
                                                                        <div class="dropdown-divider"></div>
                                                                        <form method="POST" action="" class="mb-0">
                                                                            <input type="hidden" name="user_id"
                                                                                   value="<?php echo $user['id']; ?>">
                                                                            <button type="submit" name="reset_pdf"
                                                                                    class="dropdown-item <?php echo $user['pdf_generated'] == 0 ? 'disabled' : ''; ?>">
                                                                                <i class="fas fa-redo text-info mr-2"></i>
                                                                                Reset PDF Generation
                                                                            </button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                                <div class="card-footer text-right">
                                    <nav class="d-inline-block">
                                        <ul class="pagination mb-0">
                                            <li class="page-item <?php echo $current_page <= 1 ? 'disabled' : ''; ?>">
                                                <a class="page-link"
                                                   href="?page=<?php echo $current_page - 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>"
                                                   tabindex="-1">
                                                    <i class="fas fa-chevron-left"></i>
                                                </a>
                                            </li>

                                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                                <li class="page-item <?php echo $i === $current_page ? 'active' : ''; ?>">
                                                    <a class="page-link"
                                                       href="?page=<?php echo $i; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>

                                            <li class="page-item <?php echo $current_page >= $total_pages ? 'disabled' : ''; ?>">
                                                <a class="page-link"
                                                   href="?page=<?php echo $current_page + 1; ?><?php echo !empty($search_term) ? '&search=' . urlencode($search_term) : ''; ?>">
                                                    <i class="fas fa-chevron-right"></i>
                                                </a>
                                            </li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <footer class="main-footer">
            <div class="footer-left">
                Copyright &copy; 2025
                <div class="bullet"></div>
                Design by Stisla
            </div>
            <div class="footer-right">
                v1.0.0
            </div>
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

<!-- Template JS File -->
<script src="assets/js/scripts.js"></script>
<script src="assets/js/custom.js"></script>

<script>
    $(document).ready(function () {
        // Confirm before resetting PDF generation
        $('form[action=""]').on('submit', function (e) {
            if ($(this).find('button[name="reset_pdf"]').length > 0) {
                if (!confirm('Are you sure you want to reset PDF generation for this user?')) {
                    e.preventDefault();
                    return false;
                }
            }
            return true;
        });
    });
</script>
</body>
</html>