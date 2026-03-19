<?php
include_once dirname(__FILE__) . "/../db.php";

function generateWeeklyMHDReport($conn) {
    $today = date('Y-m-d');
    $next30Days = date('Y-m-d', strtotime('+30 days'));
    
    // Fetch products expiring within the next 30 days or already expired
    $query = "SELECT * FROM products WHERE expiry <= '$next30Days' ORDER BY expiry ASC";
    $result = mysqli_query($conn, $query);
    
    if (!$result) return false;
    
    $items = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $items[] = $row;
    }
    
    if (empty($items)) {
        // Still generate a report saying everything is OK?
        // Let's at least log it.
    }
    
    $timestamp = date('Y-m-d_H-i-s');
    $reportFilename = "mhd_report_$timestamp.html";
    $reportPath = dirname(__FILE__) . "/reports/" . $reportFilename;
    
    $html = "
    <!DOCTYPE html>
    <html lang='de'>
    <head>
        <meta charset='UTF-8'>
        <style>
            body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; color: #333; line-height: 1.6; padding: 40px; }
            .header { border-bottom: 2px solid #2e7d32; padding-bottom: 20px; margin-bottom: 30px; }
            h1 { color: #2e7d32; margin: 0; }
            .date { color: #666; font-size: 0.9em; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { padding: 12px; text-align: left; border-bottom: 1px solid #eee; }
            th { background-color: #f8f9fa; color: #2e7d32; font-weight: bold; }
            .expired { color: #d32f2f; font-weight: bold; }
            .soon { color: #f57c00; font-weight: bold; }
            .ok { color: #2e7d32; }
            .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8em; font-weight: bold; }
            .badge-danger { background-color: #ffebee; color: #d32f2f; }
            .badge-warning { background-color: #fff3e0; color: #f57c00; }
            .no-print { margin-top: 30px; }
            .btn { background: #2e7d32; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        </style>
        <title>Wöchentlicher MHD-Report - " . date('d.m.Y') . "</title>
    </head>
    <body>
        <div class='header'>
            <h1>Matin Food: Wöchentlicher MHD-Report</h1>
            <div class='date'>Erstellt am: " . date('d.m.Y H:i') . "</div>
        </div>
        
        <p>Dieser Bericht enthält alle Produkte, die bereits abgelaufen sind oder innerhalb der nächsten 30 Tage ablaufen werden.</p>
        
        <table>
            <thead>
                <tr>
                    <th>Produkt</th>
                    <th>Bestand</th>
                    <th>Ablaufdatum (MHD)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>";
    
    foreach ($items as $item) {
        $statusClass = "";
        $statusText = "";
        $badgeClass = "";
        
        if ($item['expiry'] < $today) {
            $statusClass = "expired";
            $statusText = "Abgelaufen";
            $badgeClass = "badge-danger";
        } else {
            $statusClass = "soon";
            $statusText = "Läuft bald ab";
            $badgeClass = "badge-warning";
        }
        
        $html .= "
                <tr>
                    <td>
                        <strong>" . htmlspecialchars($item['name_de']) . "</strong><br>
                        <small>" . htmlspecialchars($item['category']) . "</small>
                    </td>
                    <td>" . $item['stock'] . "</td>
                    <td class='$statusClass'>" . date('d.m.Y', strtotime($item['expiry'])) . "</td>
                    <td><span class='badge $badgeClass'>$statusText</span></td>
                </tr>";
    }
    
    if (empty($items)) {
        $html .= "<tr><td colspan='4' style='text-align:center;'>Keine kritischen MHD-Daten gefunden. Alles im grünen Bereich!</td></tr>";
    }
    
    $html .= "
            </tbody>
        </table>
        
        <div class='no-print'>
            <a href='../index.php' class='btn'>Zurück zur Lagerverwaltung</a>
            <button onclick='window.print()' class='btn' style='background:#666; margin-left: 10px;'>Bericht drucken</button>
        </div>
    </body>
    </html>";
    
    file_put_contents($reportPath, $html);
    
    // Log the run
    file_put_contents(dirname(__FILE__) . "/last_report_run.txt", date('Y-m-d'));
    
    return $reportFilename;
}

// Check if we should generate the report
$lastRunFile = dirname(__FILE__) . "/last_report_run.txt";
$shouldRun = false;

if (!file_exists($lastRunFile)) {
    $shouldRun = true;
} else {
    $lastRun = trim(file_get_contents($lastRunFile));
    $lastRunTime = strtotime($lastRun);
    $oneWeekAgo = strtotime('-7 days');
    
    if ($lastRunTime <= $oneWeekAgo) {
        $shouldRun = true;
    }
}

if ($shouldRun) {
    generateWeeklyMHDReport($conn);
}
