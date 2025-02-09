<?php
//For this file's php, the entire php statements until before the html was created by Lewis and Haaziq, later edited by Si Thu during UI implementation.

session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch the user role in the project
if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    echo "Invalid or missing project ID.";
    exit;
}

$project_id = $_GET['project_id'];
$user_id = $_SESSION['user_id'];

// Get the project name
$stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    echo "Project not found.";
    exit;
}

$project_name = $project['name'];

// Get the role of the current user in the project
$stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $user_id]);
$user_role = $stmt->fetchColumn();



// Handle deleting a task
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_task'])) {
    $task_id = $_POST['task_id'];

    try {
        // Delete task assignments related to the task first
        $stmt = $pdo->prepare("DELETE FROM task_assignments WHERE task_id = ?");
        $stmt->execute([$task_id]);

        // Now delete the task from the tasks table
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE id = ? AND project_id = ?");
        $stmt->execute([$task_id, $project_id]);

        // Redirect to avoid resubmitting the form on refresh
        header("Location: tasks.php?project_id=$project_id");
        exit;
    } catch (PDOException $e) {
        echo "Error deleting task: " . $e->getMessage();
    }
}
if (isset($_POST['delete_subtask'])) {
    $subtask_id = $_POST['subtask_id'];
    
    // Delete the subtask from the database
    $stmt = $pdo->prepare("DELETE FROM subtasks WHERE id = ?");
    $stmt->execute([$subtask_id]);

    // Optionally, add a success message or redirect
    echo "<p>Subtask deleted successfully.</p>";
    
    // Refresh the page to reflect the changes
    header("Location: tasks.php?project_id=$project_id");
    exit();
}

