<?php
session_start();
if (!isset($_SESSION["admin"])) {
    header("Location: login.php");
    exit;
}
include_once dirname(__FILE__) . "/../db.php";
include_once dirname(__FILE__) . "/generate_mhd_report.php";

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
    if ($p['stock'] == 0)
        $out_of_stock++;
    elseif ($p['stock'] < 10)
        $low_stock++;

    if ($p['expiry'] < $today)
        $expired++;
    elseif ($p['expiry'] < $soon)
        $expiring_soon++;

    $products[] = $p;
}

function formatWeight($w)
{
    if (!$w)
        return "0,75 kg";
    if (preg_match('/(\d+(?:\.\d+)?)\s*(kg|g|Gr|gr|ml|l|gr\.)/i', $w, $m)) {
        $val = (float) $m[1];
        $unit = strtolower($m[2]);
        $inKg = ($unit === 'g' || $unit === 'gr' || $unit === 'gr.' || $unit === 'ml') ? $val / 1000 : $val;
        return str_replace('.', ',', (string) $inKg) . ' kg';
    }
    return $w;
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lagerverwaltung | Matin Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap"
        rel="stylesheet">
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
                <a href="index.php" class="nav-link active">📦 Lagerverwaltung</a>
                <a href="stock_log.php" class="nav-link">📜 Audit-Log (Neu)</a>
                <a href="mhd_reports.php" class="nav-link">📉 MHD Reports (Wöchentlich)</a>
                <a href="ai_dashboard.php" class="nav-link">✨ AI Generator</a>
                <a href="help.php" class="nav-link">📚 Hilfe & Assistenz</a>

                <a href="logout.php" class="nav-link text-danger mt-Auto">🚪 Abmelden</a>
                <a href="../index.html" class="nav-link mt-5">🌐 Zum Shop</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header-actions">
                <div>
                    <h2 class="fw-bold mb-0">Lagerverwaltung Pro</h2>
                    <p class="text-muted small">1. Zentrale Bestandsführung | 2. Automatisiert Verfallsüberwachung und
                        Bestandswarnungen</p>
                </div>
                <div class="d-flex gap-2">

                </div>
            </div>

            <!-- Critical Alerts Section -->
            <?php if ($expired > 0 || $out_of_stock > 0): ?>
                <div
                    class="alert alert-danger alert-pulse-danger border-0 shadow-sm rounded-4 p-4 mb-4 d-flex align-items-center gap-4">
                    <div class="fs-1">🚨</div>
                    <div>
                        <h5 class="fw-bold mb-1">Kritische Warnungen</h5>
                        <p class="mb-0 small">
                            Es gibt <strong><?= $expired ?></strong> abgelaufene Produkte und
                            <strong><?= $out_of_stock ?></strong> ausverkaufte Artikel, die sofortige Aufmerksamkeit
                            erfordern.
                        </p>
                    </div>
                    <button class="btn btn-danger btn-sm ms-auto rounded-pill px-3 fw-bold"
                        onclick="window.scrollTo(0, document.body.scrollHeight/2)">Details ansehen</button>
                </div>
            <?php endif; ?>

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

            <!-- New Inventory Guide Card -->
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">📦 Lagerverwaltung Pro</h5>
                    <ol class="list-group list-group-numbered list-group-flush small mb-0">
                        <li class="list-group-item px-0"><strong>Zentrale Bestandsführung</strong> für alle Artikel.
                        </li>
                        <li class="list-group-item px-0"><strong>Automatisiert Verfallsüberwachung</strong> und
                            Bestandswarnungen.</li>
                        <li class="list-group-item px-0"><strong>Kritische Alarme</strong> bei MHD-Ablauf oder
                            Ausverkauf.</li>
                    </ol>
                </div>
            </div>

            <div class="content-card" id="inventory-table">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Bild</th>
                                <th>Name (DE/AR)</th>
                                <th>Gewicht</th>
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
                                    $stock_text = "Ausverkauft";
                                } elseif ($p['stock'] < 10) {
                                    $stock_class = "status-warning";
                                    $stock_text = $p['stock'] . " (Niedrig)";
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
                                        <span class="text-muted"><?= htmlspecialchars(formatWeight($p['weight'])) ?></span>
                                    </td>
                                    <td>
                                        <span class="fw-bold"><?= number_format($p['price'], 2) ?> €</span>
                                    </td>
                                    <td>
                                        <span class="status-pill <?= $stock_class ?> editable-stock"
                                            data-pid="<?= $p['id'] ?>" data-stock="<?= $p['stock'] ?>"
                                            style="cursor: pointer; min-width: 60px; display: inline-block; text-align: center;">
                                                <?= $stock_text ?>
                                        </span>
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

    <!-- AI Inventory Assistant Widget -->
    <div id="ai-inventory-widget">
        <button id="ai-inv-toggle-btn" class="ai-inv-btn shadow-lg">
            <span class="ai-icon">📊</span>
            <span class="ai-text">Analysieren</span>
        </button>

        <div id="ai-inv-chat-box" class="ai-inv-chat-box shadow-lg d-none">
            <div class="ai-inv-header">
                <div class="d-flex align-items-center gap-2">
                    <span class="fs-4">🧠</span>
                    <div>
                        <div class="fw-bold">Lager-Assistent</div>
                        <div class="small opacity-75">Bestandsanalyse & Tipps</div>
                    </div>
                </div>
                <button id="ai-inv-close-btn" class="btn-close btn-close-white"></button>
            </div>
            <div id="ai-inv-messages" class="ai-inv-body">
                <div class="ai-msg ai-msg-bot">
                    Hallo! Ich habe Ihren Lagerbestand analysiert. Fragen Sie mich z.B. "Was läuft bald ab?" oder "Was
                    muss ich nachbestellen?".
                </div>
            </div>
            <div class="ai-inv-footer">
                <div class="input-group">
                    <input type="text" id="ai-inv-input" class="form-control border-0 bg-light"
                        placeholder="Frage stellen..." style="box-shadow: none;">
                    <button id="ai-inv-send-btn" class="btn btn-primary">➤</button>
                </div>
                <div class="text-center mt-1" style="font-size: 10px; color: #999;">Matin Intelligence v1.0</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Stock Editing Logic
            document.querySelectorAll('.editable-stock').forEach(pill => {
                pill.addEventListener('click', async (e) => {
                    const pid = pill.getAttribute('data-pid');
                    const oldStock = pill.getAttribute('data-stock');
                    const newStockStr = prompt("Neuer Lagerbestand für dieses Produkt:", oldStock);

                    if (newStockStr === null || newStockStr === oldStock) return;

                    const newStock = parseInt(newStockStr);
                    if (isNaN(newStock) || newStock < 0) {
                        alert("Pflicht: Bitte eine gültige, positive Zahl eingeben.");
                        return;
                    }

                    const reason = prompt("Grund für Lagerbewegung (Korrektur, Inventur, etc.)", "Manuelle Bestandsänderung");
                    if (reason === null) return;

                    try {
                        pill.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
                        const res = await fetch('../backend/api/update_stock.php', {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ product_id: pid, new_stock: newStock, reason: reason })
                        });
                        const data = await res.json();

                        if (data.success) {
                            window.location.reload(); // Quick refresh to update all stats and status classes
                        } else {
                            alert("Fehler beim Aktualisieren: " + data.error);
                            window.location.reload();
                        }
                    } catch (err) {
                        alert("Verbindungsfehler");
                        window.location.reload();
                    }
                });
            });

            const toggleBtn = document.getElementById('ai-inv-toggle-btn');
            const closeBtn = document.getElementById('ai-inv-close-btn');
            const chatBox = document.getElementById('ai-inv-chat-box');
            const input = document.getElementById('ai-inv-input');
            const sendBtn = document.getElementById('ai-inv-send-btn');
            const messages = document.getElementById('ai-inv-messages');

            function toggleChat() {
                chatBox.classList.toggle('d-none');
                if (!chatBox.classList.contains('d-none')) input.focus();
            }

            toggleBtn.addEventListener('click', toggleChat);
            closeBtn.addEventListener('click', toggleChat);

            async function sendMessage() {
                const text = input.value.trim();
                if (!text) return;

                addMessage(text, 'user');
                input.value = '';
                const typingId = addMessage('Analysiere Lagerdaten...', 'bot');

                // Get API Key from Cookie
                const apiKeyMatch = document.cookie.match(/gemini_key=([^;]+)/);
                const apiKey = apiKeyMatch ? apiKeyMatch[1] : null;

                if (!apiKey) {
                    document.getElementById(typingId).remove();
                    addMessage('⚠️ Kein API Key gefunden. Bitte gehen Sie zuerst zum "AI Setup" und speichern Sie dort Ihren Key.', 'bot text-danger');

                    if (confirm('Möchten Sie jetzt zum AI Setup gehen?')) {
                        window.location.href = 'ai_setup.php';
                    }
                    return;
                }

                try {
                    const response = await fetch('../backend/api/ai_inventory_handler.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ api_key: apiKey, query: text })
                    });

                    const result = await response.json();
                    document.getElementById(typingId).remove();

                    if (result.success) {
                        addMessage(result.response, 'bot');
                    } else {
                        addMessage('Fehler: ' + result.error, 'bot text-danger');
                    }
                } catch (error) {
                    document.getElementById(typingId).remove();
                    addMessage('Netzwerkfehler.', 'bot text-danger');
                }
            }

            sendBtn.addEventListener('click', sendMessage);
            input.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });

            function addMessage(text, sender) {
                const div = document.createElement('div');
                div.className = `ai-msg ai-msg-${sender}`;
                div.id = 'msg-' + Date.now();

                // Allow simple formatting specifically for the bot
                if (sender === 'bot') {
                    // Convert line breaks to <br> and bold text
                    let formatted = text.replace(/\n/g, '<br>');
                    formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    div.innerHTML = formatted;
                } else {
                    div.textContent = text;
                }

                messages.appendChild(div);
                messages.scrollTop = messages.scrollHeight;
                return div.id;
            }
        });
    </script>
</body>

</html>