<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
include_once dirname(__FILE__) . "/../db.php";

// Configuration for pagination and filtering
$limit = 50;
$page = isset($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Filter params
$filter_product = isset($_GET['product_id']) ? (int) $_GET['product_id'] : null;
$filter_action = isset($_GET['action_type']) ? $_GET['action_type'] : null;
$filter_date_from = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$filter_date_to = isset($_GET['date_to']) ? $_GET['date_to'] : null;

// Base query
$where = "WHERE 1=1";
$params = [];
$types = "";

if ($filter_product) {
    $where .= " AND sl.product_id = ?";
    $params[] = $filter_product;
    $types .= "i";
}
if ($filter_action) {
    $where .= " AND sl.action_type = ?";
    $params[] = $filter_action;
    $types .= "s";
}
if ($filter_date_from) {
    $where .= " AND DATE(sl.created_at) >= ?";
    $params[] = $filter_date_from;
    $types .= "s";
}
if ($filter_date_to) {
    $where .= " AND DATE(sl.created_at) <= ?";
    $params[] = $filter_date_to;
    $types .= "s";
}

// Export CSV handler
if (isset($_GET['export']) && $_GET['export'] == 'csv') {
    $sql = "SELECT sl.*, p.name_de as product_name 
            FROM stock_log sl 
            LEFT JOIN products p ON sl.product_id = p.id 
            $where 
            ORDER BY sl.created_at DESC";

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=stock_log_' . date('Y-m-d_H-i-s') . '.csv');

    $output = fopen('php://output', 'w');
    // Write UTF-8 BOM for Excel
    fputs($output, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
    fputcsv($output, ['ID', 'Datum', 'Produkt', 'Wer', 'Aktion', 'Menge', 'Neuer Bestand', 'Details']);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['product_name'] ?: 'Produkt gelöscht (#' . $row['product_id'] . ')',
            $row['user_name'],
            $row['action_type'],
            $row['quantity_change'],
            $row['new_stock'],
            $row['details']
        ]);
    }
    fclose($output);
    exit;
}

// Fetch total for pagination
$sql_count = "SELECT COUNT(*) as total FROM stock_log sl $where";
$stmt_count = $conn->prepare($sql_count);
if (!empty($params)) {
    $stmt_count->bind_param($types, ...$params);
}
$stmt_count->execute();
$total_rows = $stmt_count->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

// Fetch logs
$sql = "SELECT sl.*, p.name_de as product_name, p.img as product_img 
        FROM stock_log sl 
        LEFT JOIN products p ON sl.product_id = p.id 
        $where 
        ORDER BY sl.created_at DESC 
        LIMIT ?, ?";
$params[] = $offset;
$params[] = $limit;
$types .= "ii";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$logs = $stmt->get_result();

