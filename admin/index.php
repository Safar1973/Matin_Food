<?php
include_once dirname(__FILE__) . "/../db.php";

// Fetch all products
$query = "SELECT * FROM products ORDER BY id DESC";
$res = mysqli_query($conn, $query);

if (!$res) {
    die("Error fetching products: " . mysqli_error($conn));
}

// KPI Calculations
$total_products = 0;
$low_stock = 0;
$out_of_stock = 0;
$expired = 0;
$expiring_soon = 0;
$today = date('Y-m-d');
$soon = date('Y-m-d', strtotime('+30 days'));

$products = [];
while ($p = mysqli_fetch_assoc($res)) {
    $total_products++;
    if ($p['stock'] == 0) $out_of_stock++;
    elseif ($p['stock'] < 10) $low_stock++;

    if ($p['expiry'] < $today) $expired++;
    elseif ($p['expiry'] < $soon) $expiring_soon++;
    
    $products[] = $p;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lagerverwaltung | Matin Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
</head>
<body>

    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-title">
                <span>🌿</span> MATIN FOOD
            </div>
            <nav>
                <a href="index.php" class="nav-link active">📦 Lagerverwaltung</a>
                <a href="../setup.php" class="nav-link" onclick="return confirm('Datenbank wirklich zurücksetzen?')">⚙️ DB Setup</a>
                <a href="../index.html" class="nav-link mt-5">🌐 Zum Shop</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header-actions">
                <div>
                    <h2 class="fw-bold mb-0">Bestandsübersicht</h2>
                    <p class="text-muted">Lagerbestand und MHD-Überwachung</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 fw-bold" style="background: var(--primary); border: none;">
                    + Neues Produkt
                </button>
            </div>

            <!-- KPI Row -->
            <div class="kpi-row">
                <div class="kpi-card">
                    <div class="kpi-label">Gesamt Produkte</div>
                    <div class="kpi-value"><?= $total_products ?></div>
                </div>
                <div class="kpi-card kpi-warning">
                    <div class="kpi-label">Niedriger Bestand</div>
                    <div class="kpi-value text-warning"><?= $low_stock ?></div>
                </div>
                <div class="kpi-card kpi-danger">
                    <div class="kpi-label">Ausverkauft</div>
                    <div class="kpi-value text-danger"><?= $out_of_stock ?></div>
                </div>
                <div class="kpi-card kpi-danger">
                    <div class="kpi-label">Abgelaufen (MHD)</div>
                    <div class="kpi-value text-danger"><?= $expired ?></div>
                </div>
            </div>

            <div class="content-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bild</th>
                                <th>Name (DE/AR)</th>
                                <th>Preis</th>
                                <th>Lager</th>
                                <th>Herstellung</th>
                                <th>Ablauf (MHD)</th>
                                <th class="text-end">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $p) { 
                                // Stock Status logic
                                $stock_class = "status-success";
                                $stock_text = $p['stock'];
                                if ($p['stock'] == 0) {
                                    $stock_class = "status-danger";
                                    $stock_text = "Out of Stock";
                                } elseif ($p['stock'] < 10) {
                                    $stock_class = "status-warning";
                                    $stock_text = $p['stock'] . " (Low)";
                                }

                                // MHD Status logic
                                $mhd_class = "text-muted";
                                if ($p['expiry'] < $today) {
                                    $mhd_class = "text-danger fw-bold";
                                } elseif ($p['expiry'] < $soon) {
                                    $mhd_class = "text-warning fw-bold";
                                }
                            ?>
                            <tr>
                                <td>
                                    <img src="../<?= htmlspecialchars($p['img']) ?>" alt="thumb" class="product-thumb">
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($p['name_de']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($p['name_ar']) ?></div>
                                    <span class="badge-cat mt-1"><?= htmlspecialchars($p['category']) ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold"><?= number_format($p['price'], 2) ?> €</span>
                                </td>
                                <td>
                                    <span class="status-pill <?= $stock_class ?>"><?= $stock_text ?></span>
                                </td>
                                <td class="small">
                                    <?= $p['production_date'] ? date("d.m.Y", strtotime($p['production_date'])) : '-' ?>
                                </td>
                                <td class="small <?= $mhd_class ?>">
                                    <?= date("d.m.Y", strtotime($p['expiry'])) ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary btn-action">✏️</button>
                                    <button class="btn btn-sm btn-outline-danger btn-action">🗑️</button>
                                </td>
                            </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
