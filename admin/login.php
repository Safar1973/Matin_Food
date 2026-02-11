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
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Matin Food</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #2e7d32;
            --accent: #fdd835;
            --bg-light: #f1f8e9;
        }
        body {
            font-family: 'Outfit', sans-serif;
            background-color: var(--bg-light);
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }
        .login-card {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
            border-top: 5px solid var(--primary);
        }
        .logo {
            font-size: 24px;
            font-weight: 800;
            color: var(--primary);
            margin-bottom: 20px;
            display: block;
        }
        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-sizing: border-box;
            font-family: inherit;
        }
        input:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
        }
        button {
            width: 100%;
            padding: 12px;
            background: var(--primary);
            color: white;
            border: none;
            border-radius: 8px;
            font-weight: 700;
            cursor: pointer;
            font-size: 16px;
            transition: background 0.3s;
        }
        button:hover {
            background: #1b5e20;
        }
        .error {
            color: #d32f2f;
            margin-top: 15px;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="logo">
        <img src="../images/logo-1694787094.jpg" alt="Logo" style="height: 70px; border-radius: 15px;">
    </div>
    <form method="post">
        <input name="username" placeholder="Benutzername" required>
        <input name="password" type="password" placeholder="Passwort" required>
        <button type="submit">Anmelden</button>
        <div class="error"><?= $error ?? "" ?></div>
    </form>
</div>

</body>
</html>
