<?php
include_once dirname(__FILE__) . "/../db.php";
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Hilfe (AI) | Matin Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="admin-style.css">
    <style>
        .chat-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            height: 600px;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        .chat-header {
            background: linear-gradient(135deg, #0ea5e9, #2563eb);
            color: white;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .chat-messages {
            flex-grow: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8fafc;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .chat-input-area {
            padding: 20px;
            background: white;
            border-top: 1px solid #e2e8f0;
        }
        .msg {
            max-width: 80%;
            padding: 12px 16px;
            border-radius: 12px;
            font-size: 15px;
            line-height: 1.5;
        }
        .msg-bot {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px 12px 12px 0;
            align-self: flex-start;
            color: #333;
        }
        .msg-user {
            background: #e0f2fe;
            color: #0369a1;
            border-radius: 12px 12px 0 12px;
            align-self: flex-end;
            font-weight: 500;
        }
        .quick-actions button {
            transition: all 0.2s;
        }
        .quick-actions button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

    <div class="admin-wrapper">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar-title">
                <span>🌿</span> MATIN FOOD
            </div>
            <nav>
                <a href="index.php" class="nav-link">📦 Lagerverwaltung</a>
                <a href="ai_dashboard.php" class="nav-link">✨ AI Generator</a>
                <a href="help.php" class="nav-link active">📚 Hilfe (AI)</a>
                <a href="../setup.php" class="nav-link" onclick="return confirm('Datenbank wirklich zurücksetzen?')">⚙️ DB Setup</a>
                <a href="../index.html" class="nav-link mt-5">🌐 Zum Shop</a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="header-actions mb-4">
                <div>
                    <h2 class="fw-bold mb-0">Hilfe & Support</h2>
                    <p class="text-muted">Ihr persönlicher System-Assistent</p>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm rounded-4 mb-4">
                        <div class="card-body p-4">
                            <h5 class="fw-bold mb-3">Schnelle Fragen</h5>
                            <div class="d-grid gap-2 quick-actions">
                                <button onclick="askQuestion('Wie füge ich ein neues Produkt hinzu?')" class="btn btn-light text-start py-3 px-3 rounded-3 border">
                                    📦 Produkt hinzufügen
                                </button>
                                <button onclick="askQuestion('Wie nutze ich den AI Generator?')" class="btn btn-light text-start py-3 px-3 rounded-3 border">
                                    ✨ AI Texte erstellen
                                </button>
                                <button onclick="askQuestion('Was bedeuten die roten Karten im Lager?')" class="btn btn-light text-start py-3 px-3 rounded-3 border">
                                    🚨 Rote Karten / MHD
                                </button>
                                <button onclick="askQuestion('Wie setze ich die Datenbank zurück?')" class="btn btn-light text-start py-3 px-3 rounded-3 border">
                                    ⚙️ Datenbank Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card border-0 shadow-sm rounded-4 bg-primary text-white">
                        <div class="card-body p-4 text-center">
                            <div class="fs-1 mb-2">💡</div>
                            <h5 class="fw-bold">Tipp</h5>
                            <p class="mb-0 opacity-75">Speichern Sie Ihren API Key im AI Generator, damit der Assistent funktioniert.</p>
                            <a href="ai_dashboard.php" class="btn btn-light btn-sm mt-3 fw-bold rounded-pill px-4">Zum AI Generator</a>
                        </div>
                    </div>
                </div>

                <div class="col-md-8">
                    <div class="chat-container">
                        <div class="chat-header">
                            <span class="fs-2">🎓</span>
                            <div>
                                <div class="fw-bold fs-5">Matin Admin Guide</div>
                                <div class="small opacity-75">Powered by AI</div>
                            </div>
                        </div>
                        <div id="chatMessages" class="chat-messages">
                            <div class="msg msg-bot">
                                Hallo Administrator! 👋<br>
                                Ich kenne das gesamte System. Fragen Sie mich einfach, wenn Sie nicht weiter wissen.
                            </div>
                        </div>
                        <div class="chat-input-area">
                            <div class="input-group">
                                <input type="text" id="userInput" class="form-control border bg-light py-3 px-4" placeholder="Ihre Frage zum System..." style="border-radius: 50px 0 0 50px;">
                                <button id="sendBtn" class="btn btn-primary px-4 fw-bold" style="border-radius: 0 50px 50px 0;">Senden ➤</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sendBtn = document.getElementById('sendBtn');
            const userInput = document.getElementById('userInput');
            const messages = document.getElementById('chatMessages');

            // Quick Question Handler
            window.askQuestion = function(question) {
                userInput.value = question;
                sendMessage();
            }

            async function sendMessage() {
                const text = userInput.value.trim();
                if (!text) return;

                // Add User Message
                addMessage(text, 'user');
                userInput.value = '';

                // Check API Key
                const apiKeyMatch = document.cookie.match(/openai_key=([^;]+)/);
                const apiKey = apiKeyMatch ? apiKeyMatch[1] : null;

                if (!apiKey) {
                    addMessage('⚠️ Bitte speichern Sie zuerst Ihren API Key im "AI Generator".', 'bot text-danger');
                    return;
                }

                // AI Typing
                const typingId = addMessage('Schreibe Antwort...', 'bot opacity-50');

                try {
                    const response = await fetch('../backend/api/ai_help_handler.php', {
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
                    addMessage('Verbindungsfehler.', 'bot text-danger');
                }
            }

            sendBtn.addEventListener('click', sendMessage);
            userInput.addEventListener('keypress', (e) => { if (e.key === 'Enter') sendMessage(); });

            function addMessage(text, sender) {
                const div = document.createElement('div');
                div.className = `msg msg-${sender} ${sender === 'bot' && text.includes('text-danger') ? 'text-danger bg-danger-subtle border-danger' : ''}`;
                div.id = 'msg-' + Date.now();
                
                // Markdown-like formatting
                let formatted = text.replace(/\n/g, '<br>');
                formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                div.innerHTML = formatted;

                messages.appendChild(div);
                messages.scrollTop = messages.scrollHeight;
                return div.id;
            }
        });
    </script>
</body>
</html>
