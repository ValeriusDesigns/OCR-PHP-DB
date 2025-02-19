<?php
require_once 'includes/config.php';
include 'header.php';


// Prüfen, ob ein Tag ausgewählt wurde
if (!isset($_GET['tag_id']) || !is_numeric($_GET['tag_id'])) {
    echo "<p>Ungültige Tag-ID.</p>";
    exit;
}

$tagId = intval($_GET['tag_id']);

// Tag-Name abrufen
$stmt = $conn->prepare("SELECT tag_name FROM tags WHERE id = ?");
$stmt->bind_param("i", $tagId);
$stmt->execute();
$stmt->bind_result($tagName);
$stmt->fetch();
$stmt->close();

if (!$tagName) {
    echo "<p>Tag nicht gefunden.</p>";
    exit;
}

// Anzahl der Dokumente pro Seite
$documentsPerPage = 20;

// Aktuelle Seite aus der URL holen (standardmäßig Seite 1, wenn nicht gesetzt)
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

// Abrufen der Gesamtzahl der Dokumente für das Tag
$totalStmt = $conn->prepare("SELECT COUNT(d.id) AS total_documents 
                             FROM documents d
                             JOIN document_tags dt ON d.id = dt.document_id
                             WHERE dt.tag_id = ?");
$totalStmt->bind_param("i", $tagId);
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalDocuments = $totalRow['total_documents'];

// Berechne die Gesamtzahl der Seiten
$totalPages = ceil($totalDocuments / $documentsPerPage);

// Berechne den Startwert für die LIMIT-Klausel
$offset = ($currentPage - 1) * $documentsPerPage;

// Dokumente abrufen, die mit diesem Tag verknüpft sind (mit LIMIT und OFFSET)
$query = "
    SELECT d.id, d.filename, d.file_path, d.created_at 
    FROM documents d
    JOIN document_tags dt ON d.id = dt.document_id
    WHERE dt.tag_id = ?
    ORDER BY d.created_at DESC
    LIMIT ?, ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $tagId, $offset, $documentsPerPage);
$stmt->execute();
$result = $stmt->get_result();

$pdfFiles = [];
$pdfEditorUrl = $projectPath . "/pdfjs/web/viewer.html?file=";
$index = 0;

?>

<h2>Dokumente mit Tag: <?php echo htmlspecialchars($tagName); ?></h2>

<?php if ($result->num_rows > 0): ?>
    <div class='all-documents'>
        <?php while ($row = $result->fetch_assoc()):
            $fileId = htmlspecialchars($row['id']);
            $filePath = htmlspecialchars($row['file_path']);
            $fileName = htmlspecialchars($row['filename']);
            $pdfUrl = $projectPath . "/pdf_proxy.php?file=" . urlencode($filePath);
            $viewerUrl = $pdfEditorUrl . urlencode($pdfUrl);
            $canvasId = "pdfCanvas_" . $index;
            $pdfFiles[] = ['url' => $pdfUrl, 'canvasId' => $canvasId, 'viewerUrl' => $viewerUrl];
        ?>
            <div class='result-item'>
                <strong>Datei Name:</strong> 
                <div class='file-name'><?php echo $fileName; ?></div>
                <div class='pdf-preview'>
                    <canvas id='<?php echo $canvasId; ?>' class='pdf-canvas' data-viewer-url='<?php echo $viewerUrl; ?>'></canvas>
                </div>
                <div style='text-align:center'>
                    <a href='<?php echo $viewerUrl; ?>' target='_blank' class='button-document'>Ansehen</a>
                    oder
                    <a href='edit_document.php?documentId=<?php echo $fileId; ?>' class='button-document'>Bearbeiten</a>
                </div>
            </div>
        <?php $index++; endwhile; ?>
    </div>
<?php else: ?>
    <p>Keine Dokumente mit diesem Tag gefunden.</p>
<?php endif; ?>

<div class='pagination'>
    <?php if ($currentPage > 1): ?>
        <a href='?tag_id=<?= urlencode($tagId) ?>&page=1'>Erste</a>
        <a href='?tag_id=<?= urlencode($tagId) ?>&page=<?php echo ($currentPage - 1); ?>'>Zurück</a>
    <?php endif; ?>
    <span>Seite <?php echo $currentPage; ?> von <?php echo $totalPages; ?></span>
    <?php if ($currentPage < $totalPages): ?>
        <a href='?tag_id=<?= urlencode($tagId) ?>&page=<?php echo ($currentPage + 1); ?>'>Weiter</a>
        <a href='?tag_id=<?= urlencode($tagId) ?>&page=<?php echo $totalPages; ?>'>Letzte</a>
    <?php endif; ?>
</div>



<!-- PDF.js einbinden -->
<script type="module">
    import { getDocument, GlobalWorkerOptions } from './pdfjs/build/pdf.mjs';

    GlobalWorkerOptions.workerSrc = './pdfjs/build/pdf.worker.mjs';

    function renderPDF(pdfUrl, canvasId) {
        getDocument(pdfUrl).promise.then(function (pdf) {
            pdf.getPage(1).then(function (page) {
                var scale = 1.0;
                var viewport = page.getViewport({ scale: scale });

                var canvas = document.getElementById(canvasId);
                if (!canvas) {
                    console.error('Canvas nicht gefunden:', canvasId);
                    return;
                }

                var context = canvas.getContext('2d');
                canvas.width = viewport.width;
                canvas.height = viewport.height;

                var renderContext = {
                    canvasContext: context,
                    viewport: viewport
                };
                page.render(renderContext);
            });
        }).catch(function (error) {
            console.error('Fehler beim Laden der PDF:', error);
        });
    }

    const pdfFiles = <?php echo json_encode($pdfFiles); ?>;

    document.addEventListener('DOMContentLoaded', function () {
        pdfFiles.forEach(file => renderPDF(file.url, file.canvasId));
        document.querySelectorAll('.pdf-canvas').forEach(canvas => {
            canvas.addEventListener('click', function () {
                let viewerUrl = this.getAttribute('data-viewer-url');
                window.open(viewerUrl, '_blank');
            });
        });
    });
</script>

<?php
$stmt->close();
$conn->close();
include 'footer.php';
?>