// State management
let currentLanguage = 'de';
let products = [];
let cart = JSON.parse(localStorage.getItem('cart')) || [];
let wishlist = JSON.parse(localStorage.getItem('wishlist')) || [];
let cartTimer = null;

function toggleMobileMenu() {
    const overlay = document.getElementById('mobile-nav-overlay');
    if (overlay) {
        overlay.classList.toggle('active');
        document.body.style.overflow = overlay.classList.contains('active') ? 'hidden' : '';
    }
}

// Constants
const API_URL = 'backend/api/get_products.php';

// Initialize Application
document.addEventListener('DOMContentLoaded', () => {
    // 1. Core Site Initialization
    currentLanguage = getCookie('lang') || 'de';
    checkProtocol();
    setLanguage(currentLanguage);
    updateCartUI();
    updateWishlistUI();
    setupNavigation();
    setupFilters();
    setupCartBackdrop();

    // 2. AI Chat Widget Initialization
    initAiChat();

    // 3. Cookie Banner Initialization
    initCookieBanner();

    // 4. Shop Status Initialization
    refreshShopStatus();
});

// Cookie Helper Functions
function setCookie(name, value, days) {
    let expires = "";
    if (days) {
        let date = new Date();
        date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "") + expires + "; path=/; SameSite=Lax";
}

function getCookie(name) {
    let nameEQ = name + "=";
    let ca = document.cookie.split(';');
    for (let i = 0; i < ca.length; i++) {
        let c = ca[i];
        while (c.charAt(0) == ' ') c = c.substring(1, c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
    }
    return null;
}

async function refreshShopStatus() {
    const countEl = document.getElementById('db-product-count');
    if (!countEl) return;

    try {
        const res = await fetch('backend/api/get_count.php');
        const data = await res.json();
        if (data.success) {
            countEl.innerText = data.count;
        } else {
            countEl.innerText = '?';
        }
    } catch (e) {
        countEl.innerText = '?';
    }
}

// AI Chat Widget Logic
function initAiChat() {
    const aiToggleBtn = document.getElementById('ai-toggle-btn');
    const aiCloseBtn = document.getElementById('ai-close-btn');
    const aiChatBox = document.getElementById('ai-chat-box');
    const aiInput = document.getElementById('ai-input');
    const aiSendBtn = document.getElementById('ai-send-btn');
    const aiMessages = document.getElementById('ai-messages');

    let chatHistory = [];

    if (!aiToggleBtn) return;

    // Toggle Chat
    function toggleChat() {
        aiChatBox.classList.toggle('d-none');
        if (!aiChatBox.classList.contains('d-none')) {
            aiInput.focus();
        }
    }

    aiToggleBtn.addEventListener('click', toggleChat);
    aiCloseBtn.addEventListener('click', toggleChat);

    // Send Message
    async function sendMessage() {
        const text = aiInput.value.trim();
        if (!text) return;

        // Add User Message
        addMessage(text, 'user');
        aiInput.value = '';

        // Typing Indicator
        const typingId = addMessage('<div class="typing-dots"><span class="typing-dot"></span><span class="typing-dot"></span><span class="typing-dot"></span></div>', 'bot typing');

        try {
            // Get Key from Cookies (Gemini) using helper
            const apiKey = getCookie('gemini_key');

            const response = await fetch('backend/api/gemini_chat_handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    query: text,
                    history: chatHistory,
                    api_key: apiKey
                })
            });

            const result = await response.json();
            const typingEl = document.getElementById(typingId);
            if (typingEl) typingEl.remove();

            if (result.success) {
                addMessage(result.response, 'bot');
                // Gemini uses 'model' role
                chatHistory.push({ role: 'user', content: text });
                chatHistory.push({ role: 'model', content: result.response });
                // Keep history reasonable
                if (chatHistory.length > 10) chatHistory.shift();
            } else {
                let errorMsg = result.error || 'Ein unbekannter Fehler ist aufgetreten.';
                if (result.debug_code) errorMsg += ` (HTTP ${result.debug_code})`;

                addMessage('❌ ' + errorMsg, 'bot text-danger');

                // Enhanced error handling for Gemini API issues
                const errorStr = errorMsg.toLowerCase();
                if (errorStr.includes('api key') || errorStr.includes('invalid') || errorStr.includes('403') || errorStr.includes('401')) {
                    const message = `⚠️ Es scheint ein Problem mit dem Gemini API-Key vorzuliegen.\n\n` +
                        `Möchten Sie jetzt einen neuen Google Gemini API-Key eingeben?\n\n` +
                        `Holen Sie sich einen Key unter: https://aistudio.google.com/app/apikey`;

                    const newKey = prompt(message);
                    if (newKey && newKey.trim().startsWith('AIza')) {
                        setCookie('gemini_key', newKey.trim(), 365); // 1 year using helper
                        addMessage('✅ API-Key wurde aktualisiert. Bitte versuchen Sie es erneut.', 'bot text-success');
                    } else if (newKey) {
                        addMessage('❌ Ungültiges Key-Format. Ein Gemini Key beginnt normalerweise mit "AIza".', 'bot text-warning');
                    }
                }
            }
        } catch (error) {
            const typingEl = document.getElementById(typingId);
            if (typingEl) typingEl.remove();
            console.error('AI Chat Error:', error);
            addMessage('Verbindungsfehler zur KI. Bitte prüfen Sie Ihre Internetverbindung.', 'bot text-danger');
        }
    }

    aiSendBtn.addEventListener('click', sendMessage);
    aiInput.addEventListener('keypress', (e) => {
        if (e.key === 'Enter') sendMessage();
    });

    function addMessage(text, sender) {
        const msgDiv = document.createElement('div');
        const id = 'msg-' + Date.now() + Math.random().toString(36).substr(2, 5);
        msgDiv.id = id;
        msgDiv.className = `ai-msg ai-msg-${sender.split(' ')[0]}`; // Handle 'bot typing' case

        if (sender.includes('bot')) {
            // Simple Markdown support
            let formatted = text.replace(/\n/g, '<br>');
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            msgDiv.innerHTML = formatted;
        } else {
            msgDiv.textContent = text;
        }

        aiMessages.appendChild(msgDiv);
        aiMessages.scrollTop = aiMessages.scrollHeight;
        return id;
    }

    // Expose clear function
    window.clearAiChat = () => {
        aiMessages.innerHTML = '<div class="ai-msg ai-msg-bot">Chat-Verlauf gelöscht. Wie kann ich Ihnen helfen?</div>';
        chatHistory = [];
    };
}

function checkProtocol() {
    if (window.location.protocol === 'file:') {
        const grid = document.getElementById('product-grid');
        if (grid) {
            grid.innerHTML = `
                <div class="alert alert-warning text-center w-100 p-4" style="grid-column: 1 / -1; border-radius: 12px;">
                    <h4 class="fw-bold">⚠️ Wichtiger Hinweis: Lokale Dateiansicht</h4>
                    <p class="mb-0">Sie haben die Seite direkt als Datei geöffnet. Da die Produkte von einem Server geladen werden müssen, öffnen Sie die Seite bitte über Ihren XAMPP-Server unter:<br>
                    <code class="d-block mt-2 bg-white p-2 border rounded">http://localhost/Matin_Food/index.html</code></p>
                </div>
            `;
        }
    }
}

// Navigation logic
function setupNavigation() {
    // Basic navigation is now handled by goToSection
}

