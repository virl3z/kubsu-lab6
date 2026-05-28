<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Регистрационная форма</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f5f5f5;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border: 1px solid #ccc;
        }
        h1 {
            font-size: 1.5em;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .required::after {
            content: " *";
            color: red;
        }
        input, select, textarea {
            width: 100%;
            padding: 5px;
            border: 1px solid #ccc;
            font-family: Arial, sans-serif;
        }
        select[multiple] {
            height: 120px;
        }
        .radio-group {
            display: flex;
            gap: 20px;
        }
        .radio-group label {
            font-weight: normal;
        }
        button {
            background: #333;
            color: white;
            border: none;
            padding: 8px 15px;
            cursor: pointer;
            width: 100%;
        }
        button:hover {
            background: #555;
        }
        .error-message {
            background: #ffdddd;
            color: #ff0000;
            padding: 5px 10px;
            margin-bottom: 10px;
            border: 1px solid #ff0000;
        }
        .success-message {
            background: #ddffdd;
            color: #008000;
            padding: 5px 10px;
            margin-bottom: 10px;
            border: 1px solid #008000;
        }
        .error-input {
            border: 2px solid red !important;
            background: #ffeeee;
        }
        .auth-link {
            text-align: right;
            margin-bottom: 15px;
        }
        small {
            font-size: 0.8em;
            color: #666;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Регистрационная форма</h1>
    
    <div class="auth-link">
        <?php if (isset($_SESSION['login'])): ?>
            <a href="login.php?logout=1">Выйти (<?= htmlspecialchars($_SESSION['login']) ?>)</a>
        <?php else: ?>
            <a href="login.php">Войти</a> для изменения данных
        <?php endif; ?>
    </div>

    <?php if (!empty($messages)): ?>
        <div id="messages">
            <?php foreach ($messages as $message): ?>
                <?= $message ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form action="" method="POST">
        <div class="form-group">
            <label class="required">ФИО</label>
            <input type="text" name="full_name" 
                   class="<?= $errors['full_name'] ? 'error-input' : '' ?>"
                   value="<?= htmlspecialchars($values['full_name'] ?? '') ?>"
                   placeholder="Иванов Иван Иванович">
            <small>Только буквы, пробелы и дефисы</small>
        </div>

        <div class="form-group">
            <label class="required">Телефон</label>
            <input type="text" name="phone" 
                   class="<?= $errors['phone'] ? 'error-input' : '' ?>"
                   value="<?= htmlspecialchars($values['phone'] ?? '') ?>"
                   placeholder="+7 (123) 456-78-90">
            <small>Только цифры, пробелы, +, (, ), -</small>
        </div>

        <div class="form-group">
            <label class="required">E-mail</label>
            <input type="text" name="email" 
                   class="<?= $errors['email'] ? 'error-input' : '' ?>"
                   value="<?= htmlspecialchars($values['email'] ?? '') ?>"
                   placeholder="example@mail.ru">
            <small>Должен содержать символ @</small>
        </div>

        <div class="form-group">
            <label class="required">Дата рождения</label>
            <input type="text" name="birth_date" 
                   class="<?= $errors['birth_date'] ? 'error-input' : '' ?>"
                   value="<?= htmlspecialchars($values['birth_date'] ?? '') ?>"
                   placeholder="ГГГГ-ММ-ДД">
            <small>Формат: ГГГГ-ММ-ДД</small>
        </div>

        <div class="form-group">
            <label class="required">Пол</label>
            <div class="radio-group">
                <label><input type="radio" name="gender" value="male"
                       <?= ($values['gender'] ?? '') == 'male' ? 'checked' : '' ?>> Мужской</label>
                <label><input type="radio" name="gender" value="female"
                       <?= ($values['gender'] ?? '') == 'female' ? 'checked' : '' ?>> Женский</label>
            </div>
        </div>

        <div class="form-group">
            <label class="required">Любимый язык программирования</label>
            <select name="languages[]" multiple
                    class="<?= $errors['languages'] ? 'error-input' : '' ?>">
                <?php
                $langs = ['Pascal', 'C', 'C++', 'JavaScript', 'PHP', 'Python', 'Java', 
                          'Haskell', 'Clojure', 'Prolog', 'Scala', 'Go'];
                $selected_langs = explode(',', $values['languages'] ?? '');
                foreach ($langs as $lang): ?>
                    <option value="<?= $lang ?>" <?= in_array($lang, $selected_langs) ? 'selected' : '' ?>>
                        <?= $lang ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <small>Удерживайте Ctrl для выбора нескольких</small>
        </div>

        <div class="form-group">
            <label>Биография</label>
            <textarea name="biography" rows="4" 
                      class="<?= $errors['biography'] ? 'error-input' : '' ?>"
                      placeholder="Расскажите о себе..."><?= htmlspecialchars($values['biography'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="agreed" value="1"
                       <?= !empty($values['agreed']) ? 'checked' : '' ?>> 
                Я ознакомлен с контрактом
            </label>
        </div>

        <button type="submit">Сохранить</button>
    </form>
</div>
</body>
</html>