<?php
/**
 * admin.php - Панель администратора
 * 
 * HTTP-авторизация, просмотр всех данных пользователей,
 * редактирование, удаление, статистика по языкам
 */

// ============================================
// ПОДКЛЮЧЕНИЕ К БАЗЕ ДАННЫХ
// ============================================

$db_user = 'u82669';
$db_pass = '9085380'; 

try {
    $db = new PDO('mysql:host=localhost;dbname=u82669', $db_user, $db_pass,
        [PDO::ATTR_PERSISTENT => true, PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) {
    die("Ошибка подключения к БД: " . $e->getMessage());
}

// ============================================
// 1. HTTP-АВТОРИЗАЦИЯ (проверка в БД)
// ============================================

$auth_login = $_SERVER['PHP_AUTH_USER'] ?? '';
$auth_pass = $_SERVER['PHP_AUTH_PW'] ?? '';

// Ищем администратора в таблице admin
$stmt = $db->prepare("SELECT * FROM admin WHERE login = ? AND password_hash = ?");
$stmt->execute([$auth_login, md5($auth_pass)]);
$admin = $stmt->fetch();

// Если не найден - показываем окно авторизации
if (empty($admin)) {
    header('HTTP/1.1 401 Unauthorized');
    header('WWW-Authenticate: Basic realm="Admin Panel"');
    echo '<h1>401 Требуется авторизация</h1>';
    echo '<p>Доступ разрешён только администратору.</p>';
    exit();
}

// ============================================
// 2. ОБРАБОТКА ДЕЙСТВИЙ (удаление и редактирование)
// ============================================

// УДАЛЕНИЕ записи
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $delete_id = (int)$_GET['delete'];
    
    // Удаляем связанные языки
    $stmt = $db->prepare("DELETE FROM user_languages WHERE user_id = ?");
    $stmt->execute([$delete_id]);
    
    // Удаляем пользователя
    $stmt = $db->prepare("DELETE FROM users WHERE id = ?");
    $stmt->execute([$delete_id]);
    
    header('Location: admin.php?message=deleted');
    exit();
}

// РЕДАКТИРОВАНИЕ записи (POST запрос из формы)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['edit_id'])) {
    $edit_id = (int)$_POST['edit_id'];
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $birth_date = $_POST['birth_date'];
    $gender = $_POST['gender'];
    $biography = trim($_POST['biography']);
    $agreed = isset($_POST['agreed']) ? 1 : 0;
    $languages = $_POST['languages'] ?? [];
    
    // Простая проверка на заполненность
    $errors = false;
    if (empty($full_name)) $errors = true;
    if (empty($phone)) $errors = true;
    if (empty($email)) $errors = true;
    if (empty($birth_date)) $errors = true;
    if (empty($gender)) $errors = true;
    if (empty($languages)) $errors = true;
    
    if (!$errors) {
        // Обновляем основные данные
        $stmt = $db->prepare("UPDATE users SET 
            full_name = ?, phone = ?, email = ?, birth_date = ?, 
            gender = ?, biography = ?, agreed = ? 
            WHERE id = ?");
        $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $agreed, $edit_id]);
        
        // Обновляем языки (удаляем старые и вставляем новые)
        $stmt_del = $db->prepare("DELETE FROM user_languages WHERE user_id = ?");
        $stmt_del->execute([$edit_id]);
        
        $stmt_lang = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $stmt_insert = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
        
        foreach ($languages as $lang_name) {
            $stmt_lang->execute([$lang_name]);
            $lang_id = $stmt_lang->fetchColumn();
            if ($lang_id) {
                $stmt_insert->execute([$edit_id, $lang_id]);
            }
        }
        
        header('Location: admin.php?message=updated');
        exit();
    }
}

// ============================================
// 3. ПОЛУЧЕНИЕ ВСЕХ ДАННЫХ ДЛЯ ТАБЛИЦЫ
// ============================================

