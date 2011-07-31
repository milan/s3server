<?php
require "../s3/server.php";
$service = 'soap';
$s3      = new SimpleStorageService();
$s3->process($service);
?>
