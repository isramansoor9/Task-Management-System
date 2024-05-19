<?php
session_start(); // Start or resume a session

include 'database_connection.php';

// If the user is already logged in, redirect to day_mode.php
if (isset($_SESSION["username"])) {
    header("Location: day_mode.php");
    exit;
}

// Process the login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Check if username exists
    $stmt = $conn->prepare("SELECT password FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        // Username exists, fetch the hashed password
        $stmt->bind_result($hashed_password);
        $stmt->fetch();

        // Verify the password
        if ($password == $hashed_password) {
            // Set the username in the session
            $_SESSION["username"] = $username;
            echo "<script>
                alert('Login successful.');
                window.location.href = 'day_mode.php';
                </script>";
        } else {
            echo "<script>
                alert('Incorrect password.');
                window.location.href = 'login.php';
                </script>";
        }
    } else {
        echo "<script>
            alert('Username does not exist.');
            window.location.href = 'signup.php';
            </script>";
        // Handle username not found
    }

    // Close prepared statement
    $stmt->close();
}

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Page</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="login.css">
    
    <script>
        function validateForm() {
          var username = document.getElementById("username").value;
          var password = document.getElementById("password").value;
    
          if (username.trim() === "" || password.trim() === "") {
            alert("Please enter both username and password.");
            return false; // Prevent form submission
          }
    
          return true; // Allow form submission
        }
    </script>
</head>
<body style="background-color: rgb(69, 67, 67);">
    <div class="login-panel">
        <h2 class="text-center mb-4">Login</h2>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="POST" onsubmit="return validateForm()">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username">
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password">
            </div>
            <button type="submit" class="btn btn-primary btn-block">Login</button>
        </form>
        <div class="text-center mt-3">
            <p>Don't have an account? <a href="signup.php">Sign up</a></p> <!-- Link to signup.html -->
        </div>
    </div>
    
    <!-- Bootstrap JS (Optional) -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
