<?php
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $token = bin2hex(random_bytes(16)); // Token generieren
    $servername = $_POST['db_host'];
    $dbname = $_POST['db_name'];
    $username = $_POST['db_user'];
    $password = $_POST['db_pass'];
    $user_name = $_POST['user_name'];
    $user_password = $_POST['user_password'];
    $confirm_password = $_POST['confirm_password']; // Passwortbestätigung
    $app_name = "OCR PHP DB";
    $app_copyright = "by valerius.app";
    $ocr_path = "/volume1/rechnungen/ocr_outbox";
    $user_status = 1;

    // Prüfen, ob die Passwörter übereinstimmen
    if ($user_password !== $confirm_password) {
        $errorMessage = "Die eingegebenen Passwörter stimmen nicht überein. Bitte versuche es erneut.";
    } else {
        try {
            // Versuch, eine Verbindung zur Datenbank herzustellen
            $conn = new mysqli($servername, $username, $password, $dbname);

            // Überprüfen auf Verbindungsfehler
            if ($conn->connect_error) {
                throw new Exception("Fehler bei der Verbindung zur Datenbank: " . $conn->connect_error);
            }

            // Wenn keine Fehler, fahre fort mit der Installation
            $hashedPassword = password_hash($user_password, PASSWORD_DEFAULT);

            // SQL-Installationsdatei ausführen
            $sql = file_get_contents('install.sql');
            if (!$conn->multi_query($sql)) {
                throw new Exception("Fehler beim Ausführen der SQL-Datei: " . $conn->error);
            }

            // Warte darauf, dass alle Anfragen verarbeitet werden
            while ($conn->more_results()) {
                $conn->next_result();
            }

            // Installationsdatei löschen
            if (file_exists('install.sql')) {
                unlink('install.sql');
                unlink(__FILE__);
            }

            // Überprüfen, ob die users-Tabelle existiert
            $checkTable = $conn->query("SHOW TABLES LIKE 'users'");
            if ($checkTable->num_rows == 0) {
                throw new Exception("Fehler: Die Tabelle 'users' wurde nicht erstellt.");
            }

            // Benutzer in die users-Tabelle einfügen
            $stmt = $conn->prepare("INSERT INTO `users` (`user_name`, `user_password`, `app_name`, `app_copyright`, `ocr_path`, `user_status`, `api_token`) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if (!$stmt) {
                throw new Exception("Fehler beim Vorbereiten des Statements: " . $conn->error);
            }

            $stmt->bind_param('sssssis', $user_name, $hashedPassword, $app_name, $app_copyright, $ocr_path, $user_status, $token);
            if (!$stmt->execute()) {
                throw new Exception("Fehler beim Einfügen des Benutzers: " . $stmt->error);
            }

            // Konfigurationsdatei speichern
            $configContent = "<?php\n";
            $configContent .= "// Datenbankverbindungseinstellungen\n";
            $configContent .= "\$servername = \"$servername\";\n";
            $configContent .= "\$username = \"$username\";\n";
            $configContent .= "\$password = \"$password\";\n";
            $configContent .= "\$dbname = \"$dbname\";\n\n";
            $configContent .= "// Verbindung zur Datenbank herstellen\n";
            $configContent .= "\$conn = new mysqli(\$servername, \$username, \$password, \$dbname);\n\n";
            $configContent .= "// Überprüfen, ob die Verbindung erfolgreich war\n";
            $configContent .= "if (\$conn->connect_error) {\n";
            $configContent .= "  die(\"Verbindung zur Datenbank fehlgeschlagen: \" . \$conn->connect_error);\n";
            $configContent .= "}\n";

            $configFile = fopen("includes/server-conf.php", "w");
            if (fwrite($configFile, $configContent)) {
                $successMessage = "Die Konfigurationsdatei wurde erfolgreich gespeichert. Du kannst starten! <a style='color:red' href='index.php'>Klicke hier um zur Startseite zu gelangen.</a>";
                fclose($configFile);

                // Formularbereich schließen
                $_POST = array(); // Formular leeren, damit es nicht erneut abgesendet wird
                unlink(__FILE__); // Lösche das Installationsskript
            } else {
                $errorMessage = "Fehler beim Speichern der Konfigurationsdatei.";
            }

        } catch (Exception $e) {
            $errorMessage = $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html>

<head>
    <title>Install synOCR PHP DB</title>
    <link rel="stylesheet" type="text/css" href="assets/css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta name="screen-orientation" content="portrait">
</head>

<body>
    <div class="login-content">
        <div class="edit-data">
            <h1 style="padding-bottom:10px">Installation der synOCR PHP DB</h1>

            <?php if (isset($successMessage)): ?>
                <!-- Erfolgsmeldung wird angezeigt, wenn alles erfolgreich war -->
                <div class="result-message" style="color: green;">
                    <?php echo $successMessage; ?>
                </div>
            <?php else: ?>
                <!-- Fehler wird im Formular angezeigt -->
                <?php if (isset($errorMessage)): ?>
                    <div class="error-message" style="color: red;">
                        <?php echo $errorMessage; ?>
                    </div>
                <?php endif; ?>

                <h2>Schritt 1: Datenbankverbindung konfigurieren</h2>
                <form action="install.php" method="POST">
                    <div class="add-edit-container" style="margin-bottom:20px;">
                        <div class="form-group">
                            <label for='db_host'>Datenbank Host:</label>
                            <input type='text' name='db_host' value='localhost' required>
                        </div>
                        <div class="form-group">
                            <label for='db_name'>Datenbankname:</label>
                            <input type='text' name='db_name' required>
                        </div>
                        <div class="form-group">
                            <label for='db_user'>Datenbank benutzername:</label>
                            <input type='text' name='db_user' required>
                        </div>
                        <div class="form-group">
                            <label for='db_pass'>Datenbank passwort:</label>
                            <input type='password' name='db_pass' required>
                        </div>
                    </div>
                    <h2>Schritt 2: Benutzerdaten festlegen</h2>
                    <div class="add-edit-container">
                        <div class="form-group">
                            <label for='user_name'>Administrator Benutzername:</label>
                            <input type='text' name='user_name' required>
                        </div>
                        <div class="form-group">
                            <label for='user_password'>Administrator Passwort:</label>
                            <input type='password' name='user_password' required>
                        </div>
                        <div class="form-group">
                            <label for='confirm_password'>Passwort wiederholen:</label>
                            <input type="password" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="install">
                        <button type='submit'>Weiter</button>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>