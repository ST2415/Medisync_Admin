<?php
    date_default_timezone_set('Asia/Bangkok');
    $servername = "crud_db";
    $username = "user";
    $password = "userpassword";
    $dbname = "medisync";

    $conn = mysqli_connect($servername, $username, $password, $dbname);
    if(!$conn){
        die("Connection failed".mysqli_connect_error());
    }
    //echo"Connection successfully";
?>