function setupFilters() {
    const priceFilter = document.getElementById('price-filter');
    if (priceFilter) {
        priceFilter.addEventListener('input', (e) => {
            renderProducts(products.filter(p => p.price <= e.target.value));
        });
    }
}

function goToSection(sectionId) {
    document.querySelectorAll('.page-section').forEach(section => {
        section.classList.remove('active');
    });
    const target = document.getElementById(sectionId);
    if (target) target.classList.add('active');

    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Search logic
function handleSearch(event) {
    event.preventDefault();
    const query = document.getElementById('main-search').value.toLowerCase();
    const filtered = products.filter(p => {
        const nameInLang = (p['name_' + currentLanguage] || p.name).toLowerCase();
        const matchesQuery = nameInLang.includes(query) ||
            p.category.toLowerCase().includes(query);
        const matchesCategory = selectedSearchCategory === 'all' || p.category === selectedSearchCategory;
        return matchesQuery && matchesCategory;
    });
    renderProducts(filtered);
    goToSection('home-section');
}

// Load products from API
async function loadProducts() {
    try {
        const response = await fetch(`${API_URL}?lang=${currentLanguage}`);
        products = await response.json();
        renderProducts();
        renderCategories();
    } catch (error) {
        console.error('Error loading products:', error);
    }
}

// Helper for weight formatting
function formatWeightToKg(weightStr) {
    if (!weightStr) return '0,75 kg';
    const wMatch = weightStr.match(/(\d+(?:\.\d+)?)\s*(kg|g|Gr|gr|ml|l)/i);
    if (!wMatch) return weightStr;

    const val = parseFloat(wMatch[1]);
    const unit = wMatch[2].toLowerCase();

    let inKg = val;
    if (unit === 'g' || unit === 'gr' || unit === 'ml') {
        inKg = val / 1000;
    }

    // Format to 2 decimals if needed, replace dot with comma
    return inKg.toString().replace('.', ',') + ' kg';
}

// Render product grid
function renderProducts(productsToRender = products, category = 'all') {
    const grid = document.getElementById('product-grid');
    if (!grid) return;

    // Sort by ID to ensure consistency
    productsToRender.sort((a, b) => parseInt(a.id) - parseInt(b.id));

    if (productsToRender.length === 0) {
        grid.innerHTML = `
            <div class="col-12 text-center py-5 no-results-container" style="grid-column: 1 / -1; min-height: 300px;">
                <div class="display-3 mb-4 opacity-50">🔍</div>
                <h3 class="fw-bold mb-3" data-i18n="${category === 'wishlist' ? 'empty_wishlist_msg' : 'empty_category_msg'}">Keine Produkte in dieser Kategorie gefunden.</h3>
                <p class="text-muted mb-4 mx-auto" style="max-width: 450px;" data-i18n="try_other_filter">Leider konnten wir keine Produkte finden, die Ihren Suchkriterien entsprechen. Probieren Sie es mit anderen Filtern.</p>
                <button class="btn btn-primary rounded-pill px-4 py-2 fw-bold" onclick="filterByCategory('all')" data-i18n="show_all_products">Alle Produkte anzeigen</button>
            </div>`;
        updateStaticText();
        return;
    }

    grid.innerHTML = productsToRender.map(product => {
        const discount = parseInt(product.discount) || 0;

        // Dynamic Weight Logic (Converted to KG)
        const dispWeight = formatWeightToKg(product.weight);

        return `
        <div class="product-card">
            <div class="product-image-container">
                <div class="badge-container">
                    ${discount > 0 ? `<span class="badge-discount">-${discount}%</span>` : ''}
                    ${product.id % 4 === 0 ? `<span class="badge-neu-pill">${translations[currentLanguage]['new_label']}</span>` : ''}
                    <span class="badge-nr">${translations[currentLanguage]['product_nr']} ${product.id}</span>
                </div>
                <button class="wishlist-btn-ref ${wishlist.some(w => w == product.id) ? 'active' : ''}" 
                        onclick="toggleWishlist(${product.id}, event)" 
                        title="${translations[currentLanguage]['wishlist_tooltip']}">
                    ${wishlist.some(w => w == product.id) ? '❤️' : '♡'}
                </button>
                <img src="${product.img}" alt="${product.name}" class="product-img" onclick="openProductModal(${product.id})">
            </div>
            <div class="product-info-ref text-center">
                <span class="product-cat-ref" data-i18n="${product.category.toLowerCase()}">${translations[currentLanguage][product.category.toLowerCase()] || product.category}</span>
                <h3 class="product-title-ref" onclick="openProductModal(${product.id})">${product['name_' + currentLanguage] || product.name}</h3>
                <div class="price-container-ref">
                    <span class="current-price-ref">${parseFloat(product.price).toFixed(2)} €</span>
                    ${discount > 0 ? `<span class="old-price-ref">${(product.price / (1 - (discount / 100))).toFixed(2)} €</span>` : ''}
                </div>
                <div class="weight-info-ref">
                    ${dispWeight}
                </div>
                <button class="add-to-cart-btn-ref w-100" onclick="addToCart(${product.id})">
                    <span data-i18n="add_to_cart_btn">${translations[currentLanguage]['add_to_cart_btn']}</span>
                </button>
            </div>
        </div>`;
    }).join('');

    updateStaticText();
}

// i18n Dictionary
const translations = {
    'de': {
        'help': 'Hilfe',
        'about_us': 'Über uns',
        'contact': 'Kontakt',
        'all_categories': 'Alle Kategorien',
        'account': 'Mein Konto',
        'wishlist': 'Lieblingsprodukte',
        'cart': 'Warenkorb',
        'home': 'Startseite',
        'cat_grains': 'Getreide & Körner',
        'cat_canned': 'Konserven',
        'cat_syrups': 'Sirupe & Saucen',
        'offers': 'ANGEBOTE %',
        'recommended': 'Empfohlen',
        'categories': 'Kategorien',
        'welcome_title': 'Willkommen bei MATIN FOOD',
        'welcome_text': 'Entdecken Sie authentische orientalische Spezialitäten und frische Lebensmittel in bester Qualität.',
        'all_products': 'Alle Produkte',
        'popular': 'Beliebt',
        'new_in_shop': 'Neu im Shop',
        'bestseller': 'Bestseller',
        'sold_out_soon': 'Bald ausverkauft!',
        'grains': 'Getreide & Körner',
        'canned': 'Konserven',
        'syrups': 'Sirupe & Saucen',
        'bakery': 'Bäckerei',
        'fresh': 'Frische Ware',
        'sweets': 'Süßigkeiten',
        'additional_categories': 'Zusätzliche Kategorien',
        'shopp_by_marken': 'Marken entdecken',
        'mhd_ware': 'MHD-Ware',
        'deals': 'Deals',
        'filters': 'Filter',
        'price_until': 'Preis bis',
        'tax_info': 'Inkl. MwSt. zzgl. Versandkosten',
        'in_cart': 'In den',
        'empty_cart_msg': 'Ihr Warenkorb ist leer!',
        'empty_category_msg': 'Keine Produkte in dieser Kategorie gefunden.',
        'empty_wishlist_msg': 'Ihre Liste der Lieblingsprodukte ist noch leer.',
        'show_all_products': 'Alle Produkte anzeigen',
        'order_summary': 'Bestellübersicht',
        'total_sum': 'Gesamtsumme',
        'checkout_btn': 'Zur Kasse gehen',
        'clear_cart': 'Warenkorb leeren',
        'opening_hours': 'Öffnungszeiten',
        'monday': 'Montag',
        'tuesday': 'Dienstag',
        'wednesday': 'Mittwoch',
        'thursday': 'Donnerstag',
        'friday': 'Freitag',
        'saturday': 'Samstag',
        'sunday': 'Sonntag',
        'login': 'Anmelden',
        'register': 'Registrieren',
        'email': 'E-Mail Adresse',
        'password': 'Passwort',
        'remember_me': 'Angemeldet bleiben',
        'forgot_pw': 'Passwort vergessen?',
        'create_account': 'Konto erstellen',
        'full_name': 'Vollständiger Name',
        'accept_terms': 'Ich akzeptiere die AGB und Datenschutzbestimmungen',
        'agb': 'AGB',
        'privacy': 'Datenschutzbestimmungen',
        'checkout_title': 'Zur Kasse',
        'personal_data': 'Persönliche Daten',
        'name_placeholder': 'Vorname & Nachname',
        'phone': 'Telefon',
        'address': 'Adresse',
        'city': 'Stadt',
        'payment_method': 'Zahlungsart',
        'online_payment': 'Online Zahlung (Karte / PayPal)',
        'card_delivery': 'Karte bei Lieferung',
        'cash_delivery': 'Barzahlung bei Lieferung',
        'order_btn': 'Jetzt zahlungspflichtig bestellen',
        'added_to_cart': 'Zum Warenkorb hinzugefügt',
        'category_label': 'Kategorie',
        'weight_label': 'Gewicht',
        'expiry_label': 'Haltbarkeit',
        'description_label': 'Beschreibung',
        'description_val': 'Authentische Qualität für Ihre Küche. Premium-Import.',
        'cart_title': 'Ihr Warenkorb',
        'cookie_title': '🍪 Cookie-Einstellungen',
        'cookie_text': 'Wir verwenden Cookies, um Ihr Einkaufserlebnis zu verbessern, personalisierte Inhalte anzuzeigen und unseren Datenverkehr zu analysieren. Mit Klick auf "Alle akzeptieren" stimmen Sie der Verwendung aller Cookies zu.',
        'cookie_accept_all': 'Alle akzeptieren',
        'cookie_only_essential': 'Nur Essenzielle',
        'location_header': 'Filialen & Maps',
        'locations_title': 'Unsere Standorte',
        'about_title': 'Über uns',
        'about_text_1': 'Gegründet im Jahr 2016, hat sich Matin Food als vertrauenswürdiger Spezialist für feinste Lebensmittel etabliert. Unser Fokus liegt darauf, Ihnen eine exquisite Auswahl an sorgfältig kuratierten Produkten zu bieten – von authentischen Trockenwaren bis hin zu fangfrischem Obst und Gemüse.<br><br>Mit fundierter Expertise im orientalischen Lebensmittelhandel garantieren wir Frische, Qualität und eine partnerschaftliche Beziehung zu unseren Kunden. Diese Werte sind das Fundament unserer täglichen Hingabe.',
        'about_assortment_title': 'Unser Sortiment',
        'about_assortment_text': 'Bei Metin Food finden unsere Kunden eine vielfältige Auswahl an Lebensmitteln für den täglichen Bedarf. Unser Sortiment umfasst unter anderem:',
        'about_assortment_list': '<li>Lebensmittel verschiedener Art</li><li>Konserven und haltbare Produkte</li><li>Getränke</li><li>Frisches Obst und Gemüse</li>',
        'about_assortment_end': 'Wir achten darauf, Produkte anzubieten, die sowohl qualitativ hochwertig als auch preislich attraktiv sind.',
        'about_b2b_title': 'Großhandel und Einzelhandel',
        'about_b2b_text': 'Unser Unternehmen beliefert sowohl Großhandelskunden wie Restaurants, Geschäfte und Händler als auch Privatkunden.<br><br>Darüber hinaus bieten wir unseren Kunden die Möglichkeit, unsere Produkte online zu bestellen, sodass sie bequem und schnell auf unser Sortiment zugreifen können.',
        'about_values_title': 'Unsere Werte',
        'about_values_intro': 'Bei Metin Food stehen folgende Prinzipien im Mittelpunkt:',
        'about_values_list': '<div class="col-md-3"><div class="fs-1 mb-2">⭐</div><strong>Qualität:</strong><br>Sorgfältige Auswahl<br>unserer Produkte</div><div class="col-md-3"><div class="fs-1 mb-2">🍎</div><strong>Frische:</strong><br>Besonders bei<br>Obst und Gemüse</div><div class="col-md-3"><div class="fs-1 mb-2">🤝</div><strong>Vertrauen:</strong><br>Ehrlicher und<br>transparenter Handel</div><div class="col-md-3"><div class="fs-1 mb-2">💎</div><strong>Service:</strong><br>Kundenzufriedenheit<br>hat höchste Priorität</div>',
        'about_vision_title': 'Unsere Vision',
        'about_vision_text': 'Unsere Vision ist es, uns kontinuierlich weiterzuentwickeln und unseren Kunden stets ein zuverlässiger Partner für hochwertige Lebensmittel zu sein. Wir streben nach langfristigen Partnerschaften durch exzellenten Service.',
        'new_label': 'Neu',
        'qty_label': 'Menge',
        'confirm_clear_cart': 'Möchten Sie den Warenkorb wirklich leeren?',
        'support_label': 'Support',
        'free_shipping': 'Versandkostenfrei ab 50€',
        'live_status': 'Produkte live',
        'print_label': 'Drucken',
        'search_placeholder': 'Wonach suchen Sie?',
        'add_to_cart_btn': 'In den Warenkorb',
        'product_nr': 'Nr.',
        'wishlist_tooltip': 'Zu Lieblingsprodukten',
        'error_order': 'Fehler bei der Bestellung',
        'technical_error': 'Ein technischer Fehler ist aufgetreten.',
        'wishlist_added': 'Zu Lieblingsprodukten hinzugefügt ❤️',
        'wishlist_removed': 'Von Lieblingsprodukten entfernt',
        'loading': 'Ladend...',
        'misc': 'Verschiedenes',
        'feedback_label': 'Feedback',
        'feedback_title': 'Was unsere Kunden sagen',
        'feedback_subtitle': 'Ihre Zufriedenheit ist unser größter Ansporn. Teilen Sie uns Ihre Erfahrungen mit!',
        'verified_buyer': 'Verifizierter Käufer',
        'leave_feedback': 'Feedback geben',
        'rating': 'Bewertung',
        'message': 'Ihre Nachricht',
        'send_feedback': 'Feedback senden',
        'feedback_success': 'Vielen Dank für Ihr Feedback! Wir schätzen Ihre Meinung.',
        'feature_nav_title': 'Klare & einfache Navigation',
        'feature_nav_desc': 'Finden Sie blitzschnell exactly das, was Sie suchen.',
        'feature_shipping_title': 'Schneller Versand',
        'feature_shipping_desc': 'Ihre Bestellung ist innerhalb von 24h versandbereit.',
        'feature_quality_title': 'Beste Qualität',
        'feature_quality_desc': 'Frische orientalische Spezialitäten, sorgfältig ausgewählt.',
        'feature_payment_title': 'Sicher Bezahlen',
        'feature_payment_desc': 'Vielfältige und sichere Zahlungsmöglichkeiten.',
        'try_other_filter': 'Leider konnten wir keine Produkte finden, die Ihren Suchkriterien entsprechen. Probieren Sie es mit anderen Filtern.'
    },
    'en': {
        'help': 'Help',
        'about_us': 'About Us',
        'contact': 'Contact',
        'all_categories': 'All Categories',
        'account': 'My Account',
        'wishlist': 'Wishlist',
        'cart': 'Cart',
        'home': 'Home',
        'cat_grains': 'Grains & Seeds',
        'cat_canned': 'Canned Food',
        'cat_syrups': 'Syrups & Sauces',
        'offers': 'OFFERS %',
        'recommended': 'Recommended',
        'categories': 'Categories',
        'welcome_title': 'Welcome to MATIN FOOD',
        'welcome_text': 'Discover authentic oriental specialties and fresh foods of the highest quality.',
        'all_products': 'All Products',
        'popular': 'Popular',
        'new_in_shop': 'New in Shop',
        'bestseller': 'Bestseller',
        'sold_out_soon': 'Sold out soon!',
        'grains': 'Grains & Seeds',
        'canned': 'Canned Food',
        'syrups': 'Syrups & Sauces',
        'bakery': 'Bakery',
        'fresh': 'Fresh Goods',
        'sweets': 'Sweets',
        'additional_categories': 'Additional Categories',
        'shopp_by_marken': 'Explore Brands',
        'mhd_ware': 'Clearance (Near Expiry)',
        'deals': 'Deals',
        'filters': 'Filters',
        'price_until': 'Price up to',
        'tax_info': 'Incl. VAT plus shipping costs',
        'in_cart': 'Add to',
        'empty_cart_msg': 'Your cart is empty!',
        'empty_category_msg': 'No products found in this category.',
        'empty_wishlist_msg': 'Your wishlist is currently empty.',
        'show_all_products': 'Show all products',
        'order_summary': 'Order Summary',
        'total_sum': 'Total Sum',
        'checkout_btn': 'Go to Checkout',
        'clear_cart': 'Clear Cart',
        'opening_hours': 'Opening Hours',
        'monday': 'Monday',
        'tuesday': 'Tuesday',
        'wednesday': 'Wednesday',
        'thursday': 'Thursday',
        'friday': 'Friday',
        'saturday': 'Saturday',
        'sunday': 'Sunday',
        'login': 'Login',
        'register': 'Register',
        'email': 'Email Address',
        'password': 'Password',
        'remember_me': 'Remember me',
        'forgot_pw': 'Forgot password?',
        'create_account': 'Create Account',
        'full_name': 'Full Name',
        'accept_terms': 'I accept the T&Cs and Privacy Policy',
        'agb': 'T&Cs',
        'privacy': 'Privacy Policy',
        'checkout_title': 'Checkout',
        'personal_data': 'Personal Data',
        'name_placeholder': 'First & Last Name',
        'phone': 'Phone',
        'address': 'Address',
        'city': 'City',
        'payment_method': 'Payment Method',
        'online_payment': 'Online Payment (Card / PayPal)',
        'card_delivery': 'Card on Delivery',
        'cash_delivery': 'Cash on Delivery',
        'order_btn': 'Order Now (Commit to Pay)',
        'added_to_cart': 'Added to cart',
        'category_label': 'Category',
        'weight_label': 'Weight',
        'expiry_label': 'Expiry',
        'description_label': 'Description',
        'description_val': 'Authentic quality for your kitchen. Premium import.',
        'cart_title': 'Your Cart',
        'cookie_title': '🍪 Cookie Settings',
        'cookie_text': 'We use cookies to improve your shopping experience, show personalized content and analyze our traffic. By clicking "Accept All", you agree to the use of all cookies.',
        'cookie_accept_all': 'Accept All',
        'cookie_only_essential': 'Essential Only',
        'location_header': 'Branches & Maps',
        'locations_title': 'Our Locations',
        'about_title': 'About Us',
        'about_text_1': 'Founded in 2016, Matin Food has established itself as a trusted specialist for the finest groceries. Our mission is to provide you with an exquisite selection of carefully curated products – from authentic dry goods to the freshest fruits and vegetables.<br><br>With deep expertise in the oriental food trade, we guarantee freshness, quality, and a dedicated relationship with our customers. These values are the cornerstone of our daily commitment.',
        'about_assortment_title': 'Our Assortment',
        'about_assortment_text': 'At Metin Food, our customers will find a diverse selection of everyday groceries. Our range includes, among other things:',
        'about_assortment_list': '<li>Groceries of various kinds</li><li>Canned goods and non-perishables</li><li>Beverages</li><li>Fresh fruit and vegetables</li>',
        'about_assortment_end': 'We take care to offer products that are both high in quality and attractively priced.',
        'about_b2b_title': 'Wholesale and Retail',
        'about_b2b_text': 'Our company supplies wholesale customers such as restaurants, shops, and dealers, as well as private customers.<br><br>Furthermore, we offer our customers the opportunity to order our products online, so they can access our assortment conveniently and quickly.',
        'about_values_title': 'Our Values',
        'about_values_intro': 'At Metin Food, the following principles are at the heart of what we do:',
        'about_values_list': '<div class="col-md-3"><div class="fs-1 mb-2">⭐</div><strong>Quality:</strong><br>Careful selection<br>of our products</div><div class="col-md-3"><div class="fs-1 mb-2">🍎</div><strong>Freshness:</strong><br>Especially for<br>fruit and vegetables</div><div class="col-md-3"><div class="fs-1 mb-2">🤝</div><strong>Trust:</strong><br>Honest and<br>transparent trade</div><div class="col-md-3"><div class="fs-1 mb-2">💎</div><strong>Service:</strong><br>Customer satisfaction<br>is top priority</div>',
        'about_vision_title': 'Our Vision',
        'about_vision_text': 'Our vision is to continue developing and to always be a reliable partner for high-quality food. We strive for long-term partnerships through excellent service.',
        'new_label': 'New',
        'qty_label': 'Qty',
        'confirm_clear_cart': 'Do you really want to clear the cart?',
        'support_label': 'Support',
        'free_shipping': 'Free shipping over 50€',
        'live_status': 'Products live',
        'print_label': 'Print',
        'search_placeholder': 'What are you looking for?',
        'add_to_cart_btn': 'Add to Cart',
        'product_nr': 'No.',
        'wishlist_tooltip': 'Add to wishlist',
        'error_order': 'Error placing order',
        'technical_error': 'A technical error occurred.',
        'wishlist_added': 'Added to favorites ❤️',
        'wishlist_removed': 'Removed from favorites',
        'loading': 'Loading...',
        'misc': 'Miscellaneous',
        'feedback_label': 'Feedback',
        'feedback_title': 'What our customers say',
        'feedback_subtitle': 'Your satisfaction is our greatest motivation. Share your experiences with us!',
        'verified_buyer': 'Verified Buyer',
        'leave_feedback': 'Give Feedback',
        'rating': 'Rating',
        'message': 'Your Message',
        'send_feedback': 'Send Feedback',
        'feedback_success': 'Thank you for your feedback! We value your opinion.',
        'feature_nav_title': 'Clear & Simple Navigation',
        'feature_nav_desc': 'Find exactly what you are looking for in no time.',
        'feature_shipping_title': 'Fast Shipping',
        'feature_shipping_desc': 'Your order is ready for shipment within 24h.',
        'feature_quality_title': 'Best Quality',
        'feature_quality_desc': 'Fresh oriental specialties, carefully selected.',
        'feature_payment_title': 'Secure Payment',
        'feature_payment_desc': 'Diverse and secure payment options.',
        'try_other_filter': 'Unfortunately, we could not find any products that match your search criteria. Try other filters.'
    },
    'ar': {
        'help': 'مساعدة',
        'about_us': 'من نحن',
        'contact': 'اتصل بنا',
        'all_categories': 'جميع الفئات',
        'account': 'حسابي',
        'wishlist': 'قائمة الأمنيات',
        'cart': 'عربة التسوق',
        'home': 'الرئيسية',
        'cat_grains': 'حبوب وبقوليات',
        'cat_canned': 'معلبات',
        'cat_syrups': 'شراب وصوص',
        'offers': 'عروض %',
        'recommended': 'مقترح لك',
        'categories': 'الفئات',
        'welcome_title': 'مرحباً بكم في MATIN FOOD',
        'welcome_text': 'اكتشف التخصصات الشرقية الأصيلة والأغذية الطازجة بأعلى معايير الجودة.',
        'all_products': 'جميع المنتجات',
        'popular': 'الأكثر شعبية',
        'new_in_shop': 'جديد في المتجر',
        'bestseller': 'الأكثر مبيعاً',
        'sold_out_soon': 'قاربت على النفاد!',
        'grains': 'حبوب وبقوليات',
        'canned': 'معلبات',
        'syrups': 'شراب وصوص',
        'bakery': 'مخبوزات',
        'fresh': 'مواد طازجة',
        'sweets': 'حلويات',
        'additional_categories': 'فئات إضافية',
        'shopp_by_marken': 'تسوق حسب العلامة التجارية',
        'mhd_ware': 'عروض قاربت على الانتهاء',
        'deals': 'صفقات',
        'filters': 'تصفية',
        'price_until': 'السعر حتى',
        'tax_info': 'شامل ضريبة القيمة المضافة بالإضافة إلى تكاليف الشحن',
        'in_cart': 'أضف إلى',
        'empty_cart_msg': 'عربة التسوق فارغة!',
        'empty_category_msg': 'لم يتم العثور على منتجات في هذه الفئة.',
        'empty_wishlist_msg': 'قائمة منتجاتك المفضلة فارغة حاليا.',
        'show_all_products': 'عرض جميع المنتجات',
        'order_summary': 'ملخص الطلبية',
        'total_sum': 'المجموع الإجمالي',
        'checkout_btn': 'الذهاب للدفع',
        'clear_cart': 'تفريغ العربة',
        'opening_hours': 'ساعات العمل',
        'monday': 'الاثنين',
        'tuesday': 'الثلاثاء',
        'wednesday': 'الأربعاء',
        'thursday': 'الخميس',
        'friday': 'الجمعة',
        'saturday': 'السبت',
        'sunday': 'الأحد',
        'login': 'تسجيل الدخول',
        'register': 'إنشاء حساب',
        'email': 'البريد الإلكتروني',
        'password': 'كلمة المرور',
        'remember_me': 'تذكرني',
        'forgot_pw': 'نسيت كلمة المرور؟',
        'create_account': 'إنشاء حساب',
        'full_name': 'الاسم الكامل',
        'accept_terms': 'أوافق على الشروط والأحكام وسياسة الخصوصية',
        'agb': 'الشروط والأحكام',
        'privacy': 'سياسة الخصوصية',
        'checkout_title': 'إتمام الطلب',
        'personal_data': 'البيانات الشخصية',
        'name_placeholder': 'الاسم الكامل',
        'phone': 'رقم الهاتف',
        'address': 'العنوان',
        'city': 'المدينة',
        'payment_method': 'طريقة الدفع',
        'online_payment': 'دفع عبر الإنترنت (بطاقة / باي بال)',
        'card_delivery': 'بطاقة عند الاستلام',
        'cash_delivery': 'نقدًا عند الاستلام',
        'order_btn': 'اطلب الآن (التزام بالدفع)',
        'added_to_cart': 'تم الإضافة للسلة',
        'category_label': 'الفئة',
        'weight_label': 'الوزن',
        'expiry_label': 'الصلاحية',
        'description_label': 'الوصف',
        'description_val': 'جودة أصيلة لمطبخك. استيراد ممتاز.',
        'cart_title': 'عربة تسوقك',
        'cookie_title': '🍪 إعدادات ملفات تعريف الارتباط',
        'cookie_text': 'نحن نستخدم ملفات تعريف الارتباط لتحسين تجربة التسوق الخاصة بك، وعرض محتوى مخصص وتحليل حركة المرور لدينا. بالنقر فوق "قبول الكل"، فإنك توافق على استخدام جميع ملفات تعريف الارتباط.',
        'cookie_accept_all': 'قبول الكل',
        'cookie_only_essential': 'الضرورية فقط',
        'location_header': 'الفروع والخرائط',
        'locations_title': 'مواقعنا',
        'about_title': 'من نحن',
        'about_text_1': 'تأسست شركة متين فود في عام 2016، ومنذ ذلك الحين رسخت مكانتها كخبير موثوق في أجود أنواع المواد الغذائية. هدفنا هو تزويدكم بمجموعة متميزة من المنتجات المختارة بعناية - من السلع الغذائية الأصيلة إلى الفواكه والخضروات الطازجة.<br><br>بفضل خبرتنا العميقة في تجارة الأغذية الشرقية، نضمن الجودة والنضارة وبناء علاقات متينة مع عملائنا. هذه القيم هي حجر الأساس لالتزامنا اليومي بتقديم الأفضل.',
        'about_assortment_title': 'تشكيلتنا',
        'about_assortment_text': 'في متين فود، سيجد عملاؤنا مجموعة متنوعة من البقالة اليومية. تشمل تشكيلتنا، من بين أمور أخرى:',
        'about_assortment_list': '<li>مواد غذائية بأنواع مختلفة</li><li>معلبات ومنتجات طويلة الأمد</li><li>مشروبات</li><li>فواكه وخضروات طازجة</li>',
        'about_assortment_end': 'نحرص على تقديم منتجات ذات جودة عالية وبأسعار جذابة.',
        'about_b2b_title': 'البيع بالجملة والتجزئة',
        'about_b2b_text': 'تزود شركتنا عملاء الجملة مثل المطاعم والمتاجر والتجار، وكذلك العملاء من الأفراد.<br><br>علاوة على ذلك، نقدم لعملائنا فرصة طلب منتجاتنا عبر الإنترنت، حتى يتمكنوا من الوصول إلى تشكيلتنا بسهولة وسرعة.',
        'about_values_title': 'قيمنا',
        'about_values_intro': 'في متين فود، نركز على المبادئ التالية:',
        'about_values_list': '<div class="col-md-3"><div class="fs-1 mb-2">⭐</div><strong>الجودة:</strong><br>اختيار دقيق<br>لمنتجاتنا</div><div class="col-md-3"><div class="fs-1 mb-2">🍎</div><strong>النضارة:</strong><br>خاصة للفواكه<br>والخضروات</div><div class="col-md-3"><div class="fs-1 mb-2">🤝</div><strong>الثقة:</strong><br>تجارة صادقة<br>وشفافة</div><div class="col-md-3"><div class="fs-1 mb-2">💎</div><strong>الخدمة:</strong><br>رضا العملاء<br>أولويتنا القصوى</div>',
        'about_vision_title': 'رؤيتنا',
        'about_vision_text': 'رؤيتنا هي مواصلة التطور لنكون شريكاً موثوقاً للأغذية عالية الجودة. نسعى لبناء شراكات طويلة الأمد من خلال التميز في الخدمة.',
        'new_label': 'جديد',
        'qty_label': 'الكمية',
        'confirm_clear_cart': 'هل تريد حقاً تفريغ السلة؟',
        'support_label': 'الدعم الفني',
        'free_shipping': 'شحن مجاني فوق 50 يورو',
        'live_status': 'منتج مباشر',
        'print_label': 'طباعة',
        'search_placeholder': 'عن ماذا تبحث؟',
        'add_to_cart_btn': 'أضف إلى السلة',
        'product_nr': 'رقم',
        'wishlist_tooltip': 'إضافة إلى المفضلة',
        'error_order': 'خطأ في إتمام الطلب',
        'technical_error': 'حدث خطأ تقني.',
        'wishlist_added': 'تمت الإضافة للمفضلة ❤️',
        'wishlist_removed': 'تمت الإزالة من المفضلة',
        'loading': 'جاري التحميل...',
        'misc': 'متنوع',
        'feedback_label': 'ملاحظات العملاء',
        'feedback_title': 'ماذا يقول عملاؤنا',
        'feedback_subtitle': 'رضاكم هو أكبر حافز لنا. شاركنا تجربتك!',
        'verified_buyer': 'مشتري مؤكد',
        'leave_feedback': 'أضف تعليقك',
        'rating': 'التقييم',
        'message': 'رسالتك',
        'send_feedback': 'إرسال التعليق',
        'feedback_success': 'شكراً جزيلاً لتعليقك! نحن نقدر رأيك.',
        'feature_nav_title': 'تنقل واضح وبسيط',
        'feature_nav_desc': 'تجد بالضبط ما تبحث عنه في أسرع وقت.',
        'feature_shipping_title': 'شحن سريع',
        'feature_shipping_desc': 'طلبك جاهز للشحن خلال ٢٤ ساعة.',
        'feature_quality_title': 'أفضل جودة',
        'feature_quality_desc': 'تخصصات شرقية طازجة، مختارة بعناية.',
        'feature_payment_title': 'دفع آمن',
        'feature_payment_desc': 'خيارات دفع متنوعة وآمنة.',
        'try_other_filter': 'للأسف، لم نتمكن من العثور على أي منتجات تطابق معايير بحثك. جرب عوامل تصفية أخرى.'
    }
};

// Category mapping for localized display (legacy support)
const categoryMap = translations['de'];

// Render categories (search dropdown and sidebar)
function renderCategories() {
    const searchCatList = document.getElementById('search-cat-list');
    const sidebarCatList = document.getElementById('sidebar-categories');
    const mobileCatList = document.getElementById('mobile-sidebar-categories');

    const categories = [...new Set(products.map(p => p.category))];
    const dict = translations[currentLanguage];

    if (searchCatList) {
        searchCatList.innerHTML = `
            <li><a class="dropdown-item" href="#" onclick="setSearchCategory('all')">${dict['all_categories']}</a></li>
            ${categories.map(cat => `
                <li><a class="dropdown-item" href="#" onclick="setSearchCategory('${cat}')">${dict[cat] || cat}</a></li>
            `).join('')}
        `;
    }

    if (sidebarCatList) {
        sidebarCatList.innerHTML = `
            <li class="category-item-ref" onclick="filterByCategory('all')">${dict['all_products']}</li>
            ${categories.map(cat => `
                <li class="category-item-ref" onclick="filterByCategory('${cat}')">${dict[cat] || cat}</li>
            `).join('')}
        `;
    }

    if (mobileCatList) {
        mobileCatList.innerHTML = `
            <li onclick="filterByCategory('all'); toggleMobileMenu()">${dict['all_products']}</li>
            ${categories.map(cat => `
                <li onclick="filterByCategory('${cat}'); toggleMobileMenu()">${dict[cat] || cat}</li>
            `).join('')}
        `;
    }
}

let selectedSearchCategory = 'all';
function setSearchCategory(category) {
    selectedSearchCategory = category;
    const btn = document.getElementById('searchCatBtn');
    const dict = translations[currentLanguage];
    if (btn) btn.innerText = category === 'all' ? dict['all_categories'] : (dict[category] || category);
}

function filterByCategory(category) {
    let filtered = products;

    if (category === 'all') {
        filtered = products;
    } else if (category === 'neu') {
        filtered = products.filter(p => p.id % 4 === 0);
    } else if (category === 'beliebt') {
        filtered = products.filter(p => p.id % 5 === 0);
    } else if (category === 'bestseller') {
        filtered = products.filter(p => p.price > 4);
    } else if (category === 'ausverkauft') {
        filtered = products.filter(p => p.id % 7 === 0);
    } else if (category === 'wishlist') {
        filtered = products.filter(p => wishlist.some(w => w == p.id));
    } else if (category === 'offers') {
        filtered = products.filter(p => parseInt(p.discount) > 0);
    } else {
        filtered = products.filter(p => p.category.toLowerCase() === category.toLowerCase());
    }

    renderProducts(filtered, category);
    goToSection('home-section');
}

function extractKeyword(name) {
    // 1. Remove weight info like (1kg)
    let clean = name.split('(')[0].trim();

    // 2. Specific multi-word keywords that should stay together
    const multiWords = ['Tomato Paste', 'Apple Vinegar', 'Stuffed Grape Leaves', 'Mixed Vegetables', 'Pomegranate Molasses', 'Arabic Bread', 'Oriental Sweets'];
    for (const mw of multiWords) {
        if (clean.toLowerCase().includes(mw.toLowerCase())) return mw;
    }

    // 3. For German/English: usually the last word is the main category if multi-word
    // e.g. "Ägyptischer Reis" -> "Reis"
    // For Arabic: usually the first word
    // e.g. "رز مصري" -> "رز"

    let words = clean.split(' ');
    if (currentLanguage === 'ar') {
        return words[0];
    } else {
        // Return last word if multiple, otherwise the word itself
        return words.length > 1 ? words[words.length - 1] : words[0];
    }
}

function filterByVariety(productName, event) {
    if (event) event.stopPropagation();

    const keyword = extractKeyword(productName);
    console.log(`Filtering for variety of: ${productName}, keyword: ${keyword}`);

    const filtered = products.filter(p => {
        const nameInLang = (p['name_' + currentLanguage] || p.name).toLowerCase();
        return nameInLang.includes(keyword.toLowerCase());
    });

    renderProducts(filtered);

    // Show a small UI hint about the filter
    const grid = document.getElementById('product-grid');
    if (grid && filtered.length > 0) {
        const filterInfo = document.createElement('div');
        filterInfo.className = 'col-12 mb-4 filter-active-hint';
        filterInfo.innerHTML = `
            <div class="alert alert-info d-flex justify-content-between align-items-center shadow-sm" style="border-radius: 15px; background: rgba(227, 242, 253, 0.9);">
                <span>
                    <span class="fs-5">🔍</span> 
                    Zeige alle Ergebnisse für: <strong>"${keyword}"</strong> (${filtered.length} Artikel)
                </span>
                <button class="btn btn-sm btn-outline-primary rounded-pill px-3" onclick="renderProducts()">Alle Produkte anzeigen</button>
            </div>
        `;
        grid.prepend(filterInfo);
    }

    goToSection('home-section');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}


// Cart management
function addToCart(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    const existingItem = cart.find(item => item.id == productId);
    if (existingItem) {
        existingItem.qty++;
    } else {
        cart.push({
            id: product.id,
            name: product.name,
            price: product.price,
            img: product.img,
            qty: 1
        });
    }

    saveCart();
    updateCartUI();

    // Automatically open cart and set auto-close timer
    const panel = document.getElementById('cart-panel');
    if (!panel.classList.contains('active')) {
        toggleCart();
    } else {
        // Reset timer if already open
        startCartTimer();
    }

    showToast(`${product.name} ${translations[currentLanguage]['added_to_cart']}`);
}

function startCartTimer() {
    clearTimeout(cartTimer);
    cartTimer = setTimeout(() => {
        const panel = document.getElementById('cart-panel');
        if (panel.classList.contains('active')) {
            toggleCart();
        }
    }, 3000);
}

function toggleCart() {
    const panel = document.getElementById('cart-panel');
    const backdrop = document.getElementById('cart-backdrop');

    clearTimeout(cartTimer);
    panel.classList.toggle('active');

    if (backdrop) {
        if (panel.classList.contains('active')) {
            backdrop.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
            startCartTimer(); // Start timer when opened
        } else {
            backdrop.classList.remove('active');
            document.body.style.overflow = '';
        }
    }
}

function setupCartBackdrop() {
    let backdrop = document.getElementById('cart-backdrop');
    let panel = document.getElementById('cart-panel');

    if (!backdrop) {
        backdrop = document.createElement('div');
        backdrop.id = 'cart-backdrop';
        backdrop.className = 'cart-backdrop';
        document.body.appendChild(backdrop);

        backdrop.addEventListener('click', () => {
            if (panel.classList.contains('active')) toggleCart();
        });
    }

    if (panel) {
        // Stop timer if user interacts with the cart
        panel.addEventListener('mouseenter', () => clearTimeout(cartTimer));
        panel.addEventListener('click', () => clearTimeout(cartTimer));
    }
}

function updateCartUI() {
    const itemsContainer = document.getElementById('cart-items');
    const totalEl = document.getElementById('cart-total');
    const countEl = document.getElementById('cart-count');

    if (itemsContainer) {
        itemsContainer.innerHTML = cart.map(item => `
            <div class="d-flex align-items-center mb-4 gap-3">
                <img src="${item.img}" alt="${item.name}" style="width: 70px; height: 70px; object-fit: contain; border-radius: 8px; background: #f1f8e9; border: 1px solid #eee;">
                <div class="flex-grow-1">
                    <div class="fw-bold d-block text-truncate" style="max-width: 180px;">${item.name}</div>
                    <div class="text-danger fw-bold">${parseFloat(item.price).toFixed(2)} €</div>
                    <div class="small text-muted">${translations[currentLanguage]['qty_label']}: ${item.qty}</div>
                </div>
                <button class="btn btn-sm btn-outline-light text-dark border-0" onclick="removeFromCart(${item.id})">🗑️</button>
            </div>
        `).join('');
    }

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    if (totalEl) totalEl.innerText = `${total.toFixed(2)} €`;
    if (countEl) countEl.innerText = cart.reduce((sum, item) => sum + item.qty, 0);

    updateCheckoutSummary();
}

function removeFromCart(productId) {
    cart = cart.filter(item => item.id != productId);
    saveCart();
    updateCartUI();
}

function clearCart() {
    if (confirm(translations[currentLanguage]['confirm_clear_cart'])) {
        cart = [];
        saveCart();
        updateCartUI();
    }
}

function saveCart() {
    localStorage.setItem('cart', JSON.stringify(cart));
}

// Order handling
function openCheckout() {
    const dict = translations[currentLanguage];
    if (cart.length === 0) {
        alert(dict['empty_cart_msg']);
        return;
    }
    const panel = document.getElementById('cart-panel');
    panel.classList.remove('active');
    const backdrop = document.getElementById('cart-backdrop');
    if (backdrop) backdrop.classList.remove('active');
    document.body.style.overflow = '';

    goToSection('checkout-section');
}

function updateCheckoutSummary() {
    const summaryItems = document.getElementById('order-summary-items');
    const summaryTotal = document.getElementById('order-total-value');

    if (!summaryItems) return;

    summaryItems.innerHTML = cart.map(item => `
        <div class="d-flex justify-content-between mb-2">
            <span>${item.name} <span class="text-muted">x${item.qty}</span></span>
            <span class="fw-medium">${(item.price * item.qty).toFixed(2)} €</span>
        </div>
    `).join('');

    const total = cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
    summaryTotal.innerText = `${total.toFixed(2)} €`;
}

async function submitOrder(event) {
    event.preventDefault();

    const orderData = {
        name: document.getElementById('cust-name').value,
        address: document.getElementById('cust-address').value,
        city: document.getElementById('cust-city').value,
        phone: document.getElementById('cust-phone').value,
        payment_method: document.getElementById('payment-method-select').value,
        total_amount: cart.reduce((sum, item) => sum + (item.price * item.qty), 0),
        items: cart.map(item => ({
            product_id: item.id,
            qty: item.qty,
            price: item.price
        }))
    };

    try {
        const response = await fetch('backend/api/place_order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(orderData)
        });
        const result = await response.json();

        if (result.success) {
            cart = [];
            saveCart();
            updateCartUI();
            window.location.href = `invoice.php?order_id=${result.order_id}`;
        } else {
            alert(translations[currentLanguage]['error_order'] + ': ' + result.error);
        }
    } catch (error) {
        console.error('Order error:', error);
        alert(translations[currentLanguage]['technical_error']);
    }
}

