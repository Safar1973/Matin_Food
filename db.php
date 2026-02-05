<?php
// Robust Database Connection
mysqli_report(MYSQLI_REPORT_OFF);

$user = "root";
$pass = "";
$db   = "matin_food";
$port = 3306;

// Try 1: IPv4 Loopback
$host = "127.0.0.1";
$conn = @mysqli_connect($host, $user, $pass, $db, $port);

// Try 2: Localhost (Named Pipes/Socket)
if (!$conn) {
    $host = "localhost";
    $conn = @mysqli_connect($host, $user, $pass, $db, $port);
}

// Try 3: IPv6 Loopback
if (!$conn) {
    $host = "::1";
    $conn = @mysqli_connect($host, $user, $pass, $db, $port);
}

// Final Check
if (!$conn) {
    $error = mysqli_connect_error();
    die("<div style='background:#f8d7da; color:#721c24; padding:20px; text-align:center; font-family:sans-serif;'>
            <h3>Datenbank-Verbindungsfehler</h3>
            <p>Es konnte keine Verbindung zu MySQL hergestellt werden.</p>
            <p><strong>Mögliche Lösungen:</strong></p>
            <ul style='text-align:left; display:inline-block;'>
                <li>Prüfen Sie, ob <strong>Apache</strong> und <strong>MySQL</strong> im XAMPP Control Panel gestartet sind (Grün).</li>
                <li>Prüfen Sie, ob Port 3306 von einer Firewall blockiert wird.</li>
            </ul>
            <br><small>Technischer Fehler: $error</small>
         </div>");
}

mysqli_set_charset($conn, "utf8mb4");

// Helper for PDO (used in setup scripts)
try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    // Fallback to localhost for PDO
    try {
        $pdo = new PDO("mysql:host=localhost;dbname=$db;charset=utf8mb4", $user, $pass);
    } catch (PDOException $e2) {
        // Silent fail for PDO if main connection worked, relying on mysqli for now
    }
}
?>
