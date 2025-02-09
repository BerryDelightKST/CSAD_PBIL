<?php
//For this file's php, the entire php statements until before the html was created by Lewis and edited by Si Thu during UI implementation.
session_start();
require 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['project_id']) || !is_numeric($_GET['project_id'])) {
    echo "Invalid or missing project ID.";
    exit;
}

$project_id = $_GET['project_id'];
$user_id = $_SESSION['user_id'];

// Fetch the project name
$stmt = $pdo->prepare("SELECT name FROM projects WHERE id = ?");
$stmt->execute([$project_id]);
$project = $stmt->fetch();

if (!$project) {
    echo "Project not found.";
    exit;
}

$project_name = $project['name'];

// Fetch tasks for the specific project
$stmt = $pdo->prepare("SELECT * FROM tasks WHERE project_id = ?");
$stmt->execute([$project_id]);
$tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!--The entire html and inline php statement has been created by Si Thu before and edited during UI implementation-->
<!--The ability of being able to create the gantt chart was implemented by Lewis while Si Thu made the chart, downloadable as png-->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Gantt Chart for <?php echo htmlspecialchars($project_name); ?></title>
    <link rel="stylesheet" href="css/chart.css">
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script type="text/javascript">
        google.charts.load('current', {
            packages: ['gantt']
        });
        google.charts.setOnLoadCallback(drawChart);
        function downloadImage() {
            html2canvas(document.getElementById("chart_div")).then(canvas => {
                let a = document.createElement("a");
                a.href = canvas.toDataURL("image/png");
                a.download = "chart.png";
                a.click();
            });
        }
        function drawChart() {
            var data = new google.visualization.DataTable();
            data.addColumn('string', 'Task ID');
            data.addColumn('string', 'Task Name');
            data.addColumn('string', 'Resource');
            data.addColumn('date', 'Start Date');
            data.addColumn('date', 'End Date');
            data.addColumn('number', 'Duration');
            data.addColumn('number', 'Percent Complete');
            data.addColumn('string', 'Dependencies');

            <?php
            // Prepare the tasks for the Gantt chart
            foreach ($tasks as $task) {
                $task_id = $task['id'];
                $task_name = addslashes($task['name']);
                $start_date = date('Y, m, d', strtotime($task['start_date']));
                $end_date = date('Y, m, d', strtotime($task['end_date']));
                $progress = $task['progress'];

                echo "data.addRow(['$task_id', '$task_name', '', new Date($start_date), new Date($end_date), null, $progress, null]);";
            }
            ?>

            var chart = new google.visualization.Gantt(document.getElementById('chart_div'));
            chart.draw(data);
        }
    </script>
</head>
<header><h2>Gantt Chart for Project: <?php echo htmlspecialchars($project_name); ?></h2></header>
<body>
    <div id="chart_div"></div>
    <div class="bottom_buttons">
        <button onclick="location.href='tasks.php?project_id=<?php echo $project_id; ?>'" class="new_btn">Back to Tasks</button>
        <button onclick="downloadImage()" id="download">
            <img src="./css/drawables/download.png" class="icon">
        </button>
    </div>
    <footer>
        <p>Designed by Hazziq, Lewis, Chun Zen and Si Thu. Script sourced from gstatics.com</p> 
    </footer>
</body>
</html>
