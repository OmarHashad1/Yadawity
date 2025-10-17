<?php

$db = new mysqli('127.0.0.1', 'root', '', 'yadawity');

if($db->connect_error){
    die("Connection Failed: " . $db->connect_error);
}

?>