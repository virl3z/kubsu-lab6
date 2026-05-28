<?php
session_start();
header('Content-Type: text/html; charset=UTF-8');

// Подключение к БД
$db_user = 'u82669';
$db_pass = '9085380';
try {
    $db = new PDO('mysql:host=localhost;dbname=u82669', $db_user, $db_pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Ошибка: " . $e->getMessage());
}

// Выход
if (isset($_GET['logout'])) {
    session_destroy();
    header('Location: index.php');
    exit();
}

// Если уже авторизован
if (!empty($_SESSION['login'])) {
    header('Location: index.php');
    exit();
}

// Форма входа
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Вход</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
            .container { max-width: 400px; margin: 50px auto; background: white; padding: 20px; border: 1px solid #ccc; }
            h1 { font-size: 1.5em; text-align: center; }
            .form-group { margin-bottom: 15px; }
            label { display: block; font-weight: bold; margin-bottom: 5px; }
            input { width: 100%; padding: 5px; border: 1px solid #ccc; }
            button { background: #333; color: white; border: none; padding: 8px 15px; cursor: pointer; width: 100%; }
            button:hover { background: #555; }
            .error { background: #ffdddd; color: red; padding: 5px 10px; margin-bottom: 10px; border: 1px solid red; }
            .back-link { text-align: center; margin-top: 15px; }
            a { color: #333; }
        </style>
    </head>
    <body>
    <div class="container">
        <h1>Вход</h1>
        <?php if (isset($_GET['error'])): ?>
            <div class="error">Неверный логин или пароль</div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Логин</label>
                <input type="text" name="login" required>
            </div>
            <div class="form-group">
                <label>Пароль</label>
                <input type="password" name="pass" required>
            </div>
            <button type="submit">Войти</button>
        </form>
        <div class="back-link">
            <a href="index.php">← Назад</a>
        </div>
    </div>
    </body>
    </html>
    <?php
    exit();
}

// Проверка логина
$login = trim($_POST['login'] ?? '');
$pass = trim($_POST['pass'] ?? '');

if (empty($login) || empty($pass)) {
    header('Location: login.php?error=1');
    exit();
}

$stmt = $db->prepare("SELECT * FROM users_auth WHERE login = ?");
$stmt->execute([$login]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user && $user['password_hash'] == md5($pass)) {
    $_SESSION['login'] = $user['login'];
    $_SESSION['uid'] = $user['id'];
    header('Location: index.php');
    exit();
} else {
    header('Location: login.php?error=1');
    exit();
}
?>