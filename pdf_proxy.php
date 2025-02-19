<?php
require_once 'includes/config.php';

if (isset($_GET['file'])) {
    $filePath = realpath($_GET['file']); // Sicherstellen, dass der Pfad gültig ist

    if ($filePath === false || !file_exists($filePath)) {
        header("HTTP/1.1 404 Not Found");
        exit("PDF nicht gefunden.");
    }
    
    
    if (pathinfo($filePath, PATHINFO_EXTENSION) !== 'pdf') {
        header("HTTP/1.1 403 Forbidden");
        exit("Ungültiger Dateityp.");
    }


    header('Content-Type: application/pdf');
    header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}
?>