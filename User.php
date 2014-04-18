<?php

require_once('Cosign.php');
require_once('BarChart.php');
require_once('Db.php');
require_once('Stats.php');

class User
{
  public $user_id;
  public $meno;
  public $data;
  protected $cosign;
  protected $history = array();
  protected $foodCount = array();

  public function __construct()
  {
    Db::init();
  }

  public function __destruct()
  {
    Db::terminate();
  }

  public function login($meno=NULL,$heslo=NULL)
  {
    try
    {
      $this->cosign = new Cosign();
      try
      {
		if(!isset($_SESSION['user_remote_login']))throw new Exception('No username available.');
        $this->cosign->loginWithCookie();
        $this->user_id = $_SESSION['user_id'];
        echo "Logged in with cookie.<br/>\n";
      }
      catch(Exception $e)
      {
        echo $e->getMessage() . "...logging in with user and password<br/>\n";
        if(is_null($meno) || is_null($heslo))
        {
          throw new Exception("No user/password available");
        }
        $this->cosign->login($meno,$heslo);
        $_SESSION['user_remote_login'] = $meno;
        
      }
      $this->user_id = Db::forceGetUserId($_SESSION['user_remote_login'], $this->getData());
      $_SESSION['user_id'] = $this->user_id;
    }
    catch(Exeption $e)
    {
      throw new Exception('Unable to login: ' . $e->getMessage());
    }
  }

  public function narokyNaStravu()
  {
    $pocet = $this->cosign->doc['home']['#ctl00_ContentPlaceHolderMain_gvNaroky tr:eq(1) td:eq(2)']->html();
    if($pocet=="")$pocet = 'neda sa zistit, kolko';
    return $pocet;
  }

  public function zostatokNaKarte()
  {
    $zostatok = $this->cosign->doc['home']['#ctl00_ContentPlaceHolderMain_lblAccount']->html();
    if($zostatok=="")$zostatok = 'neda sa zistit, kolko';
    return $zostatok;
  }

  public function getData()
  {
    $this->data['user_name'] = $this->cosign->doc['home']['#ctl00_ContentPlaceHolderMain_lblName']->html();
    $this->data['user_surname'] = $this->cosign->doc['home']['#ctl00_ContentPlaceHolderMain_lblSurname']->html();
    $this->data['user_address'] = $this->cosign->doc['home']['#ctl00_ContentPlaceHolderMain_lblAddress']->html();
    $this->data['user_mobile'] = $this->cosign->doc['home']['#ctl00_ContentPlaceHolderMain_txtMobil']->html();
    $this->data['user_email'] = $this->cosign->doc['home']['#ctl00_ContentPlaceHolderMain_lblEmail']->html();
    if(substr($this->data['user_surname'],-4)=='ová' || substr($this->data['user_surname'],-4)=='ská')
    {
      $this->data['user_sex'] = 'female';
    }
    else
    {
      $this->data['user_sex'] = 'male';
    }
    return $this->data;
  }

  public function parseHistory()
  {
    echo $this->cosign->doc['operace']['#ctl00_ContentPlaceHolderMain_gvAccountHistory'];
    foreach($this->cosign->doc['operace']['#ctl00_ContentPlaceHolderMain_gvAccountHistory']->find('tr') as $tr)
    {
      if($tr->childNodes->length!=9) continue;
      if($tr->childNodes->item(2)->textContent == 'Skratka')continue;
      $tr_data = array();
      $tr_data['time'] = trim($tr->childNodes->item(0)->textContent);
      $tr_data['service'] = trim($tr->childNodes->item(1)->textContent);
      $tr_data['shortcut'] = trim($tr->childNodes->item(2)->textContent);
      $tr_data['description'] = trim($tr->childNodes->item(3)->textContent);
      $tr_data['amount'] = trim($tr->childNodes->item(4)->textContent);
      $tr_data['method'] = trim($tr->childNodes->item(5)->textContent);
      $tr_data['obj'] = trim($tr->childNodes->item(6)->textContent);
      $tr_data['payed'] = trim($tr->childNodes->item(7)->textContent);
     
      if($tr_data['service']=='MEN' && $tr_data['shortcut']!='STR')
      {
		$this->history []= $tr_data;
      }
    }
    return $this;
  }

  public function updateLocalDb()
  {
	Db::addAccess();
    foreach($this->history as $tr_data)
    {
      $meal_id = Db::forceGetMealId($tr_data['shortcut'], $tr_data['description']);
      $user_meal_id = Db::addUserMealId($_SESSION['user_id'], $meal_id, strtotime(str_replace(". ", ".",$tr_data['time'])), str_replace(array(",","-"),array(".",""),$tr_data['amount']));
    }
  }

  public function getChartTimeMoneySpent()
  {
    $spent_in_month = array();
    foreach($this->history as $tr_data)
    {
      if($tr_data['service']!='MEN')continue;
      $timeparts = explode(" ",$tr_data['time']);
      $month = $timeparts[1];
      $year = $timeparts[2];
      $month_string = $month . $year;
      $amount = -floatval(str_replace(",",".",$tr_data['amount']));
      if(!array_key_exists($year,$spent_in_month))
      {
        $spent_in_month[$year] = array(
        '1.'=>0,
        '2.'=>0,
        '3.'=>0,
        '4.'=>0,
        '5.'=>0,
        '6.'=>0,
        '7.'=>0,
        '8.'=>0,
        '9.'=>0,
        '10.'=>0,
        '11.'=>0,
        '12.'=>0
        );
      }
      if(!array_key_exists($month,$spent_in_month[$year]))
      {
        $spent_in_month[$year][$month] = 0;
      }
      $spent_in_month[$year][$month] += $amount;
    }
    $imgs = '';
    krsort($spent_in_month);
    $i=0;
    foreach($spent_in_month as $year=>$sm)
    {
      $i++;
      ksort($sm);
      $bar_chart = new BarChart();
      $bar_chart->setData($sm);
      //$bar_chart->setServer($i);
      $imgs .= $year . '<br/><img src="' . $bar_chart->getImgUrl() . '" alt=""/><br/>';
    }
    return $imgs;
  }

  public function displayDashBoard()
  {
	$stats = new Stats();
    echo 'Meno: ' . $this->data['user_name'] . ' ' . $this->data['user_surname'] . '<br/>';
    //echo 'Pohlavie: ' . ($this->data['female']?'zena':'muz') . ' <br/>';
    echo 'Adresa: ' . $this->data['user_address'] . '<br/>';
    echo 'Email: ' . $this->data['user_email'] . '<br/>';
    echo 'Do konca dna ti ostava ' . $this->narokyNaStravu() . ' obedov<br/>';
    echo 'Na karte mas nabity ucet vo vyske ' . $this->zostatokNaKarte() . '<br/>';
	/*
    if($ratio<20)echo 'Maximalna konzervativizmus. Zase ryza? A syr.';
    else if($ratio<35)echo 'Stale jes to iste, iba zriedkavo ochutnas nieco nove.';
    else if($ratio<52)echo 'Rad ochutnas nove jedlo, ale nepohrdnes ani klasikou.';
    else if($ratio<70)echo 'Skusas nove jedlo skoro vzdy ked sa objavi.';
    else echo 'Nejdes do jedalne, ked nemaju nic nove.';
	*/
	echo $stats->topMeals($_SESSION['user_id']);
	echo $stats->topMeals();
	if($_SESSION['user_id']==1 && isset($_GET['user_id']))echo $stats->topMeals($_GET['user_id']);
	echo $this->getChartTimeMoneySpent();
  }
}