// Language management
function setLanguage(lang) {
    currentLanguage = lang;
    setCookie('lang', lang, 365); // 1 year using helper

    // Update active dropdown item
    document.documentElement.lang = lang;
    document.documentElement.dir = (lang === 'ar') ? 'rtl' : 'ltr';

    // Update UI components
    updateStaticText();
    updateLanguageSwitcherUI();
    loadProducts();
}

function updateStaticText() {
    const dict = translations[currentLanguage];
    if (!dict) return;
    
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (dict[key]) {
            el.innerHTML = dict[key];
        }
    });

    // Update search placeholder
    const searchInput = document.getElementById('main-search');
    if (searchInput && dict['search_placeholder']) {
        searchInput.placeholder = dict['search_placeholder'];
    }
}

function updateLanguageSwitcherUI() {
    const currentLangText = document.getElementById('current-lang-text');
    if (currentLangText) {
        const langMap = { 'de': 'Deutsch', 'en': 'English', 'ar': 'العربية' };
        currentLangText.innerText = langMap[currentLanguage];
    }
}

// Wishlist handling
function toggleWishlist(productId, event) {
    if (event) event.stopPropagation();

    const index = wishlist.findIndex(w => w == productId);
    const dict = translations[currentLanguage];
    if (index === -1) {
        wishlist.push(productId);
        showToast(dict['wishlist_added']);
    } else {
        wishlist.splice(index, 1);
        showToast(dict['wishlist_removed']);
    }

    localStorage.setItem('wishlist', JSON.stringify(wishlist));
    // Provide explicit category 'wishlist' context if applicable, otherwise 'all'.
    renderProducts(undefined, selectedSearchCategory || 'all');
    updateWishlistUI();

    // Update modal button if it's open for this product
    const modalWishlistBtn = document.getElementById('modal-wishlist-btn');
    if (modalWishlistBtn) {
        const isCurrentlyIn = wishlist.some(w => w == productId);
        modalWishlistBtn.innerText = isCurrentlyIn ? '❤️' : '♡';
        modalWishlistBtn.classList.toggle('btn-danger', isCurrentlyIn);
        modalWishlistBtn.classList.toggle('btn-outline-danger', !isCurrentlyIn);
    }
}