// Handle updating task progress, dates, and description
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_progress'])) {
    $task_id = $_POST['task_id'];
    $progress = $_POST['progress'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $description = $_POST['description'];

    if ($user_role === 'owner') {
        // Make sure this query is properly executed when the owner submits
        $stmt = $pdo->prepare("UPDATE tasks SET progress = ?, start_date = ?, end_date = ?, description = ? WHERE id = ?");
        $stmt->execute([$progress, $start_date, $end_date, $description, $task_id]);
        echo "Task updated by owner.";
    }
    // Update query for the editor
    elseif ($task_assignment_role === 'editor') {
        // Only update the progress and description for the editor
        $stmt = $pdo->prepare("UPDATE tasks SET progress = ?, description = ? WHERE id = ?");
        $stmt->execute([$progress, $description, $task_id]);
        echo "Task updated by editor.";
    }

    // Ensure the progress is a valid number between 0 and 100
    if ($progress >= 0 && $progress <= 100) {
        try {
            // Update the task progress, dates, and description in the database
            $stmt = $pdo->prepare("UPDATE tasks SET progress = ?, start_date = ?, end_date = ?, description = ? WHERE id = ? AND project_id = ?");
            $stmt->execute([$progress, $start_date, $end_date, $description, $task_id, $project_id]);

            // Redirect to avoid resubmitting the form on refresh
            header("Location: tasks.php?project_id=$project_id");
            exit;
        } catch (PDOException $e) {
            echo "Error updating progress: " . $e->getMessage();
        }
    } else {
        echo "Invalid progress value. It should be between 0 and 100.";
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['subtask_id'])) {
    $subtask_id = $_POST['subtask_id'];
    $completed = isset($_POST['completed']) ? 1 : 0;

    try {
        $stmt = $pdo->prepare("UPDATE subtasks SET completed = ? WHERE id = ?");
        $stmt->execute([$completed, $subtask_id]);

        // Redirect to avoid resubmitting the form on refresh
        header("Location: tasks.php?project_id=$project_id");
        exit;
    } catch (PDOException $e) {
        echo "Error updating subtask: " . $e->getMessage();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subtask'])) {
    $task_id = $_POST['task_id'];
    $subtask_name = $_POST['subtask_name'];

    try {
        $stmt = $pdo->prepare("INSERT INTO subtasks (task_id, name) VALUES (?, ?)");
        $stmt->execute([$task_id, $subtask_name]);

        // Redirect to avoid resubmitting the form on refresh
        header("Location: tasks.php?project_id=$project_id");
        exit;
    } catch (PDOException $e) {
        echo "Error adding subtask: " . $e->getMessage();
    }
}


// Fetch tasks for the specific project
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = ?");
$stmt->execute([$project_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users for the project (including the owner)
$stmt = $pdo->prepare("SELECT u.email, pm.role FROM project_members pm JOIN users u ON pm.user_id = u.id WHERE pm.project_id = ?");
$stmt->execute([$project_id]);
$project_users = $stmt->fetchAll(PDO::FETCH_ASSOC);
// Fetch subtasks for the current task
// Fetch subtasks for the current task
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = ?");
$stmt->execute([$project_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($tasks as $task) {
    // Ensure $task['id'] exists and is not null
    if (isset($task['id']) && !empty($task['id'])) {
        // Fetch subtasks for the current task
        $stmt = $pdo->prepare("SELECT * FROM subtasks WHERE task_id = ?");
        $stmt->execute([$task['id']]);
        $subtasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Now you can safely process the subtasks
        // ...
    } else {
        echo "Task ID is missing or invalid.";
    }
}


?>

<!--The entire html and inline php statement has been created by Si Thu and Lewis and edited by Si Thu during UI implementation-->

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project Tasks</title>
    <link href="css/task-manage.css" rel="stylesheet">
    <script src="javascript/task_form.js"></script>
</head>
<body>

    <header>
        <h2>Project Tasks: <?php echo htmlspecialchars($project_name); ?></h2>
    </header>

    <div class="container">
        <h3>Project Members</h3>
        <ul>
            <?php foreach ($project_users as $user): ?>
                <li>
                    <strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?>
                    <?php if ($user['role'] === 'owner'): ?>
                        <img src='./css/drawables/owner.png' class="icon">
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>

        <a href="gantt_chart.php?project_id=<?php echo $project_id; ?>" class="new_btn">Generate Gantt Chart</a>

        <!-- Only show "Create Task" button to the owner -->
        <?php if ($user_role === 'owner'): ?>
            <a href="create_task.php?project_id=<?php echo $project_id; ?>" class="new_btn">Create Task</a>
        <?php else: ?>
            <p>You do not have permission to create tasks or manage assignments.</p>
        <?php endif; ?>         
        <h3>Tasks</h3>
        <ul>
        <?php foreach ($tasks as $task): ?>
                <?php
                $current_date = date('Y-m-d');
                $is_due_class = ($task['end_date'] <= $current_date) ? "due-task" : "";
                ?>
                <li>
                    
                    <div class="task-container <?php echo $is_due_class; ?>">
                    <div class="task-details">
                            <h4><?php echo htmlspecialchars($task['name']); ?></h4>
                            <p><strong>Progress:</strong> <?php echo $task['progress']; ?>%</p>
                            <p><strong>Start Date:</strong> <?php echo $task['start_date']; ?></p>
                            <p><strong>End Date:</strong> <?php echo $task['end_date']; ?></p>
                            <p><strong>Description:</strong></p>
                            <p><?php echo nl2br(htmlspecialchars($task['description'])); ?></p>
                            <div class="task-actions">
                            <?php if ($user_role === 'owner' && isset($task['id'])): ?>
                                <a href="assign_users_to_task.php?task_id=<?php echo $task['id']; ?>&project_id=<?php echo $project_id; ?>" class="new_btn_xl">Manage Assignments</a>
                            <?php endif; ?>
                        </div>
                        </div>
                        <div class="task-assignment">
                            <h4>Assigned Editors</h4>
                            <?php
                            $stmt = $pdo->prepare("SELECT u.email, ta.role 
                                                   FROM task_assignments ta
                                                   JOIN users u ON ta.user_id = u.id
                                                   JOIN project_members pm ON ta.user_id = pm.user_id
                                                   WHERE ta.task_id = ? 
                                                   AND pm.project_id = ? 
                                                   AND ta.role = 'editor'");
                            $stmt->execute([$task['id'], $project_id]);
                            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($assignments)) {
                                echo "<p>No editors assigned.</p>";
                            } else {
                                foreach ($assignments as $assignment) {
                                    echo "<p>Email: " . htmlspecialchars($assignment['email']) . " - Role: " . htmlspecialchars($assignment['role']) . "</p>";
                                }
                            }
                            ?>
                        </div>
                        <div class="task-update">
                            <?php
                            // Check if the user has an "editor" role in the task_assignments table
                            $stmt = $pdo->prepare("SELECT role FROM task_assignments WHERE task_id = ? AND user_id = ?");
                            $stmt->execute([$task['id'], $user_id]);
                            $task_assignment_role = $stmt->fetchColumn();

                            // Allow editing if the user is a project owner OR has the "editor" role in task assignments
                            if ($user_role === 'owner'): 
                            ?>
                                <form method="POST" style="display:inline;">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <label for="progress">Progress:</label>
                                    <input type="number" name="progress" min="0" max="100" value="<?php echo $task['progress']; ?>" required><br>
                                    <label for="start_date">Start Date:</label>
                                    <input type="date" name="start_date" value="<?php echo $task['start_date']; ?>" required><br>
                                    <label for="end_date">End Date:</label>
                                    <input type="date" name="end_date" value="<?php echo $task['end_date']; ?>" required><br>
                                    <label for="description">Description:</label><br>
                                    <textarea name="description" required><?php echo htmlspecialchars($task['description']); ?></textarea><br>
                                    <button type="submit" name="update_progress" class="new_btn">Update</button>
                                </form>
                            <?php
                            elseif ($task_assignment_role === 'editor'): // Corrected else if condition
                            ?>
                                <form method="POST" style="display:inline;">
                                <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                <label for="progress">Progress:</label>
                                <input type="number" name="progress" min="0" max="100" value="<?php echo $task['progress']; ?>" required><br>
                                <label for="description">Description:</label><br>
                                <textarea name="description" required><?php echo htmlspecialchars($task['description']); ?></textarea><br>
                                <button class="new_btn"type="submit" name="update_progress">Update</button>

                                </form>
                                <?php endif;?>

                        
                        </div>

                        <div class="subtasks">
                            <h4>Subtasks</h4>
                            <?php
                            $stmt = $pdo->prepare("SELECT * FROM subtasks WHERE task_id = ?");
                            $stmt->execute([$task['id']]);
                            $subtasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

                            if (empty($subtasks)) {
                                echo "<p>No subtasks found.</p>";
                            } else {
                                echo "<ul>";
                                foreach ($subtasks as $subtask) {
                                    echo "<li>";
                                    echo "<div class='subtask-actions'>";
                                    if ($user_role === 'owner' || $task_assignment_role === 'editor') {
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo htmlspecialchars($subtask['name']);
                                        echo "<input type='hidden' name='subtask_id' value='" . $subtask['id'] . "'>";
                                        echo "<input type='checkbox' name='completed' " . ($subtask['completed'] ? 'checked' : '') . " onchange='this.form.submit()'>";
                                        echo "</form>";
                                    } else {
                                        echo htmlspecialchars($subtask['name']);
                                    }
                                    if ($user_role === 'owner' || $task_assignment_role === 'editor') {
                                       
                                        echo "<form method='POST' style='display:inline;'>";
                                        echo "<input type='hidden' name='subtask_id' value='" . $subtask['id'] . "'>";
                                        echo "<button type='submit' name='delete_subtask' id='bin_smol_ic' onclick='return confirm(\"Are you sure you want to delete this subtask?\")'>
                                        <img class='bin_smol' src='./css/drawables/bin_closed.png'></button>";
                                        echo "</form>";
                                        echo "</div>";
                                    }
                                    echo "</li>";
                                }
                                echo "</ul>";
                            }
                            ?>
                            <?php if ($user_role === 'owner' || $task_assignment_role === 'editor'): ?>
                                <form method="POST" style="margin-top: 10px;">
                                    <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                                    <div class="form-group">
                                        <label for="subtask_name">Add Subtask</label>
                                        <input type="text" name="subtask_name" placeholder="Subtask Name" required>
                                    </div>
                                    <button type="submit" name="add_subtask" class = "new_btn" >Add</button>
                                </form>
                            <?php endif; ?>
                        </div>

                    </div>

                    <?php if ($user_role === 'owner'): ?>
                        <form method="POST" style="display:inline;">
                            <input type="hidden" name="task_id" value="<?php echo $task['id']; ?>">
                            <button id="bin_ic" type="submit" name="delete_task" onclick="return confirm('Are you sure you want to delete this task?')">
                                <img class = "bin" src="./css/drawables/bin_closed.png"></button>
                        </form>
                    <?php endif; ?>

                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <div class="container">
        <a href="profile.php" class="leave_btn">Back to Profile</a>
    </div>
</body>
</html>
