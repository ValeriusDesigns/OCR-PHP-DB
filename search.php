<?php
require_once 'includes/config.php';

$search_query = filter_input(INPUT_GET, 'query', FILTER_SANITIZE_STRING) ?? '';
include 'header.php';

$pdfFiles = [];
$searchResults = [];
$documentsPerPage = 20;

// Aktuelle Seite aus der URL holen (standardmäßig Seite 1, wenn nicht gesetzt)
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

if ($search_query) {
    // Gesamtzahl der Dokumente abrufen
    $totalStmt = $conn->prepare("SELECT COUNT(*) AS total FROM documents WHERE MATCH(text_content) AGAINST (? IN NATURAL LANGUAGE MODE)");
    $totalStmt->bind_param("s", $search_query);
    $totalStmt->execute();
    $totalResult = $totalStmt->get_result();
    $totalRow = $totalResult->fetch_assoc();
    $totalDocuments = $totalRow['total'];
    $totalStmt->close();
    
    // Berechne die Gesamtzahl der Seiten
    $totalPages = ceil($totalDocuments / $documentsPerPage);
    
    // Berechne den Startwert für die LIMIT-Klausel
    $offset = ($currentPage - 1) * $documentsPerPage;
    
    $stmt = $conn->prepare("SELECT id, filename, file_path FROM documents WHERE MATCH(text_content) AGAINST (? IN NATURAL LANGUAGE MODE) LIMIT ?, ?");
    $stmt->bind_param("sii", $search_query, $offset, $documentsPerPage);
    $stmt->execute();
    $result = $stmt->get_result();

    $pdfEditorUrl = $projectPath . "/pdfjs/web/viewer.html?file=";
    $index = 0;

    while ($row = $result->fetch_assoc()) {
        $fileId = htmlspecialchars($row['id']);
        $filePath = htmlspecialchars($row['file_path']);
        $fileName = htmlspecialchars($row['filename']);
        $pdfUrl = $projectPath . "/pdf_proxy.php?file=" . urlencode($filePath);
        $viewerUrl = $pdfEditorUrl . urlencode($pdfUrl);
        $canvasId = "pdfCanvas_" . $index;

        $pdfFiles[] = ['url' => $pdfUrl, 'canvasId' => $canvasId, 'viewerUrl' => $viewerUrl];
        $searchResults[] = ['id' => $fileId, 'name' => $fileName, 'canvasId' => $canvasId, 'viewerUrl' => $viewerUrl];

        $index++;
    }
    $stmt->close();
}
?>

<div class="content-data">
    <h2>Suchergebnisse für: <?= htmlspecialchars($search_query) ?></h2>
    <div class="search-results">
        <?php if (!empty($searchResults)): ?>
            <?php foreach ($searchResults as $file): ?>
                <div class="result-item">
                    <strong>Datei Name:</strong>
                    <div class="file-name"><?= $file['name'] ?></div>
                    <div class="pdf-preview">
                        <canvas id="<?= $file['canvasId'] ?>" class="pdf-canvas" data-viewer-url="<?= $file['viewerUrl'] ?>"></canvas>
                    </div>
                    <div style="text-align:center">
                        <a href="<?= $file['viewerUrl'] ?>" target="_blank" class="button-document">Ansehen</a>
                        oder
                        <a href="edit_document.php?documentId=<?= $file['id'] ?>" class="button-document">Bearbeiten</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="documents-count">Keine Ergebnisse gefunden.</div>
        <?php endif; ?>
    </div>
</div>

<?php if (!empty($searchResults)): ?>
<!-- Pagination -->
<div class="pagination">
    <?php if ($currentPage > 1): ?>
        <a href="?query=<?= urlencode($search_query) ?>&page=1">Erste</a>
        <a href="?query=<?= urlencode($search_query) ?>&page=<?= ($currentPage - 1) ?>">Zurück</a>
    <?php endif; ?>
    <span>Seite <?= $currentPage ?> von <?= $totalPages ?></span>
    <?php if ($currentPage < $totalPages): ?>
        <a href="?query=<?= urlencode($search_query) ?>&page=<?= ($currentPage + 1) ?>">Weiter</a>
        <a href="?query=<?= urlencode($search_query) ?>&page=<?= $totalPages ?>">Letzte</a>
    <?php endif; ?>
</div>
<?php endif; ?>

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

                var renderContext = { canvasContext: context, viewport: viewport };
                page.render(renderContext);
            });
        }).catch(function (error) {
            console.error('Fehler beim Laden der PDF:', error);
        });
    }

    const pdfFiles = <?= json_encode($pdfFiles); ?>;

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

<?php include 'footer.php'; ?>