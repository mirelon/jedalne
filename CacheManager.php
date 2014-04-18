<?php 
	require_once('Seeker.php');
/**
*
*	Manager that checks cache. If it is up to date, returns data from cache.
*	Otherwise calls Seeker to catch more data, caches it and returns.
*
*
*/

class CacheManager
{
	public $CACHE_EXPIRE = 600; //in seconds
	
	public $FILE_NAME = 'cache.txt';
	
	protected $data;
	
	public function getData()
	{
		$this->checkCache();
		return $this->data;
	}
	
	/**
	*	If cache is update, it does nothing. Otherwise updates it.
	*
	*/
	public function checkCache()
	{
		$now = time();
		$cache = file_get_contents($this->FILE_NAME);
		if(!$cache)
		{
			d("Cant get cache...");
			return $this->updateCache();
		}
		$cacheBirth = substr($cache,0,10);
		if(!$cacheBirth)
		{
			d("Cant get cache time...");
			return $this->updateCache();
		}
		if($now-$cacheBirth > $this->CACHE_EXPIRE || isset($_GET['nocache']))
		{
			d("Cache expired (".($now-$cacheBirth)."s)...");
			return $this->updateCache();
		}
		//it is up to date
		d("Cache is fresh (".($now-$cacheBirth)."s)...");
		$this->data = substr($cache,11);
		return $this;
	}
	
	/**
	*
	*	Calls seeker, gets data, formats it to table and sets $this->data
	*
	*
	*/
	public function callSeeker()
	{
		d("Fetching new data...");
		$seeker = new Seeker();
		$seeker->getDataDolna()->getDataHorna();
		
		$table = "<table class=\"table-autosort:4 table-autofilter table-stripeclass:alternate\">";
		$table .= "<thead><tr>";
		$table .= "<th id=\"den\" class=\"table-filterable table-sortable:alphanumeric table-sortable\">DEN</th>";
		$table .= "<th id=\"jedlo\" class=\"table-sortable table-sortable:alphanumeric\">JEDLO</th>";
		$table .= "<th id=\"cena\"class=\"table-sortable:numeric table-sortable table-sorted-asc\">CENA</th>";
		$table .= "<th id=\"typ\" class=\"table-filterable table-sortable table-sortable:alphanumeric\">TYP</th>";
		$table .= "<th id=\"kde\" class=\"table-filterable table-sortable table-sortable:alphanumeric\">KDE</th>";
		$table .= "</tr></thead>";
		foreach($seeker->meal as $jedlo){
			$hid=0;
			if(isset($_GET['dnes']) && $_GET['dnes']==1 && $jedlo['den']!='Dnes')$hid=1;
			if(isset($_GET['kde'])&& $jedlo['jedalen']!=$_GET['kde'])$hid=1;
			if(isset($_GET['typ']) && $jedlo['typ']!=$_GET['typ'])$hid=1;
			$table .= "<tr ";
			if($hid)$table .= "style=\"display:none;\" ";
			$table .= "class=\"".$jedlo["jedalen"]."\">";
			$table .= "<td>".$jedlo["den"]."</td>";
			$table .= "<td>".$jedlo["nazov"]."</td>";
			$table .= "<td>".$jedlo["cena"]."</td>";
			$table .= "<td>".$jedlo["typ"]."</td>";
			$table .= "<td>".$jedlo["jedalen"]."</td>";
			$table .= "</tr>";
		}
		$table .= "</table>";
		$this->data = $table;		
		return $this;
	}
	
	public function updateCache()
	{
		$this->callSeeker();
		$now = time();
		file_put_contents($this->FILE_NAME,$now."\n".$this->data);
	}
}
