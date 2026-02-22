<?php
$host = "127.0.0.1";
$root_user = "root";
$root_pass = "";

$conn = @mysqli_connect($host, $root_user, $root_pass);
if (!$conn) {
    die("Root connection failed: " . mysqli_connect_error());
}

echo "--- Grants for 'pma' ---\n";
$res = mysqli_query($conn, "SELECT DISTINCT User, Host FROM mysql.user WHERE User='pma'");
while ($row = mysqli_fetch_assoc($res)) {
    $u = $row['User'];
    $h = $row['Host'];
    echo "User: '$u'@'$h'\n";
    $grant_res = mysqli_query($conn, "SHOW GRANTS FOR '$u'@'$h'");
    while ($g_row = mysqli_fetch_row($grant_res)) {
        echo "  " . $g_row[0] . "\n";
    }
}

mysqli_close($conn);
?>
