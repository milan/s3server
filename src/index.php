<?php
error_reporting(E_ALL ^ E_NOTICE);
require "s3/server.php";
$service = 'rest';
$s3      = new SimpleStorageService();
$s3->process($service);
?>
