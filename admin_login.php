<?php
session_start();


$_SESSION["is_admin"] = true;

echo json_encode(["ok" => true]);
?>
