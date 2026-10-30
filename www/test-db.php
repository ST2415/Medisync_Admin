<?php
require_once 'db_conn.php';

echo "Connected to MySQL successfully!";

$result = mysqli_query($conn, "SHOW TABLES");

if (!$result) {
    die("Query Error: " . mysqli_error($conn));
}

while ($row = mysqli_fetch_array($result)) {
    echo "<br>" . $row[0];
}
?>