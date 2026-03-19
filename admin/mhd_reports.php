<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
include_once dirname(__FILE__) . "/../db.php";

$reportsDir = dirname(__FILE__) . "/reports/";
$reports = [];

if (is_dir($reportsDir)) {
    $files = scandir($reportsDir);
    foreach ($files as $file) {
        if (strpos($file, 'mhd_report_') === 0) {
            $reports[] = [
                'name' => $file,
                'path' => 'reports/' . $file,
                'date' => date('d.m.Y H:i', filemtime($reportsDir . $file))
            ];
        }
    }
}

// Sort by date descending
usort($reports, function($a, $b) {
    return filemtime(dirname(__FILE__) . '/' . $b['path']) - filemtime(dirname(__FILE__) . '/' . $a['path']);
});

// For manual generation
if (isset($_GET['generate'])) {
    include_once "generate_mhd_report.php";
    generateWeeklyMHDReport($conn);
    header("Location: mhd_reports.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MHD Reports | Matin Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>

    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-title" style="padding: 1rem;">
                <img src="../images/logo-1694787094.jpg" alt="Logo" style="height: 40px; border-radius: 6px;">
            </div>
            <nav>
                <a href="index.php" class="nav-link">📦 Lagerverwaltung</a>
                <a href="mhd_reports.php" class="nav-link active">📉 MHD Reports (Wöchentlich)</a>
                <a href="ai_dashboard.php" class="nav-link">✨ AI Generator</a>
                <a href="help.php" class="nav-link">📚 Hilfe & Assistenz</a>
                <a href="../setup.php" class="nav-link" onclick="return confirm('Datenbank wirklich zurücksetzen?')">⚙️ DB Setup</a>
                <a href="logout.php" class="nav-link text-danger mt-Auto">🚪 Abmelden</a>
                <a href="../index.html" class="nav-link mt-5">🌐 Zum Shop</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header-actions">
                <div>
                    <h2 class="fw-bold mb-0">Wöchentliche MHD-Reports</h2>
                    <p class="text-muted small">Automatische Berichte zur Haltbarkeit Ihres Bestands</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="mhd_reports.php?generate=1" class="btn btn-primary rounded-pill px-4 fw-bold" style="background: var(--primary); border: none;">
                        + Jetzt Bericht erstellen
                    </a>
                </div>
            </div>

            <div class="content-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Berichtsname</th>
                                <th>Erstellt am</th>
                                <th class="text-end">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($reports)): ?>
                            <tr>
                                <td colspan="3" class="text-center text-muted py-5">
                                    <div class="fs-1 mb-3">📄</div>
                                    Keine MHD-Reports gefunden.
                                </td>
                            </tr>
                            <?php else: ?>
                            <?php foreach ($reports as $r): ?>
                            <tr>
                                <td class="fw-bold">
                                    MHD-Report - <?= date('d.m.Y', filemtime(dirname(__FILE__) . '/' . $r['path'])) ?>
                                </td>
                                <td><?= $r['date'] ?></td>
                                <td class="text-end">
                                    <a href="<?= htmlspecialchars($r['path']) ?>" class="btn btn-sm btn-outline-primary rounded-pill px-3" target="_blank">📄 Ansehen</a>
                                    <a href="<?= htmlspecialchars($r['path']) ?>" download class="btn btn-sm btn-outline-secondary rounded-pill px-2">📥</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm rounded-4 mt-4 bg-light">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">💡 Info zur Automatisierung</h5>
                    <p class="mb-0">
                        Das System erstellt <strong>jeden Montag (oder beim ersten Login der Woche)</strong> automatisch einen neuen Bericht. 
                        In den Berichten werden alle Produkte aufgeführt, deren MHD in den nächsten 30 Tagen abläuft oder bereits abgelaufen ist.
                    </p>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
