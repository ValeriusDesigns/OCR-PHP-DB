<?php
require_once 'includes/config.php';

// Anzahl der Dokumente pro Seite
$documentsPerPage = 20;

// Aktuelle Seite aus der URL holen (standardmäßig Seite 1, wenn nicht gesetzt)
$currentPage = isset($_GET['page']) ? filter_input(INPUT_GET, 'page', FILTER_VALIDATE_INT) : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}

// Abrufen der Gesamtzahl der Dokumente
$totalStmt = $conn->prepare("SELECT COUNT(id) AS total_documents FROM documents");
$totalStmt->execute();
$totalResult = $totalStmt->get_result();
$totalRow = $totalResult->fetch_assoc();
$totalDocuments = $totalRow['total_documents'];
$totalStmt->close();

// Berechne die Gesamtzahl der Seiten
$totalPages = ceil($totalDocuments / $documentsPerPage);

// Berechne den Startwert für die LIMIT-Klausel
$offset = ($currentPage - 1) * $documentsPerPage;

// Dokumente für die aktuelle Seite abrufen
$stmt = $conn->prepare("SELECT id, filename, file_path, created_at FROM documents ORDER BY created_at DESC LIMIT ?, ?");
$stmt->bind_param("ii", $offset, $documentsPerPage);
$stmt->execute();
$result = $stmt->get_result();

$index = 0; // Zähler für eindeutige Canvas-IDs
$pdfFiles = []; // Speichert die PDF-URLs und Canvas-IDs
$pdfEditorUrl = $projectPath . "/pdfjs/web/viewer.html?file="; // Basis-URL für den Editor

include 'header.php';
?>

<div class="content-data">
    <div class="documents">
        <h2>Alle Dokumente</h2>
        <div class="all-documents">
            <?php 
            if ($result->num_rows == 0) {
                // Wenn keine Dokumente gefunden wurden
                ?>
                <div class="documents-count">Es wurden noch keine Dokumente eingelesen!</div>
                <?php
            } else {
                // Dokumente anzeigen
                while ($row = $result->fetch_assoc()) {
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
                        <div class="file-name"><?= $fileName; ?></div>
                        <div class="pdf-preview">
                            <canvas id="<?= $canvasId; ?>" class="pdf-canvas" data-viewer-url="<?= $viewerUrl; ?>"></canvas>
                        </div>
                        <div style='text-align:center'>
                            <a href='<?= $viewerUrl; ?>' target='_blank' class='button-document'>Ansehen</a> oder
                            <a href='edit_document.php?documentId=<?= $fileId; ?>' class='button-document'>Bearbeiten</a>
                        </div>
                    </div>
                    <?php
                    $index++;
                }
            }
            ?>
        </div>


        <?php if (!$result->num_rows == 0): ?>
        <!-- Paginierung -->
        <div class="pagination">
            <?php if ($currentPage > 1): ?>
                <a href="?page=1">Erste</a>
                <a href="?page=<?= $currentPage - 1; ?>">Zurück</a>
            <?php endif; ?>

            <span>Seite <?= $currentPage; ?> von <?= $totalPages; ?></span>

            <?php if ($currentPage < $totalPages): ?>
                <a href="?page=<?= $currentPage + 1; ?>">Weiter</a>
                <a href="?page=<?= $totalPages; ?>">Letzte</a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

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