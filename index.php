<?php
session_start();

if (file_exists('install.php')) {
  header("Location: install.php");
  exit;
}

require_once 'includes/server-conf.php';

if ($_SERVER['HTTPS'] != 'on') {
  $redirectUrl = 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
  header("Location: $redirectUrl", true, 301);
  exit();
}

if (isset($_SESSION['username'])) {
  // Benutzer ist angemeldet, Weiterleitung zum Main
  header("Location: main.php");
  exit();
}

$query = "SELECT app_name FROM users LIMIT 1";  // Wenn nur ein Benutzer vorhanden ist, kannst du LIMIT 1 verwenden
$result = $conn->query($query);
$settings = $result->fetch_assoc();

if ($settings) {
  $appName = $settings['app_name'];
} else {
  $appName = "synOCR PHP DB"; // Standardwert, falls keine Einstellungen vorhanden sind
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $username = $_POST['username'];
  $password = $_POST['password'];

  // Query, um den Benutzer in der Datenbank zu suchen
  $query = "SELECT * FROM users WHERE user_name = '$username'";
  $result = $conn->query($query);

  if ($result->num_rows == 1) {
    $user = $result->fetch_assoc();
    $hashedPassword = $user['user_password'];

    // Überprüfung des eingegebenen Passworts mit dem gehashten Passwort in der Datenbank
    if (password_verify($password, $hashedPassword)) {
      // Benutzer gefunden, Sitzungsvariablen setzen und Weiterleitung zum Admin-Panel
      $_SESSION['username'] = $username;
      header("Location: main.php");
      exit();
    }
  }

  // Fehlermeldung anzeigen
  $errorMessage = "Ungültige Benutzerdaten";
}

$conn->close();
?>

<!DOCTYPE html>
<html>

<head>
  <title>Login <?php echo $appName; ?></title>
  <link rel="stylesheet" type="text/css" href="assets/css/style.css">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="screen-orientation" content="portrait">
  <meta name="x5-orientation" content="portrait">
  <meta name="apple-mobile-web-app-title" content="OCR PHP DB">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <link rel="apple-touch-icon" sizes="180x180" href="assets/180x180.png">
   <link rel="icon" href="assets/192x192.png" type="image/png" />
   <link rel="icon" type="image/x-icon" href="assets/favicon.ico">
  <link rel="manifest" href="manifest.php">
</head>

<body>

  <div class="login-content">
    <div class="edit-data">
      <h2>Anmelden auf <span style="color:red;"><?php echo $appName; ?></span></h2>
      <form action="index.php" method="POST">
        <div class="add-edit-container">
          <?php if (isset($errorMessage)): ?>
            <div class="error-message" style="color: red;">
              <?php echo $errorMessage; ?>
            </div>
          <?php endif; ?>
          <div class="form-group">
            <label for="username">Benutzername </label>
            <input type="text" id="username" name="username" required>
          </div>
          <div class="form-group">
            <label for='password'>Passwort:</label>
            <input type="password" id="password" name="password" required>
          </div>
        </div>
        <div class="install">
          <button type="submit">Anmelden</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    if ("serviceWorker" in navigator) {
      window.addEventListener("load", function () {
        navigator.serviceWorker.register("assets/service-worker.js").then(
          function (success) {
            console.log("ServiceWorker wurde erfolgreich registriert.", success);
          }
        ).catch(
          function (error) {
            console.log("ServiceWorker konnte leider nicht registriert werden.", error);
          }
        );
      });
    }
  </script>
</body>

</html>