<?php
// Datenbankverbindungseinstellungen
$servername = "";
$username = "";
$password = "";
$dbname = "";

// Verbindung zur Datenbank herstellen
$conn = new mysqli($servername, $username, $password, $dbname);

// Überprüfen, ob die Verbindung erfolgreich war
if ($conn->connect_error) {
  die("Verbindung zur Datenbank fehlgeschlagen: " . $conn->connect_error);
}
