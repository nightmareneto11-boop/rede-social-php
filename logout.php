<?php require 'lib.php';
if($_SERVER['REQUEST_METHOD']==='POST'&&hash_equals(csrf(),$_POST['csrf']??'')){session_destroy();}
header('Location: login.php');
