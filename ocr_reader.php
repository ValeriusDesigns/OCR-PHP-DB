<?php
require_once 'includes/server-conf.php';

ignore_user_abort(true);
set_time_limit(0); // Verhindert Timeout
ob_start(); // Output-Buffering für JSON-Sicherheit
header("Content-Type: application/json");

// Hole den OCR-Pfad aus der Tabelle 'users'
$query = "SELECT ocr_path, api_token FROM users LIMIT 1";  // Wenn nur ein Benutzer vorhanden ist, kannst du LIMIT 1 verwenden
$result = $conn->query($query);


// Überprüfe, ob die Abfrage erfolgreich war
if ($result && $result->num_rows > 0) {
    // Hole den OCR-Pfad aus der Datenbank
    $user = $result->fetch_assoc();
    $import_dir = $user['ocr_path']; // Der OCR-Pfad aus der DB
    $valid_token = $user['api_token']; // Das Token aus der DB

    // Überprüfe das Token aus der URL (GET-Parameter)
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

    // Gebe den tatsächlichen Pfad zur Überprüfung aus
    error_log("OCR-Pfad aufgeloest: " . $resolved_path);
} else {
    echo json_encode(["status" => "error", "message" => "Kein OCR-Pfad für den Benutzer gefunden."]);
    exit();
}

function addTag($conn, $tag)
{
    $tagId = null; // Initialisierung, um Fehler zu vermeiden

    $stmt = $conn->prepare("SELECT id FROM tags WHERE tag_name = ?");
    $stmt->bind_param("s", $tag);
    $stmt->execute();
    $stmt->store_result();
    $stmt->bind_result($tagId);

    if ($stmt->fetch()) {
        $stmt->close();
        return $tagId;
    }

    $stmt->close();

    $stmt = $conn->prepare("INSERT INTO tags (tag_name) VALUES (?)");
    $stmt->bind_param("s", $tag);
    $stmt->execute();
    $newTagId = $stmt->insert_id;
    $stmt->close();

    return $newTagId;
}

function linkDocumentTag($conn, $documentId, $tagId)
{
    $stmt = $conn->prepare("INSERT IGNORE INTO document_tags (document_id, tag_id) VALUES (?, ?)");
    $stmt->bind_param("ii", $documentId, $tagId);
    $stmt->execute();
    $stmt->close();
}

// Tags aus der Datenbank laden
$keywordTags = [];
$result = $conn->query("SELECT tag_name FROM tags");
while ($row = $result->fetch_assoc()) {
    $keywordTags[] = $row['tag_name'];
}

/**
 * Rekursive Funktion, um alle PDFs in einem Ordner und Unterordnern zu finden
 */
function scanDirectory($dir, &$files)
{
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..')
            continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            scanDirectory($path, $files);
        } elseif (is_file($path) && pathinfo($path, PATHINFO_EXTENSION) === 'pdf') {
            $files[] = realpath($path);
        }
    }
}

$pdfFiles = [];
scanDirectory($resolved_path, $pdfFiles);

// Alle gefundenen PDFs verarbeiten
foreach ($pdfFiles as $pdf_file) {
    $filename = basename($pdf_file);
    $file_path = realpath($pdf_file);

    // Prüfen, ob Datei schon in der Datenbank ist
    $stmt = $conn->prepare("SELECT id FROM documents WHERE filename = ?");
    $stmt->bind_param("s", $filename);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
        $stmt->close();
        continue;
    }
    $stmt->close();

    // PDF-Text auslesen
    $output = shell_exec("pdftotext " . escapeshellarg($pdf_file) . " -");
    if ($output === null)
        continue;
    $cleanText = strtolower(trim($output));

    if (!empty($cleanText)) {
        // In die Datenbank speichern
        $stmt = $conn->prepare("INSERT INTO documents (filename, file_path, text_content) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $filename, $file_path, $output);
            $stmt->execute();
            $documentId = $stmt->insert_id;
            $stmt->close();

            // **Tags basierend auf dem PDF-Text erkennen**
            $foundTags = [];
            foreach ($keywordTags as $tag) {
                if (stripos($cleanText, strtolower($tag)) !== false) {
                    $foundTags[] = $tag;
                }
            }

            // Tags nur hinzufügen, wenn sie gefunden wurden
            if (!empty($foundTags)) {
                foreach ($foundTags as $tag) {
                    $tagId = addTag($conn, $tag);
                    linkDocumentTag($conn, $documentId, $tagId);
                }
            }
        } else {
            error_log("SQL-Fehler: Error");
        }
    }
}

$conn->close();

echo json_encode(["status" => "success", "message" => "Synchronisation abgeschlossen!"]);
?>