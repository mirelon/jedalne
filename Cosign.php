<?php

require_once('phpQuery.php');

class Cosign
{

const COSIGN_LOGIN = 'https://login.uniba.sk/cosign.cgi';
const COSIGN_LOGOUT = 'https://login.uniba.sk/logout.cgi';

  public $doc = array();
  protected $data;

  protected function getCookieFile()
  {
    if(isset($_GET['cookie']))
    {
      return dirname(__FILE__).DIRECTORY_SEPARATOR.'cookies'.DIRECTORY_SEPARATOR.$_GET['cookie'];
    }
    return dirname(__FILE__).DIRECTORY_SEPARATOR.'cookies'.DIRECTORY_SEPARATOR.session_id();
  }

  public function download($url, $post = null, $xWwwFormUrlencoded = true,$headers=null)
  {
    //echo "Download: " . $url . "<br/><br/>Post: " . var_export($post,true) . "<br/><br/>";
    //echo "Sent cookie: " . file_get_contents($this->getCookieFile()) . "<br/><br/>";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $this->getCookieFile());
    curl_setopt($ch, CURLOPT_COOKIEJAR, $this->getCookieFile());
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; sk; rv:1.9.1.7) Gecko/20091221 Firefox/3.5.7');
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_AUTOREFERER, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_VERBOSE, false);

    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // AIS2 nema koser certifikat

    if (is_array($post))
    {
      curl_setopt($ch, CURLOPT_POST, true);
      if ($xWwwFormUrlencoded === true)
      {
        $newPost = '';
        foreach ($post as $key => $value) $newPost .= urlencode($key).'='.urlencode($value).'&';
        $post = substr($newPost, 0, -1);
      }
      curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
      //echo 'Real post: <pre>'.$post.'</pre>';
    }
    if(is_array($headers))
    {
      curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $output = curl_exec($ch);
    if (curl_errno($ch)) echo curl_error($ch);

    if (strpos($output, "\x1f\x8b\x08\x00\x00\x00\x00\x00") === 0) $output = gzdecode($output); //ak to zacina ako gzip, tak to odzipujeme
    curl_close($ch);
    return $output;
  }

  protected function fetchUserData()
  {
    $this->doc['home'] = phpQuery::newDocument($this->data);  
    $this->data = self::download('https://konto.uniba.sk/Secure/Operace.aspx');
    $this->doc['operace'] = phpQuery::newDocument($this->data);
  }

  public function loginWithCookie()
  {
    $this->data = self::download('https://login.uniba.sk/?cosign-filter-moja.uniba.sk&https://moja.uniba.sk/iskam');
    if(substr_count($this->data,'Priezvisko')==0)
    {
      throw new Exception('Cant login with cookie.');
    }
    $this->fetchUserData();
    return $this;
  }

  public function login($user,$password)
  {
    $this->data = self::download('https://login.uniba.sk/?cosign-filter-moja.uniba.sk&https://moja.uniba.sk/iskam');
    if(substr_count($this->data,'Priezvisko')==0)
    {
      $post = $this->parseFields($this->data,array('ref'));
      $post['login'] = $user;
      $post['password'] = $password;
      $this->data = self::download('https://login.uniba.sk/cosign.cgi',$post);
    }
    if(substr_count($this->data,'Priezvisko')==0)
    {
      throw new Exception('Unable to login properly.');
    }
    $this->fetchUserData();
    return $this;
  }

  public function parseFields($data,$fields)
  {
    $doc = phpQuery::newDocument($data);
    $post = array();
    foreach($fields as $field)
    {
      $post[$field] = $doc['input[name='.$field.']']->attr('value');
    }
    return $post;
  }

}
