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
    die("Ошибка подключения к БД: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $messages = array();

    if (!empty($_COOKIE['save'])) {
        setcookie('save', '', 100000);
        $messages[] = '<div class="success-message">Спасибо, результаты сохранены.</div>';
        
        if (!empty($_COOKIE['login']) && !empty($_COOKIE['pass'])) {
            $messages[] = sprintf('<div class="success-message">
                Ваш логин: <strong>%s</strong><br>
                Ваш пароль: <strong>%s</strong><br>
                Вы можете <a href="login.php">войти</a> для изменения данных.
            </div>', strip_tags($_COOKIE['login']), strip_tags($_COOKIE['pass']));
            setcookie('login', '', 100000);
            setcookie('pass', '', 100000);
        }
    }

    $errors = array();
    $values = array();

    $fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'languages', 'biography', 'agreed'];

    foreach ($fields as $field) {
        $errors[$field] = !empty($_COOKIE[$field . '_error']);
        $values[$field] = $_COOKIE[$field . '_value'] ?? '';
        
        if ($errors[$field]) {
            setcookie($field . '_error', '', 100000);
            setcookie($field . '_value', '', 100000);
        }
    }

    // Проверяем, авторизован ли пользователь
    $logged_in = false;
    if (!empty($_COOKIE[session_name()]) && isset($_SESSION['login'])) {
        $logged_in = true;
        $messages[] = '<div class="success-message">Вы вошли как: ' . htmlspecialchars($_SESSION['login']) . '. <a href="logout.php">Выйти</a></div>';
        
        // Загружаем данные пользователя из БД
        try {
            $stmt = $db->prepare("SELECT * FROM users WHERE user_id = ? ORDER BY id DESC LIMIT 1");
            $stmt->execute([$_SESSION['uid']]);
            $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user_data) {
                $values['full_name'] = $user_data['full_name'];
                $values['phone'] = $user_data['phone'];
                $values['email'] = $user_data['email'];
                $values['birth_date'] = $user_data['birth_date'];
                $values['gender'] = $user_data['gender'];
                $values['biography'] = $user_data['biography'];
                $values['agreed'] = $user_data['agreed'];
                
                // Загружаем языки
                $stmt_lang = $db->prepare("SELECT pl.name FROM user_languages ul 
                                           JOIN programming_languages pl ON ul.language_id = pl.id 
                                           WHERE ul.user_id = ?");
                $stmt_lang->execute([$user_data['id']]);
                $langs = $stmt_lang->fetchAll(PDO::FETCH_COLUMN);
                $values['languages'] = implode(',', $langs);
            }
        } catch (PDOException $e) {
            $messages[] = '<div class="error-message">Ошибка загрузки данных: ' . $e->getMessage() . '</div>';
        }
    }

    if ($errors['full_name']) {
        $messages[] = '<div class="error-message">Ошибка в поле "ФИО": ФИО должно содержать только буквы, пробелы и дефисы (не более 150 символов).</div>';
    }
    if ($errors['phone']) {
        $messages[] = '<div class="error-message">Ошибка в поле "Телефон": Телефон должен содержать только цифры, пробелы, +, (, ), - (5-20 символов).</div>';
    }
    if ($errors['email']) {
        $messages[] = '<div class="error-message">Ошибка в поле "E-mail": Введите корректный email (пример: name@domain.ru).</div>';
    }
    if ($errors['birth_date']) {
        $messages[] = '<div class="error-message">Ошибка в поле "Дата рождения": Дата рождения не может быть в будущем. Формат: ГГГГ-ММ-ДД.</div>';
    }
    if ($errors['gender']) {
        $messages[] = '<div class="error-message">Ошибка в поле "Пол": Выберите пол.</div>';
    }
    if ($errors['languages']) {
        $messages[] = '<div class="error-message">Ошибка в поле "Любимый язык программирования": Выберите хотя бы один язык из списка.</div>';
    }
    if ($errors['agreed']) {
        $messages[] = '<div class="error-message">Ошибка: Вы должны ознакомиться с контрактом.</div>';
    }

    include('form.php');
    exit();
}

// POST запрос
$errors = false;

// Получаем данные
$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$birth_date = $_POST['birth_date'] ?? '';
$gender = $_POST['gender'] ?? '';
$languages = $_POST['languages'] ?? [];
$biography = trim($_POST['biography'] ?? '');
$agreed = isset($_POST['agreed']) ? 1 : 0;

// 1. ФИО
if (empty($full_name)) {
    setcookie('full_name_error', '1', time() + 24 * 60 * 60);
    $errors = true;
} elseif (strlen($full_name) > 150) {
    setcookie('full_name_error', '1', time() + 24 * 60 * 60);
    $errors = true;
} elseif (!preg_match('/^[a-zA-Zа-яА-ЯёЁ\s\-]+$/u', $full_name)) {
    setcookie('full_name_error', '1', time() + 24 * 60 * 60);
    $errors = true;
}
setcookie('full_name_value', $full_name, time() + 30 * 24 * 60 * 60);

// 2. Телефон
if (empty($phone)) {
    setcookie('phone_error', '1', time() + 24 * 60 * 60);
    $errors = true;
} elseif (!preg_match('/^[\d\s\+\(\)-]{5,20}$/', $phone)) {
    setcookie('phone_error', '1', time() + 24 * 60 * 60);
    $errors = true;
}
setcookie('phone_value', $phone, time() + 30 * 24 * 60 * 60);

