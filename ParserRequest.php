<?php
/**
 * Осуществляет запрос curl'ом
 *
 * PHP version 5
 *
 * @author   Eugene Smith <easmith@mail.ru>
 * @link     nolink
 */

class headResponse
{
	private $response = array();
	private $all = array();
	
	/**
	 * Парсер заголовков
	 *
	 * @param String $heads 
	 */
	public function __construct($heads)
	{
		$heads = explode("\r\n\r\n", trim($heads));
		foreach ($heads as $head)
		{
			$this->all[] = $this->makeArray($head);
		}
		$this->response = $this->all[count($this->all) - 1];
	}
	
	private function makeArray($head)
	{
		$result = array();
		$response = explode("\r\n", $head);
		foreach ($response as $n => $res)
		{
			if ($n == 0)
			{
				$result[0] = $res;
				continue;
			}
			$header = explode(":", $res);
			$hName = strtoupper($header[0]);
			if ($hName == "") continue;
			unset($header[0]);
			$hValue = join(":", $header);
			$result[$hName] = trim($hValue);
		}
		return $result;
	}
	
	public function __get($name)
	{
		return @$this->response[strtoupper($name)];
	}
	
	public function value($name = null)
	{
		return is_null($name) ? $this->response : $this->response[strtoupper($name)];
	}
}

class ParserRequest
{
	/**
	 * URL запроса
	 *
	 * @var string
	 */
	private $url;

	/**
	 * UserAgent
	 *
	 * @var string
	 */
	private $userAgents = array(
		"5.0.5 (6533.21.1, r84622) — Mozilla/5.0 (Macintosh; I; Intel Mac OS X 10_6_7; ru-ru) AppleWebKit/534.31+ (KHTML, like Gecko) Version/5.0.5 Safari/533.21.1",
		"Mozilla/5.0 (Windows; I; Windows NT 5.1; ru; rv:1.9.2.13) Gecko/20100101 Firefox/4.0",
		"Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)",
		"Mozilla/5.0 (Macintosh; U; Intel Mac OS X 10_6_7; en-US) AppleWebKit/534.16 (KHTML, like Gecko) Chrome/10.0.648.205 Safari/534.16",
		"Mozilla/5.0 (X11; U; Linux i686; en-US; rv:1.8.0.7) Gecko/20060928 (Debian|Debian-1.8.0.7-1) Epiphany/2.14",
		"Mozilla/5.0 (X11; U; Linux i686; ru; rv:1.9.2.17) Gecko/20110422 Ubuntu/10.04 (lucid) Firefox/3.6.18",
		"Opera/9.80 (Windows NT 6.1; U; ru) Presto/2.8.131 Version/11.10",
		);

	/**
	 * Id UserAgent'а
	 *
	 * @var integer
	 */
	private $userAgent = null;

	/**
	 * Следовать за перенаправлениями?
	 *
	 * @var integer
	 */
	private $followLocation = 5;

	/**
	 * Параметры запроса
	 *
	 * @var array
	 */
	private $params = array();

	/**
	 * Проверять SSL сертификат?
	 *
	 * @var boolean
	 */
	public $verifySSL = false;

	/**
	 * Хранить куки?
	 *
	 * @var boolean
	 */
	private $saveCookie = true;

	/**
	 * Заголовки запроса
	 *
	 * @var array
	 */
	private $headers = array();

	/**
	 * Прокси IP:PORT ""
	 *
	 * @var string
	 */
	private $proxy = null;

	/**
	 * Тип прокси
	 *
	 * @var string 
	 */
	private $proxyType = null;

	/**
	 * Дополниельные опции CURL
	 *
	 * @var array
	 */
	private $extOpt = array();
	
	/**
	 * Название группы для фалов кук
	 *
	 * @var string
	 */
	private $groupname;

	/**
	 * Имя файла куки
	 *
	 * @var string
	 */
	private $cookiename;

	/**
	 * Осуществляет запрос к сайту
	 *
	 * @param string $groupname Имя группы запросов (Для хранения куки)
	 * @param string $cookiename Уникальное имя куки
	 */
	public function  __construct($groupname = null, $cookiename = null)
	{
		$this->groupname = is_null($groupname) ? 'group' : $groupname;
		$this->cookiename = is_null($cookiename) ? 'cookie' : $cookiename;
	}

	/**
	 * Имя группы запросов и куки
	 * 
	 * @param string $groupname Имя группы запросов (Для хранения куки)
	 * @param string $cookiename Уникальное имя куки
	 *
	 * @return void
	 */
	public function setNames($groupname = null, $cookiename = null)
	{
		if (!is_null($groupname)) $this->groupname = $groupname;
		if (!is_null($cookiename)) $this->cookiename = $cookiename;
	}
	