// Получаем всех пользователей
$stmt = $db->query("SELECT * FROM users ORDER BY id DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Для каждого получаем выбранные языки
foreach ($users as &$user) {
    $stmt_lang = $db->prepare("SELECT pl.name FROM user_languages ul 
                               JOIN programming_languages pl ON ul.language_id = pl.id 
                               WHERE ul.user_id = ?");
    $stmt_lang->execute([$user['id']]);
    $langs = $stmt_lang->fetchAll(PDO::FETCH_COLUMN);
    $user['languages'] = implode(', ', $langs);
}

// ============================================
// 4. СТАТИСТИКА ПО ЯЗЫКАМ
// ============================================

// Количество пользователей по каждому языку
$stmt_stats = $db->query("
    SELECT pl.name, COUNT(ul.user_id) as count 
    FROM programming_languages pl
    LEFT JOIN user_languages ul ON pl.id = ul.language_id
    GROUP BY pl.id
    ORDER BY count DESC, pl.name
");
$language_stats = $stmt_stats->fetchAll(PDO::FETCH_ASSOC);

// Общее количество пользователей
$stmt_total = $db->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'];

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Панель администратора</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
        }
        h1, h2 {
            text-align: center;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
            vertical-align: top;
        }
        th {
            background: #333;
            color: white;
        }
        tr:nth-child(even) {
            background: #f9f9f9;
        }
        .actions a {
            margin-right: 10px;
            text-decoration: none;
            padding: 3px 8px;
            border-radius: 3px;
        }
        .edit-btn {
            background: #4CAF50;
            color: white;
        }
        .delete-btn {
            background: #f44336;
            color: white;
        }
        .stats {
            margin: 20px 0;
            padding: 15px;
            background: #eef;
            border: 1px solid #99c;
        }
        .stats ul {
            list-style: none;
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        .stats li {
            background: white;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .edit-form {
            background: #f0f0f0;
            padding: 15px;
            margin: 20px 0;
            border: 1px solid #ccc;
            display: none;
        }
        .edit-form.active {
            display: block;
        }
        .form-group {
            margin-bottom: 10px;
        }
        .form-group label {
            display: inline-block;
            width: 150px;
            font-weight: bold;
        }
        select[multiple] {
            width: 200px;
            height: 100px;
        }
        .back-link {
            text-align: center;
            margin-top: 20px;
        }
    </style>
    <script>
        function showEditForm(id) {
            // Скрываем все формы
            document.querySelectorAll('.edit-form').forEach(form => {
                form.classList.remove('active');
            });
            // Показываем выбранную форму
            const form = document.getElementById('edit-form-' + id);
            if (form) {
                form.classList.add('active');
            }
        }
    </script>
</head>
<body>
<div class="container">
    <h1>Панель администратора</h1>
    
    <!-- Сообщения -->
    <?php if (isset($_GET['message'])): ?>
        <?php if ($_GET['message'] == 'deleted'): ?>
            <div class="message success">Запись успешно удалена.</div>
        <?php elseif ($_GET['message'] == 'updated'): ?>
            <div class="message success">Данные успешно обновлены.</div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Статистика -->
    <div class="stats">
        <h2>Статистика по языкам программирования</h2>
        <p><strong>Всего пользователей:</strong> <?= $total_users ?></p>
        <ul>
            <?php foreach ($language_stats as $stat): ?>
                <li>
                    <strong><?= htmlspecialchars($stat['name']) ?>:</strong> 
                    <?= $stat['count'] ?> пользователей
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
    
    <!-- Таблица -->
    <h2>Все записи пользователей</h2>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>ФИО</th>
                <th>Телефон</th>
                <th>Email</th>
                <th>Дата рождения</th>
                <th>Пол</th>
                <th>Языки</th>
                <th>Биография</th>
                <th>Согласие</th>
                <th>Дата создания</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="11" style="text-align: center;">Нет данных</td>
                </tr>
            <?php else: ?>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['full_name']) ?></td>
                        <td><?= htmlspecialchars($user['phone']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['birth_date']) ?></td>
                        <td><?= $user['gender'] == 'male' ? 'Мужской' : 'Женский' ?></td>
                        <td><?= htmlspecialchars($user['languages']) ?></td>
                        <td><?= htmlspecialchars(substr($user['biography'] ?? '', 0, 50)) ?>...</td>
                        <td><?= $user['agreed'] ? 'Да' : 'Нет' ?></td>
                        <td><?= $user['created_at'] ?></td>
                        <td class="actions">
                            <a href="javascript:void(0)" onclick="showEditForm(<?= $user['id'] ?>)" class="edit-btn">Редактировать</a>
                            <a href="admin.php?delete=<?= $user['id'] ?>" onclick="return confirm('Удалить запись?')" class="delete-btn">Удалить</a>
                        </td>
                    </tr>
                    
                    <!-- Форма редактирования -->
                    <tr id="edit-form-<?= $user['id'] ?>" class="edit-form">
                        <td colspan="11">
                            <h3>Редактирование записи ID <?= $user['id'] ?></h3>
                            <form method="POST">
                                <input type="hidden" name="edit_id" value="<?= $user['id'] ?>">
                                
                                <div class="form-group">
                                    <label>ФИО:</label>
                                    <input type="text" name="full_name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Телефон:</label>
                                    <input type="text" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Email:</label>
                                    <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Дата рождения:</label>
                                    <input type="date" name="birth_date" value="<?= $user['birth_date'] ?>" required>
                                </div>
                                
                                <div class="form-group">
                                    <label>Пол:</label>
                                    <select name="gender" required>
                                        <option value="male" <?= $user['gender'] == 'male' ? 'selected' : '' ?>>Мужской</option>
                                        <option value="female" <?= $user['gender'] == 'female' ? 'selected' : '' ?>>Женский</option>
                                    </select>
                                </div>
                                
                                <div class="form-group">
                                    <label>Языки:</label>
                                    <select name="languages[]" multiple required>
                                        <?php
                                        $all_langs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                                        $user_langs = explode(', ', $user['languages']);
                                        foreach ($all_langs as $lang): ?>
                                            <option value="<?= $lang ?>" <?= in_array($lang, $user_langs) ? 'selected' : '' ?>>
                                                <?= $lang ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small>Удерживайте Ctrl для выбора нескольких</small>
                                </div>
                                
                                <div class="form-group">
                                    <label>Биография:</label>
                                    <textarea name="biography" rows="3"><?= htmlspecialchars($user['biography'] ?? '') ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <label>Согласие:</label>
                                    <input type="checkbox" name="agreed" value="1" <?= $user['agreed'] ? 'checked' : '' ?>>
                                </div>
                                
                                <button type="submit">Сохранить изменения</button>
                                <button type="button" onclick="showEditForm(0)">Отмена</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
    
    <div class="back-link">
        <a href="index.php">Вернуться к форме регистрации</a>
    </div>
</div>
</body>
</html>