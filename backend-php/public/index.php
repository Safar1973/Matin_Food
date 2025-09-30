<?php
declare(strict_types=1);
session_start();
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET,POST,OPTIONS");
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
if ($method === 'OPTIONS') { http_response_code(204); exit; }

$DB_PATH = __DIR__ . '/../data/products.db';
$DB_DIR = dirname($DB_PATH);
if (!is_dir($DB_DIR)) { mkdir($DB_DIR, 0777, true); }
$ADMIN_PASSWORD = getenv('ADMIN_PASSWORD') ?: 'admin123';

function db(): PDO {
  global $DB_PATH;
  static $pdo = null;
  if ($pdo === null) {
    $pdo = new PDO('sqlite:' . $DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
  }
  return $pdo;
}

function json_res($data, int $code=200) {
  header('Content-Type: application/json; charset=utf-8');
  http_response_code($code);
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function seed_if_empty() {
  $pdo = db();
  $pdo->exec("CREATE TABLE IF NOT EXISTS products (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    cat TEXT NOT NULL,
    name_en TEXT NOT NULL,
    name_ar TEXT NOT NULL,
    name_de TEXT NOT NULL,
    price REAL NOT NULL,
    stock INTEGER NOT NULL,
    expiry TEXT NOT NULL,
    image TEXT NOT NULL
  )");
  $count = (int)$pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
  if ($count === 0) {
    $seedPath = __DIR__ . '/../seed.json';
    $seed = json_decode(file_get_contents($seedPath), true);
    $stmt = $pdo->prepare("INSERT INTO products (cat,name_en,name_ar,name_de,price,stock,expiry,image) VALUES (?,?,?,?,?,?,?,?)");
    foreach ($seed as $row) {
      $stmt->execute([$row['cat'],$row['name_en'],$row['name_ar'],$row['name_de'],$row['price'],$row['stock'],$row['expiry'],$row['image']]);
    }
  }
}
seed_if_empty();

$req = $_SERVER["REQUEST_URI"] ?? "/";
$path = parse_url($req, PHP_URL_PATH);

if ($path === '/api/products') {
  $lang = $_GET['lang'] ?? 'ar';
  $cat = $_GET['cat'] ?? null;
  $q = $_GET['q'] ?? null;
  $pdo = db();
  $sql = "SELECT * FROM products";
  $params = [];
  if ($cat) { $sql .= " WHERE cat = ?"; $params[] = $cat; }
  $stmt = $pdo->prepare($sql);
  $stmt->execute($params);
  $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
  $out = [];
  foreach ($rows as $r) {
    $name = ($lang==='ar') ? $r['name_ar'] : (($lang==='de') ? $r['name_de'] : $r['name_en']);
    $out[] = ["id"=>(int)$r['id'],"cat"=>$r['cat'],"name"=>$name,
      "name_en"=>$r['name_en'],"name_ar"=>$r['name_ar'],"name_de"=>$r['name_de'],
      "price"=>(float)$r['price'],"stock"=>(int)$r['stock'],"expiry"=>$r['expiry'],"image"=>"/images/".$r['image']];
  }
  if ($q) {
    $ql = mb_strtolower($q, 'UTF-8');
    $out = array_values(array_filter($out, function($p) use ($ql) {
      return mb_strpos(mb_strtolower($p['name'],'UTF-8'), $ql) !== false
          || mb_strpos(mb_strtolower($p['name_en'],'UTF-8'), $ql) !== false
          || mb_strpos(mb_strtolower($p['name_ar'],'UTF-8'), $ql) !== false
          || mb_strpos(mb_strtolower($p['name_de'],'UTF-8'), $ql) !== false;
    }));
  }
  json_res($out);
}
if (preg_match('#^/api/products/(\d+)$#', $path, $m)) {
  $id = (int)$m[1];
  $lang = $_GET['lang'] ?? 'ar';
  $pdo = db();
  $stmt = $pdo->prepare("SELECT * FROM products WHERE id=?");
  $stmt->execute([$id]);
  $r = $stmt->fetch(PDO::FETCH_ASSOC);
  if (!$r) json_res(["error"=>"not_found"], 404);
  $name = ($lang==='ar') ? $r['name_ar'] : (($lang==='de') ? $r['name_de'] : $r['name_en']);
  $item = ["id"=>(int)$r['id'],"cat"=>$r['cat'],"name"=>$name,"name_en"=>$r['name_en'],"name_ar"=>$r['name_ar'],"name_de"=>$r['name_de'],
           "price"=>(float)$r['price'],"stock"=>(int)$r['stock'],"expiry"=>$r['expiry'],"image"=>"/images/".$r['image']];
  json_res($item);
}
if ($path === '/api/cart') {
  $cart = $_SESSION['cart'] ?? [];
  $pdo = db();
  $items = []; $subtotal = 0.0;
  foreach ($cart as $pid=>$qty) {
    $stmt = $pdo->prepare("SELECT id,name_ar,price,image FROM products WHERE id=?");
    $stmt->execute([(int)$pid]);
    if ($p = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $line_total = (float)$p['price'] * (int)$qty;
      $subtotal += $line_total;
      $items[] = ["id"=>(int)$p['id'],"name"=>$p['name_ar'],"price"=>(float)$p['price'],"qty"=>(int)$qty,"image"=>"/images/".$p['image'],"line_total"=>round($line_total,2)];
    }
  }
  $tax = round($subtotal*0.05,2); $delivery = ($subtotal<25)?3.0:0.0; $total = round($subtotal+$tax+$delivery,2);
  json_res(["items"=>$items,"subtotal"=>round($subtotal,2),"tax"=>$tax,"delivery_fee"=>$delivery,"total"=>$total]);
}
if ($path === '/api/cart/add' && $method==='POST') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $pid = strval($data['product_id'] ?? ''); $qty = intval($data['qty'] ?? 1);
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
  $_SESSION['cart'][$pid] = ($_SESSION['cart'][$pid] ?? 0) + $qty;
  json_res(["ok"=>true]);
}
if ($path === '/api/cart/update' && $method==='POST') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $pid = strval($data['product_id'] ?? ''); $qty = intval($data['qty'] ?? 1);
  if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];
  if ($qty <= 0) { unset($_SESSION['cart'][$pid]); } else { $_SESSION['cart'][$pid] = $qty; }
  json_res(["ok"=>true]);
}
if ($path === '/api/cart/checkout' && $method==='POST') {
  $data = json_decode(file_get_contents('php://input'), true) ?: [];
  $_SESSION['cart'] = [];
  json_res(["ok"=>true,"message"=>"Order placed","method"=>$data['method']??'cod',"address"=>$data['address']??null,"delivery_date"=>$data['delivery_date']??null]);
}