function updateWishlistUI() {
    // We could add a badge to the Lieblingsprodukte icon if desired
    // For now, let's just make sure the header link works
    const items = document.querySelectorAll('.action-item');
    items.forEach(item => {
        const labelEl = item.querySelector('.label');
        if (labelEl && (labelEl.getAttribute('data-i18n') === 'wishlist' || labelEl.innerText.includes('Lieblingsprodukte'))) {
            item.setAttribute('onclick', "filterByCategory('wishlist')");
        }
    });

    const wishlistBadge = document.getElementById('wishlist-count');
    if (wishlistBadge) {
        wishlistBadge.innerText = wishlist.length;
    }
}
function showToast(message) {
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.style.cssText = `
            position: fixed;
            top: 30px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 10000;
            display: flex;
            flex-direction: column;
            gap: 10px;
            pointer-events: none;
        `;
        document.body.appendChild(toastContainer);
    }

    const toast = document.createElement('div');
    toast.className = 'toast-notification';
    toast.style.cssText = `
        background: rgba(80, 130, 82, 0.66);
        color: white;
        padding: 12px 30px;
        border-radius: 50px;
        font-weight: 700;
        box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        backdrop-filter: blur(5px);
        animation: toast-in 0.4s cubic-bezier(0.18, 0.89, 0.32, 1.28), toast-out 0.4s 2.6s forwards;
        pointer-events: auto;
        white-space: nowrap;
        border: 1px solid rgba(255,255,255,0.2);
    `;
    toast.innerText = message;
    toastContainer.appendChild(toast);

    setTimeout(() => toast.remove(), 3000);
}