	/**
	 * Создает курл =)
	 *
	 * @param type $url URL
	 * 
	 * @return type resource
	 */
	private function makeCurl($url)
	{
		$ch = curl_init();
		
		if (@$this->extOpt[CURLOPT_HTTPGET])
			$url .= (strpos($url, "?") ? "&" : "?") . $this->getParams();
		
		curl_setopt($ch, CURLOPT_URL, $url);

		// устанавливаем случайно выбранный UserAgent
		if (is_null($this->userAgent)) $this->userAgent = rand(0, count($this->userAgents) - 1);
		curl_setopt($ch, CURLOPT_USERAGENT, trim($this->userAgents[$this->userAgent]));

		$this->headers[] = "Expect:";
		curl_setopt($ch, CURLOPT_ENCODING, 'gzip,defalate');
		curl_setopt($ch, CURLOPT_HTTPHEADER, array_unique($this->headers));

		// vefifySSL
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->verifySSL);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->verifySSL);

		if ($this->saveCookie)
		{
			$cookiefilePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie' . DIRECTORY_SEPARATOR . $this->groupname . '_' . $this->cookiename . '_cookies.txt';
			curl_setopt($ch, CURLOPT_COOKIEJAR, $cookiefilePath);
			curl_setopt($ch, CURLOPT_COOKIEFILE, $cookiefilePath);
		}

		curl_setopt($ch, CURLOPT_REFERER, $url);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->followLocation != 0);
		curl_setopt($ch, CURLOPT_MAXREDIRS, $this->followLocation);


		if ($this->proxy)
		{
			curl_setopt($ch, CURLOPT_PROXY, $this->proxy);
			curl_setopt($ch, CURLOPT_PROXYTYPE, $this->proxyType);
		}

		curl_setopt($ch, CURLOPT_FAILONERROR, 0);
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 60);
//		curl_setopt($ch, CURLOPT_VERBOSE, 1);

		// для использования других опций или перезаписи имеющихся
		foreach (is_array($this->extOpt) ? $this->extOpt : array() as $option => $value)
		{
			if (!curl_setopt($ch, $option, $value))
				echo "{$option} on value '{$value}'";
		}
		
		return $ch;
	}
	
	/**
	 * Отправляем запрос
	 *
	 * @param string $url URL
	 *
	 * @return string
	 */
	private function request($url = null)
	{
		$url = is_null($url) ? $this->url : $url;
		
		if (is_array($url))
			return $this->multiRequest($url);
		
		$ch = $this->makeCurl($url);

		$result = curl_exec($ch);
		
		$info = curl_getinfo($ch);
		
		curl_close($ch);
		
		return array('url' => $url, 'info' => $info, 'result' => $result);
	}
	
	/**
	 * Несколько урлов...
	 *
	 * @param type $urls Массив URL
	 * 
	 * @return type 
	 */
	private function multiRequest($urls = array())
	{
		$multi = curl_multi_init();
		
		$result = array();
		
		// TODO: ограничение на слишком большое количество урлов...
		$urls = array_slice($urls, 0, 100);
		
        foreach ($urls as $url)
		{
            $ch = $this->makeCurl($url);
			curl_multi_add_handle($multi, $ch);
			
			$result[(string) $ch] = array('url' => $url);
        }
		
		do {
			while (($execrun = curl_multi_exec($multi, $running)) == CURLM_CALL_MULTI_PERFORM) ;
			
			// Если все завершено выходим из цикла
			if ($execrun != CURLM_OK) break;
			
			// a request was just completed -- find out which one
			while ($done = curl_multi_info_read($multi))
			{
				// get the info and content returned on the request
				$info = curl_getinfo($done['handle']);
				$output = curl_multi_getcontent($done['handle']);

				$result[(string) $done['handle']]['info'] = $info;
				$result[(string) $done['handle']]['result'] = $output;

				// remove the curl handle that just completed
				curl_multi_remove_handle($multi, $done['handle']);
			}

			// Block for data in / output; error handling is done by curl_multi_exec
			if ($running) curl_multi_select($multi);

        } while ($running);
		
        curl_multi_close($multi);
		
        return $result;
	}
	
	/**
	 * GET запрос
	 *
	 * @param type $url URL
	 * @param type $params Передаваемые параметры
	 * 
	 * @return type 
	 */
	public function get($url, $params = null)
	{
		$this->setParams($params);
		
		$this->extOpt[CURLOPT_HTTPGET] = true;
		$result = $this->request($url);
		
		unset($this->extOpt[CURLOPT_HTTPGET]);
		
		return $result;
	}
	
	public function post($url, $params = array())
	{
		$this->setParams($params);
		
		$this->extOpt[CURLOPT_POST] = true;
		$this->extOpt[CURLOPT_POSTFIELDS] = $this->getParams();
		$result = $this->request($url);
		
		unset($this->extOpt[CURLOPT_POST], $this->extOpt[CURLOPT_POSTFIELDS]);
		
		return $result;
	}
	
	public function head($url, $params = null)
	{
		$this->setParams($params);
		
		$this->extOpt[CURLOPT_NOBODY] = true;
		$this->extOpt[CURLOPT_HEADER] = true;
		
		$preResult = $this->request($url);
		
		unset($this->extOpt[CURLOPT_NOBODY], $this->extOpt[CURLOPT_HEADER]);
		
		$result = array();

		foreach ($preResult as &$res)
		{
			$res['result'] = new headResponse(&$res['result']);
		}

		return $preResult;
	}
	
	/**
	 * Устанавливаем url запроса
	 *
	 * @param string $url URL
	 * @param array $GETParam Параметры
	 *
	 * @return void
	 */
	public function setUrl($url, $GETParam = null)
	{
		$this->url = $url;
		if ($GETParam != null) $this->url .= "?". http_build_query($GETParam);
	}

	/**
	 * Устанавливает User Agent
	 *
	 * @param String $ua UserAgent
	 *
	 * @return void
	 */
	public function setUserAgent($ua)
	{
//		$this->userAgent = $ua;
		return false;
	}

	/**
	 * Дополнительные заголовки к запросу
	 * Например:
	 * ->setHeaders(array("Accept: application/json, text/javascript, *\/*",
	 *						"X-Requested-With: XMLHttpRequest"))
	 *
	 * @param array $headers Загаловки
	 *
	 * @return void
	 */
	public function setHeaders($headers)
	{
		$this->headers = $headers;
	}

	/**
	 * Параметры запроса
	 *
	 * @param array $params Параметры
	 *
	 * @return void
	 */
	public function setParams($params)
	{
		if (is_array($params))
			$this->params = array_merge($this->params, $params);
	}

	public function getParams()
	{
		return http_build_query($this->params);
	}

	/**
	 * Устанавливает прокси
	 *
	 * @param string $proxy 123.123.123.123:80
	 *
	 * @return void
	 */
	public function setProxy($proxy, $proxyType = 'socks5')
	{
		$this->proxy = $proxy;

		switch ($proxyType)
		{
			case 'http': $this->proxyType = CURLPROXY_HTTP; break;
			case 'socks4': $this->proxyType = CURLPROXY_SOCKS4; break;
			case 'socks5': $this->proxyType = CURLPROXY_SOCKS5; break;
			default : $this->proxyType = CURLPROXY_HTTP; break;
 		}
	}

	/**
	 * Сброс параметров
	 *
	 * @return void
	 */
	public function clear()
	{
		$this->headers = null;
		$this->params = null;
		$this->extOpt = null;
	}
	
	/**
	 * Возврашает имя файла с кукисами
	 *
	 * @return type 
	 */
	public function getCookieFileName()
	{
		return dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie' . DIRECTORY_SEPARATOR . $this->groupname . '_' . $this->cookiename . '_cookie.txt';
	}

	/**
	 * Устанавливаем или изменяем параметры сеанса curl
	 *
	 * @param array $options Дополнительные Опции
	 *
	 * @return void
	 */
	public function setExtOpt($options = array())
	{
		$this->extOpt = $options;
	}
	
	public function getCookies()
	{
		$cookieFileName = $this->getCookieFileName();
		
		if (!file_exists($cookieFileName)) return null;
		
		$cookie = file_get_contents($cookieFileName);
		
		$lines = explode("\n", $cookie);
		
		foreach ($lines as $line)
		{
			// http://www.hashbangcode.com/blog/netscape-http-cooke-file-parser-php-584.html
			if (isset($line[0]) && substr_count($line, "\t") == 6) {

				// get tokens in an array
				$tokens = explode("\t", $line);

				// trim the tokens
				$tokens = array_map('trim', $tokens);

				$cookie = array();

				// Extract the data
				$cookie['domain'] = $tokens[0];
				$cookie['flag'] = $tokens[1];
				$cookie['path'] = $tokens[2];
				$cookie['secure'] = $tokens[3];

				// Convert date to a readable format
				$cookie['expiration'] = date('Y-m-d h:i:s', $tokens[4]);

				$cookie['name'] = $tokens[5];
				$cookie['value'] = $tokens[6];

				// Record the cookie.
				$cookies[] = $cookie;
			}
		}
		return $cookies;
	}
	
	/**
	 * Чистит куки
	 *
	 * @return void
	 */
	public function clearCookie()
	{
		$cookiefilePath = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'cookie' . DIRECTORY_SEPARATOR . $this->groupname . '_' . $this->cookiename . '_cookies.txt';
		unlink($cookiefilePath);
	}
}

?>