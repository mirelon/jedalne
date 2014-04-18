<?php

class BarChart
{
  protected $_data = array();
  protected $_width = 1000;
  protected $_server = '';

  public function setData($data)
  {
    $this->_data = $data;
    return $this;
  }

  public function setServer($server)
  {
    $this->_server = $server . '.';
    return $this;
  }

  public function getImgUrl()
  {
    $url = 'http://' . $this->_server . 'chart.apis.google.com/chart?chxl=1:';
    foreach(array_keys($this->_data) as $value)
    {
      $url .= '|' . $value;
    }
    $url .= '&chxs=1,676767,11.5,0,_,676767&chxt=y,x&chbh=a&chs=' . $this->_width . 'x225&cht=bvg&chco=787878&chd=t:';
    $url .= implode(",",array_values($this->_data));
    $url .= '&chtt=V%C3%BDdavky+na+jedlo&chm=N*f1*,666666,0,-1,11&chds=a';
    return $url;
  }
}
?>