// Add CSS for toast animations if not present
if (!document.getElementById('toast-styles')) {
    const styleContent = `
        @keyframes toast-in { from { opacity: 0; transform: translateY(-20px) scale(0.9); } to { opacity: 1; transform: translateY(0) scale(1); } }
        @keyframes toast-out { from { opacity: 1; transform: translateY(0) scale(1); } to { opacity: 0; transform: translateY(-20px) scale(0.9); } }
    `;
    const styleEl = document.createElement('style');
    styleEl.id = 'toast-styles';
    styleEl.textContent = styleContent;
    document.head.appendChild(styleEl);
}

function openProductModal(productId) {
    const product = products.find(p => p.id == productId);
    if (!product) return;

    document.getElementById('modal-img').src = product.img;
    document.getElementById('modal-title').innerHTML = `
        ${product.name} <br>
        <small class="text-muted" style="font-family: Arial, sans-serif;">${product.name_ar}</small>
    `;
    document.getElementById('modal-price').innerText = `${parseFloat(product.price).toFixed(2)} €`;
    const dict = translations[currentLanguage];
    document.getElementById('modal-details-content').innerHTML = `
        <strong>${dict['category_label']}:</strong> ${dict[product.category] || product.category}<br>
        <strong>${dict['weight_label']}:</strong> ${formatWeightToKg(product.weight)}<br>
        <strong>${dict['expiry_label']}:</strong> ${product.expiry}<br>
        <div class="mt-2">
            <strong>${dict['description_label']}:</strong><br>
            ${product['description_' + currentLanguage] || dict['description_val']}
        </div>
    `;
    document.getElementById('modal-btn').innerText = dict['in_cart'] + ' ' + (dict['cart'] || 'Warenkorb');
    document.getElementById('modal-btn').onclick = () => {
        addToCart(product.id);
        const modal = bootstrap.Modal.getInstance(document.getElementById('product-modal'));
        if (modal) modal.hide();
    };

    const modalWishlistBtn = document.getElementById('modal-wishlist-btn');
    if (modalWishlistBtn) {
        const isCurrentlyIn = wishlist.some(w => w == product.id);
        modalWishlistBtn.innerText = isCurrentlyIn ? '❤️' : '♡';
        modalWishlistBtn.classList.toggle('btn-danger', isCurrentlyIn);
        modalWishlistBtn.classList.toggle('btn-outline-danger', !isCurrentlyIn);
        modalWishlistBtn.onclick = () => toggleWishlist(product.id);
    }

    const productModal = new bootstrap.Modal(document.getElementById('product-modal'));
    productModal.show();
}

