<?php
session_start();
require 'config.php';
//Process logout header first to prevent error later on
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit;
}
    
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project'])) {
    $project_id = $_POST['project_id'];

    try {
        // Begin transaction to ensure all deletions are successful
        $pdo->beginTransaction();

        // Step 1: Delete task assignments associated with the project
        $stmt = $pdo->prepare("DELETE FROM task_assignments WHERE task_id IN (SELECT id FROM tasks WHERE project_id = ?)");
        $stmt->execute([$project_id]);

        // Step 2: Delete tasks associated with the project
        $stmt = $pdo->prepare("DELETE FROM tasks WHERE project_id = ?");
        $stmt->execute([$project_id]);

        // Step 3: Delete members of the project
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ?");
        $stmt->execute([$project_id]);

        // Step 4: Delete the project itself
        $stmt = $pdo->prepare("DELETE FROM projects WHERE id = ?");
        $stmt->execute([$project_id]);

        // Commit the transaction
        $pdo->commit();

        // Redirect back to profile after deletion
        header("Location: profile.php");
        exit;

    } catch (PDOException $e) {
        // If there's an error, roll back the transaction
        $pdo->rollBack();
        echo "Error: " . $e->getMessage();
    }
}




// Handle leaving the project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['leave_project'])) {
    $project_id = $_POST['project_id'];

    // Check if the user is the owner of the project
    $stmt = $pdo->prepare("SELECT role FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    $user_role = $stmt->fetchColumn();

    if ($user_role === 'owner') {
        // If user is the owner, prevent them from leaving the project
        echo "<p>You cannot leave your own project.</p>";
        exit;
    }

    try {
        // Remove user from the project
        $stmt = $pdo->prepare("DELETE FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);

        $stmt = $pdo->prepare("DELETE FROM task_assignments WHERE task_id IN (SELECT id FROM tasks WHERE project_id = ?) AND user_id = ?");
        $stmt->execute([$project_id, $user_id]);

        // Redirect back to profile after leaving project
        header("Location: profile.php");
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}

// Handle inviting users to project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['invite_user'])) {
    $project_id = $_POST['project_id'];
    $invite_email = $_POST['invite_email'];

    // Check if the email exists in the users table
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$invite_email]);
    $user_to_invite = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user_to_invite) {
        // Check if the user is already a member of the project
        $stmt = $pdo->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
        $stmt->execute([$project_id, $user_to_invite['id']]);
        $existing_member = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing_member) {
            echo "This user is already a member of the project.";
        } else {
            try {
                // Begin a transaction to ensure both inserts succeed
                $pdo->beginTransaction();

                // Insert the invited user into the project_members table with the role of 'viewer'
                $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'viewer')");
                $stmt->execute([$project_id, $user_to_invite['id']]);

                // Insert the invited user into the task_assignments table as a 'viewer' for each task in the project
                $stmt = $pdo->prepare("SELECT id FROM tasks WHERE project_id = ?");
                $stmt->execute([$project_id]);
                $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

                foreach ($tasks as $task) {
                    // Assign the user as a viewer to each task
                    $stmt = $pdo->prepare("INSERT INTO task_assignments (task_id, user_id, role) VALUES (?, ?, 'viewer')");
                    $stmt->execute([$task['id'], $user_to_invite['id']]);
                }

                // Commit the transaction
                $pdo->commit();

                echo "User invited successfully as a viewer!";
            } catch (PDOException $e) {
                // Rollback the transaction if there is any error
                $pdo->rollBack();
                echo "Error: " . $e->getMessage();
            }
        }
    } else {
        echo "<script>alert('No user found with that email.')</script>";
    }
}


// Fetch the user's own projects (projects they own)
$stmt = $pdo->prepare("SELECT projects.*, project_members.role FROM projects
                        JOIN project_members ON projects.id = project_members.project_id
                        WHERE project_members.user_id = ? AND project_members.role = 'owner'");
$stmt->execute([$user_id]);
$projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch the projects the user is invited to (but not the owner)
$stmt = $pdo->prepare("SELECT projects.*, project_members.role FROM projects
                        JOIN project_members ON projects.id = project_members.project_id
                        WHERE project_members.user_id = ? AND project_members.role != 'owner'");
$stmt->execute([$user_id]);
$invited_projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <script src="javascript/epic_loader.js"></script>
    <link rel="stylesheet" href="css/profile.css">
</head>
<body>
    <header>
         <ul>
            <li><h2 id="splash">Welcome to Streamline<!--,<?php echo htmlspecialchars($user_id); ?>-->!</h2></li>
            <li>
                <form id ="logout" method="POST" action="profile.php">
                    <button id="logout_btn" type="submit" name="logout">Logout</button>
                </form>
            </li>
        </ul>
    </header>
    <div id="progress-bar"></div>
    <div class = "title">
        <h3>Projects You Created</h3>
        <button type = "button" id ="add" class="create-btn" onclick="openAdd()">&plus;</button>
    </div>
    <div id= "new_project">
        <div id = "project_pop">
        <form method="POST">
            <input type="text" name="project_name" required placeholder="Project Name">
        <div class ="button_group">
            <button type="submit" class ="new_btn" formaction="create_project.php">Create</button>
            <button class="del_btn" type="button" onclick="closeForm()">Close</button>
        </div>   
        </form>
        </div>
    </div>
    <div id="create-project">
    <ul>
        <?php foreach ($projects as $project): ?>
            <li>
                <?php echo htmlspecialchars($project['name']); ?>
                <a href="tasks.php?project_id=<?php echo $project['id']; ?>">
                    <button class="new_btn">View Tasks</button>
                </a>
                <!-- Invite user form -->
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <input type="email" name="invite_email" placeholder="Invite by email" required>
                    <button class=" new_btn" type="submit" name="invite_user">Invite User</button>
                </form>

                <form method="POST" style="display:inline;">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <button class="del_btn" type="submit" name="delete_project" onclick="return confirm('Are you sure you want to delete this project?')">Delete</button>
                </form>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <button class="leave_btn" type="button" disabled>Cannot Leave Your Own Project</button>
                </form>  
            </li>
            <br><br>
        <?php endforeach; ?>
    </ul>
    </div>
    <div class = "title">     
    <h3>Projects You Are Invited To</h3><br>
    </div>
    <div id="invited-projects">
    <ul>
        <?php foreach ($invited_projects as $project): ?>
            <li>
                <?php echo htmlspecialchars($project['name']); ?>
                <form method="POST" style="display:inline;">
                    <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                    <button class="del_btn" type="submit" name="leave_project"onclick="return confirm('Are you sure you want to leave this project?')">Leave</button>
                </form>

                <a href="tasks.php?project_id=<?php echo $project['id']; ?>">
                    <button class="new_btn">View Tasks</button>
                </a>
            </li>
            <br><br>
        <?php endforeach; ?>
    </ul>

    </div>
    
    
</body>
<footer>
    <p>Designed by Hazziq, Lewis, Chun Zen and Si Thu.</p>
</footer>
</html>