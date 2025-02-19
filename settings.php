<?php
require_once 'includes/config.php';

session_start();

// Überprüfen, ob die Benutzer-ID gültig ist
if (!isset($_GET['userId']) || !is_numeric($_GET['userId'])) {
    header("Location: main.php");
    exit();
}

$userId = (int) $_GET['userId'];
$successMessage = $_GET['successMessage'] ?? null;
$errorMessage = null;

// Benutzer aus der Datenbank abrufen
$query = "SELECT * FROM users WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: main.php");
    exit();
}

// Benutzer aktualisieren
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_user'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $appName = trim($_POST['appName']);
    $appCopyright = trim($_POST['appCopyright']);
    $token = !empty($_POST['token']) ? trim($_POST['token']) : $user['api_token'];
    $ocrPath = trim($_POST['ocrPath']);
    $status = isset($_POST['status']) ? 1 : 0;

    if (!empty($password) && $password !== $confirmPassword) {
        $errorMessage = "Die Passwörter stimmen nicht überein.";
    } else {
        if (!empty($password)) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET user_name = ?, user_password = ?, app_name = ?, app_copyright = ?, ocr_path = ?, user_status = ?, api_token = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sssssssi", $username, $hashedPassword, $appName, $appCopyright, $ocrPath, $status, $token, $userId);
        } else {
            $query = "UPDATE users SET user_name = ?, app_name = ?, app_copyright = ?, ocr_path = ?, user_status = ?, api_token = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssssi", $username, $appName, $appCopyright, $ocrPath, $status, $token, $userId);
        }

        if ($stmt->execute()) {
            $_SESSION['username'] = $username;
            header("Location: settings.php?userId=$userId&successMessage=Erfolgreich aktualisiert");
            exit();
        } else {
            $errorMessage = "Fehler beim Aktualisieren des Benutzers.";
        }
        $stmt->close();
    }
}

$conn->close();

include 'header.php';
?>

<div class="edit-data">
    <h2>Einstellungen bearbeiten</h2>
    <div class="add-edit-container" style="margin-bottom:20px;">
        <?php if ($errorMessage): ?>
            <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        <?php if ($successMessage): ?>
            <div class="result-message"><?= htmlspecialchars($successMessage) ?></div>
        <?php endif; ?>
        <form action="settings.php?userId=<?= $userId ?>" method="POST">
            <div class="form-group">
                <label>Benutzername:</label>
                <input type="text" name="username" value="<?= htmlspecialchars($user['user_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>Passwort:</label>
                <input type="password" name="password">
            </div>
            <div class="form-group">
                <label>Passwort wiederholen:</label>
                <input type="password" name="confirm_password">
            </div>
            <div class="form-group">
                <label>App Name:</label>
                <input type="text" name="appName" value="<?= htmlspecialchars($user['app_name']) ?>" required>
            </div>
            <div class="form-group">
                <label>App Copyright:</label>
                <input type="text" name="appCopyright" value="<?= htmlspecialchars($user['app_copyright']) ?>" required>
            </div>
            <div class="form-group">
                <label>OCR Api Token:</label>
                <input type="text" id="token" name="token" value="<?= htmlspecialchars($user['api_token']) ?>" required>
                <small>wget --no-check-certificate -q -O - "<?= $projectPath ?>/ocr_reader.php?token=<?= urlencode($user['api_token']) ?>"</small><br>
                <small>wget --no-check-certificate -q -O - "<?= $projectPath ?>/ocr_remover.php?token=<?= urlencode($user['api_token']) ?>"</small>
            </div>
            <div class="form-group">
                <label>Pfad zu fertigen OCR PDFs ordner:</label>
                <input type="text" name="ocrPath" value="<?= htmlspecialchars($user['ocr_path']) ?>" required>
            </div>
            <div class="form-group">
                <button type="submit" name="update_user">Daten aktualisieren</button>
            </div>
        </form>
    </div>

    <h2>DB Synchronisieren</h2>
    <div class="add-edit-container">
        <button id="syncButton">Neue Dokumente einlesen</button>
        <button id="removeButton">Verwaiste Dokumente entfernen</button>

        <div id="loading" style="display: none;">Synchronisation läuft...</div>
        <div id="message"></div>
    </div>

    <h2 style="padding-top:20px">Alle Dokumente löschen</h2>
    <div class="delete-container">
    <div id="delete-loading" style="display: none;">Löschung läuft...</div>
    <div id="delete_message"></div>
        <button id="deleteAllButton">Jetzt löschen</button><br>
        Die PDF-Dateien und OCR-Daten bleiben auf dem Server erhalten. Es werden nur die Datenbankeinträge gelöscht.
        Du kannst bei Bedarf jederzeit Dokumente erneut einlesen.
       
    </div>

    <script>
       
       function runOCRProcess(script, messageContainer) {
    let syncButton = document.getElementById("syncButton");
    let removeButton = document.getElementById("removeButton");
    let loading = document.getElementById("loading");
    let message = document.getElementById(messageContainer);

    syncButton.disabled = true;
    removeButton.disabled = true;
    loading.style.display = "block";
    message.innerHTML = "";

    fetch(script, { method: "POST" }) // POST nutzen für Sicherheit
        .then(response => {
            if (!response.ok) throw new Error("Fehler beim Abruf der Daten!");
            return response.json();
        })
        .then(data => {
            message.innerHTML = `${data.message}`;
        })
        .catch(error => {
            message.innerHTML = `Fehler: ${error.message}`;
        })
        .finally(() => {
            setTimeout(function () {
                message.innerHTML = ""; // Versteckt die Nachricht nach 5 Sekunden
            }, 5000);
            syncButton.disabled = false;
            removeButton.disabled = false;
            loading.style.display = "none";
        });
}

