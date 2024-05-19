<?php
session_start(); // Start or resume a session

// Check if user is logged in, if not redirect to login page
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit;
}

include 'database_connection.php';

// Retrieve user's username
$username = $_SESSION["username"];

// Count the number of completed tasks for the user
$stmt = $conn->prepare("SELECT COUNT(*) AS completed_tasks FROM user_tasks WHERE username = ? AND status = 'completed'");
if ($stmt) {
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $completed_tasks = $row['completed_tasks'];
    $stmt->close();
} else {
    echo "Error: " . $conn->error;
}

// Check if the user has completed less than 5 tasks
if ($completed_tasks < 5) {
    // Display an alert informing the user of the number of completed tasks
    echo "<script>alert('You have completed $completed_tasks tasks. You need to complete " . (5 - $completed_tasks) . " more tasks to play the game.');</script>";
    // Redirect to night_mode.php after acknowledging the alert
    echo "<script>window.location.href = 'night_mode.php';</script>";
    exit; // Stop further execution of the PHP code
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Points - Task Management</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="night_styles.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

    <style>
        /* Your existing CSS styles */
        /* Custom styles for the panel */
        .panel {
            background-color: gray;
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin: 20px;
            text-align: center;
        }

        /* Snake game styles */
        #game-container {
            width: 300px;
            height: 300px;
            background-color: <?php echo $completed_tasks < 5 ? 'gray' : 'gray'; ?>;
            border-radius: 10px;
            border: 2px solid #212529;
            position: relative;
            overflow: hidden;
            display: inline-block;
            margin-bottom: 20px;
            visibility: <?php echo $completed_tasks < 5 ? 'hidden' : 'visible'; ?>;
        }

        #game-controls {
            width: 300px;
            margin-top: 20px;
            text-align: center;
            display: inline-block;
            visibility: <?php echo $completed_tasks < 5 ? 'hidden' : 'visible'; ?>;
        }

        #game-controls button {
            margin: 5px;
        }

        #game-controls p {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 10px;
        }

        .rules-panel {
            background-color: #212529;
            border-radius: 10px;
            padding: 20px;
            margin-top: -20px;
            margin-left: 20px;
            color: #ffffff;
            display: <?php echo ($completed_tasks < 5) ? 'none' : 'inline-block'; ?>;
            vertical-align: top;
            width: 300px;
            height: 300px;
        }

        .rules-panel h3 {
            margin-top: 0;
        }

        .rules-panel ul {
            list-style-type: none;
            padding: 0;
        }

        .rules-panel li {
            margin-bottom: 10px;
        }

        .main-container {
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            height: 100vh;
        }

        .main-content {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            align-items: center;
            max-width: 1000px; /* Adjust as needed */
        }

        .completed-tasks {
            text-align: center;
            margin-top: 20px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="main-container">
        <div class="completed-tasks">
            <div class="panel">
                <h2>Your Completed Tasks</h2>
                <p>Total Completed Tasks: <?php echo $completed_tasks; ?></p>
            </div>
        </div>
        <div class="main-content">
            <div id="game-container">
                <canvas id="gameCanvas" width="300" height="300"></canvas>
            </div>
            <div class="rules-panel">
                <h3>Rules</h3>
                <ul>
                    <li>Use arrow keys to control the snake.</li>
                    <li>Eat the red food to grow and earn points.</li>
                    <li>Don't run into the walls or into yourself!</li>
                </ul>
                <div id="game-controls">
                    <form method="post" action="">
                        <button style="margin-right: 40px;" id="startButton" class="btn btn-primary" name="startButton">Start</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS (Optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <script>
        // Snake game functionality
        let snake;
        let food;
        let dx = 10;
        let dy = 0;
        let changingDirection = false;
        let score = 0;
        const gameCanvas = document.getElementById('gameCanvas');
        const ctx = gameCanvas.getContext('2d');
        let gameInterval; // Define gameInterval outside main function
        let isPaused = false; // Track if the game is paused

        function main() {
            gameInterval = setInterval(function onTick() {
                if (!isPaused) { // Check if the game is not paused
                    changingDirection = false;
                    clearCanvas();
                    moveSnake();
                    drawSnake();
                    drawFood();
                    if (endGame()) {
                        clearInterval(gameInterval);
                    }
                }
            }, 100);
        }

        function endGame() {
            for (let i = 4; i < snake.length; i++) {
                if (snake[i].x === snake[0].x && snake[i].y === snake[0].y) {
                    gameFinished();
                    return true;
                }
            }

            if (snake[0].x < 0 || snake[0].x >= gameCanvas.width || snake[0].y < 0 || snake[0].y >= gameCanvas.height) {
                gameFinished();
                return true;
            }

            return false;
        }

        function gameFinished() {
            alert("Game Finished!");
            window.location.href = 'night_mode.php';
        }

        function clearCanvas() {
            ctx.clearRect(0, 0, gameCanvas.width, gameCanvas.height);
        }

        function drawSnakePart(snakePart) {
            ctx.fillStyle = 'white';
            ctx.fillRect(snakePart.x, snakePart.y, 10, 10);
        }

        function drawSnake() {
            snake.forEach(drawSnakePart);
        }

        function moveSnake() {
            if (!isPaused) { // Check if the game is not paused
                const head = { x: snake[0].x + dx, y: snake[0].y + dy };
                snake.unshift(head);
                if (head.x === food.x && head.y === food.y) {
                    score += 1;
                    createFood();
                } else {
                    snake.pop();
                }
            }
        }

        function changeDirection(event) {
            const LEFT_KEY = 37;
            const RIGHT_KEY = 39;
            const UP_KEY = 38;
            const DOWN_KEY = 40;

            if (changingDirection) return;
            changingDirection = true;

            const keyPressed = event.keyCode;
            const goingUp = dy === -10;
            const goingDown = dy === 10;
            const goingRight = dx === 10;
            const goingLeft = dx === -10;

            if (keyPressed === LEFT_KEY && !goingRight) {
                dx = -10;
                dy = 0;
            }

            if (keyPressed === UP_KEY && !goingDown) {
                dx = 0;
                dy = -10;
            }

            if (keyPressed === RIGHT_KEY && !goingLeft) {
                dx = 10;
                dy = 0;
            }

            if (keyPressed === DOWN_KEY && !goingUp) {
                dx = 0;
                dy = 10;
            }
        }

        function drawFood() {
            ctx.fillStyle = '#212529';
            ctx.fillRect(food.x, food.y, 10, 10);
        }

        function randomTen(min, max) {
            return Math.round((Math.random() * (max - min) + min) / 10) * 10;
        }

        function createFood() {
            food = { x: randomTen(0, gameCanvas.width - 10), y: randomTen(0, gameCanvas.height - 10) };
            snake.forEach(function isFoodOnSnake(part) {
                const foodIsOnSnake = part.x == food.x && part.y == food.y;
                if (foodIsOnSnake) createFood();
            });
        }

        document.addEventListener('keydown', changeDirection);

        snake = [{ x: 200, y: 200 }, { x: 190, y: 200 }, { x: 180, y: 200 }, { x: 170, y: 200 }, { x: 160, y: 200 }];

        food = { x: 250, y: 250 };
        createFood();

        document.getElementById('startButton').addEventListener('click', function(event) {
            event.preventDefault();
            main();
        });

        document.getElementById('pauseResumeButton').addEventListener('click', function() {
            isPaused = !isPaused; // Toggle pause state
        });
    </script>
</body>
</html>
