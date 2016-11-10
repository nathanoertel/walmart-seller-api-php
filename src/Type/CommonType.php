<?php
namespace WalmartSellerAPI\Type;

use WalmartSellerAPI\Utility\XSDParser;

class CommonType {
	private static $types;

	protected $__fields;

	protected $__name;

	public static function getType($type) {
		if(!isset(self::$types[$type])) {

			$types = XSDParser::parse($type);

			foreach($types['types'] as $name => $t) {
				self::$types[$name] = $t;
			}
		}

		if(is_array(self::$types[$type])) {
			if(isset(self::$types[$type]['_fields'])) self::$types[$type] = new CommonType($type, self::$types[$type]);
		}

		return self::$types[$type];
	}

	public function __construct($name, $fields) {
		$this->__name = $name;
		$this->__fields = $fields['_fields'];
	}

	public function __set($key, $value) {
		if(isset($this->__fields[$key])) {
			$this->$key = $value;
		} else if(isset($this->__fields['_elements']['elements'][$key])) {
			foreach($this->__fields['_elements']['elements'] as $name => $fields) {
				if($name != $key) unset($this->$key);
			}

			if($this->__fields['_elements']['max'] == 1) {
				$this->$key = $value;
			} else {
				if(isset($this->$key)) $values = $this->$key;
				else $values = array();
				$values[] = $value;
				$this->$key = $values;
			}
		} else trigger_error('Unknown Field: '.$key.' not defined for '.$this->__name, E_USER_WARNING);
	}
	
	public function __get($key) {
		if(isset($this->__fields[$key])) {
			if(isset($this->$key)) return $this->$key;
			else if(isset($this->__fields[$key]['default'])) return $this->__fields[$key]['default'];
			else return null;
		} else if(isset($this->__fields['_elements']['elements'][$key])) {
			if(isset($this->$key)) return $this->$key;
			else return null;
		}
		
		trigger_error('Unknown Field: '.$key.' not defined for '.$this->__name, E_USER_WARNING);
		
		return null;
	}

	public function getElementOptions() {
		if(isset($this->__fields['_elements'])) return $this->__fields['_elements']['elements'];
		else return array();
	}
	
	public function getFields() {
		return $this->__fields;
	}
	
	public function parse($data) {
		foreach($this->__fields as $key => $value) {
			if(isset($data[$key])) {
				$this->$key = $this->__parse($value, $data[$key]);
			}
		}
	}
	
	private function __parse($type, $data) {
		
		$value = null;
		
		switch($type['type']) {
			case 'integer':
				$value = intval($data);
				break;
			case 'float':
				$value = floatval($data);
				break;
			case 'string':
				$value = $data;
				break;
			case 'timestamp':
				$value = $data;
				break;
			case 'boolean':
				$value = filter_var($data, FILTER_VALIDATE_BOOLEAN);
				break;
			case 'array':
				$value = array();
				$values = array();
				
				if(isset($type['type-data']['key'])) {
					if(isset($data[$type['type-data']['key']])) {
						// determine if it a single entry of associative array keys
						if(array_keys($data[$type['type-data']['key']]) === range(0, count($data[$type['type-data']['key']])-1)) {
							$values = $data[$type['type-data']['key']];
						} else {
							$values = array($data[$type['type-data']['key']]);
						}
					}
				} else $values = $data;
				
				if(!empty($values)) {
					foreach($values as $entry) {
						$value[] = $this->__parse($type['type-data'], $entry);
					}
				}
			default:
				$class = 'LightspeedPOS_Object_'.$type['type'];
				
				if(class_exists($class)) {
					$value = new $class();
					
					$value->parse($data);
				}
				break;
		}
		
		return $value;
	}

	public function jsonSerialize() {
		$result = array();

		foreach($this->__fields as $key => $value) {
			$val = $this->$key;

			if($val != null) {
				switch($value['type']) {
					case 'array':
						$result[$key] = array(
							$value['type-data']['key'] => $val
						);
						break;
					default:
						$result[$key] = $val;
						break;
				}
			}
		}

		return json_encode($result);
	}
	
	private function __json($type, $val) {
		switch($type) {
			case 'integer':
			case 'float':
			case 'string':
			case 'boolean':
			case 'array':
				return $val;
				break;
			default:
				return $val->jsonSerialize();
		}
	}
}