// Event Listener für beide Buttons
document.getElementById("syncButton").addEventListener("click", function () {
    runOCRProcess("ocr_reader.php?token=<?php echo $user['api_token']; ?>", "message");
});

document.getElementById("removeButton").addEventListener("click", function () {
    runOCRProcess("ocr_remover.php?token=<?php echo $user['api_token']; ?>", "message");
});

document.getElementById("deleteAllButton").addEventListener("click", function () {
    // Zeige ein Bestätigungs-Popup
    let confirmDelete = confirm("Bist du sicher, dass du alle Dokumente löschen möchtest?");
    if (confirmDelete) {
        // Wenn der Benutzer "OK" klickt, führe den Löschvorgang aus
        runDeleteProcess("delete_documents.php", "delete_message");
    }
});

function runDeleteProcess(script, messageContainer) {
    let deleteAllButton = document.getElementById("deleteAllButton");
    let deleteLoading = document.getElementById("delete-loading");
    let message = document.getElementById(messageContainer);

    // Button und Ladeanzeige anpassen
    deleteAllButton.disabled = true;
    deleteLoading.style.display = "block";
    message.innerHTML = "";

    // Sende die Anfrage zum Löschen
    fetch(script, { method: "POST" })
        .then(response => {
            if (!response.ok) throw new Error("Fehler beim Abruf der Daten!");
            return response.json();
        })
        .then(data => {
            // Erfolgs- oder Fehlermeldung anzeigen
            message.innerHTML = `${data.delete_message || "Loeschung abgeschlossen."}`;
        })
        .catch(error => {
            message.innerHTML = `Fehler: ${error.message}`;
        })
        .finally(() => {
            // Ladeanzeige nach 5 Sekunden ausblenden
            setTimeout(function () {
                message.innerHTML = "";
            }, 5000);
            // Button wieder aktivieren und Ladeanzeige ausblenden
            deleteAllButton.disabled = false;
            deleteLoading.style.display = "none";
        });
}
    </script>
</div>

<?php include 'footer.php'; ?>