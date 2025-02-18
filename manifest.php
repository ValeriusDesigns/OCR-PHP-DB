<?php
header("Content-Type: application/json");

// Aktuelle URL mit Pfad ermitteln
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
$folderPath = dirname($_SERVER['SCRIPT_NAME']); // Ordnerpfad

$startUrl = "$protocol://$host$folderPath/index.php";

// JSON ausgeben
$iconBasePath = "$protocol://$host$folderPath/assets/";

echo json_encode([
    "description" => "admin console",
    "display" => "standalone",
    "name" => "OCR PHP DB",
    "orientation" => "portrait",
    "short_name" => "OCR PHP DB",
    "theme_color" => "#262626",
    "id" => "/",
    "scope" => "/",
    "background_color" => "#262626",
    "icons" => [
        [
            "src" => $iconBasePath . "48x48.png",
            "sizes" => "48x48",
            "type" => "image/png"
        ],
        [
            "src" => $iconBasePath . "180x180.png",
            "sizes" => "180x180",
            "type" => "image/png"
        ],
        [
            "src" => $iconBasePath . "192x192.png",
            "sizes" => "192x192",
            "type" => "image/png"
        ]
    ],
    "start_url" => $startUrl
]);