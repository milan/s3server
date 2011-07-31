<?php
include('../../src/s3/lib/storage.php');

$storage = new Storage();
$storage->connect();

function generateAccessId($chars = 20)
{
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
    return substr(str_shuffle($letters), 0, $chars);
}

function generateSecretKey($chars = 40)
{
    $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890abcderghijklmnopqrstuvwxyz';
    return substr(str_shuffle($letters), 0, $chars);
}

$displayName = $_POST['element_1_1'].$_POST['element_1_2'];
$email       = $_POST['element_2'];
$accessId    = generateAccessId();  // a 20 character capitized alphanumeric key i.e. 0PN5J17HBGZHT7JJ3X82
$secretKey   = generateSecretKey(); // a 40 character alphanumeric key will do   i.e. uV3F3YluFJax1cknvbcGwgjvx4QpvBaleU8dUj2o

$storage->createUser($displayName, $email, $accessId, $secretKey);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>Create user</title>
<link rel="stylesheet" type="text/css" href="view.css" media="all">
<script type="text/javascript" src="view.js"></script>

</head>
<body id="main_body" >
	
	<img id="top" src="top.png" alt="">
	<div id="form_container">
	
		<h1><a>Create user</a></h1>
		<form id="form_38026" class="appnitro"  method="post" action="">
					<div class="form_description">
			<h2>Your user is created</h2>
			<p>Remember to keep your accessId and secret key safe</p>
		</div>						
			<ul >
			
		<li id="li_2" >
		<label class="description" for="element_1">Display Name </label>
		<div>
			<input id="element_2" name="element_1" class="element text medium" readonly="readonly" type="text" maxlength="255" value="<?php print $displayName; ?>"/> 
		</div> 
		</li>	
		<li id="li_2" >
		<label class="description" for="element_2">Email </label>
		<div>
			<input id="element_2" name="element_2" class="element text medium" readonly="readonly" type="text" maxlength="255" value="<?php print $email; ?>"/> 
		</div> 
		</li>
		<li id="li_3" >
		<label class="description" for="element_3">AccessID </label>
		<div>
			<input id="element_2" name="element_3" class="element text medium" readonly="readonly" type="text" size="40" maxlength="255" value="<?php print $accessId; ?>"/> 
		</div> 
		</li>
		<li id="li_4" >
		<label class="description" for="element_4">Secret Key </label>
		<div>
			<input id="element_2" name="element_4" class="element text medium" readonly="readonly" type="text" size="40" maxlength="255" value="<?php print $secretKey; ?>"/> 
		</div> 
		</li>					
			</ul>
		</form>	
	</div>
	<img id="bottom" src="bottom.png" alt="">
	</body>
</html>