function closeModal() {
    const modal = bootstrap.Modal.getInstance(document.getElementById('product-modal'));
    if (modal) modal.hide();
}

// Account view switching
function switchAccountView(view) {
    const loginForm = document.getElementById('login-form');
    const registerForm = document.getElementById('register-form');
    const loginTab = document.getElementById('login-tab');
    const registerTab = document.getElementById('register-tab');

    if (view === 'login') {
        loginForm.style.display = 'block';
        registerForm.style.display = 'none';
        loginTab.classList.add('active');
        registerTab.classList.remove('active');
    } else {
        loginForm.style.display = 'none';
        registerForm.style.display = 'block';
        loginTab.classList.remove('active');
        registerTab.classList.add('active');
    }
}

// Cookie Banner Logic
function initCookieBanner() {
    const banner = document.getElementById('cookie-banner');
    const cookieConsent = getCookie('cookie-consent');

    if (!cookieConsent && banner) {
        setTimeout(() => {
            banner.style.display = 'block';
        }, 1500);
    }
}

function showCookieBanner(e) {
    if (e) e.preventDefault();
    const banner = document.getElementById('cookie-banner');
    if (banner) {
        banner.style.display = 'block';
        banner.style.animation = 'slideInUp 0.5s cubic-bezier(0.19, 1, 0.22, 1)';
    }
}

