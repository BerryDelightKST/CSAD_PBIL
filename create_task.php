<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    echo "Invalid or missing project ID.";
    exit;
}

$project_id = $_GET['project_id'];
$task_id = isset($_GET['task_id']) ? $_GET['task_id'] : null;

// Fetch the user role in the project
$stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $user_id]);
$user_role = $stmt->fetchColumn();

if ($user_role !== 'owner') {
    echo "You do not have permission to create tasks in this project.";
    exit;
}

// Handle task creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_task'])) {
    $task_name = $_POST['task_name'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $progress = $_POST['progress'];
    $description = $_POST['description'];

    if ($progress >= 0 && $progress <= 100) {
        try {
            $stmt = $pdo->prepare("INSERT INTO tasks (project_id, name, start_date, end_date, progress, description) 
                                   VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$project_id, $task_name, $start_date, $end_date, $progress, $description]);

            $task_id = $pdo->lastInsertId();

            $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id, role) VALUES (?, ?, ?)");
            $stmt->execute([$task_id, $user_id, 'owner']);

            header("Location: create_task.php?project_id=$project_id&task_id=$task_id");
            exit;
        } catch (PDOException $e) {
            echo "Error creating task: " . $e->getMessage();
        }
    } else {
        echo "Invalid progress value. It should be between 0 and 100.";
    }
}

// Fetch available users
$stmt = $pdo->prepare("SELECT u.id, u.email 
                       FROM users u
                       JOIN project_members pm ON u.id = pm.user_id
                       WHERE pm.project_id = ? 
                       AND u.id NOT IN (SELECT user_id FROM task_assignments WHERE task_id = ?)");
$stmt->execute([$project_id, $task_id]);
$available_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch project details
$stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Task</title>
    <link href="./css/create_task.css" rel="stylesheet">
</head>
<body>
    <div id="popup_overlay">
        <div class ="popup">
            <h2>Create a New Task for Project: <?php echo htmlspecialchars($project['name']); ?></h2>
            <form method="POST">
                <label for="task_name">Task Name:</label>
                <input type="text" name="task_name" required><br>

                <label for="start_date">Start Date:</label>
                <input type="date" name="start_date" required><br>

                <label for="end_date">End Date:</label>
                <input type="date" name="end_date" required><br>

                <label for="progress">Progress:</label>
                <input type="number" name="progress" min="0" max="100" required><br>

                <label for="description">Description:</label>
                <textarea name="description" required></textarea><br>

                <button type="submit" name="create_task">Create</button>
            </form>

            <?php if (isset($task_id) && !empty($task_id)): ?>
                <h3>Assign Users to Task</h3>
                <form method="POST">
                    <label for="user_id">Select User:</label>
                    <select name="user_id" required>
                        <option value="">-- Select a user --</option>
                        <?php foreach ($available_users as $user): ?>
                            <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['email']); ?></option>
                        <?php endforeach; ?>
                    </select>

                    <label for="role">Role:</label>
                    <select name="role" required>
                        <option value="editor">Editor</option>
                        <option value="viewer">Viewer</option>
                    </select>

                    <button type="submit" name="assign_user">Assign</button>
                </form>
            <?php endif; ?>
            <div id = "bottom">
            <a href="tasks.php?project_id=<?php echo $project_id; ?>">
                <img src ="./css/drawables/back.png" class="icon">
            </a>
            </div>
        </div>
    </div>
</body>
</html>
