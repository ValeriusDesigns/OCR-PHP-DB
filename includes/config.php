<?php
session_start();

// Sicherstellen, dass die Session-Cookies sicher sind
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);  // Nur für HTTPS-Verbindungen

if (file_exists('install.php')) {
    header("Location: install.php");
    exit;
}


require_once 'server-conf.php';

if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
    $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
    header("Location: $redirectUrl", true, 301);
    exit();
}

// Path
$domain = $_SERVER['HTTP_HOST'];
$folderPath = rtrim(dirname($_SERVER['PHP_SELF']), '/');
$projectPath = 'https://' . $domain . $folderPath;

// Datenbankverbindungseinstellungen


// Überprüfe, ob der Benutzer angemeldet ist
if (!isset($_SESSION['username'])) {
  header("Location: index.php");
  exit();
}

$query = "SELECT app_name, app_copyright FROM users LIMIT 1";
$stmt = $conn->prepare($query);
$stmt->execute();
$result = $stmt->get_result();
$settings = $result->fetch_assoc();

// Überprüfen, ob ein Ergebnis vorhanden ist
if ($settings) {
  $appName = $settings['app_name'];
  $appCopyright = $settings['app_copyright'];
} else {
  $appName = "synOCR DB"; // Standardwert, falls keine Einstellungen vorhanden sind
  $appCopyright = "by valerius.app"; // Standardwert, falls keine Einstellungen vorhanden sind
}

// Benutzerdaten abrufen
$query = "SELECT user_id FROM users LIMIT 1";
$result = $conn->query($query);
$user = $result->fetch_assoc();

if (!$user) {
    error_log("Benutzer nicht gefunden für die ID: " . $userId);
    echo "Es gab ein Problem beim Laden des Benutzers. Bitte versuchen Sie es später noch einmal.";
    exit();
}

$userId = $user['user_id'];

// Link zur Bearbeitung des Benutzers erstellen
$editUserLink = "settings.php?userId=" . urlencode($userId);
