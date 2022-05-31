<?php
namespace WalmartSellerAPI;

use phpseclib\Crypt\Random;
use phpseclib\Crypt\RSA;

abstract class AbstractRequest {

	const ENV_PROD = 'prod';
	const ENV_DEV = 'dev';
	
	const BASE_URL_PROD = 'https://marketplace.walmartapis.com';
	const BASE_URL_DEV = 'https://marketplace.stg.walmartapis.com/gmp-gateway-service-app';

	const GET = 'GET';
	const ADD = 'ADD';
	const UPDATE = 'POST';
	const PUT = 'PUT';
	const DELETE = 'DELETE';

	public $env;

	protected $config = array(
		'max_retries' => 3
	);

	private $logger;

	/**
	 * @param array $config
	 * @param string $env
	 * @throws \Exception
	 */
	public function __construct(array $config = array(), $logger = null, $env = self::ENV_PROD)
	{
		// check the environment
		if(!in_array($env, array(self::ENV_PROD, self::ENV_DEV))) {
			throw new \Exception('Invalid environment');
		}

		$this->env = $env;

		// check that the necessary keys are set
		if(
			!((isset($config['clientId']) && isset($config['clientSecret'])) ||
			(isset($config['consumerId']) && isset($config['privateKey'])))
		) {
			throw new \Exception('Configuration missing consumerId or privateKey');
		}
	
		// Apply some defaults.
		$this->config = array_merge_recursive($this->config, $config);
		
		$this->logger = $logger;
	}

	public function get($path = '', $parameters = array()) {
		return $this->request(self::GET, $path, $parameters);
	}

	public function post($path, $parameters = array()) {
		return $this->request(self::UPDATE, $path, $parameters);
	}

	public function put($path, $parameters = array()) {
		return $this->request(self::PUT, $path, $parameters);
	}

	public function delete($path, $parameters = array()) {
		return $this->request(self::DELETE, $path, $parameters);
	}

	private function request($method, $path, $data = array()) {
		$result = false;

		$url = $this->getEnvBaseUrl($this->env).$this->getEndpoint().$path;

		$curl = curl_init();

		$time = round(microtime(true)*1000);

		$options = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_URL => $url,
			CURLOPT_USERAGENT => 'Digital Cloud Commerce',
			CURLOPT_HEADER => 1,
			CURLOPT_RETURNTRANSFER => 1,
			CURLINFO_HEADER_OUT => true
		);

		$httpHeaders = array();

		if($method == self::GET) {
			if(!empty($data)) $options[CURLOPT_URL] .= '?'.http_build_query($data);
			$this->log('GET '.$options[CURLOPT_URL]);
		} else if($method == self::UPDATE || $method == self::PUT) {
			$options[CURLOPT_POST] = 1;
			$options[CURLOPT_POSTFIELDS] = $this->getPostFields($data);
			$httpHeaders[] = $this->getPostContentType();
			if($method == self::PUT) {
				$options[CURLOPT_CUSTOMREQUEST] = 'PUT';
				$this->log('PUT '.$options[CURLOPT_URL]);
			} else $this->log('UPDATE '.$options[CURLOPT_URL]);
			if(!empty($data)) $this->log($this->formatXml($data));
		} else if($method == self::ADD) {
			$options[CURLOPT_POST] = 1;
			$options[CURLOPT_POSTFIELDS] = $data;
			$this->log('ADD '.$options[CURLOPT_URL]);
			$this->log($data);
		} else if($method == self::DELETE) {
			if(!empty($data)) $options[CURLOPT_URL] .= '?'.http_build_query($data);
			$options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
			$this->log('DELETE '.$options[CURLOPT_URL]);
		}

		$options[CURLOPT_HTTPHEADER] = $this->getHeaders($options[CURLOPT_URL], $method, $httpHeaders);

		curl_setopt_array($curl, $options);

		$response = curl_exec($curl);
		$information = curl_getinfo($curl);
		
		$this->log($information['request_header']);

		if($response !== false) {
			$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

			$headers = substr($response, 0, $headerSize);
			$body = substr($response, $headerSize);

			$responseClass = $this->getResponse();

			$result = new $responseClass($headers, $body, $method);

			if($result->isSuccess()) {
				$this->log($headers);
				$this->log($this->formatXml($body));
			} else {
				$this->log($response);
			}
			unset($headerSize, $headers, $body);

			if(!$result->isSuccess() && $result->getError() == 'RateLimitedException') {
				throw new \Exception($result->getErrorMessage(), $result->getErrorCode());
			}
		} else {
			$this->log(curl_error($curl));
		}
		
		curl_close($curl);

