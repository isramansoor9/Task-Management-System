<?php
session_start(); // Start or resume a session

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

include 'database_connection.php';

// Delete task if delete button is clicked
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['delete'])) {
    $task_id = $_POST['task_id'];

    // Delete task from the user's task list
    $stmt = $conn->prepare("DELETE FROM user_tasks WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Process the task submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['submit'])) {
    $task = trim($_POST['task']); // Trim to remove leading and trailing whitespace
    $username = $_SESSION["username"];

    // Check if task is empty
    if (!empty($task)) {
        // Insert new task into the user's task list
        $stmt = $conn->prepare("INSERT INTO user_tasks (username, task) VALUES (?, ?)");
        if ($stmt) {
            $stmt->bind_param("ss", $username, $task);
            $stmt->execute();
            $stmt->close();
        } else {
            echo "Error: " . $conn->error;
        }
    } else {
        // Task is empty, display JavaScript alert
        echo "<script>alert('Task cannot be empty');</script>";
    }
}

// Mark task as completed
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['complete'])) {
    $task_id = $_POST['task_id'];

    // Update the status of the task to 'completed' in the database
    $stmt = $conn->prepare("UPDATE user_tasks SET status = 'completed' WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $task_id);
        $stmt->execute();
        $stmt->close();
    } else {
        echo "Error: " . $conn->error;
    }
}

// Logout functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['logout'])) {
    // Destroy the session and redirect to login page
    session_destroy();
    header("Location: login.php");
    exit;
}


// Retrieve user's tasks
$username = $_SESSION["username"];
$sql = "SELECT id, task, status FROM user_tasks WHERE username = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

// Close the database connection
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Day Mode - Task Management</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="day_styles.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        .logout-button {
            margin-left: 90px; /* Adjust margin as needed */
            position: static; /* Reset position */
            margin-top: 50px; /* New margin-top */
        }
    </style>
    <script>
        function deleteTask(taskId) {
            // Send AJAX request to delete task
            var xhttp = new XMLHttpRequest();
            xhttp.onreadystatechange = function() {
                if (this.readyState == 4 && this.status == 200) {
                    // Remove the list item from the page
                    var listItem = document.getElementById("task_" + taskId);
                    if (listItem) {
                        listItem.remove();
                    }
                }
            };
            xhttp.open("POST", "<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>", true);
            xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            xhttp.send("delete=true&task_id=" + taskId);
        }

        function validateTask() {
            var taskInput = document.getElementById("task").value.trim(); // Trim to remove leading and trailing whitespace
            if (taskInput === "") {
                alert("Task cannot be empty");
                return false;
            }
            return true;
        }

        // Function to redirect to night_mode.php
        function toggleTheme() {
            // Get the current URL
            var currentUrl = window.location.href;
            // Check if it's day_mode.php or night_mode.php
            if (currentUrl.includes('day_mode.php')) {
                // If it's day_mode.php, redirect to night_mode.php
                window.location.href = 'night_mode.php';
            } else {
                // If it's night_mode.php, redirect to day_mode.php
                window.location.href = 'day_mode.php';
            }
        }
    </script>
</head>
<body>
    <!-- Toggle Switch -->
    <label class="switch toggle-switch">
        <input type="checkbox" onclick="toggleTheme()">
        <span class="slider round"></span>
    </label>

    <!-- Logout Button -->
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="logout-button">
        <button type="submit" class="btn btn-secondary" name="logout">Logout</button>
    </form>

    <div class="container mt-5" style="margin-bottom: 50px;"> <!-- Adjust the margin-bottom value as needed -->
        <h2 style="margin-top: -30px;" class="text-center mb-4">Task Management</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return validateTask();">
            <div class="form-group">
                <label for="task">Create Task:</label>
                <input type="text" class="form-control" id="task" name="task" placeholder="Enter your task">
            </div>
            <button type="submit" class="btn btn-primary" name="submit">Add Task</button>
        </form>
        <hr>
        <h3>Tasks:</h3>
        <ul class="list-group mt-3">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $task_id = $row["id"];
                    $task_text = $row["task"];
                    $task_status = $row["status"];
                    echo "<li id='task_$task_id' class='list-group-item d-flex justify-content-between align-items-center'>
                            <span class='" . ($task_status == 'completed' ? 'completed' : '') . "'>$task_text</span>
                            <div class='btn-group' role='group'>
                                <button type='button' class='btn btn-danger btn-sm mr-1' onclick='deleteTask($task_id)'>Delete</button>";
                                
                    // Display "Completed" button only if the task is incomplete
                    if ($task_status != 'completed') {
                        echo "<form action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' method='POST' style='display: inline;'>
                                <input type='hidden' name='task_id' value='$task_id'>
                                <button type='submit' class='btn btn-success btn-sm mr-1' name='complete'>Completed</button>
                              </form>";
                    }

                    echo "<form action='edit_task_day.php' method='POST' style='display: inline;'>
                            <input type='hidden' name='task_id' value='$task_id'>
                            <button type='submit' class='btn btn-info btn-sm mr-1' name='edit'>Edit</button>
                          </form>
                        </div>
                      </li>";
                }
            } else {
                echo "<li class='list-group-item'>No tasks yet.</li>";
            }
            ?>
        </ul>
    </div>
    <!-- Bootstrap JS (Optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
