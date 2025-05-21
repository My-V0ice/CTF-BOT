<?php
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\RunningMode\Webhook;

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    die('Autoload.php not found');
}
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config.php';
$pdo = require_once __DIR__ . '/db.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/bot_error.log');

try {
    $bot = new Nutgram(BOT_TOKEN);
    $bot->setRunningMode(Webhook::class);

    // Функция для получения случайной задачи
    function getRandomTask($pdo, $userId) {
        $sql = "SELECT t.* FROM tasks t 
                WHERE t.id NOT IN (
                    SELECT task_id FROM skipped_tasks WHERE user_id = :user_id
                )
                ORDER BY RAND() LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':user_id' => $userId]);
        return $stmt->fetch();
    }

    $bot->onCommand('start', function (Nutgram $bot) use ($pdo) {
        try {
            $sql = "INSERT INTO users (telegram_id, username) VALUES (:telegram_id, :username)
                ON DUPLICATE KEY UPDATE username = :username";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                ':telegram_id' => $bot->user()->id,
                ':username' => $bot->user()->username ?? 'unknown',
            ]);
            $bot->sendMessage('Привет! Я бот с задачами. Используйте /task для получения задачи.');
        } catch (\PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            $bot->sendMessage('Произошла ошибка при обработке команды.');
        }
    });

    $bot->onCommand('task', function (Nutgram $bot) use ($pdo) {
        try {
            $task = getRandomTask($pdo, $bot->user()->id);
            if ($task) {
                $bot->sendMessage("Задача: " . $task['task_text']);
            } else {
                $bot->sendMessage("У вас больше нет доступных задач!");
            }
        } catch (\PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            $bot->sendMessage('Произошла ошибка при получении задачи.');
        }
    });

    $bot->onCommand('skip', function (Nutgram $bot) use ($pdo) {
        try {
            // Получаем последнюю задачу пользователя
            $sql = "SELECT t.* FROM tasks t 
                    WHERE t.id NOT IN (
                        SELECT task_id FROM skipped_tasks WHERE user_id = :user_id
                    )
                    ORDER BY RAND() LIMIT 1";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([':user_id' => $bot->user()->id]);
            $task = $stmt->fetch();

            if ($task) {
                // Добавляем задачу в пропущенные
                $sql = "INSERT INTO skipped_tasks (user_id, task_id) VALUES (:user_id, :task_id)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([
                    ':user_id' => $bot->user()->id,
                    ':task_id' => $task['id']
                ]);

                // Получаем новую задачу
                $newTask = getRandomTask($pdo, $bot->user()->id);
                if ($newTask) {
                    $bot->sendMessage("Задача пропущена. Новая задача:\n" . $newTask['task_text']);
                } else {
                    $bot->sendMessage("Задача пропущена. У вас больше нет доступных задач!");
                }
            } else {
                $bot->sendMessage("У вас нет активных задач для пропуска!");
            }
        } catch (\PDOException $e) {
            error_log('Database error: ' . $e->getMessage());
            $bot->sendMessage('Произошла ошибка при пропуске задачи.');
        }
    });

    $bot->run();
} catch (\Exception $e) {
    error_log('Bot error: ' . $e->getMessage());
    die('Произошла ошибка при запуске бота.');
}