// Admin
if ($path === '/admin/login' && $method==='GET') {
  echo '<!doctype html><meta charset="utf-8"><form method="post" action="/admin/login" dir="rtl"><h3>تسجيل دخول المشرف — ماتين فود</h3><input type="password" name="password" placeholder="كلمة المرور"><button type="submit">دخول</button></form>'; exit;
}
if ($path === '/admin/login' && $method==='POST') {
  $pwd = $_POST['password'] ?? '';
  if ($pwd === $GLOBALS['ADMIN_PASSWORD']) { $_SESSION['is_admin'] = true; header('Location: /admin'); exit; }
  http_response_code(401); echo 'Unauthorized'; exit;
}
if ($path === '/admin/logout') { $_SESSION['is_admin'] = false; header('Location: /admin/login'); exit; }
if ($path === '/admin') {
  if (!($_SESSION['is_admin'] ?? false)) { header('Location: /admin/login'); exit; }
  $pdo = db(); $rows = $pdo->query("SELECT * FROM products")->fetchAll(PDO::FETCH_ASSOC);
  echo '<!doctype html><meta charset="utf-8"><h2>MATIN FOOD — Admin</h2><a href="/admin/logout">Logout</a>';
  echo '<h3>Add/Edit</h3><form method="post" action="/admin/save"><input name="id" placeholder="id (optional)">';
  echo '<input name="cat" placeholder="cat"><input name="name_en" placeholder="name_en"><input name="name_ar" placeholder="name_ar"><input name="name_de" placeholder="name_de">';
  echo '<input name="price" placeholder="price"><input name="stock" placeholder="stock"><input name="expiry" placeholder="YYYY-MM-DD"><input name="image" placeholder="image filename"><button>Save</button></form>';
  echo '<h3>Products</h3><table border="1" cellpadding="6"><tr><th>ID</th><th>cat</th><th>name_ar</th><th>price</th><th>stock</th><th>expiry</th><th>image</th><th>del</th></tr>';
  foreach ($rows as $r) {
    echo '<tr><td>'.$r['id'].'</td><td>'.$r['cat'].'</td><td>'.$r['name_ar'].'</td><td>'.$r['price'].'</td><td>'.$r['stock'].'</td><td>'.$r['expiry'].'</td><td>'.$r['image'].'</td>';
    echo '<td><form method="post" action="/admin/delete/'.$r['id'].'"><button>Delete</button></form></td></tr>';
  }
  echo '</table>'; exit;
}
if ($path === '/admin/save' && $method==='POST') {
  if (!($_SESSION['is_admin'] ?? false)) { http_response_code(401); echo 'Unauthorized'; exit; }
  $pdo = db();
  $id = $_POST['id'] ?? null;
  if ($id) {
    $stmt = $pdo->prepare("UPDATE products SET cat=?, name_en=?, name_ar=?, name_de=?, price=?, stock=?, expiry=?, image=? WHERE id=?");
    $stmt->execute([$_POST['cat'],$_POST['name_en'],$_POST['name_ar'],$_POST['name_de'],(float)$_POST['price'],(int)$_POST['stock'],$_POST['expiry'],$_POST['image'],(int)$id]);
  } else {
    $stmt = $pdo->prepare("INSERT INTO products (cat,name_en,name_ar,name_de,price,stock,expiry,image) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$_POST['cat'],$_POST['name_en'],$_POST['name_ar'],$_POST['name_de'],(float)$_POST['price'],(int)$_POST['stock'],$_POST['expiry'],$_POST['image']]);
  }
  header('Location: /admin'); exit;
}
if (preg_match('#^/admin/delete/(\d+)$#', $path, $m) && $method==='POST') {
  if (!($_SESSION['is_admin'] ?? false)) { http_response_code(401); echo 'Unauthorized'; exit; }
  $pdo = db(); $stmt = $pdo->prepare("DELETE FROM products WHERE id=?"); $stmt->execute([(int)$m[1]]);
  header('Location: /admin'); exit;
}
if (preg_match('#^/images/(.+)$#', $path, $m)) {
  $file = __DIR__ . '/images/' . basename($m[1]);
  if (is_file($file)) { $mime = mime_content_type($file); header("Content-Type: $mime"); readfile($file); exit; }
}
http_response_code(404); echo "Not Found";
