<meta http-equiv="Content-Type" content="charset=utf-8" />
<?php
require_once('Db.php');

Db::init();

$skipped = array();
if(isset($_GET['skipped'])) {
  $skipped = explode(',', $_GET['skipped']);
}

if(isset($_GET['meal_parent_id']) && isset($_GET['meal_child_id'])) {
  Db::updateMealParentId($_GET['meal_child_id'], $_GET['meal_parent_id']);
}

$min_dist = PHP_INT_MAX;
$min_meal_id_1 = -1;
$min_meal_id_2 = -1;

$meals = Db::getAllMeals();
foreach($meals as $meal1) {
  if(in_array($meal1['meal_id'], $skipped))continue;
  foreach($meals as $meal2) {
    if(in_array($meal2['meal_id'], $skipped))continue;
    if($meal1['meal_id'] == $meal2['meal_id'])continue;
    $dist = levenshtein($meal1['meal_description'], $meal2['meal_description'], 1, 2, 1);
    if($dist<$min_dist) {
      $min_dist = $dist;
      $min_meal_id_1 = $meal1['meal_id'];
      $min_meal_id_2 = $meal2['meal_id'];
    }
  }
}
  $meal1 = Db::getMeal($min_meal_id_1);
  $meal2 = Db::getMeal($min_meal_id_2);
  echo 'Meal 1 (' . $meal1['meal_id'] . '): ' . $meal1['meal_description'] . ' <a href="?meal_parent_id=' . $meal1['meal_id'] . '&meal_child_id=' . $meal2['meal_id'] . '&skipped=' . implode(',', $skipped) . '">Will be parent</a><br/>';
  echo 'Meal 2 (' . $meal2['meal_id'] . '): ' . $meal2['meal_description'] .' <a href="?meal_parent_id=' . $meal2['meal_id'] . '&meal_child_id=' . $meal1['meal_id'] . '&skipped=' . implode(',', $skipped) . '">Will be parent</a><br/>';
  echo 'Distance: ' . $min_dist . '<br/>';
  $skipped []= $meal1['meal_id'];
  $skipped []= $meal2['meal_id'];
  echo '<a href="?skipped=' . implode(',', $skipped) . '">Skip</a><br/>';

Db::terminate();
?>
