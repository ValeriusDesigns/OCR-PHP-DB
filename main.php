<?php
require_once 'includes/config.php';

// Anzahl der Dokumente abrufen (sichere Prepared Statement)
$count_stmt = $conn->prepare("SELECT COUNT(*) as documentsCount FROM documents");
$count_stmt->execute();
$count_result = $count_stmt->get_result();
$documentsCount = $count_result->fetch_assoc()['documentsCount'];
$count_stmt->close();

// Letzte 20 hochgeladene Dateien abrufen
$last_stmt = $conn->prepare("SELECT id, filename, file_path, created_at FROM documents ORDER BY created_at DESC LIMIT 20");
$last_stmt->execute();
$last_result = $last_stmt->get_result();

// Alle Tags aus der Datenbank abrufen (sichere Prepared Statement)
$tag_stmt = $conn->prepare("
    SELECT DISTINCT t.id, t.tag_name
    FROM tags t
    INNER JOIN document_tags dt ON t.id = dt.tag_id
    INNER JOIN documents d ON d.id = dt.document_id
");

try {
    $tag_stmt->execute();
    $tag_result = $tag_stmt->get_result();
} catch (Exception $e) {
    // Fehler anzeigen
    echo "Fehler bei der Ausführung der SQL-Abfrage: " . $e->getMessage();
}

$index = 0; // Zähler für eindeutige Canvas-IDs
$pdfFiles = []; // Speichert die PDF-URLs und Canvas-IDs
$pdfEditorUrl = $projectPath . "/pdfjs/web/viewer.html?file="; // Basis-URL für den Editor

include 'header.php';
?>

<div class="grid-latest-container">
    <!-- **LINKS: TAGS (30%)** -->

    <div class="tags">
        <div class="documents-count">
            <h2>Anzahl der Dokumente</h2>
            Aktuell (<?= htmlspecialchars($documentsCount); ?>) <a href='documents.php'>Alle anzeigen</a>
        </div>
        <h2>Tags</h2>
        <div class="tag-cloud">
        <?php 
        if ($tag_result->num_rows == 0) {
            // Keine Tags gefunden
            ?>
            <div class="documents-count">Es gibt noch keine Dokumente,<br>somit auch keine Tags.</div>
            <?php
        } else {
            // Tags anzeigen
            while ($tag = $tag_result->fetch_assoc()) { ?>
                <a href='tag_view.php?tag_id=<?= htmlspecialchars($tag['id']); ?>' class="tag">
                    <?= htmlspecialchars($tag['tag_name']); ?>
                </a>
            <?php }
        }
        ?>
    </div>
    </div>

    <!-- **RECHTS: LETZTE DOKUMENTE (70%)** -->
    <div class="documents">
    <h2>Zuletzt hochgeladen</h2>
    <div class="latest-documents">
        <?php 
        // Überprüfen, ob keine Dokumente gefunden wurden
        if ($last_result->num_rows == 0) {
            ?>
            <div class="documents-count">Es gibt noch keine Dokumente!!! Dokumente wurden noch nicht eingelesen. Bitte passe zuerst deine <a href="edit_tags.php">Tags</a> für deine Dokumente an. Danach in denn <a href="<?php echo htmlspecialchars($editUserLink); ?>">Einstellungen</a> zuerst Pfad zu OCR PDFs ordner eingeben und Neue Dokumente einlesen.</div>
            <?php
        } else {
            // Wenn Dokumente gefunden wurden, diese anzeigen
            while ($row = $last_result->fetch_assoc()) {
                $fileId = htmlspecialchars($row['id']);
                $filePath = htmlspecialchars($row['file_path']);
                $fileName = htmlspecialchars($row['filename']);
                $pdfUrl = $projectPath . "/pdf_proxy.php?file=" . urlencode($filePath);
                $viewerUrl = $pdfEditorUrl . urlencode($pdfUrl);

                $canvasId = "pdfCanvas_" . $index;
                $pdfFiles[] = ['url' => $pdfUrl, 'canvasId' => $canvasId, 'viewerUrl' => $viewerUrl]; // Speichert für das JS
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
</div>
</div>

<!-- PDF.js einbinden -->
<script type="module">
    import { getDocument, GlobalWorkerOptions } from './pdfjs/build/pdf.mjs';

    // Setzt den Worker-Pfad für PDF.js
    GlobalWorkerOptions.workerSrc = './pdfjs/build/pdf.worker.mjs';

    function renderPDF(pdfUrl, canvasId) {
        getDocument(pdfUrl).promise.then(function (pdf) {
            pdf.getPage(1).then(function (page) {
                var scale = 1.0;  // Skaliert die PDF-Seite für Vorschau
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

    // Liste der PDFs aus PHP holen
    const pdfFiles = <?php echo json_encode($pdfFiles); ?>;

    // PDF-Vorschau für alle Dokumente rendern
    document.addEventListener('DOMContentLoaded', function () {
        pdfFiles.forEach(file => renderPDF(file.url, file.canvasId));

        // Klick-Event für Canvas-Elemente hinzufügen
        document.querySelectorAll('.pdf-canvas').forEach(canvas => {
            canvas.addEventListener('click', function () {
                let viewerUrl = this.getAttribute('data-viewer-url');
                window.open(viewerUrl, '_blank'); // PDF im Editor öffnen
            });
        });
    });
</script>

<?php
$last_stmt->close();
$tag_stmt->close();
$conn->close();
include 'footer.php';
?>