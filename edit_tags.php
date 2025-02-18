<?php
require_once 'includes/config.php';

// Neuen Tag hinzufügen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_tag'])) {
    $newTag = trim(filter_input(INPUT_POST, 'new_tag', FILTER_SANITIZE_STRING));
    if (!empty($newTag)) {
        $stmt = $conn->prepare("INSERT IGNORE INTO tags (tag_name) VALUES (?)");
        $stmt->bind_param("s", $newTag);
        $stmt->execute();
        $stmt->close();
    }
}

// Tag löschen
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete_tag'])) {
    $deleteTag = trim(filter_input(INPUT_POST, 'delete_tag', FILTER_SANITIZE_STRING));
    if (!empty($deleteTag)) {
        $stmt = $conn->prepare("DELETE FROM tags WHERE tag_name = ?");
        $stmt->bind_param("s", $deleteTag);
        $stmt->execute();
        $stmt->close();
    }
}

// Alle Tags abrufen (mit prepared statement)
$stmt = $conn->prepare("SELECT tag_name FROM tags");
$stmt->execute();
$result = $stmt->get_result();

$tags = [];
while ($row = $result->fetch_assoc()) {
    $tags[] = htmlspecialchars($row['tag_name']);
}

$stmt->close();
$conn->close();

include 'header.php';
?>
<div class="edit-data">
    <h2>Tags hinzufügen</h2>
    <div class="fix-edit-container" style="margin-bottom: 20px;">
        <div class="edit-tag-container">
            <form method="POST">
                <input type="text" name="new_tag" placeholder="Neuen Tag hinzufügen" required>
                <button type="submit">+</button>
            </form>
        </div>
    </div>

    <h2>Gespeicherte Tags</h2>
    <div class="fix-edit-container">
        <ul>
            <?php foreach ($tags as $tag): ?>
                <li>
                    <?php echo $tag; ?>
                    <form method="POST" style="display:inline;" onsubmit="return confirmDelete('<?php echo $tag; ?>');">
                        <input type="hidden" name="delete_tag" value="<?php echo $tag; ?>">
                        <button type="submit">Löschen</button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>

<script>
    function confirmDelete(tagName) {
        return confirm("Möchtest du das Tag '" + tagName + "' wirklich löschen?");
    }
</script>

<?php
include 'footer.php';
?>