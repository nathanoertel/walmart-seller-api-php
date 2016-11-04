<?php
namespace WalmartSellerAPI;

abstract class WalmartSellerAPI_AbtractRequest {

	const ENV_PROD = 'prod';
	const ENV_DEV = 'dev';
	
	const BASE_URL_PROD = 'https://marketplace.walmartapis.com';
	const BASE_URL_DEV = 'https://marketplace.stg.walmartapis.com/gmp-gateway-service-app';

	public $env;

	private $config = array(
		'max_retries' => 3
	);

	private $logger;

	/**
	 * @param array $config
	 * @param string $env
	 * @throws \Exception
	 */
	public function __construct(array $config = [], $env = self::ENV_PROD)
	{
		// check the environment
		if(!in_array($env, [self::ENV_PROD, self::ENV_STAGE])) {
			throw new Exception('Invalid environment');
		}

		$this->env = $env;

		// check that the necessary keys are set
		if(!isset($config['consumerId']) || !isset($config['privateKey'])) {
			throw new Exception('Configuration missing consumerId or privateKey');
		}
	
		// Apply some defaults.
		$this->config = array_merge_recursive($this->config, [
			'http_client_options' => [
				'defaults' => [
					'auth' => [
						$config['consumerId'],
						$config['privateKey']
					]
				],
			],
		]);

		// If an override base url is not provided, determine proper baseurl from env
		if(!isset($config['description_override']['baseUrl'])) {
			$config = array_merge_recursive($config , [
				'description_override' => [
					'baseUrl' => $this->getEnvBaseUrl($env),
				],
			]);
		}

		// // Ensure that ApiVersion is set.
		// $this->setConfig(
		// 	'defaults/ApiVersion',
		// 	$this->getDescription()->getApiVersion()
		// );
	}

	public function get() {

	}

	private function request($method, $parameters) {

		$url = $this->getEnvBaseUrl().$this->getEndpoint();

		$curl = curl_init();

		$time = time();

		$options = array(
		    CURLOPT_RETURNTRANSFER => 1,
		    CURLOPT_URL => $this->endpointBase.$this->getEndpoint(),
		    CURLOPT_USERAGENT => 'Digital Cloud Commerce',
		    CURLOPT_HTTPHEADER => array(
		    	'WM_SVC.NAME: Walmart Marketplace',
		    	'WM_SEC.AUTH_SIGNATURE: ',
		    	'WM_CONSUMER.ID: '.$this->config['consumerId'],
		    	'WM_SEC.TIMESTAMP: '.$time,
		    	'WM_QOS.CORRELATION_ID: '.base64_encode(Random::string(16))
		    ),
		    CURLOPT_HEADER => 1,
		    CURLOPT_RETURNTRANSFER => 1
		);
		
		if($method == WalmartSellerAPI__AbstractRequest::GET && !empty($data)) {
			$options[CURLOPT_URL] .= '?'.http_build_query($data);
			if($this->logger) $this->logger->log('GET '.$options[CURLOPT_URL]);
		} else if($method == WalmartSellerAPI__AbstractRequest::FIND) {
			$options[CURLOPT_URL] .= '/'.$data['id'].(empty($data['data']) ? '' : '?'.http_build_query($data['data']));
			if($this->logger) $this->logger->log('FIND '.$options[CURLOPT_URL]);
		} else if($method == WalmartSellerAPI__AbstractRequest::UPDATE) {
			$options[CURLOPT_URL] .= '/'.$data['id'];
			$options[CURLOPT_CUSTOMREQUEST] = 'PUT';
		    $options[CURLOPT_POSTFIELDS] = $data['data'];
			if($this->logger) {
				$this->logger->log('UPDATE '.$options[CURLOPT_URL]);
				$this->logger->log($options[CURLOPT_POSTFIELDS]);
			}
		} else if($method == LightspeedPOS_AbstractRequest::ADD) {
			$options[CURLOPT_POST] = 1;
		    $options[CURLOPT_POSTFIELDS] = $data;
			if($this->logger) {
				$this->logger->log('ADD '.$options[CURLOPT_URL]);
				$this->logger->log($options[CURLOPT_POSTFIELDS]);
			}
		} else if($method == LightspeedPOS_AbstractRequest::DELETE) {
			$options[CURLOPT_URL] .= '/'.$data;
			$options[CURLOPT_CUSTOMREQUEST] = 'DELETE';
			if($this->logger) $this->logger->log('DELETE '.$options[CURLOPT_URL]);
		}
		
		curl_setopt_array($curl, $options);
		
		$response = curl_exec($curl);

		echo $response;

		if($response !== false) {
			if($this->logger) $this->logger->log($response);
			
			$headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);

			$headers = substr($response, 0, $headerSize);
			$body = substr($response, $headerSize);
			
			//$responseClass = $this->getResponse();

			//$result = new $responseClass($headers, $body, $method);

			unset($headerSize, $headers, $body);

			if(!$result->isSuccess() && !$retry && $result->getError() == 'RateLimitedException') {
				throw new Exception($result->getErrorMessage(), $result->getErrorCode());
			}
		} else {
			if($this->logger) $this->logger->log(curl_error($curl));
		}
		
		curl_close($curl);
	}

	private function getSignature($consumerId, $privateKey, $requestUrl, $requestMethod, $timestamp) {
		$message = $consumerId."\n".$requestUrl."\n".strtoupper($requestMethod)."\n".$timestamp."\n";

		$decodedPrivateKey = base64_decode($privateKey);
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

	public function setConfig($key, $value) {
		$keys = explode('/', $key);

		$temp = &$this->config;

		foreach($keys as $k) {
			$temp = &$temp[$k];
		}

		$temp = $value;

		unset($temp);
	}
}