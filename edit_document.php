<?php
require_once 'includes/config.php';

$documentId = isset($_GET['documentId']) ? intval($_GET['documentId']) : 0;

if (!$documentId) {
    header("Location: main.php");
    exit();
}

// Stelle sicher, dass die Header gesetzt werden, bevor etwas ausgegeben wird
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['add_tag'])) {
        $newTagId = intval($_POST['add_tag']);
        $stmt = $conn->prepare("INSERT INTO document_tags (document_id, tag_id) VALUES (?, ?)");
        $stmt->bind_param("ii", $documentId, $newTagId);
        $stmt->execute();

        $stmt = $conn->prepare("SELECT tag_name FROM tags WHERE id = ?");
        $stmt->bind_param("i", $newTagId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tag = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'tagName' => $tag['tag_name']
        ]);
        exit();
    }


    if (isset($_POST['remove_tag'])) {
        $removeTagId = intval($_POST['remove_tag']);
        $stmt = $conn->prepare("DELETE FROM document_tags WHERE document_id = ? AND tag_id = ?");
        $stmt->bind_param("ii", $documentId, $removeTagId);
        $stmt->execute();

        // Hole den Tag-Namen, um ihn in den verfügbaren Tags hinzuzufügen
        $stmt = $conn->prepare("SELECT tag_name FROM tags WHERE id = ?");
        $stmt->bind_param("i", $removeTagId);
        $stmt->execute();
        $result = $stmt->get_result();
        $tag = $result->fetch_assoc();
        echo json_encode([
            'status' => 'success',
            'tagName' => $tag['tag_name']
        ]);
        exit();

    }

    if (isset($_POST['update_document'])) {
        $textContent = $_POST['text_content'] ?? "";
        $stmt = $conn->prepare("UPDATE documents SET text_content = ? WHERE id = ?");
        $stmt->bind_param("si", $textContent, $documentId);
        $stmt->execute();
        header("Location: edit_document.php?documentId=$documentId&success=1");
        exit();
    }
}

// Lade Dokumentdaten
$query = "SELECT * FROM documents WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $documentId);
$stmt->execute();
$result = $stmt->get_result();
$document = $result->fetch_assoc();

if (!$document) {
    header("Location: main.php");
    exit();
}

// Lade Tags
$queryTagsForDocument = "SELECT t.id, t.tag_name FROM tags t JOIN document_tags dt ON t.id = dt.tag_id WHERE dt.document_id = ?";
$stmt = $conn->prepare($queryTagsForDocument);
$stmt->bind_param("i", $documentId);
$stmt->execute();
$resultTagsForDocument = $stmt->get_result();
$currentTags = $resultTagsForDocument->fetch_all(MYSQLI_ASSOC);

$queryAvailableTags = "SELECT t.id, t.tag_name FROM tags t WHERE t.id NOT IN (SELECT tag_id FROM document_tags WHERE document_id = ?)";
$stmt = $conn->prepare($queryAvailableTags);
$stmt->bind_param("i", $documentId);
$stmt->execute();
$resultAvailableTags = $stmt->get_result();

$conn->close();
include 'header.php';
?>

<div class="edit-data">
    <h2>Dokument bearbeiten</h2>

    <div class="add-edit-container">

        <?php if (isset($_GET['success'])): ?>
            <p style="color: green;">Änderungen erfolgreich gespeichert!</p>
        <?php endif; ?>
        <form action="edit_document.php?documentId=<?php echo $documentId; ?>" method="POST">
            <div class="form-group">
                <label>Datei Name:</label>
                <input type="text" name="name" value="<?php echo htmlspecialchars($document['filename']); ?>" disabled>
            </div>
            <div class="form-group">
                <label>PDF Inhalt:</label>
                <textarea name="text_content"
                    rows="7"><?php echo htmlspecialchars($document['text_content']); ?></textarea>
            </div>

            <div class="form-group">
                <label>Aktuelle Tags:</label>
                <div class="tag-cloud" id="currentTagsList">
                    <?php foreach ($currentTags as $tag): ?>
                        <a onclick="removeTag(<?= $tag['id']; ?>)" id="currentTag-<?= $tag['id']; ?>" class="tag tag-item">
                            <?= htmlspecialchars($tag['tag_name']); ?>  <span class="remove-icon">X</span>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-group">
                <label>Verfügbare Tags:</label>
                <div class="tag-cloud" id="availableTagsList">
                    <?php while ($tag = $resultAvailableTags->fetch_assoc()): ?>
                        <a onclick="addTag(<?= $tag['id']; ?>)" id="tag-<?= $tag['id']; ?>" class="tag tag-item">
                            <?= htmlspecialchars($tag['tag_name']); ?>  <span class="add-icon">+</span>
                        </a>
                    <?php endwhile; ?>
                </div>
            </div>

            <div class="form-group">
                <button type="submit" name="update_document">Speichern</button>
            </div>
        </form>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  function addTag(tagId) {
    $.post("edit_document.php?documentId=<?php echo $documentId; ?>", { add_tag: tagId }, function (response) {
        try {
            var data = JSON.parse(response);
            if (data.status === 'success') {
                var tagName = data.tagName;
                $("#tag-" + tagId).remove();
                $("#currentTagsList").append('<a onclick="removeTag(' + tagId + ')" id="currentTag-' + tagId + '" class="tag tag-item">' + tagName + ' <span class="remove-icon">X</span></a>');

                // Aktualisiere die verfügbaren Tags
                $("#availableTagsList").html(data.availableTags);
            }
        } catch (e) {
            console.error("Fehler beim Parsen der JSON-Antwort:", e);
            alert("Es gab ein Problem bei der Kommunikation mit dem Server.");
        }
    }).fail(function (xhr, status, error) {
        console.error("Fehler:", status, error);
        alert("Netzwerkfehler oder Serverproblem. Status: " + status + " Fehler: " + error);
    });
}

function removeTag(tagId) {
    $.post("edit_document.php?documentId=<?php echo $documentId; ?>", { remove_tag: tagId }, function (response) {
        try {
            var data = JSON.parse(response);
            if (data.status === 'success') {
                var tagName = data.tagName;

                $("#currentTag-" + tagId).remove();
                $("#availableTagsList").append('<a onclick="addTag(' + tagId + ')" id="tag-' + tagId + '" class="tag tag-item">' +
                    tagName + ' <span class="add-icon">+</span></a>');

                // Aktualisiere die verfügbaren Tags
                $("#availableTagsList").html(data.availableTags);
            } else {
                console.error('Fehler:', data.message);
            }
        } catch (e) {
            console.error("Fehler beim Parsen der JSON-Antwort:", e);
            alert("Es gab ein Problem bei der Kommunikation mit dem Server.");
        }
    }).fail(function (xhr, status, error) {
        console.error("Fehler:", status, error);
        alert("Netzwerkfehler oder Serverproblem.");
    });
}
</script>

<?php include 'footer.php'; ?>