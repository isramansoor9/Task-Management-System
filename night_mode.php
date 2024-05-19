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
    $priority = $_POST['priority'];
    $username = $_SESSION["username"];

    // Check if task is empty
    if (!empty($task)) {
        // Insert new task into the user's task list
        $stmt = $conn->prepare("INSERT INTO user_tasks (username, task, priority) VALUES (?, ?, ?)");
        if ($stmt) {
            $stmt->bind_param("sss", $username, $task, $priority);
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

// Check if filtering by status or priority
$filter = isset($_GET['status']) ? $_GET['status'] : (isset($_GET['priority']) ? $_GET['priority'] : 'all');
if ($filter === 'completed') {
    $sql = "SELECT id, task, status, priority FROM user_tasks WHERE username = ? AND status = 'completed' ORDER BY FIELD(priority, 'High', 'Medium', 'Low')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
} elseif ($filter === 'incomplete') {
    $sql = "SELECT id, task, status, priority FROM user_tasks WHERE username = ? AND status != 'completed' ORDER BY FIELD(priority, 'High', 'Medium', 'Low')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
} elseif ($filter === 'High' || $filter === 'Medium' || $filter === 'Low') {
    $sql = "SELECT id, task, status, priority FROM user_tasks WHERE username = ? AND priority = ? ORDER BY FIELD(priority, 'High', 'Medium', 'Low')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $filter);
} else {
    $sql = "SELECT id, task, status, priority FROM user_tasks WHERE username = ? ORDER BY FIELD(priority, 'High', 'Medium', 'Low')";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
}

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
    <title>Night Mode - Task Management</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="night_styles.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <style>
        /* Change the color of the placeholder text to red */
        ::placeholder {
            color: whitesmoke !important;
        }
        .body {
            color: white !important;
        }
        h2 {
            color: white !important;
        }

        .task-item {
            background-color: gray !important;
        }
        .task-text {
            color: white;
        }
        .completed-text {
            color: lightgray;
            text-decoration: line-through;
        }
        /* Add hover effect to list items */
        .task-item:hover {
            background-color: rgb(102, 101, 101) !important;
        }

        /* Floating animation for the Add Task button */
        .floating-button {
            animation: floating 2s infinite ease-in-out;
        }

        .list-group-item {
            background-color: gray !important;
        }

        .list-group-item:hover {
            background-color: rgb(102, 101, 101) !important;
        }

        @keyframes floating {
            0% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-10px);
            }
            100% {
                transform: translateY(0);
            }
        }

        /* Container div to fix the switch position */
        .switch-container {
            position: fixed;
            top: 10px; /* Adjust top position as needed */
            right: 10px; /* Adjust right position as needed */
            z-index: 9999; /* Ensure it's above other elements */
        }

        .form-control {
            background-color: gray;
            color: white !important;
        }

        textarea,
        .form-control {
            background-color: gray !important;
            color: whitesmoke !important;
            border-color: gray;
        }

        /* Gray color for dropdown */
        .dropdown-menu {
            background-color: gray;
        }

        .dropdown-item {
            color: whitesmoke;
        }

        .dropdown-item:hover {
            background-color: rgb(102, 101, 101);
        }

        /* Center the priority text */
        .priority-text {
            display: block;
            text-align: center;
            width: 100px; /* Adjust as needed */
        }

        .logout-button {
            margin-left: 90px; /* Adjust margin as needed */
            position: static; /* Reset position */
            margin-top: 50px; /* New margin-top */
        }
        
    </style>

<script>
    // Function to delete task
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

    // Function to validate task
    function validateTask() {
        var taskInput = document.getElementById("task").value.trim(); // Trim to remove leading and trailing whitespace
        if (taskInput === "") {
            alert("Task cannot be empty");
            return false;
        }
        return true;
    }

    // Function to redirect to day_mode.php
    function toggleTheme() {
        window.location.href = 'day_mode.php';
    }

    // Function to filter tasks by priority
    function filterByPriority(priority) {
        var filterLink = '?priority=' + priority;
        window.location.href = filterLink;
    }

    // Function to show dropdown menu when "Filter by Priority" button is clicked
    document.addEventListener('DOMContentLoaded', function() {
        var priorityDropdown = document.getElementById('priorityDropdown');
        priorityDropdown.addEventListener('click', function() {
            var dropdownMenu = document.querySelector('.dropdown-menu');
            dropdownMenu.classList.toggle('show');
        });
    });
