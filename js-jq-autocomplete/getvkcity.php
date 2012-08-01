<?php

/**

CREATE TABLE `vkcitycache` (
	`id` INT(10) NOT NULL AUTO_INCREMENT,
	`date` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
	`searchStr` VARCHAR(32) NULL DEFAULT NULL,
	`result` TEXT NULL,
	PRIMARY KEY (`id`),
	UNIQUE INDEX `searchStr` (`searchStr`)
)
COLLATE='utf8_general_ci'
ENGINE=MyISAM
ROW_FORMAT=DEFAULT

**/




class vkcity
{
	private $dbLink = null;
	
	/**
	 * Если TRUE - кэшировать. иначе не кэшировать
	 *
	 * @var type 
	 */
	private $cache = false;
	
	/**
	 * База данных городов для автоселекта на основе VK.COM
	 * 
	 * @param string $host Хост базы данных
	 * @param string $user Имя пользователя базы данных
	 * @param string $pass Пароль к базе данных
	 * @param string $name Имя базы данных
	 * 
	 * @return void
	 */
	public function __construct($host = null, $user = null, $pass = null, $name = null)
	{
		if ($this->cache && $host && $user && $pass && $name)
		{
			$this->dbLink = mysql_connect($host, $user, $pass) OR die("Can't connect to server");
			mysql_select_db($name) or die(mysql_error());
		}
	}
	
	/**
	 * Get result from cache by str
	 * 
	 * @param string $str Search string
	 * 
	 * @return array
	 */
	public function getdbcity($str)
	{
		if (!$this->cache) return null;
		
		$qRes = mysql_query("SELECT result FROM vkcitycache WHERE searchStr = '" . $str ."' AND DATEDIFF(date, NOW()) < 30 LIMIT 1", $this->dbLink) OR die(mysql_error());
		
		$result = mysql_fetch_array($qRes);
		
		return unserialize(@$result['result']);
	}
	
	/**
	 * Save result by str
	 * 
	 * @param string $str Search string
	 * @param array $result Result
	 */
	public function saveCache($str, $result)
	{
		if (!$this->cache) return null;
		
		$result = serialize($result);
		$sql = "INSERT INTO vkcitycache (searchStr, result) ";
		$sql.= "VALUES ('{$str}', '{$result}') ";
		$sql.= "ON DUPLICATE KEY UPDATE result = '{$result}'";
		mysql_query($sql, $this->dbLink) OR die(mysql_error());
	}
	
	/**
	 * Find by str
	 * 
	 * @param string $str Search string
	 * 
	 * @return array
	 */
	public function find($str)
	{
		if (mb_strlen($str, "utf-8") < 3) return null;
		$result = $this->getdbcity($str);
		
		if ($result == null)
		{
			$result = $this->getvkcity($str);
			$this->saveCache($str, $result);
		}
		
		return $result;
	}
	
	/**
	 * Find city by str via vk
	 * 
	 * @param type $str
	 * 
	 * @return array
	 */
	public static function getvkcity($str)
	{
		$ch = curl_init();

		$url = "http://vk.com/select_ajax.php?act=a_get_cities&country=1&str=" . $str;

		curl_setopt($ch, CURLOPT_URL, $url);

		curl_setopt($ch, CURLOPT_USERAGENT, "Opera/9.80 (Windows NT 6.1; U; ru) Presto/2.8.131 Version/11.10");

		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,defalate');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array("Expect:"));

		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

//		curl_setopt($ch, CURLOPT_PROXY, $proxy);
//		curl_setopt($ch, CURLOPT_PROXYTYPE, $proxyType);

		curl_setopt($ch, CURLOPT_FAILONERROR, 0);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);

		$result = curl_exec($ch);

		curl_close($ch);

		return json_decode(str_replace("'", "\"", $result));
	}
}

$vkc = new vkcity();

echo json_encode($vkc->find($_GET['str']));