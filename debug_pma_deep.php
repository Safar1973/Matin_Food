<?php
$host = "127.0.0.1";
$root_user = "root";
$root_pass = "";

$conn = @mysqli_connect($host, $root_user, $root_pass);
if (!$conn) {
    die("Root connection failed: " . mysqli_connect_error());
}

$db = "phpmyadmin";
mysqli_select_db($conn, $db);

echo "--- Table Check ---\n";
$res = mysqli_query($conn, "SHOW TABLES");
$existing_tables = [];
while($row = mysqli_fetch_row($res)) {
    $existing_tables[] = $row[0];
}

$config_tables = [
    'pma__bookmark', 'pma__relation', 'pma__table_info', 'pma__table_coords',
    'pma__pdf_pages', 'pma__column_info', 'pma__history',
    'pma__tracking', 'pma__userconfig', 'pma__recent', 'pma__table_uiprefs',
    'pma__users', 'pma__usergroups', 'pma__navigationhiding', 'pma__savedsearches',
    'pma__central_columns', 'pma__designer_settings', 'pma__export_templates', 'pma__favorite'
];

foreach ($config_tables as $table) {
    if (in_array($table, $existing_tables)) {
        echo "[OK] $table exists.\n";
    } else {
        echo "[MISSING] $table is missing!\n";
    }
}

echo "\n--- Control User Check ---\n";
$pma_user_res = mysqli_query($conn, "SELECT User, Host FROM mysql.user WHERE User = 'pma'");
if (mysqli_num_rows($pma_user_res) == 0) {
    echo "[FAIL] User 'pma' does not exist in mysql.user.\n";
} else {
    while($row = mysqli_fetch_assoc($pma_user_res)) {
        echo "[INFO] Found user 'pma'@'{$row['Host']}'.\n";
    }
}

echo "\n--- Attempting Connection as 'pma' ---\n";
$pma_conn = @mysqli_connect($host, "pma", "");
if (!$pma_conn) {
    echo "[FAIL] Could not connect as 'pma'@'$host' with empty password: " . mysqli_connect_error() . "\n";
} else {
    echo "[SUCCESS] Connected as 'pma'.\n";
    if (mysqli_select_db($pma_conn, $db)) {
        echo "[SUCCESS] 'pma' has access to '$db' database.\n";
        // Check if pma can see tables
        $t_res = mysqli_query($pma_conn, "SHOW TABLES");
        $t_count = mysqli_num_rows($t_res);
        echo "[INFO] 'pma' sees $t_count tables in '$db'.\n";
    } else {
        echo "[FAIL] 'pma' does NOT have access to '$db' database: " . mysqli_error($pma_conn) . "\n";
    }
    mysqli_close($pma_conn);
}

mysqli_close($conn);
?>
