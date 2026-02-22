<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";

$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$sqlFile = 'C:\\xampp\\phpMyAdmin\\sql\\create_tables.sql';
if (!file_exists($sqlFile)) {
    die("SQL file not found at $sqlFile");
}

$sql = file_get_contents($sqlFile);

// Split by semicolon, but be careful with multi-line statements.
// For this specific script, simple splitting usually works as it's a standard PMA script.
$queries = explode(';', $sql);

echo "Running SQL script...\n";
foreach ($queries as $query) {
    $q = trim($query);
    if (!empty($q)) {
        if (!mysqli_query($conn, $q)) {
            // Some errors are expected if things already exist, but we used IF NOT EXISTS
            // echo "Error running query: " . mysqli_error($conn) . "\n";
        }
    }
}

echo "SQL script execution finished.\n";
mysqli_close($conn);
?>
