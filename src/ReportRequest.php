<?php
namespace WalmartSellerAPI;

use WalmartSellerAPI\model\Inventory;

class ReportRequest extends AbstractJSONRequest {
	public function getFilenameFromURL($url) {
		$path = parse_url($url, PHP_URL_PATH);
		$filename = basename($path, '.zip');
		return $filename . '.csv';
	}

	private function loadCSVFromFile($filename) {
		$data = [];
		$header = false;

		$file = fopen($filename, 'r');

		while (($line = fgetcsv($file)) !== false) {
			if ($header === false) {
				$header = $line;
			} else {
				$row = [];

				foreach ($header as $index => $name) {
					$row[$name] = $line[$index];
				}

				$data[] = $row;
			}
		}

		return $data;
	}

	private function downloadUnzipGetContents($url) {
    $data = file_get_contents($url);

    $path = tempnam(sys_get_temp_dir(), 'walmart-report');

    $temp = fopen($path, 'w');
    fwrite($temp, $data);
    fseek($temp, 0);
    fclose($temp);

    $pathExtracted = tempnam(sys_get_temp_dir(), 'walmart-report');

    $filenameInsideZip = $this->getFilenameFromURL($url);
    copy("zip://".$path."#".$filenameInsideZip, $pathExtracted);

    $data = $this->loadCSVFromFile($pathExtracted);

    unlink($path);
    unlink($pathExtracted);

    return $data;
	}

	public function requestReport($type, $version) {
		$url = '/reportRequests?' .http_build_query([
			'reportType' => $type,
			'reportVersion' => $version,
		]);
		return $this->post($url);
	}

	public function status($requestId) {
		return $this->get('/reportRequests/' . $requestId);
	}

	public function getDownloadUrl($requestId) {
		$result = $this->get('/downloadReport', [
			'requestId' => $requestId,
		]);

		if ($result && $result->isSuccess()) {
			$result = $result->getResults();

			if ($result['requestStatus'] === 'READY') {
				return $result['downloadURL'];
			}
		}
		
		return false;
	}
	
	public function download($requestId) {
		if ($url = $this->getDownloadUrl($requestId)) {
			return $this->downloadUnzipGetContents($url);
		}
		
		return false;
	}

	protected function getAcceptType() {
		return 'Accept: application/json';
	}

	public function getEndpoint() {
		return '/v3/reports';
	}

	protected function getResponse() {
		return 'WalmartSellerAPI\ReportResponse';
	}
}