<?php
include "../db.php";
$res = mysqli_query($conn, "SELECT * FROM products");
?>
<h2>لوحة التحكم – المنتجات</h2>
<table border="1">
<tr><th>ID</th><th>Name</th><th>Price</th><th>Stock</th></tr>
<?php while ($p = mysqli_fetch_assoc($res)) { ?>
<tr>
  <td><?= $p["id"] ?></td>
  <td><?= $p["name"] ?></td>
  <td><?= $p["price"] ?></td>
  <td><?= $p["stock"] ?></td>
</tr>
<?php } ?>
</table>
