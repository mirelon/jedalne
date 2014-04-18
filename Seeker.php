<?php

require_once('DomDocument.php');
require_once('phpQuery-onefile.php');

/**
*
*	Class for getting menu from other pages. Uses CURL and DomDocument
*
*/
class Seeker
{
	public $meal = array();

  /**
    * @param string
    * @return string
    *
    */
	public function getRealType($typ)
	{
		if(substr($typ,0,5)=="Hlavn")$typ = "Hlavne jedla a polievky";
		if(substr($typ,0,8)=="Polievky")$typ = "Hlavne jedla a polievky";
		if(substr($typ,0,3)=="Nad")$typ = "Hlavne jedla a polievky";
		if(substr($typ,0,5)=="Pizze")$typ = "Doplnkový tovar";
		if(substr($typ,0,7)=="Dresing")$typ = "Doplnkový tovar";
		if(substr($typ,0,7)=="Nápoje")$typ = "Nápoje";
		if($typ=="Tovar prílohový")$typ = "Prílohy";
		if($typ=="studené jedlá")$typ = "Doplnkový tovar";
		if($typ=="Šaláty")$typ = "Doplnkový tovar";
		if($typ=="Raňajky")$typ = "Doplnkový tovar";
		if($typ=="Bufetový tovar")$typ = "Doplnkový tovar";
		return $typ;
	}

	public function getDataHorna()
	{
		return $this->_getData('horna','http://www.mlynska-dolina.sk/stravovanie/vsetky-zariadenia/eat-meet/denne-menu');
	}
	
	public function getDataDolna()
	{
		return $this->_getData('dolna','http://www.mlynska-dolina.sk/stravovanie/vsetky-zariadenia/venza/denne-menu');
	}
	
	protected function _getData($jedalen, $url)
	{
		phpQuery::newDocumentFileHTML($url);
		$data = pq('.view-nodehierarchy-denne-menu-list .views-field-body .field-content');
		$den = "";
		$dnes = false;
		foreach($data->children() as $node)
		{
			$pqNode = pq($node);
			if($node->tagName=="h2")
			{
				$den = $pqNode->find('a')->html();
				setlocale(LC_TIME, "sk_SK");
				if(preg_match("/^" . strftime("%A") . "/",$den))
				{
					$den = "Dnes";
				}
			}
			if($node->tagName=="p")
			{
				if(!$den)continue;
				$typ = $this->getRealType($pqNode->find('strong')->html());
			}
			if($node->tagName=="table")
			{
				foreach($pqNode->find('tr') as $tr)
				{
					$tr = pq($tr);
					$nazov = $tr->find('td:first')->html();
					$cenaRaw = $tr->find('td:eq(1)')->html();
					$cena = substr($cenaRaw, strlen($cenaRaw)-10,4);
					$this->meal[]=array("den"=>$den,"typ"=>$typ,"cena"=>$cena,"nazov"=>$nazov,"jedalen"=>$jedalen);
				}
			}
		}
		return $this;
	}
}
