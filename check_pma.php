<?php
$host = "127.0.0.1";
$user = "root";
$pass = "";

$conn = @mysqli_connect($host, $user, $pass);
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

$db = "phpmyadmin";
$result = mysqli_query($conn, "SHOW DATABASES LIKE '$db'");
if (mysqli_num_rows($result) == 0) {
    echo "Database '$db' does NOT exist.\n";
} else {
    echo "Checking tables...\n";
    mysqli_select_db($conn, $db);
    $tables = [
        'pma__bookmark', 'pma__relation', 'pma__table_info', 'pma__table_coords',
        'pma__pdf_pages', 'pma__column_info', 'pma__history', 'pma__designer_coords',
        'pma__tracking', 'pma__userconfig', 'pma__recent', 'pma__table_uiprefs',
        'pma__users', 'pma__usergroups', 'pma__navigationhiding', 'pma__savedsearches',
        'pma__central_columns', 'pma__designer_settings', 'pma__export_templates', 'pma__favorite'
    ];
    
    foreach ($tables as $table) {
        $res = mysqli_query($conn, "SHOW TABLES LIKE '$table'");
        if (mysqli_num_rows($res) > 0) {
            echo "[OK] $table exists.\n";
        } else {
            echo "[MISSING] $table IS MISSING.\n";
        }
    }
}

// Check pma user
$user_res = mysqli_query($conn, "SELECT User FROM mysql.user WHERE User = 'pma'");
if (mysqli_num_rows($user_res) == 0) {
    echo "\nUSER_CHECK: User 'pma' does NOT exist.\n";
} else {
    echo "\nUSER_CHECK: User 'pma' exists.\n";
}

mysqli_close($conn);
?>