// Get products list for filter
$products_res = $conn->query("SELECT id, name_de FROM products ORDER BY name_de ASC");
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit-Log & Nachverfolgbarkeit | Matin Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .filter-section {
            background: #fff;
            padding: 20px;
            border-radius: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
        }

        .log-table th {
            background: #f8f9fa;
            font-weight: 700;
        }

        .badge-qty {
            font-weight: 700;
            padding: 0.4rem 0.8rem;
            border-radius: 50px;
        }

        .qty-plus {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .qty-minus {
            background: #ffebee;
            color: #c62828;
        }

        .product-thumb-small {
            width: 40px;
            height: 40px;
            object-fit: cover;
            border-radius: 8px;
            background: #eee;
        }
    </style>
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
                <a href="stock_log.php" class="nav-link active">📜 Audit-Log (Neu)</a>
                <a href="mhd_reports.php" class="nav-link">📉 MHD Reports (Wöchentlich)</a>
                <a href="ai_dashboard.php" class="nav-link">✨ AI Generator</a>
                <a href="help.php" class="nav-link">📚 Hilfe & Assistenz</a>
                <a href="../setup.php" class="nav-link" onclick="return confirm('Datenbank wirklich zurücksetzen?')">⚙️
                    DB Setup</a>
                <a href="logout.php" class="nav-link text-danger mt-Auto">🚪 Abmelden</a>
                <a href="../index.html" class="nav-link mt-5">🌐 Zum Shop</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header-actions">
                <div>
                    <h2 class="fw-bold mb-0">Audit-Log & Nachverfolgbarkeit</h2>
                    <p class="text-muted small">Vollständiges, unveränderliches Protokoll aller Lagerbewegungen</p>
                </div>
                <div class="d-flex gap-2">
                    <button onclick="window.print()" class="btn btn-outline-secondary rounded-pill px-4 fw-bold">
                        🖨️ PDF / Drucken
                    </button>
                    <a href="?<?= http_build_query(array_merge($_GET, ['export' => 'csv'])) ?>"
                        class="btn btn-success rounded-pill px-4 fw-bold">
                        📥 CSV Export
                    </a>
                </div>
            </div>

            <!-- Filters -->
            <div class="filter-section">
                <form action="" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Produkt</label>
                        <select name="product_id" class="form-select border-0 bg-light">
                            <option value="">Alle Produkte</option>
                            <?php while ($p = $products_res->fetch_assoc()): ?>
                                <option value="<?= $p['id'] ?>" <?= $filter_product == $p['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['name_de']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Aktion</label>
                        <select name="action_type" class="form-select border-0 bg-light">
                            <option value="">Alle</option>
                            <option value="ORDER" <?= $filter_action == 'ORDER' ? 'selected' : '' ?>>Bestellung (ORDER)
                            </option>
                            <option value="MANUAL_UPDATE" <?= $filter_action == 'MANUAL_UPDATE' ? 'selected' : '' ?>>
                                Manuelle Korrektur</option>
                            <option value="RESTOCK" <?= $filter_action == 'RESTOCK' ? 'selected' : '' ?>>Nachfüllen
                            </option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Von</label>
                        <input type="date" name="date_from" class="form-control border-0 bg-light"
                            value="<?= htmlspecialchars($filter_date_from) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold">Bis</label>
                        <input type="date" name="date_to" class="form-control border-0 bg-light"
                            value="<?= htmlspecialchars($filter_date_to) ?>">
                    </div>
                    <div class="col-md-3 d-flex gap-2">
                        <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold">Filter
                            anwenden</button>
                        <a href="stock_log.php" class="btn btn-light w-50 rounded-pill fw-bold">Reset</a>
                    </div>
                </form>
            </div>

            <div class="content-card">
                <div class="table-responsive">
                    <table class="table table-hover align-middle log-table">
                        <thead>
                            <tr>
                                <th>Zeitstempel</th>
                                <th>Produkt</th>
                                <th>Wer</th>
                                <th>Aktion</th>
                                <th class="text-center">Änderung</th>
                                <th class="text-center">Neuer Bestand</th>
                                <th>Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($total_rows == 0): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">Keine Lagerbewegungen gefunden.</td>
                                </tr>
                            <?php endif; ?>
                            <?php while ($log = $logs->fetch_assoc()): ?>
                                <tr>
                                    <td class="small">
                                        <div class="fw-bold"><?= date("d.m.Y", strtotime($log['created_at'])) ?></div>
                                        <div class="text-muted"><?= date("H:i", strtotime($log['created_at'])) ?> Uhr</div>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-3">
                                            <?php if ($log['product_img']): ?>
                                                <img src="../<?= htmlspecialchars($log['product_img']) ?>"
                                                    class="product-thumb-small" alt="">
                                            <?php else: ?>
                                                <div
                                                    class="product-thumb-small d-flex align-items-center justify-content-center text-muted fs-6">
                                                    ?</div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="fw-bold text-nowrap">
                                                    <?= htmlspecialchars($log['product_name'] ?: 'Unbekanntes Produkt') ?>
                                                </div>
                                                <div class="text-muted extra-small">ID: #<?= $log['product_id'] ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-light text-dark fw-bold border"><?= htmlspecialchars($log['user_name']) ?></span>
                                    </td>
                                    <td>
                                        <span
                                            class="small fw-bold border-bottom"><?= htmlspecialchars($log['action_type']) ?></span>
                                    </td>
                                    <td class="text-center">
                                        <span
                                            class="badge-qty <?= $log['quantity_change'] >= 0 ? 'qty-plus' : 'qty-minus' ?>">
                                            <?= $log['quantity_change'] >= 0 ? '+' : '' ?>    <?= $log['quantity_change'] ?>
                                        </span>
                                    </td>
                                    <td class="text-center fw-bold fs-5">
                                        <?= $log['new_stock'] ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= htmlspecialchars($log['details']) ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <nav class="mt-4">
                        <ul class="pagination justify-content-center">
                            <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                                    <a class="page-link shadow-none"
                                        href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                                </li>
                            <?php endfor; ?>
                        </ul>
                    </nav>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>