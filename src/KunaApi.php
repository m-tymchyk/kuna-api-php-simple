<?php

class KunaApiException extends Exception
{
}

/**
 * Class KunaApi
 */
class KunaApi
{
	/**
	 * Kuna PHP API Client
	 */
	const VERSION = "1.0.0";

	/**
	 * Kuna Host
	 */
	const HOST = 'https://kuna.io';

	/**
	 * API V2 Base Path
	 */
	const BASE_PATH = "api/v2";

	/**
	 * Market List
	 */
	const MARKET_BTCUAH = 'btcuah';


	const SIDE_BUY = 'buy';
	const SIDE_SELL = 'sell';

	/**
	 * @var string
	 */
	protected $publicKey;

	/**
	 * @var string
	 */
	protected $secretKey;

	/**
	 * @var string
	 */
	protected $userAgent;

	/**
	 * @var int
	 */
	protected $timeout = 30;


	/**
	 * KunaApi constructor.
	 *
	 * @param array $options
	 */
	public function __construct(array $options = [])
	{
		if(isset($options['publicKey']))
		{
			$this->publicKey = $options['publicKey'];
		}

		if(isset($options['secretKey']))
		{
			$this->secretKey = $options['secretKey'];
		}

		if(isset($options['timeout']))
		{
			$this->timeout = $options['timeout'];
		}

		$this->userAgent = (
			isset($options['userAgent'])
				? $options['userAgent']
				: "Kuna API Client/" . self::VERSION
			) . " ( https://github.com/reilag/kuna-api )";

	}

	/**
	 * @return int
	 */
	private function getTonce()
	{
		return intval(microtime(true) * 1000);
	}

	/**
	 * @param $path
	 * @param array $params
	 * @param string $method
	 */
	public function request($path, $params = [], $private = false, $method = "GET")
	{
		$method = strtoupper($method);
		$isPost = ($method == 'POST');
		if(!$isPost)
		{
			$method = 'GET';
		}
		$path = strtolower(trim($path, "/"));

		if($private)
		{
			$params = $this->subscribeRequest($path, $params, $method);
		}

		$url = implode("/", [self::HOST, self::BASE_PATH, $path]);

		$result = $this->curlGetContent($url, $params, $method);

		if(empty($result))
		{
			throw new KunaApiException("Content is empty.");
		}
		$object = json_decode($result, true);
		if(empty($object))
		{
			throw new KunaApiException("JSON decode failed, content: " . $result);
		}
		return $object;
	}

	/**
	 * @param $path
	 * @param $params
	 * @param $method
	 *
	 * @return array
	 * @throws KunaApiException
	 */
	private function subscribeRequest($path, $params, $method)
	{
		if( empty($this->publicKey) )
		{
			throw new KunaApiException("Public key not set");
		}

		if( empty($this->secretKey) )
		{
			throw new KunaApiException("Secret key not set");
		}

		$tonce = $this->getTonce();

		$subscribedParams = array_merge($params, [
			'tonce' => $tonce,
			'access_key' => $this->publicKey
		]);

		ksort($subscribedParams);

		$fullPath = "/" . self::BASE_PATH . "/" . $path;

		$signStr = implode("|", [
			$method,
			$fullPath,
			http_build_query($subscribedParams)
		]);

		$signature = hash_hmac('SHA256', $signStr, $this->secretKey);

		$subscribedParams['signature'] = $signature;

		return $subscribedParams;
	}

	/**
	 * @param $url
	 * @param null $params
	 * @param string $method
	 *
	 * @return mixed
	 * @throws KunaApiException
	 */
	private function curlGetContent($url, $params = null, $method = "GET")
	{
		$ch = curl_init();

		curl_setopt($ch, CURLOPT_AUTOREFERER, true);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
		curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);

		switch($method)
		{
			case "GET":
			{
				$url .= (empty($params) ? "" : "?" . http_build_query($params));
				break;
			}

			case "POST":
			{
				if(!empty($params))
				{
					curl_setopt($ch, CURLOPT_POST, 1);
					curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
				}
				break;
			}

			default:
			{
				throw new KunaApiException("Invalid method - '$method'");
			}
		}

		curl_setopt($ch, CURLOPT_URL, $url);


		$result = curl_exec($ch);
		$curlErrorNumber = curl_errno($ch);
		$curlError = curl_error($ch);
		curl_close($ch);
		if($curlErrorNumber > 0)
		{
			throw new KunaApiException("cURL Error ($curlErrorNumber): $curlError, url: {$url}");
		}

		return $result;
	}




	/**********************************\
	 *
	 * PUBLIC API Methods
	 *
	\**********************************/


	/**
	 * @param string $market
	 *
	 * @return array
	 * @throws KunaApiException
	 */
	public function tickers($market = self::MARKET_BTCUAH)
	{
		return $this->request("/tickers/{$market}");
	}


	/**
	 * @param string $market
	 *
	 * @return array
	 * @throws KunaApiException
	 */
	public function orderBook($market = self::MARKET_BTCUAH)
	{
		return $this->request("/order_book", ['market' => $market]);
	}


	/**
	 * @param string $market
	 *
	 * @return array
	 * @throws KunaApiException
	 */
	public function trades($market = self::MARKET_BTCUAH)
	{
		return $this->request("/trades", ['market' => $market]);
	}





	/**********************************\
	 *
	 * PRIVATE API Methods
	 *
	\**********************************/


	/**
	 * @return array
	 * @throws KunaApiException
	 */
	public function me()
	{
		return $this->request("/members/me", [], true);
	}

	/**
	 * @param double $volume
	 * @param double $price
	 * @param string $market
	 * @param string $side
	 *
	 * @return array
	 * @throws KunaApiException
	 */
	public function createOrder($volume, $price, $market, $side)
	{
		$params =
			[
				'volume' => $volume,
				'price' => $price,
				'market' => $market,
				'side' => $side,
			];
		return $this->request("/orders", $params, true, "POST");
	}

	/**
	 * @param double $volume
	 * @param double $price
	 * @param string $market
	 *
	 * @return array
	 */
	public function createOrderBuy($volume, $price, $market)
	{
		return $this->createOrder($volume, $price, $market, self::SIDE_BUY);
	}

	/**
	 * @param double $volume
	 * @param double $price
	 * @param string $market
	 *
	 * @return array
	 */
	public function createOrderSell($volume, $price, $market)
	{
		return $this->createOrder($volume, $price, $market, self::SIDE_SELL);
	}


	/**
	 * @param int $orderId
	 *
	 * @return array
	 * @throws KunaApiException
	 */
	public function deleteOrder($orderId)
	{
		return $this->request("/order/delete", ['id' => $orderId], true, "POST");
	}


	/**
	 * @param string $market
	 *
	 * @return array
	 * @throws KunaApiException
	 */
	public function myOrderList($market = self::MARKET_BTCUAH)
	{
		return $this->request("/orders", ['market' => $market], true, "GET");
	}


	/**
	 * @param string $market
	 *
	 * @return array
	 * @throws KunaApiException
	 */
	public function myTradesList($market = self::MARKET_BTCUAH)
	{
		return $this->request("/trades/my", ['market' => $market], true, "GET");
	}



}