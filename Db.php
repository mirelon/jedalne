<?php


class Db
{
  public static $link;

  public static function init()
  {
    require_once 'config.php';
    self::$link = mysql_connect($config['host'], $config['user'], $config['password']) or die('Could not connect to database.');
    mysql_select_db($config['dbname']);
  }

  public static function terminate()
  {
    mysql_close(self::$link);
  }

  public static function fetchAll($sql)
  {
    $result = mysql_query($sql);
    $rows = array();
    while($row = mysql_fetch_assoc($result))
    {
      $rows []= $row;
    }
    return $rows;
  }

  public static function fetchRow($sql)
  {
    $result = mysql_query($sql);
    return mysql_fetch_assoc($result);
  }


  public static function forceGetUserId($login, $data)
  {
	if(empty($login))throw new Exception('No username available.');
    $sql = sprintf('SELECT `user_id` FROM `users` WHERE `user_remote_login` = "%s"',
        mysql_real_escape_string($login)
      );
    $row = Db::fetchRow($sql);
    if(isset($row['user_id']))
    {
      $user_id = $row['user_id'];
    }
    else
    {
      $sql = sprintf('INSERT INTO `users`(`user_name`, `user_surname`, `user_email`, `user_address`, `user_sex`, `user_remote_login`) VALUES ("%s", "%s", "%s", "%s", "%s", "%s")',
          mysql_real_escape_string($data['user_name']),
          mysql_real_escape_string($data['user_surname']),
          mysql_real_escape_string($data['user_email']),
          mysql_real_escape_string($data['user_address']),
          mysql_real_escape_string($data['user_sex']),
          mysql_real_escape_string($login)
        );
      mysql_query($sql);
      $user_id = mysql_insert_id();
    }
    return $user_id;
  }

  public static function forceGetMealId($shortcut, $description='')
  {
    $sql = sprintf('SELECT `meal_id` FROM `meals` WHERE `meal_shortcut` = "%s" AND `meal_description` = "%s"',
        mysql_real_escape_string($shortcut),
		mysql_real_escape_string($description)
      );
    $row = Db::fetchRow($sql);
    if(isset($row['meal_id']))
    {
      $meal_id = $row['meal_id'];
    }
    else
    {
      $sql = sprintf('INSERT INTO `meals`(`meal_shortcut`, `meal_description`)
              VALUES ("%s", "%s")',
          mysql_real_escape_string($shortcut),
          mysql_real_escape_string($description)
      );
      mysql_query($sql);
      $meal_id = mysql_insert_id();
	  $sql = 'UPDATE `meals` SET `meal_parent_id` = '.$meal_id.' WHERE `meal_id` = '.$meal_id;
	  mysql_query($sql);
    }
    return $meal_id;
  }
  
  public static function addUserMealId($user_id, $meal_id, $user_meal_timestamp, $user_meal_price)
  {
	$sql = sprintf('SELECT `user_meal_id` FROM `user_meals` WHERE `user_id` = "%s" AND `meal_id` = "%s" AND `user_meal_timestamp` = "%s"',
        mysql_real_escape_string($user_id),
		mysql_real_escape_string($meal_id),
		mysql_real_escape_string($user_meal_timestamp)
      );
    $row = Db::fetchRow($sql);
	if($row)
	{
		return $row['user_meal_id'];
	}
	$sql = sprintf('INSERT INTO `user_meals` (`user_id`, `meal_id`, `user_meal_timestamp`, `user_meal_price`)
			VALUES ("%s", "%s", "%s", "%s")',
		mysql_real_escape_string($user_id),
		mysql_real_escape_string($meal_id),
		mysql_real_escape_string($user_meal_timestamp),
		mysql_real_escape_string($user_meal_price)
		);
	mysql_query($sql);
	$user_meal_id = mysql_insert_id();
	return $user_meal_id;
  }
  
  public static function fetchMealCounts($limit=10, $user_id = null)
  {
	$where_user_id = '';
	if(!is_null($user_id))
	{
		$where_user_id = 'AND `um`.`user_id` = ' . mysql_real_escape_string($user_id) . ' ';
	}
        $sql = 'SELECT `m2`.`meal_description` AS `description`, count(*) as `count`, `m2`.`meal_id` AS `meal_id` FROM `user_meals` AS `um` JOIN `meals` AS `m` ON `um`.`meal_id` = `m`.`meal_id` JOIN `meals` AS `m2` ON `m2`.`meal_id` = `m`.`meal_parent_id` WHERE `m`.`meal_competitive`=1 ' . $where_user_id . 'GROUP BY `m2`.`meal_parent_id` ORDER BY COUNT(*) DESC LIMIT '.$limit;
        return Db::fetchAll($sql);
  }
  
  public static function addAccess()
  {
	$sql = 'INSERT INTO `user_accesses` (`user_id`, `user_access_timestamp`) VALUES (' . mysql_real_escape_string($_SESSION['user_id']) . ', NOW())';
	mysql_query($sql);
  }
  
  public static function getKing($meal_id)
  {
	$sql = 'SELECT `u`.`user_id` AS `user_id`, CONCAT(`u`.`user_name`," ",`u`.`user_surname`) AS `name`, COUNT(*) AS `count` FROM `user_meals` AS `um` JOIN `meals` AS `m` ON `m`.`meal_id` = `um`.`meal_id` JOIN `users` AS `u` ON `u`.`user_id` = `um`.`user_id` WHERE `m`.`meal_parent_id`=' . mysql_real_escape_string($meal_id) . ' GROUP BY `um`.`user_id` ORDER BY COUNT(*) DESC LIMIT 1';
    return Db::fetchRow($sql);
  }

  public static function getMeal($meal_id)
  {
    $sql = 'SELECT * FROM `meals` WHERE `meal_id` =  ' . mysql_real_escape_string($meal_id);
    return Db::fetchRow($sql);
  }

  public static function getAllMeals()
  {
    $sql = 'SELECT * FROM `meals` WHERE `meal_parent_id` = `meal_id` ORDER BY `meal_description` ASC';
    return Db::fetchAll($sql);
  }

  public static function updateMealParentId($meal_id, $meal_parent_id)
  {
    $sql = 'SELECT * FROM `meals` WHERE `meal_id` = ' . mysql_real_escape_string($meal_id);
    $meal = Db::fetchRow($sql);
    if(!isset($meal['meal_id']))return;

    $sql = 'SELECT * FROM `meals` WHERE `meal_id` = ' . mysql_real_escape_string($meal_parent_id);
    $meal_parent = Db::fetchRow($sql);
    if(!isset($meal_parent['meal_id']))return $meal['meal_parent_id'];

    $sql = 'UPDATE `meals` SET `meal_parent_id` = ' . mysql_real_escape_string($meal_parent_id) . ' WHERE `meal_parent_id` = ' . $meal['meal_id'];
    mysql_query($sql);

    $sql = 'SELECT * FROM `meals` WHERE `meal_id` = ' . mysql_real_escape_string($meal_id);
    return Db::fetchRow($sql);
  }

  public static function getUserCount()
  {
    $sql = 'SELECT COUNT(`user_id`) AS `count` FROM `users`;';
    $row = Db::fetchRow($sql);
    return $row['count'];
  }

  public static function getMealTopScore($meal_id)
  {
    $sql = 'SELECT CONCAT(user_name, " ", user_surname) AS name, COUNT(*) AS count FROM user_meals NATURAL JOIN meals NATURAL JOIN users WHERE meal_parent_id=' . $meal_id . ' GROUP BY user_id ORDER BY count DESC;';
    return Db::fetchAll($sql);
  }

}
?>
