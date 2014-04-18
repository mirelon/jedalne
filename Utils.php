<?php

if(isset($_GET['debug']) && $_GET['debug']>='2')
{
  ini_set('display_errors', 1);
}

function d($s)
{
	if(isset($_GET['debug']) && $_GET['debug']>='1')
	{
		$trace=debug_backtrace();
		$caller = $trace[1];
                echo '<span style="font-family:monospace;font-size:10px;">';
		if (isset($caller['class']))
		{
			echo $caller['class'] . "::";
		}
		echo $caller['function'] . "(): ";
		echo $s;
                echo "</span><br/>\n";
	}
}
