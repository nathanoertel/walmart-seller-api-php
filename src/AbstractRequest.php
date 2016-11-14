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
	public function __construct(array $config = [], $logger = null, $env = self::ENV_PROD)
	{
		// check the environment
		if(!in_array($env, [self::ENV_PROD, self::ENV_DEV])) {
			throw new \Exception('Invalid environment');
		}

		$this->env = $env;

		// check that the necessary keys are set
		if(!isset($config['consumerId']) || !isset($config['privateKey'])) {
			throw new \Exception('Configuration missing consumerId or privateKey');
		}
	
		// Apply some defaults.
		$this->config = array_merge_recursive($this->config, $config);
		
		$this->logger = $logger;

		$this->init();
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
			$options[CURLOPT_POSTFIELDS] = $data;
			$httpHeaders[] = 'Content-Type: application/xml; boundary=<?xml version="1.0"?>';
			if($method == self::PUT) {
				$options[CURLOPT_CUSTOMREQUEST] = 'PUT';
				$this->log('PUT '.$options[CURLOPT_URL]);
			} else $this->log('UPDATE '.$options[CURLOPT_URL]);
			$this->log($options[CURLOPT_POSTFIELDS]);
		} else if($method == self::ADD) {
			$options[CURLOPT_POST] = 1;
			$options[CURLOPT_POSTFIELDS] = $data;
			$this->log('ADD '.$options[CURLOPT_URL]);
			$this->log($options[CURLOPT_POSTFIELDS]);
		} else if($method == self::DELETE) {
			$options[CURLOPT_URL] .= '/'.$data;
			$options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
			$this->log('DELETE '.$options[CURLOPT_URL]);
		}

		$options[CURLOPT_HTTPHEADER] = $this->getHeaders($options[CURLOPT_URL], $method, $httpHeaders);

		curl_setopt_array($curl, $options);

		$response = curl_exec($curl);
		$information = curl_getinfo($curl);

		if($response !== false) {
			$this->log($response);
			
			$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

			$headers = substr($response, 0, $headerSize);
			$body = substr($response, $headerSize);

			$responseClass = $this->getResponse();

			$result = new $responseClass($headers, $body, $method);

			unset($headerSize, $headers, $body);

			if(!$result->isSuccess() && $result->getError() == 'RateLimitedException') {
				throw new Exception($result->getErrorMessage(), $result->getErrorCode());
			}
		} else {
			$this->log(curl_error($curl));
		}
		
		curl_close($curl);

		return $result;
	}

	private function getSignature($consumerId, $privateKey, $requestUrl, $requestMethod, $timestamp) {
		$message = $consumerId."\n".$requestUrl."\n".strtoupper($requestMethod)."\n".$timestamp."\n";
echo $message;
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

	protected abstract function init();

	public function getHeaders($url, $method, $headers = array()) {
		$time = round(microtime(true)*1000);

		$headers[] = 'Accept: application/xml';
		$headers[] = 'WM_SVC.NAME: Walmart Marketplace';
		$headers[] = 'WM_CONSUMER.ID: '.$this->config['consumerId'];
		$headers[] = 'WM_SEC.TIMESTAMP: '.$time;
		$headers[] = 'WM_SEC.AUTH_SIGNATURE: '.$this->getSignature($this->config['consumerId'], $this->config['privateKey'], $url, $method, $time);
		$headers[] = 'WM_QOS.CORRELATION_ID: '.base64_encode(Random::string(16));

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

	private function log($message) {
		if($this->logger) $this->logger->log($message);
	}
}