function acceptAllCookies() {
    setCookie('cookie-consent', 'all', 365);
    hideCookieBanner();
    showToast('✅ Cookies akzeptiert');
}

function closeCookieBanner() {
    setCookie('cookie-consent', 'essential', 365);
    hideCookieBanner();
}

function hideCookieBanner() {
    const banner = document.getElementById('cookie-banner');
    if (banner) {
        banner.style.animation = 'slideOutDown 0.5s forwards';
        setTimeout(() => {
            banner.style.display = 'none';
        }, 500);
    }
}

// Add slideOutDown animation to styles if not already there
if (!document.getElementById('cookie-extra-styles')) {
    const style = document.createElement('style');
    style.id = 'cookie-extra-styles';
    style.textContent = `
        @keyframes slideOutDown {
            from { transform: translateY(0); opacity: 1; }
            to { transform: translateY(100%); opacity: 0; }
        }
    `;
    document.head.appendChild(style);
}

// Feedback Submission Logic
function handleFeedbackSubmission(event) {
    event.preventDefault();
    const name = document.getElementById('fb-name').value;
    const email = document.getElementById('fb-email').value;
    const rating = document.getElementById('fb-rating').value;
    const message = document.getElementById('fb-message').value;

    console.log('Feedback received:', { name, email, rating, message });

    // Show success message
    const successMsg = document.getElementById('feedback-success');
    const form = document.getElementById('feedback-form');
    
    if (successMsg && form) {
        form.reset();
        successMsg.classList.remove('d-none');
        
        // Use existing showToast for extra feedback
        const dict = translations[currentLanguage];
        if (typeof showToast === 'function') {
            showToast('✅ ' + dict['feedback_success']);
        }
        
        // Hide success alert after 5 seconds
        setTimeout(() => {
            successMsg.classList.add('d-none');
        }, 5000);
    }
}
