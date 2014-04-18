
<html>
<head>
<title>Jedalne</title>
<meta http-equiv="text/html" charset="utf-8"/>
<script type="text/javascript" src="table.js"></script>
<link rel="stylesheet" type="text/css" href="style.css" />
</head>
<body>
<?php
  error_reporting(E_ALL);
  ini_set('display_errors',1);
  session_start();
  require_once('User.php');
  try
  {
    $user = new User();
    if(isset($_POST['user']) && isset($_POST['password']))
    {
      $user->login($_POST['user'],$_POST['password']);
    }
    else
    {
      $user->login();
    }
	$user->parseHistory();
	$user->updateLocalDb();
    echo $user->displayDashBoard();
  }catch(Exception $e)
  {
    echo $e->getMessage();
?>

<form action="" method="post">
<table cellspacing="0" cellpadding="0">
  <tr><td>
<label for="user">Prihlasovacie meno:</label></td><td>
<input type="text" name="user" id="user" /></td></tr>
<tr><td>
<label for="password">Heslo:</label></td><td>
<input type="password" name="password" id="password" /></td></tr>
</table>
<input type="submit" value="Prihlásiť" />
</form>
<?php
  }
?>
</body>
</html>
<?php
  session_write_close();
?>
