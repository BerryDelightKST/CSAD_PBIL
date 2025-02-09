<?php
//For this file's php, the entire php statements was created by Haaziq and edited by Si Thu during UI implementation.
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, redirect to login page
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get project data from the form
    $project_name = $_POST['project_name'];

    try {
        // Insert new project into the database
        $stmt = $pdo->prepare("INSERT INTO projects (name, user_id) VALUES (?, ?)");
        $stmt->execute([$project_name, $user_id]);

        // Get the ID of the newly created project
        $project_id = $pdo->lastInsertId();

        // Optionally, assign the user as the project owner
        $stmt = $pdo->prepare("INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, ?)");
        $stmt->execute([$project_id, $user_id, 'owner']);

        // Redirect to the newly created project's tasks page
        header("Location: tasks.php?project_id=$project_id");
        exit;

    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>


