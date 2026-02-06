<?php
include "db.php";

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    die("Ungültige Bestellnummer.");
}

// Fetch order details
$order_query = "SELECT * FROM orders WHERE id = $order_id";
$order_result = mysqli_query($conn, $order_query);
$order = mysqli_fetch_assoc($order_result);

if (!$order) {
    die("Bestellung nicht gefunden.");
}

// Fetch order items
$items_query = "SELECT oi.*, p.name_de FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE oi.order_id = $order_id";
$items_result = mysqli_query($conn, $items_query);
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rechnung #<?php echo $order_id; ?> - Matin Food</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Inter', sans-serif;
        }
        .invoice-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 40px;
            margin-top: 50px;
            margin-bottom: 50px;
        }
        .invoice-header {
            border-bottom: 2px solid #f0f0f0;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .logo {
            font-size: 28px;
            font-weight: 800;
            color: #2e7d32; /* Primary Green */
            text-decoration: none;
        }
        .invoice-title {
            color: #2e7d32;
            font-weight: 800;
        }
        .table thead {
            background-color: #f1f8e9; /* Light Green */
        }
        .total-row {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2e7d32;
            background-color: #fffde7; /* Soft Yellow */
        }
        .btn-primary {
            background-color: #2e7d32 !important;
            border-color: #2e7d32 !important;
        }
        @media print {
            .no-print {
                display: none;
            }
            .invoice-card {
                box-shadow: none;
                margin-top: 0;
                padding: 0;
            }
            body {
                background-color: white;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="invoice-card">
                <div class="invoice-header d-flex justify-content-between align-items-center">
                    <div>
                        <a href="index.html">
                            <img src="images/logo-1694787094.jpg" alt="Matin Food" style="height: 50px; border-radius: 8px;">
                        </a>
                        <p class="text-muted mb-0">Frische und hochwertige Produkte</p>
                    </div>
                    <div class="text-end">
                        <h2 class="invoice-title mb-0">RECHNUNG</h2>
                        <p class="text-muted">#<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></p>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-sm-6">
                        <h6 class="mb-3 text-muted">Rechnung an:</h6>
                        <div><strong><?php echo htmlspecialchars($order['customer_name']); ?></strong></div>
                        <div><?php echo htmlspecialchars($order['address']); ?></div>
                        <div><?php echo htmlspecialchars($order['city']); ?></div>
                        <div>Tel: <?php echo htmlspecialchars($order['phone']); ?></div>
                    </div>
                    <div class="col-sm-6 text-sm-end">
                        <h6 class="mb-3 text-muted">Details:</h6>
                        <div>Bestelldatum: <?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></div>
                        <div>Zahlungsmethode: <?php echo htmlspecialchars($order['payment_method']); ?></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-borderless">
                        <thead>
                            <tr class="border-bottom">
                                <th>Produkt</th>
                                <th class="text-center">Menge</th>
                                <th class="text-end">Einzelpreis</th>
                                <th class="text-end">Gesamt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($item = mysqli_fetch_assoc($items_result)): ?>
                            <tr class="border-bottom">
                                <td><?php echo htmlspecialchars($item['name_de']); ?></td>
                                <td class="text-center"><?php echo $item['quantity']; ?></td>
                                <td class="text-end"><?php echo number_format($item['price_at_purchase'], 2, ',', '.'); ?> €</td>
                                <td class="text-end"><?php echo number_format($item['quantity'] * $item['price_at_purchase'], 2, ',', '.'); ?> €</td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-5 ms-sm-auto">
                        <table class="table table-clear">
                            <tbody>
                                <tr>
                                    <td class="left"><strong>Zwischensumme</strong></td>
                                    <td class="text-end"><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> €</td>
                                </tr>
                                <tr>
                                    <td class="left"><strong>Versand</strong></td>
                                    <td class="text-end">0,00 €</td>
                                </tr>
                                <tr class="total-row">
                                    <td class="left"><strong>Gesamtbetrag</strong></td>
                                    <td class="text-end"><?php echo number_format($order['total_amount'], 2, ',', '.'); ?> €</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="text-center mt-5 no-print">
                    <button onclick="window.print()" class="btn btn-primary px-4 py-2 me-2">Drucken / PDF speichern</button>
                    <a href="index.html" class="btn btn-outline-secondary px-4 py-2">Zurück zum Shop</a>
                </div>
            </div>
            
            <div class="text-center text-muted mb-5 small no-print d-flex align-items-center justify-content-center gap-2">
                Vielen Dank für Ihren Einkauf bei <img src="images/logo-1694787094.jpg" alt="Logo" style="height: 20px; border-radius: 4px;">!
            </div>
        </div>
    </div>
</div>

</body>
</html>
