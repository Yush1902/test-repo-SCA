<?php
// blog.php – A sample vulnerable PHP blog app

// Hardcoded credentials (Vulnerability #1)
$dbHost = 'localhost';
$dbUser = 'root';
$dbPass = 'password123'; // hardcoded sensitive info
$dbName = 'blog';

$conn = new mysqli($dbHost, $dbUser, $dbPass, $dbName);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create blog table if not exists
$conn->query("
    CREATE TABLE IF NOT EXISTS posts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title TEXT,
        content TEXT,
        author VARCHAR(255)
    )
");

function displayForm() {
    echo <<<EOD
    <h2>Create Post</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        Title: <input type="text" name="title"><br><br>
        Content:<br>
        <textarea name="content" rows="10" cols="50"></textarea><br><br>
        Author: <input type="text" name="author"><br><br>
        Upload Image: <input type="file" name="image"><br><br>
        <input type="submit" name="submit" value="Post">
    </form>
    EOD;
}

if (isset($_POST['submit'])) {
    // SQL Injection (Vulnerability #2)
    $title = $_POST['title'];
    $content = $_POST['content'];
    $author = $_POST['author'];

    $query = "INSERT INTO posts (title, content, author) VALUES ('$title', '$content', '$author')";
    if ($conn->query($query)) {
        echo "<p>Post created!</p>";
    } else {
        echo "<p>Error: " . $conn->error . "</p>";
    }

    // Insecure File Upload (Vulnerability #3)
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $target = "uploads/" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $target);
        echo "<p>Image uploaded to $target</p>";
    }
}

// Display posts
$result = $conn->query("SELECT * FROM posts ORDER BY id DESC");
while ($row = $result->fetch_assoc()) {
    $title = $row['title'];     // XSS Vulnerability #4
    $content = $row['content'];
    $author = $row['author'];

    echo "<div style='border:1px solid #ccc; padding:10px; margin:10px'>";
    echo "<h3>$title</h3>";
    echo "<p>$content</p>";
    echo "<small>Written by: $author</small>";
    echo "</div>";
}

if (isset($_GET['search'])) {
    $term = $_GET['search'];
    echo "<p>Searching for '$term'</p>";
    $output = shell_exec("grep -i '$term' blog.php");
    echo "<pre>$output</pre>";
}

echo <<<EOD
    <form method="GET">
        <input type="text" name="search" placeholder="Search code...">
        <input type="submit" value="Search">
    </form>
EOD;

displayForm();
?>
