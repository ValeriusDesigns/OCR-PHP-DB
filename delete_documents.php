<?php
require_once 'includes/config.php';

// Sicherstellen, dass die Anfrage über POST erfolgt
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Löschbefehl ausführen
    $query = "DELETE FROM documents";
    
    if ($conn->query($query) === TRUE) {
        echo json_encode(['delete_message' => 'Alle Dokumente wurden erfolgreich gelöscht.']);
    } else {
        echo json_encode(['delete_message' => 'Fehler beim Löschen der Dokumente.']);
    }
    
    $conn->close();
} else {
    echo json_encode(['delete_message' => 'Ungültige Anfrage.']);
}
?>