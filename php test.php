<?php

// Настройки подключения к базе данных
$host = 'localhost';
$user = 'root';
$password = '';
$dbname = 'todo_db';

// Установка соединения
$conn = new mysqli($host, $user, $password, $dbname);
if ($conn->connect_error) {
    die("Ошибка подключения: " . $conn->connect_error);
}

// Создание таблицы задач
$sql_create_table = "
    CREATE TABLE IF NOT EXISTS tasks (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        completed TINYINT(1) NOT NULL DEFAULT 0
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;
";
$conn->query($sql_create_table);

// Обработка добавления задачи
if (!empty($_POST['title'])) {
    $stmt = $conn->prepare("INSERT INTO tasks (title) VALUES (?)");
    $stmt->bind_param("s", $_POST['title']);
    $stmt->execute();
    $stmt->close();
}

// Обработка удаления задачи
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->bind_param("i", intval($_GET['id']));
    $stmt->execute();
    $stmt->close();
}

// Изменение статуса задачи
if (isset($_GET['action']) && $_GET['action'] === 'complete' && isset($_GET['id'])) {
    $stmt = $conn->prepare("UPDATE tasks SET completed = 1 WHERE id = ?");
    $stmt->bind_param("i", intval($_GET['id']));
    $stmt->execute();
    $stmt->close();
}

// Выбор задач в зависимости от фильтра
$filter = $_GET['filter'] ?? 'all';
$sql = match ($filter) {
    'completed' => "SELECT * FROM tasks WHERE completed = 1",
    'not_completed' => "SELECT * FROM tasks WHERE completed = 0",
    default => "SELECT * FROM tasks",
};
$result = $conn->query($sql);

?>

<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Список задач</title>
    <style>
        body { font-family: Arial; margin: 20px; }
        table { border-collapse: collapse; width: 400px; margin-top: 20px; }
        td, th { border: 1px solid #ccc; padding: 8px; }
        .completed { text-decoration: line-through; color: green; }
    </style>
</head>
<body>

<h1>Список задач</h1>

<form action="" method="post">
    <label for="title">Новая задача:</label><br>
    <input type="text" name="title" id="title" required>
    <button type="submit">Добавить</button>
</form>

<div>
    <strong>Фильтрация:</strong>
    <a href="?filter=all">Все</a> | 
    <a href="?filter=completed">Выполненные</a> | 
    <a href="?filter=not_completed">Невыполненные</a>
</div>

<table>
    <tr>
        <th>ID</th>
        <th>Задача</th>
        <th>Статус</th>
        <th>Действия</th>
    </tr>
    <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?= $row['id'] ?></td>
                <td class="<?= $row['completed'] ? 'completed' : '' ?>"><?= htmlspecialchars($row['title']) ?></td>
                <td><?= $row['completed'] ? 'Выполнено' : 'Не выполнено' ?></td>
                <td>
                    <a href="?action=delete&id=<?= $row['id'] ?>" onclick="return confirm('Вы уверены?');">Удалить</a>
                    <?php if (!$row['completed']): ?>
                        | <a href="?action=complete&id=<?= $row['id'] ?>">Отметить выполненной</a>
                    <?php endif; ?>
                </td>
            </tr>
        <?php endwhile; ?>
    <?php else: ?>
        <tr><td colspan="4">Нет задач.</td></tr>
    <?php endif; ?>
</table>

</body>
</html>

<?php
// Закрытие соединения
$conn->close();
?>