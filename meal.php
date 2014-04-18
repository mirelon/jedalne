<meta http-equiv="Content-Type" content="charset=utf-8" />
<?php
require_once('Db.php');

Db::init();
if(isset($_GET['id']))
{
  $id = $_GET['id'];
  $meal = Db::getMeal($id);
  if(isset($meal))
  {
    if(isset($_POST['meal_parent_id']))
    {
      $meal = Db::updateMealParentId($id,$_POST['meal_parent_id']);
    }
    echo '<h1>' . $meal['meal_description'] . '</h1>';
    echo $meal['meal_shortcut'];
    echo '<br/><form method="post" action="meal.php?id=' . $meal['meal_id'] . '">Parent ID: ';
    echo '<input type="text" name="meal_parent_id" value="'.$meal['meal_parent_id'].'" /><br/>';
    echo '<input type="submit" value="submit" />';
    echo '</form>';
    echo '<table><tr><td><table>';
    foreach(Db::getAllMeals() as $meal_item)
    {
      echo '<tr><td><a href="meal.php?id='.$meal_item['meal_id'].'">' . $meal_item['meal_description'] . '</a></td><td>' . $meal_item['meal_id'] . '</td></tr>';
    }
    echo '</table></td><td style="vertical-align:top;"><table>';
    foreach(Db::getMealTopScore($id) as $row)
    {
      echo '<tr><td>' . $row['name'] . '</td><td>' . $row['count'] . '</td></tr>'; 
    }
    echo '</table></td></tr></table>';
  }
}
Db::terminate();
?>
