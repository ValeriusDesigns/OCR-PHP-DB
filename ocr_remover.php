<?php
require_once 'includes/server-conf.php';

ignore_user_abort(true);
set_time_limit(0); // Verhindert Timeout
ob_start(); // Output-Buffering für JSON-Sicherheit
header("Content-Type: application/json");

// Hole den OCR-Pfad aus der Tabelle 'users'
$query = "SELECT ocr_path, api_token FROM users LIMIT 1"; 
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    $user = $result->fetch_assoc();
    $import_dir = $user['ocr_path']; // Der OCR-Pfad aus der DB
    $valid_token = $user['api_token']; // Das Token aus der DB

    if (!isset($_GET['token']) || !hash_equals($valid_token, $_GET['token'])) {
        echo json_encode(["status" => "error", "message" => "Ungueltiges Token. Zugriff verweigert."]);
        exit();
    }

    // Überprüfen, ob der OCR-Pfad existiert und ein Verzeichnis ist
    $resolved_path = realpath($import_dir);
    if (!$resolved_path || !is_dir($resolved_path)) {
        echo json_encode(["status" => "error", "message" => "Der OCR-Pfad existiert nicht oder ist kein Verzeichnis: " . htmlspecialchars($import_dir)]);
        exit();
    }

    error_log("OCR-Pfad aufgeloest: " . $resolved_path);
} else {
    echo json_encode(["status" => "error", "message" => "Kein OCR-Pfad fuer den Benutzer gefunden."]);
    exit();
}

// Alle gespeicherten Dateien aus der Datenbank abrufen
$existingFiles = [];
$result = $conn->query("SELECT id, file_path FROM documents");
while ($row = $result->fetch_assoc()) {
    $existingFiles[$row['id']] = $row['file_path'];
}

// Alle existierenden PDFs im Ordner finden
$foundFiles = [];
function scanDirectory($dir, &$files) {
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            scanDirectory($path, $files); // Rekursion für Unterordner
        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'pdf') {
            $files[] = realpath($path);
        }
    }
}
scanDirectory($import_dir, $foundFiles);

// **Dateien aus DB löschen, die nicht mehr existieren**
foreach ($existingFiles as $id => $filePath) {
    if (!in_array($filePath, $foundFiles)) {
        echo "Loesche fehlende Datei aus DB: $filePath\n";

        // Zuerst die Tags für das Dokument entfernen
        $stmt = $conn->prepare("DELETE FROM document_tags WHERE document_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        // Dann das Dokument selbst entfernen
        $stmt = $conn->prepare("DELETE FROM documents WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
echo json_encode(["status" => "success", "message" => "Synchronisation abgeschlossen!"]);
?>