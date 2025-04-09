<?php
require('fpdfile.php');
session_start();

// Database configuration
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'cards_printer';
$db_table = 'users';

// Create directories if they don't exist
if (!file_exists('uploads')) mkdir('uploads', 0755, true);
if (!file_exists('outputs')) mkdir('outputs', 0755, true);

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    try {
        // Check if user is logged in
        if (!isset($_SESSION['user_id'])) {
            throw new Exception('Session expired. Please login again.');
        }

        // Connect to database to check pdf_generated status
        $conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
        if ($conn->connect_error) {
            throw new Exception('Database connection failed');
        }

        // Check if PDF was already generated for this user
        $stmt = $conn->prepare("SELECT pdf_generated FROM $db_table WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            if ($user['pdf_generated'] == 1) {
                throw new Exception('You have already generated your PDF. Each user can only generate one PDF.');
            }
        } else {
            throw new Exception('User not found in database');
        }
        $stmt->close();

        // Verify credentials (replace with your actual authentication)
        $username = isset($_POST['username']) ? $_POST['username'] : '';
        $password = isset($_POST['password']) ? $_POST['password'] : '';

        // Get text positions (convert from percentage to decimal)
        $ux = isset($_POST['ux']) ? floatval($_POST['ux']) : 5; // Username X position (10% by default)
        $uy = isset($_POST['uy']) ? floatval($_POST['uy']) : 5; // Username Y position (10% by default)
        $px = isset($_POST['px']) ? floatval($_POST['px']) : 5; // Password X position (10% by default)
        $py = isset($_POST['py']) ? floatval($_POST['py']) : 15; // Password Y position (25% by default)

        if ($username !== 'username' || $password !== 'password') {
            throw new Exception('Invalid credentials');
        }

        // Process file upload
        if (!isset($_FILES['csv'])) {
            throw new Exception('No CSV file uploaded');
        }

        $csvFile = $_FILES['csv'];
        if ($csvFile['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('File upload error: ' . $csvFile['error']);
        }

        // Validate file type
        $fileExt = strtolower(pathinfo($csvFile['name'], PATHINFO_EXTENSION));
        if ($fileExt !== 'csv') {
            throw new Exception('Only CSV files are allowed');
        }

        // Save uploaded file
        $uploadPath = 'uploads/' . uniqid() . '_' . basename($csvFile['name']);
        if (!move_uploaded_file($csvFile['tmp_name'], $uploadPath)) {
            throw new Exception('Failed to save uploaded file');
        }

        // Get form settings
        $fontSize = isset($_POST['size']) ? intval($_POST['size']) : '12';
        $dimensionSetting = isset($_POST['dim']) ? $_POST['dim'] : '10';

        // Generate PDF
        $pdf = new FPDF('P', 'mm', 'A4');
        $pdf->SetAutoPageBreak(false);
        $pdf->SetFont('Arial', '', $fontSize);
        $pdf->AddPage(); // Add first page

        // Get card dimensions based on user selection
        list($cardWidth, $cardHeight, $cardsPerRow, $rowsPerPage) = getCardDimensions($dimensionSetting);
        $cardsPerPage = $cardsPerRow * $rowsPerPage;

        // Calculate margins to center the cards
        $pageWidth = 210; // A4 width in mm
        $totalCardsWidth = $cardsPerRow * $cardWidth;
        $leftMargin = ($pageWidth - $totalCardsWidth) / 2;

        $currentCard = 0;
        $currentRow = 0;
        $currentCol = 0;

        // Read CSV and create cards
        if (($handle = fopen($uploadPath, "r")) !== false) {
            while (($data = fgetcsv($handle, 1000, ";")) !== false) {
                // Skip empty rows
                if (empty(array_filter($data))) {
                    continue;
                }

                // Add new page if needed
                if ($currentCard > 0 && $currentCard % $cardsPerPage === 0) {
                    $pdf->AddPage();
                    $currentRow = 0;
                    $currentCol = 0;
                }

                // Calculate position for current card
                $x = $leftMargin + ($currentCol * $cardWidth);
                $y = 10 + ($currentRow * $cardHeight);

                // Draw card border
                $pdf->SetXY($x, $y);
                $pdf->Cell($cardWidth, $cardHeight, '', 1);

                // Add the background image
                $cardImage = '';
                $file = $_FILES['profile_photo'];
                $fileName = basename($file['name']);
                $uploadPath = 'images/' . $fileName;
                if (!file_exists($uploadPath)) {
                    $is_moved = move_uploaded_file($file['tmp_name'], $uploadPath);
                    $cardImage = "./$uploadPath";
                } else {
                    $cardImage = "./$uploadPath";
                }
                $pdf->Image($cardImage, $x, $y, $cardWidth, $cardHeight);

                // Add content to card (centered)
                $pdf->Rect($x, $y, $cardWidth, $cardHeight);

                // Calculate scaling factors (from 520px preview to actual card size in mm)
                $scaleX = $cardWidth / 520;
                $scaleY = $cardHeight / 270;

                // Add username to card (with scaled position)
                if (isset($data[1])) {
                    $usernameText = trim($data[1], '"');
                    $usernameX = $x + ($ux * $scaleX);
                    $usernameY = $y + ($uy * $scaleY);
                    $pdf->SetXY($usernameX, $usernameY);
                    $pdf->Cell(0, 5, $usernameText);
                }

                // Add password to card (with scaled position)
                if (isset($data[2])) {
                    $passwordText = trim($data[2], '"');
                    $passwordX = $x + ($px * $scaleX);
                    $passwordY = $y + ($py * $scaleY);
                    $pdf->SetXY($passwordX, $passwordY);
                    $pdf->Cell(0, 5, $passwordText);
                }

                // Update counters
                $currentCol++;
                if ($currentCol >= $cardsPerRow) {
                    $currentCol = 0;
                    $currentRow++;
                }
                $currentCard++;
            }
            fclose($handle);
        } else {
            throw new Exception('Failed to read CSV file');
        }

        // Save PDF
        $outputFilename = 'outputs/radius_cards_' . date('Ymd_His') . '.pdf';
        $pdf->Output('F', $outputFilename);
        chmod($outputFilename, 0644);

        // Update database to mark PDF as generated
        $update_stmt = $conn->prepare("UPDATE $db_table SET pdf_generated = 1 WHERE id = ?");
        $update_stmt->bind_param("i", $_SESSION['user_id']);
        if (!$update_stmt->execute()) {
            error_log("Failed to update pdf_generated flag: " . $update_stmt->error);
        }
        $update_stmt->close();
        $conn->close();

        // Return success response
        echo json_encode([
            'success' => true,
            'filepath' => $outputFilename,
            'filename' => 'Radius_Cards_' . date('Ymd_His') . '.pdf',
            'download_url' => $outputFilename,
        ]);

    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed'
    ]);
}

function getCardDimensions($setting)
{
    $dimensions = [
        '10' => [90, 50, 2, 5],    // 2x5 cards
        '21' => [60, 35, 3, 7],    // 3x7 cards
        '24' => [60, 30, 3, 8],    // 3x8 cards
        '40' => [45, 25, 4, 10],   // 4x10 cards
        '50' => [36, 25, 5, 10],   // 5x10 cards
        '60' => [36, 20, 5, 12],   // 5x12 cards
        '70' => [36, 20, 5, 14],   // 5x14 cards
    ];
    return isset($dimensions[$setting]) ? $dimensions[$setting] : [90, 50, 2, 5]; // Default to 2x5
}