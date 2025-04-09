<?php
require('fpdfile.php'); // Make sure FPDF is properly included

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_FILES['csv']) && $_FILES['csv']['error'] == 0) {
        $csvFile = $_FILES['csv']['tmp_name'];

        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            // Get user settings with defaults
            $fontSize = isset($_POST['size']) ? intval($_POST['size']) : 12;
            $dimensionSetting = isset($_POST['dim']) ? $_POST['dim'] : '10';

            // Initialize PDF with proper settings
            $pdf = new FPDF('P', 'mm', 'A4');
            $pdf->SetAutoPageBreak(false);
            $pdf->SetFont('Arial', '', $fontSize);

            // Define card dimensions based on user selection
            list($cardWidth, $cardHeight, $cardsPerRow, $rowsPerPage) = getCardDimensions($dimensionSetting);
            $cardsPerPage = $cardsPerRow * $rowsPerPage;

            // Calculate margins to center the cards
            $pageWidth = 210; // A4 width in mm
            $pageHeight = 297; // A4 height in mm
            $totalCardsWidth = $cardsPerRow * $cardWidth;
            $leftMargin = ($pageWidth - $totalCardsWidth) / 2;

            $currentCard = 0;
            $currentRow = 0;
            $currentCol = 0;

            // Read CSV and create cards
            while (($row = fgetcsv($handle, 1000, ",")) !== FALSE) {
                // Add new page if needed
                if ($currentCard % $cardsPerPage == 0 && $currentCard != 0) {
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

                // Add content to card (centered)
                $lineHeight = 5;
                $content = implode("\n", $row);
                $pdf->SetXY($x + 2, $y + 2);
                $pdf->MultiCell($cardWidth - 4, $lineHeight, $content);

                // Update counters
                $currentCol++;
                if ($currentCol >= $cardsPerRow) {
                    $currentCol = 0;
                    $currentRow++;
                }
                $currentCard++;
            }

            fclose($handle);

            // Output PDF
            $pdf->Output('D', 'radius_cards_'.date('YmdHis').'.pdf');
        } else {
            die("Error opening the CSV file.");
        }
    } else {
        die("Error: Please upload a valid CSV file.");
    }
} else {
    die("Invalid request method.");
}

function getCardDimensions($setting) {
    switch ($setting) {
        case '10':  // 2x5 cards (10 per page)
            return array(90, 50, 2, 5);
        case '21':  // 3x7 cards (21 per page)
            return array(60, 35, 3, 7);
        case '24':  // 3x8 cards (24 per page)
            return array(60, 30, 3, 8);
        case '40':  // 4x10 cards (40 per page)
            return array(45, 25, 4, 10);
        case '50':  // 5x10 cards (50 per page)
            return array(36, 25, 5, 10);
        case '60':  // 5x12 cards (60 per page)
            return array(36, 20, 5, 12);
        case '100': // 10x10 cards (100 per page)
            return array(18, 25, 10, 10);
        default:    // Default to 2x5
            return array(90, 50, 2, 5);
    }
}