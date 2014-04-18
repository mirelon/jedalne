<?php

class Stats
{
	public function topMeals($user_id = null)
	{
		$html = '';
		$meal_counts = Db::fetchMealCounts(30,$user_id);
		$html .= '<h3>Top meals';
		if(!is_null($user_id))
		{
			$html .= ' eaten by you';
		}
		else
		{
			$html .= ' global (From ' . Db::getUserCount() . ' users)';
		}
		$html .= ':</h3><table><thead><tr><th>Meal</th><th>Count</th><th>King</th></tr></thead><tbody>';
		foreach($meal_counts as $meal)
		{
                        $king = Db::getKing($meal['meal_id']);
			$html .= '<tr><td>' . $meal['description'] . '</td>';
                        $html .= '<td>' . $meal['count'] . '</td>';
                        $html .= '<td';
                        if($king['user_id'] == $_SESSION['user_id'])
                        {
                          $html .= ' style="background-color:yellow;"';
                        }
                        $html .= '>' . $king['name'] . ' (' . $king['count'] . ')</td></tr>';
		}
		$html .= '</tbody></table>';
		return $html;
	}
	
}
