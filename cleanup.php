<?php
// cleanup.php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $filepath = isset($_POST['filepath']) ? $_POST['filepath'] : '';

    // Security check - only allow deleting files in outputs/uploads
    if (strpos($filepath, 'outputs/') === 0 || strpos($filepath, 'uploads/') === 0) {
        if (file_exists($filepath)) {
            // Delete after a delay (5 minutes = 300 seconds)
            sleep(300);
            unlink($filepath);
        }
    }
}