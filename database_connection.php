<?php
// Establish a database connection
$conn = new mysqli('localhost', 'root', '', 'task_manager');
if ($conn->connect_error) {
    die('Connection Failed : ' . $conn->connect_error);
}
?>