		return $result;
	}
	
	protected function getPostFields($data) {
		return $data;
	}
	
	protected function getPostContentType() {
		return 'Content-Type: application/xml';
	}

	private function getSignature($consumerId, $privateKey, $requestUrl, $requestMethod, $timestamp) {
		$message = $consumerId."\n".$requestUrl."\n".strtoupper($requestMethod)."\n".$timestamp."\n";

		$rsa = new RSA();
		$decodedPrivateKey = base64_decode($privateKey);
		$rsa->setPrivateKeyFormat(RSA::PRIVATE_FORMAT_PKCS8);
		$rsa->setPublicKeyFormat(RSA::PRIVATE_FORMAT_PKCS8);

		/**
		 * Load private key
		 */
		if($rsa->loadKey($decodedPrivateKey,RSA::PRIVATE_FORMAT_PKCS8)){
			/**
			 * Make sure we use SHA256 for signing
			 */
			$rsa->setHash('sha256');
			$rsa->setSignatureMode(RSA::SIGNATURE_PKCS1);

			$signed = $rsa->sign($message);
			/**
			 * Return Base64 Encode generated signature
			 */
			return base64_encode($signed);
		} else {
			throw new \Exception("Unable to load private key");
		}
	}

	/**
	 * Get baseUrl for given environment
	 * @param string $env
	 * @return null|string
	 */
	public function getEnvBaseUrl($env)
	{
		switch ($env) {
			case self::ENV_PROD:
				return self::BASE_URL_PROD;
			case self::ENV_DEV:
				return self::BASE_URL_DEV;
			default:
				return null;
		}
	}

	public abstract function getEndpoint();

	protected abstract function getResponse();

	public function getHeaders($url, $method, $headers = array()) {
		if(isset($this->config['clientId']) && isset($this->config['clientSecret'])) {
			$requestTime = time();
			$time = round(microtime(true)*1000);

			if(!isset($this->config['token']) || $this->config['token']['expires'] <= $requestTime-10) {
				$curl = curl_init();

				$url = $this->getEnvBaseUrl($this->env).'/v3/token';
		
				$options = array(
					CURLOPT_RETURNTRANSFER => 1,
					CURLOPT_URL => $url,
					CURLOPT_USERAGENT => 'Digital Cloud Commerce',
					CURLOPT_HEADER => 1,
					CURLOPT_RETURNTRANSFER => 1,
					CURLINFO_HEADER_OUT => true
				);
		
				$httpHeaders = array();
		
				$options[CURLOPT_POST] = 1;
				$options[CURLOPT_POSTFIELDS] = 'grant_type=client_credentials';
				$httpHeaders[] = 'Authorization: Basic '.base64_encode($this->config['clientId'].':'.$this->config['clientSecret']);
				$httpHeaders[] = 'Content-Type: application/x-www-form-urlencoded';
				$httpHeaders[] = 'Accept: application/json';
				$httpHeaders[] = 'WM_SVC.NAME: Walmart Marketplace';
				$httpHeaders[] = 'WM_QOS.CORRELATION_ID: '.base64_encode(Random::string(16));
				$httpHeaders[] = 'WM_SVC.VERSION: 1.0.0';
			
				$options[CURLOPT_HTTPHEADER] = $httpHeaders;
		
				curl_setopt_array($curl, $options);
		
				$response = curl_exec($curl);
				$information = curl_getinfo($curl);
				
				$this->log($information['request_header']);
		
				if($response !== false) {
					$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
		
					$header = substr($response, 0, $headerSize);
					$body = substr($response, $headerSize);

					$accessToken = json_decode($body, true);

					if(isset($accessToken['access_token'])) {
						$this->config['token'] = array(
							'access_token' => $accessToken['access_token'],
							'token_type' => $accessToken['token_type'],
							'expires' => $requestTime + $accessToken['expires_in']
						);

						$this->log($header);
						$this->log($body);
					} else {
						$this->log($response);
						throw new \Exception('OAuth Failed');
					}
				} else {
					$this->log(curl_error($curl));
					throw new \Exception('OAuth Failed');
				}
				
				curl_close($curl);
			}

			$headers[] = 'Accept: application/xml';
			$headers[] = 'Authorization: Basic '.base64_encode($this->config['clientId'].':'.$this->config['clientSecret']);
			$headers[] = 'WM_SVC.NAME: Walmart Marketplace';
			$headers[] = 'WM_SEC.ACCESS_TOKEN: '.$this->config['token']['access_token'];
			$headers[] = 'WM_SEC.TIMESTAMP: '.$time;
			$headers[] = 'WM_QOS.CORRELATION_ID: '.base64_encode(Random::string(16));
		} else {
			$time = round(microtime(true)*1000);

			$headers[] = 'Accept: application/xml';
			$headers[] = 'WM_SVC.NAME: Walmart Marketplace';
			$headers[] = 'WM_CONSUMER.ID: '.$this->config['consumerId'];
			$headers[] = 'WM_SEC.TIMESTAMP: '.$time;
			$headers[] = 'WM_SEC.AUTH_SIGNATURE: '.$this->getSignature($this->config['consumerId'], $this->config['privateKey'], $url, $method, $time);
			$headers[] = 'WM_QOS.CORRELATION_ID: '.base64_encode(Random::string(16));
			if (isset($this->config['channelTypeId'])) $headers[] = 'WM_CONSUMER.CHANNEL.TYPE: '.$this->config['channelTypeId'];
		}

		return $headers;
	}

	public function setConfig($key, $value) {
		$keys = explode('/', $key);

		$temp = &$this->config;

		foreach($keys as $k) {
			$temp = &$temp[$k];
		}

		$temp = $value;

		unset($temp);
	}

	protected function formatXml($xml) {
		$xmlDocument = new \DOMDocument('1.0');
		$xmlDocument->preserveWhiteSpace = false;
		$xmlDocument->formatOutput = true;
		$xmlDocument->loadXML($xml);

		return $xmlDocument->saveXML();
	}

	private function log($message) {
		if($this->logger) $this->logger->log($message);
	}
}