</script>

</head>
<body>
    <div class="switch-container">
        <!-- Toggle Switch with checked attribute -->
        <label class="switch toggle-switch">
            <input type="checkbox" onclick="toggleTheme()" checked>
            <span class="slider round"></span>
        </label>
    </div>

    <!-- Logout Button -->
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" class="logout-button">
        <button type="submit" class="btn btn-secondary" name="logout">Logout</button>
    </form>

    <div class="container mt-5" style="margin-bottom: 50px;"> <!-- Adjust the margin-bottom value as needed -->
        <h2 style="margin-top: -30px;" class="text-center mb-4" style="color: whitesmoke !important;">Task Management</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return validateTask();">
            <div class="form-group">
                <label style="color: whitesmoke;" for="task">Create Task:</label>
                <input style="background-color: gray; color: whitesmoke; border-color: gray;" type="text" class="form-control" id="task" name="task" placeholder="Enter your task" />
            </div>
            <div class="form-group">
                <label style="color: whitesmoke;" for="priority">Priority:</label>
                <select class="form-control" id="priority" name="priority">
                    <option class="form-control" value="High">High</option>
                    <option class="form-control" value="Medium" selected>Medium</option>
                    <option class="form-control" value="Low">Low</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary floating-button" name="submit">Add Task</button>
        </form>
        <hr>
        <h3 style="color: whitesmoke">Tasks:</h3>
        <!-- Add task filtering buttons -->
        <div class="mt-3">
            <a href="?status=all" class="btn btn-secondary">All Tasks</a>
            <a href="?status=completed" class="btn btn-secondary">Completed Tasks</a>
            <a href="?status=incomplete" class="btn btn-secondary">Incomplete Tasks</a>
            <div class="dropdown" style="display: inline-block;">
                <button class="btn btn-secondary dropdown-toggle" type="button" id="priorityDropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    Filter by Priority: All
                </button>
                <div class="dropdown-menu" aria-labelledby="priorityDropdown">
                    <a class="dropdown-item" href="#" onclick="filterByPriority('All')">All Priorities</a>
                    <a class="dropdown-item" href="#" onclick="filterByPriority('High')">High Priority</a>
                    <a class="dropdown-item" href="#" onclick="filterByPriority('Medium')">Medium Priority</a>
                    <a class="dropdown-item" href="#" onclick="filterByPriority('Low')">Low Priority</a>
                </div>
            </div>
            <a href="points.php" class="btn btn-secondary">Check Points</a>
        </div>


        <ul class="list-group mt-3">
            <?php
            if ($result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $task_id = $row["id"];
                    $task_text = $row["task"];
                    $task_status = $row["status"];
                    $priority = $row["priority"];
                    echo "<li id='task_$task_id' class='list-group-item task-item d-flex justify-content-between align-items-center'>
                        <span class='task-text " . ($task_status == 'completed' ? 'completed-text' : '') . "'>$task_text</span>
                        <span class='priority-text'>$priority</span>
                        <div class='btn-group' role='group'>
                            <button type='button' class='btn btn-danger btn-sm' onclick='deleteTask($task_id)'>Delete</button>";
                                                    
                    // Display "Completed" button only if the task is incomplete
                    if ($task_status != 'completed') {
                        echo "<form action='" . htmlspecialchars($_SERVER["PHP_SELF"]) . "' method='POST' style='display: inline;'>
                                <input type='hidden' name='task_id' value='$task_id'>
                                <button type='submit' class='btn btn-success btn-sm' name='complete'>Completed</button>
                            </form>";
                    }

                    echo "<form action='edit_task_night.php' method='POST' style='display: inline;'>
                            <input type='hidden' name='task_id' value='$task_id'>
                            <button type='submit' class='btn btn-info btn-sm' name='edit'>Edit</button>
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
    <script src="task_management.js"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
