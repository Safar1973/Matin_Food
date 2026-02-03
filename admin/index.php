<?php
include_once dirname(__FILE__) . "/../db.php";

// Fetch all products
$query = "SELECT * FROM products ORDER BY id DESC";
$res = mysqli_query($conn, $query);

if (!$res) {
    die("Error fetching products: " . mysqli_error($conn));
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Matin Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .lang-badge { font-size: 0.65rem; padding: 2px 5px; margin-right: 3px; }
    </style>
</head>
<body>

    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-title">
                <span>🌿</span> MATIN ADMIN
            </div>
            <nav>
                <a href="index.php" class="nav-link active">📦 Produkte</a>
                <a href="#" class="nav-link">🛒 Bestellungen</a>
                <a href="#" class="nav-link">👤 Kunden</a>
                <a href="../index.html" class="nav-link mt-5">🌐 Shop Filter</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header-actions">
                <div>
                    <h2 class="fw-bold mb-0">Produktübersicht</h2>
                    <p class="text-muted">Verwalten Sie alle Produkte in der Datenbank</p>
                </div>
                <button class="btn btn-primary rounded-pill px-4 fw-bold" style="background: #c21f24; border: none;">
                    + Neues Produkt
                </button>
            </div>

            <div class="content-card">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bild</th>
                                <th>Name (DE/AR)</th>
                                <th>Kategorie</th>
                                <th>Preis</th>
                                <th>Lager</th>
                                <th>Haltbarkeit</th>
                                <th class="text-end">Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($p = mysqli_fetch_assoc($res)) { ?>
                            <tr>
                                <td>
                                    <img src="../<?= htmlspecialchars($p['img']) ?>" alt="thumb" class="product-thumb">
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($p['name_de']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($p['name_ar']) ?></div>
                                </td>
                                <td>
                                    <span class="badge-cat"><?= htmlspecialchars($p['category']) ?></span>
                                </td>
                                <td>
                                    <span class="fw-bold text-danger"><?= number_format($p['price'], 2) ?> €</span>
                                </td>
                                <td>
                                    <?php if($p['stock'] > 20): ?>
                                        <span class="text-success fw-bold"><?= $p['stock'] ?></span>
                                    <?php else: ?>
                                        <span class="text-warning fw-bold"><?= $p['stock'] ?> (Niedrig)</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small text-muted">
                                    <?= date("d.m.Y", strtotime($p['expiry'])) ?>
                                </td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-secondary btn-action">Bearbeiten</button>
                                    <button class="btn btn-sm btn-outline-danger btn-action">Löschen</button>
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
