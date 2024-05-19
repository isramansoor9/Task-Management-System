<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

include 'database_connection.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $task_id = $_POST['task_id'];
    $new_task = $_POST['task'];

    // Update the task in the database
    $stmt = $conn->prepare("UPDATE user_tasks SET task = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $new_task, $task_id);
        $stmt->execute();
        $stmt->close();
        header("Location: day_mode.php"); // Redirect back to task list
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}

// Fetch the task details for editing
if (isset($_POST['task_id'])) {
    $task_id = $_POST['task_id'];
    $stmt = $conn->prepare("SELECT task FROM user_tasks WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->bind_result($task);
        $stmt->fetch();
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center mb-4">Edit Task</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST">
            <div class="form-group">
                <label for="task">Task:</label>
                <input type="text" class="form-control" id="task" name="task" value="<?php echo $task; ?>">
            </div>
            <input type="hidden" name="task_id" value="<?php echo $task_id; ?>">
            <button type="submit" class="btn btn-primary" name="submit">Update Task</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