// 3. Email
if (empty($email)) {
    setcookie('email_error', '1', time() + 24 * 60 * 60);
    $errors = true;
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    setcookie('email_error', '1', time() + 24 * 60 * 60);
    $errors = true;
}
setcookie('email_value', $email, time() + 30 * 24 * 60 * 60);

// 4. Дата рождения
if (empty($birth_date)) {
    setcookie('birth_date_error', '1', time() + 24 * 60 * 60);
    $errors = true;
} elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    setcookie('birth_date_error', '1', time() + 24 * 60 * 60);
    $errors = true;
} else {
    $birth_timestamp = strtotime($birth_date);
    $today_timestamp = strtotime(date('Y-m-d'));
    if ($birth_timestamp > $today_timestamp) {
        setcookie('birth_date_error', '1', time() + 24 * 60 * 60);
        $errors = true;
    }
}
setcookie('birth_date_value', $birth_date, time() + 30 * 24 * 60 * 60);

// 5. Пол
if (empty($gender) || !in_array($gender, ['male', 'female'])) {
    setcookie('gender_error', '1', time() + 24 * 60 * 60);
    $errors = true;
}
setcookie('gender_value', $gender, time() + 30 * 24 * 60 * 60);

// 6. Языки
$allowed_langs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
if (empty($languages)) {
    setcookie('languages_error', '1', time() + 24 * 60 * 60);
    $errors = true;
} else {
    foreach ($languages as $lang) {
        if (!in_array($lang, $allowed_langs)) {
            setcookie('languages_error', '1', time() + 24 * 60 * 60);
            $errors = true;
            break;
        }
    }
}
setcookie('languages_value', implode(',', $languages), time() + 30 * 24 * 60 * 60);

// 7. Биография
if (!empty($biography) && strlen($biography) > 5000) {
    setcookie('biography_error', '1', time() + 24 * 60 * 60);
    $errors = true;
}
setcookie('biography_value', $biography, time() + 30 * 24 * 60 * 60);

// 8. Согласие
if (empty($agreed)) {
    setcookie('agreed_error', '1', time() + 24 * 60 * 60);
    $errors = true;
}
setcookie('agreed_value', $agreed, time() + 30 * 24 * 60 * 60);

if ($errors) {
    header('Location: index.php');
    exit();
}

// Удаляем Cookies с ошибками
$fields = ['full_name', 'phone', 'email', 'birth_date', 'gender', 'languages', 'biography', 'agreed'];
foreach ($fields as $field) {
    setcookie($field . '_error', '', 100000);
}

// Проверяем, авторизован ли пользователь
$logged_in = false;
$user_id = null;
if (!empty($_COOKIE[session_name()]) && isset($_SESSION['login'])) {
    $logged_in = true;
    $user_id = $_SESSION['uid'];
}

try {
    if ($logged_in && $user_id) {
        // Обновляем данные существующего пользователя
        $stmt = $db->prepare("UPDATE users SET full_name = ?, phone = ?, email = ?, birth_date = ?, gender = ?, biography = ?, agreed = ? WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $agreed, $user_id]);
        
        // Получаем id записи
        $stmt2 = $db->prepare("SELECT id FROM users WHERE user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt2->execute([$user_id]);
        $record = $stmt2->fetch(PDO::FETCH_ASSOC);
        $record_id = $record['id'];
        
        // Удаляем старые языки и вставляем новые
        $stmt_del = $db->prepare("DELETE FROM user_languages WHERE user_id = ?");
        $stmt_del->execute([$record_id]);
        
        $stmt_lang = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $stmt_insert = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
        
        foreach ($languages as $lang_name) {
            $stmt_lang->execute([$lang_name]);
            $lang_id = $stmt_lang->fetchColumn();
            if ($lang_id) {
                $stmt_insert->execute([$record_id, $lang_id]);
            }
        }
    } else {
        // Новая запись — сначала создаём пользователя в users_auth
        $login = 'user_' . rand(10000, 99999);
        $pass = substr(md5(uniqid()), 0, 8);
        $password_hash = md5($pass);
        
        $stmt_auth = $db->prepare("INSERT INTO users_auth (login, password_hash) VALUES (?, ?)");
        $stmt_auth->execute([$login, $password_hash]);
        $auth_id = $db->lastInsertId();
        
        // Сохраняем данные формы
        $stmt = $db->prepare("INSERT INTO users (full_name, phone, email, birth_date, gender, biography, agreed, user_id) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$full_name, $phone, $email, $birth_date, $gender, $biography, $agreed, $auth_id]);
        $record_id = $db->lastInsertId();
        
        // Сохраняем языки
        $stmt_lang = $db->prepare("SELECT id FROM programming_languages WHERE name = ?");
        $stmt_insert = $db->prepare("INSERT INTO user_languages (user_id, language_id) VALUES (?, ?)");
        
        foreach ($languages as $lang_name) {
            $stmt_lang->execute([$lang_name]);
            $lang_id = $stmt_lang->fetchColumn();
            if ($lang_id) {
                $stmt_insert->execute([$record_id, $lang_id]);
            }
        }
        
        // Сохраняем логин и пароль в Cookies для отображения
        setcookie('login', $login, time() + 30 * 24 * 60 * 60);
        setcookie('pass', $pass, time() + 30 * 24 * 60 * 60);
    }

} catch (PDOException $e) {
    die("Ошибка БД: " . $e->getMessage());
}

setcookie('save', '1', time() + 24 * 60 * 60);
header('Location: index.php');
?>