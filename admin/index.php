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

function formatWeight($w) {
    if (!$w) return "0,75 kg";
    if (preg_match('/(\d+(?:\.\d+)?)\s*(kg|g|Gr|gr|ml|l|gr\.)/i', $w, $m)) {
        $val = (float)$m[1];
        $unit = strtolower($m[2]);
        $inKg = ($unit === 'g' || $unit === 'gr' || $unit === 'gr.' || $unit === 'ml') ? $val / 1000 : $val;
        return str_replace('.', ',', (string)$inKg) . ' kg';
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
                <a href="index.php" class="nav-link active">📦 Lagerverwaltung</a>
                <a href="ai_dashboard.php" class="nav-link">✨ AI Generator</a>
                <a href="help.php" class="nav-link">📚 Hilfe (AI)</a>
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
                                    <span class="text-muted"><?= htmlspecialchars(formatWeight($p['weight'])) ?></span>
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
                    Hallo! Ich habe Ihren Lagerbestand analysiert. Fragen Sie mich z.B. "Was läuft bald ab?" oder "Was muss ich nachbestellen?".
                </div>
            </div>
            <div class="ai-inv-footer">
                <div class="input-group">
                    <input type="text" id="ai-inv-input" class="form-control border-0 bg-light" placeholder="Frage stellen..." style="box-shadow: none;">
                    <button id="ai-inv-send-btn" class="btn btn-primary">➤</button>
                </div>
                <div class="text-center mt-1" style="font-size: 10px; color: #999;">Matin Intelligence v1.0</div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
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
                        window.location.href = 'ai_setup.html';
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
                if(sender === 'bot') {
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
