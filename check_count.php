<?php
$conn = mysqli_connect('localhost', 'root', '', 'matin_food');
if (!$conn) {
    die('Connection failed: ' . mysqli_connect_error());
}
$res = mysqli_query($conn, 'SELECT COUNT(*) as cnt FROM products');
if ($res) {
    $row = mysqli_fetch_assoc($res);
    echo 'Current product count: ' . $row['cnt'];
} else {
    echo 'Query failed: ' . mysqli_error($conn);
}
?>
