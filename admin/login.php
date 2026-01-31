<?php
session_start();
include "../db.php";

if ($_POST) {
  $u = $_POST["username"];
  $p = $_POST["password"];

  $res = mysqli_query($conn, "SELECT * FROM admins WHERE username='$u'");
  $admin = mysqli_fetch_assoc($res);

  if ($admin && password_verify($p, $admin["password"])) {
    $_SESSION["admin"] = true;
    header("Location: index.php");
    exit;
  }

  $error = "Login failed";
}
?>
<form method="post">
  <input name="username" placeholder="Username"><br>
  <input name="password" type="password" placeholder="Password"><br>
  <button>Login</button>
  <?= $error ?? "" ?>
</form>
