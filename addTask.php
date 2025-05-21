<?php
declare(strict_types=1);

// Разрешённые IP
$allowed_ips = [
    '91.149.96.186',
    '91.149.96.113',
    '80.83.234.98',
    '85.26.241.250',
    '10.184.116.234',
    '85.15.113.82',
    '91.234.54.188',
    '62.249.129.74',
];
$valid_key = 'bemF1qQRR~g$VuxA';

// Простое получение IP клиента
function getClientIP(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

// Проверка доступа
$client_ip = getClientIP();
$provided_key = $_GET['key'] ?? '';

if (!in_array($client_ip, $allowed_ips, true) || $provided_key !== $valid_key) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=UTF-8');
    die('Доступ запрещен');
}

// Далее — код для разрешённого доступа
echo 'Доступ разрешён';

require_once __DIR__ . '/config.php';
$pdo = require_once __DIR__ . '/db.php';

// Обработка отправки формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Проверяем наличие данных
        if (empty($_POST['question']) || empty($_POST['answer'])) {
            throw new Exception('Все поля должны быть заполнены');
        }

        // Подготавливаем и выполняем запрос
        $sql = "INSERT INTO ctf_tasks (question, answer, is_active) VALUES (:question, :answer, 1)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':question' => $_POST['question'],
            ':answer' => $_POST['answer']
        ]);

        $success = 'Задача успешно добавлена!';
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="ru">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Добавление задачи</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
        }

        .form-group {
            margin-bottom: 15px;
        }

        input[type="text"] {
            width: 100%;
            padding: 8px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .success {
            color: green;
            margin-bottom: 15px;
        }

        .error {
            color: red;
            margin-bottom: 15px;
        }
    </style>
</head>

<body>
<h1>Добавление новой задачи</h1>

<?php if (isset($success)): ?>
    <div class="success"><?php echo htmlspecialchars($success); ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="error"><?php echo htmlspecialchars($error); ?></div>
<?php endif; ?>

<form action="addTask.php" method="post">
    <div class="form-group">
        <label for="question">Задача:</label>
        <input type="text" id="question" name="question" placeholder="Введите задачу" required>
    </div>
    <div class="form-group">
        <label for="answer">Ответ:</label>
        <input type="text" id="answer" name="answer" placeholder="Введите ответ" required>
    </div>
    <input type="submit" value="Добавить задачу">
</form>
</body>

</html>