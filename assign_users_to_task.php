<?php
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['task_id']) || !isset($_GET['project_id']) || !is_numeric($_GET['task_id']) || !is_numeric($_GET['project_id'])) {
    echo "Invalid or missing task/project ID.";
    exit;
}

$task_id = $_GET['task_id'];
$project_id = $_GET['project_id'];
$user_id = $_SESSION['user_id'];

try {
    // Fetch project name
    $stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if (!$project) {
        echo "Project not found.";
        exit;
    }

    $project_name = $project['name'];

    // Fetch the project owner
    $stmt = $pdo->prepare("SELECT u.id, u.email FROM project_members pm 
                           JOIN users u ON pm.user_id = u.id 
                           WHERE pm.project_id = ? AND pm.role = 'owner' 
                           LIMIT 1");
    $stmt->execute([$project_id]);
    $owner = $stmt->fetch();

    if (!$owner) {
        echo "Owner not found.";
        exit;
    }

    $owner_id = $owner['id'];
    $owner_email = $owner['email'];

    // Fetch all users in the project except the owner
    $stmt = $pdo->prepare("SELECT u.id, u.email FROM project_members pm 
                           JOIN users u ON pm.user_id = u.id 
                           WHERE pm.project_id = ? AND u.id != ?");
    $stmt->execute([$project_id, $owner_id]);
    $project_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch existing task assignments but **exclude the owner**
    $stmt = $pdo->prepare("SELECT ta.user_id, ta.role, u.email FROM task_assignments ta 
                           JOIN users u ON ta.user_id = u.id 
                           WHERE ta.task_id = ? AND ta.user_id != ?");
    $stmt->execute([$task_id, $owner_id]);
    $existing_assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Handle assigning users to the task
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_user'])) {
        $assigned_user_id = $_POST['user_id'];
        $role = $_POST['role'];

        // Prevent assigning the owner or modifying the current user's role
        if ($assigned_user_id == $user_id) {
            echo "You cannot change your own role.";
        } elseif ($assigned_user_id == $owner_id) {
            echo "You cannot change the project owner's role.";
        } else {
            // Check if the user is already assigned to this task
            $stmt = $pdo->prepare("SELECT * FROM task_assignments WHERE task_id = ? AND user_id = ?");
            $stmt->execute([$task_id, $assigned_user_id]);

            if ($stmt->rowCount() === 0) {
                // Insert the new assignment
                $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id, role) VALUES (?, ?, ?)");
                $stmt->execute([$task_id, $assigned_user_id, $role]);
            } else {
                // Update the role if the user is already assigned
                $stmt = $pdo->prepare("UPDATE task_assignments SET role = ? WHERE task_id = ? AND user_id = ?");
                $stmt->execute([$role, $task_id, $assigned_user_id]);
            }

            // Redirect to avoid form resubmission
            header("Location: tasks.php?project_id=$project_id");
            exit;
        }
    }
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign Users to Task</title>
    <link rel="stylesheet" href="./css/management.css">
</head>
<body>
    <div id="popup_overlay">
        <div class="popup">
        <h2>Assign Users to Task: <?php echo htmlspecialchars($task_id); ?> for Project: <?php echo htmlspecialchars($project_name); ?></h2>

    <h3>Available Users:</h3>
    <form method="POST">
        <label for="user_id">Select User:</label>
        <select name="user_id" required>
            <option value="">Select a User</option>
            <?php foreach ($project_users as $user): ?>
                <option value="<?php echo $user['id']; ?>"><?php echo htmlspecialchars($user['email']); ?></option>
            <?php endforeach; ?>
        </select><br>

        <label for="role">Role:</label>
        <select name="role" required>
            <option value="editor">Editor</option>
            <option value="viewer">Viewer</option>
        </select><br>

        <button type="submit" name="assign_user">Assign</button>
    </form>

    <h3>Current Assignments:</h3>
    <ul>
        <li>
            Email: <?php echo htmlspecialchars($owner_email); ?> - 
            Role: Owner 
            <span>(Owner's role cannot be changed)</span>
        </li>
        <?php foreach ($existing_assignments as $assignment): ?>
            <li>
                Email: <?php echo htmlspecialchars($assignment['email']); ?> - 
                Role: <?php echo htmlspecialchars($assignment['role']); ?> 
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="user_id" value="<?php echo $assignment['user_id']; ?>">
                    <select name="role" required>
                        <option value="editor" <?php echo ($assignment['role'] == 'editor') ? 'selected' : ''; ?>>Editor</option>
                        <option value="viewer" <?php echo ($assignment['role'] == 'viewer') ? 'selected' : ''; ?>>Viewer</option>
                    </select>
                    <button type="submit" name="assign_user">Update</button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
        <div id = "bottom">
        <a href="tasks.php?project_id=<?php echo $project_id; ?>">
            <img src ="./css/drawables/back.png" class="icon">
        </a>
        </div>
    </div>
</body>
</html>
