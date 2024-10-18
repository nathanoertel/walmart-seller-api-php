<?php
namespace WalmartSellerAPI;

use phpseclib\Crypt\Random;
use phpseclib\Crypt\RSA;

abstract class AbstractJSONRequest extends AbstractRequest {
	protected function getPostContentType() {
		return 'Content-Type: application/json';
	}

	protected function getAcceptType() {
		return 'Accept: application/json';
	}

	protected function formatResponse($response) {
		return json_encode(
			json_decode($response, true),
			JSON_PRETTY_PRINT
		);
	}

	public function getHeaders($url, $method, $headers = array()) {
		if(isset($this->config['clientId']) && isset($this->config['clientSecret'])) {
			$this->refreshToken();
			$time = round(microtime(true)*1000);
			$headers['accept'] = 'application/json';
			$headers['Authorization'] = 'Basic '.base64_encode($this->config['clientId'].':'.$this->config['clientSecret']);
			$headers['WM_SVC.NAME'] = 'Walmart Marketplace';
			$headers['WM_SEC.ACCESS_TOKEN'] = $this->config['token']['access_token'];
			$headers['WM_SEC.TIMESTAMP'] = $time;
			$headers['WM_QOS.CORRELATION_ID'] = base64_encode(Random::string(16));
		} else {
			$time = round(microtime(true)*1000);

			$headers['accept'] = 'application/json';
			$headers['WM_SVC.NAME'] = 'Walmart Marketplace';
			$headers['WM_CONSUMER.ID'] = $this->config['consumerId'];
			$headers['WM_SEC.TIMESTAMP'] = $time;
			$headers['WM_SEC.AUTH_SIGNATURE'] = $this->getSignature($this->config['consumerId'], $this->config['privateKey'], $url, $method, $time);
			$headers['WM_QOS.CORRELATION_ID'] = base64_encode(Random::string(16));
			if (isset($this->config['channelTypeId'])) $headers['WM_CONSUMER.CHANNEL.TYPE'] = $this->config['channelTypeId'];
		}

		if ($method == self::UPDATE || $method == self::PUT) {
			$headers['content-type'] = 'application/json';
		}

		return $headers;
	}

	protected function request($method, $path, $data = array()) {
		$url = $this->getEnvBaseUrl($this->env).$this->getEndpoint().$path;

		$options = [];

		if($method == self::GET) {
			if(!empty($data)) {
				$options['query'] = $data;
				$this->log('GET ' . $url . '?' . http_build_query($data));
			} else {
				$this->log('GET ' . $url);
			}
		} else if($method == self::UPDATE || $method == self::PUT) {
			if($method == self::PUT) {
				$this->log('PUT '.$url);
			} else $this->log('UPDATE '.$url);
			if(!empty($data)) {
				$options['json'] = $data;
				$this->log(
					json_encode($data, JSON_PRETTY_PRINT)
				);
			}
		} else if($method == self::ADD) {
			$options['json'] = $data;
			$this->log('ADD '.$url);
			$this->log(
				json_encode($data, JSON_PRETTY_PRINT)
			);
		} else if($method == self::DELETE) {
			if(!empty($data)) {
				$options['query'] = $data;
				$this->log('DELETE ' . $url . '?' . http_build_query($data));
			} else {
				$this->log('DELETE ' . $url);
			}
		}

    $client = new \GuzzleHttp\Client([
      'headers' => $this->getHeaders($url, $method)
    ]);

    try {
			$lowerMethod = strtolower($method);
      $response = $client->$lowerMethod($url, $options);
      
			$responseClass = $this->getResponse();

			$result = new $responseClass($response, $method);
			
      $this->debug($response->getStatusCode());
      $this->debug(implode("\n", array_map(function ($value, $key) {
        return $key . ': ' . implode(' ', $value);
      }, $response->getHeaders(), array_keys($response->getHeaders()))));
      $this->debug($response->getBody());
      return $result;
    } catch (\GuzzleHttp\Exception\RequestException $e) {
      $this->error($e->getCode());
      $this->error(implode("\n", array_map(function ($value, $key) {
        return $key . ': ' . implode(' ', $value);
      }, $e->getResponse()->getHeaders(), array_keys($e->getResponse()->getHeaders()))));
      $this->error($e->getResponse()->getBody()->getContents());
      throw $e;
    } catch (\GuzzleHttp\Exception\ClientException $e) {
      $this->error($e->getCode());
      $this->error(implode("\n", array_map(function ($value, $key) {
        return $key . ': ' . implode(' ', $value);
      }, $e->getResponse()->getHeaders(), array_keys($e->getResponse()->getHeaders()))));
      $this->error($e->getResponse()->getBody()->getContents());
      throw $e;
    } catch (\Exception $e) {
      $this->error($e->getCode());
      $this->error($e->getMessage());
      throw $e;
    }
	}
}
?>