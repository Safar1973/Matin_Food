<?php
include_once dirname(__FILE__) . "/../db.php";

// Fetch all products
$query = "SELECT * FROM products ORDER BY name_de ASC";
$res = mysqli_query($conn, $query);
$products = [];
while ($p = mysqli_fetch_assoc($res)) {
    $products[] = $p;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Content Generator | Matin Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .ai-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 2rem;
        }
        .magic-btn {
            background: linear-gradient(135deg, #6366f1 0%, #a855f7 100%);
            border: none;
            color: white;
            transition: all 0.3s ease;
        }
        .magic-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(168, 85, 247, 0.4);
            color: white;
        }
        .generated-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 1rem;
            height: 100%;
        }
        .lang-badge {
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 0.25rem 0.5rem;
            border-radius: 6px;
            margin-bottom: 0.5rem;
            display: inline-block;
        }
        .badge-de { background: #fee2e2; color: #991b1b; }
        .badge-en { background: #dbeafe; color: #1e40af; }
        .badge-ar { background: #dcfce7; color: #166534; }
        
        @media print {
            .sidebar, .header-actions, .ai-card, .btn-success { display: none !important; }
            .admin-wrapper { margin-left: 0; padding: 0; }
            .generated-box { border: none; padding: 0; }
            body { background: white; }
            #resultsArea { display: block !important; }
            textarea { border: 1px solid #ccc !important; resize: none; height: auto !important; }
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
                <a href="ai_dashboard.php" class="nav-link active">✨ AI Generator</a>
                <a href="help.php" class="nav-link">📚 Hilfe (AI)</a>
                <a href="../setup.php" class="nav-link" onclick="return confirm('Datenbank wirklich zurücksetzen?')">⚙️ DB Setup</a>
                <a href="../index.html" class="nav-link mt-5">🌐 Zum Shop</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header-actions mb-4 d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="fw-bold mb-0">AI Content Generator</h2>
                    <p class="text-muted">Erstelle Produktbeschreibungen automatisch mit KI</p>
                </div>
                <!-- Help Button -->
                <a href="help.php" class="btn btn-outline-primary rounded-pill px-3">
                    ℹ️ Hilfe & Anleitung
                </a>
            </div>

            <div class="ai-card">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label fw-bold">OpenAI API Key</label>
                        <input type="password" id="apiKey" class="form-control" placeholder="sk-..." value="<?php echo isset($_COOKIE['openai_key']) ? $_COOKIE['openai_key'] : ''; ?>">
                        <div class="form-text">Wird lokal im Browser gespeichert.</div>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label fw-bold">Produkt auswählen</label>
                        <select id="productSelect" class="form-select">
                            <option value="">-- Produkt wählen --</option>
                            <?php foreach ($products as $p): ?>
                                <option value="<?= $p['id'] ?>" data-name="<?= htmlspecialchars($p['name_de']) ?>">
                                    <?= htmlspecialchars($p['name_de']) ?> (<?= htmlspecialchars($p['name_en']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <button onclick="generateContent()" class="btn magic-btn w-100 fw-bold py-2">
                            ✨ Generieren
                        </button>
                    </div>
                </div>
            </div>

            <div id="loading" class="text-center py-5 d-none">
                <div class="spinner-border text-primary" role="status"></div>
                <p class="mt-3 text-muted">Die KI schreibt gerade kreative Texte...</p>
            </div>

            <div id="resultsArea" class="row g-4 d-none">
                <!-- German -->
                <div class="col-md-4">
                    <div class="generated-box">
                        <span class="lang-badge badge-de">Deutsch</span>
                        <textarea id="desc_de" class="form-control border-0 bg-transparent" rows="6"></textarea>
                    </div>
                </div>

                <!-- English -->
                <div class="col-md-4">
                    <div class="generated-box">
                        <span class="lang-badge badge-en">English</span>
                        <textarea id="desc_en" class="form-control border-0 bg-transparent" rows="6"></textarea>
                    </div>
                </div>

                <!-- Arabic -->
                <div class="col-md-4">
                    <div class="generated-box">
                        <span class="lang-badge badge-ar">Arabisch</span>
                        <textarea id="desc_ar" class="form-control border-0 bg-transparent text-end" dir="rtl" rows="6"></textarea>
                    </div>
                </div>

                <div class="col-12 text-end mt-4">
                    <button onclick="window.print()" class="btn btn-outline-secondary px-4 me-2">🖨️ Drucken</button>
                    <button onclick="saveToDb()" class="btn btn-success px-5 fw-bold">💾 In Datenbank speichern</button>
                </div>
            </div>

        </main>
    </div>

    <!-- Help Modal -->
    <div class="modal fade" id="helpModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content border-0 rounded-4">
                <div class="modal-header border-0">
                    <h5 class="modal-title fw-bold">💡 Anleitung</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Willkommen beim <strong>AI Content Generator</strong>! So nutzen Sie ihn:</p>
                    <ol class="list-group list-group-numbered list-group-flush mb-4">
                        <li class="list-group-item">Geben Sie Ihren <strong>OpenAI API Key</strong> ein (einmalig).</li>
                        <li class="list-group-item">Wählen Sie ein <strong>Produkt</strong> aus der Liste.</li>
                        <li class="list-group-item">Klicken Sie auf <strong>✨ Generieren</strong>.</li>
                        <li class="list-group-item">Überprüfen Sie die Texte in DE/EN/AR.</li>
                        <li class="list-group-item">Klicken Sie auf <strong>💾 Speichern</strong>, um sie im Shop sichtbar zu machen.</li>
                    </ol>
                    <div class="alert alert-info small">
                        <strong>Tipp:</strong> Sie können die generierten Texte vor dem Speichern manuell bearbeiten!
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-primary rounded-pill px-4" data-bs-dismiss="modal">Verstanden</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        async function generateContent() {
            const apiKey = document.getElementById('apiKey').value;
            const productSelect = document.getElementById('productSelect');
            const productId = productSelect.value;
            const productName = productSelect.options[productSelect.selectedIndex].getAttribute('data-name');

            if (!apiKey) {
                alert('Bitte API Key eingeben');
                return;
            }
            if (!productId) {
                alert('Bitte ein Produkt auswählen');
                return;
            }

            // Save API Key to cookie for 7 days
            document.cookie = `openai_key=${apiKey}; max-age=604800; path=/`;

            document.getElementById('loading').classList.remove('d-none');
            document.getElementById('resultsArea').classList.add('d-none');

            try {
                const response = await fetch('../backend/api/ai_handler.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        api_key: apiKey,
                        product_name: productName
                    })
                });
                
                const result = await response.json();

                if (result.success) {
                    document.getElementById('desc_de').value = result.data.description_de;
                    document.getElementById('desc_en').value = result.data.description_en;
                    document.getElementById('desc_ar').value = result.data.description_ar;
                    
                    document.getElementById('loading').classList.add('d-none');
                    document.getElementById('resultsArea').classList.remove('d-none');
                } else {
                    alert('Fehler: ' + (result.error || 'Unbekannter Fehler'));
                    console.error(result);
                    document.getElementById('loading').classList.add('d-none');
                }

            } catch (error) {
                alert('Netzwerkfehler');
                console.error(error);
                document.getElementById('loading').classList.add('d-none');
            }
        }

        async function saveToDb() {
            const productSelect = document.getElementById('productSelect');
            const productId = productSelect.value;
            
            const data = {
                product_id: productId,
                descriptions: {
                    de: document.getElementById('desc_de').value,
                    en: document.getElementById('desc_en').value,
                    ar: document.getElementById('desc_ar').value
                }
            };

            try {
                const response = await fetch('../backend/api/save_description.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                
                if (result.success) {
                    alert('Erfolgreich gespeichert!');
                } else {
                    alert('Fehler beim Speichern: ' + result.error);
                }
            } catch (error) {
                alert('Fehler beim Speichern');
            }
        }
    </script>
</body>
</html>
