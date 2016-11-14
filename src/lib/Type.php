<?php
namespace WalmartSellerAPI\lib;

use WalmartSellerAPI\Utility\XSDParser;

class Type {
	protected $__type;

	public function __construct(TypeDef $type) {
		$this->__type = $type;
	}

	public function __set($key, $value) {
		if($this->__type->hasField($key)) {
			if($this->__type->isValid($key, $value));
			$this->$key = $value;
		} else if($this->__type->hasElement($key)) {
			$elements = $this->__type->getElements();

			foreach($elements['elements'] as $name => $fields) {
				if($name != $key) unset($this->$key);
			}

			if($elements['max'] == 1) {
				$this->$key = $value;
			} else {
				if(isset($this->$key)) $values = $this->$key;
				else $values = array();
				$values[] = $value;
				$this->$key = $values;
			}
		} else trigger_error('Unknown Field: '.$key.' not defined for '.$this->__type->getName(), E_USER_WARNING);
	}
	
	public function __get($key) {
		if($this->__type->hasField($key)) {
			if(isset($this->$key)) return $this->$key;
			else if($this->__hasFieldDefault($key)) return $this->__type->getFieldDefault($key);
			else return null;
		} else if($this->__type->hasElement($key)) {
			if(isset($this->$key)) return $this->$key;
			else return null;
		}
		
		trigger_error('Unknown Field: '.$key.' not defined for '.$this->__type->getName(), E_USER_WARNING);
		
		return null;
	}

	public function getElementOptions() {
		if($this->__type->hasElements()) return $this->__type->getElements()['elements'];
		else return array();
	}
	
	public function getFields() {
		return $this->__type->getFields();
	}

	public function parse($xml) {
		foreach($this->__type->getFields() as $key => $value) {
			if(isset($xml->children($this->__type->getNamespace())->$key)) {
				if(!isset($value['maxOccurs']) || $value['maxOccurs'] == 1) {
					$this->$key = $this->__parse($value['type'], $xml->children($this->__type->getNamespace())->$key);
				} else {
					$values  = array();

					foreach($xml->children($this->__type->getNamespace())->$key as $elements) {
						$values[] = $this->__parse($value['type'], $elements);
					}
					$this->$key = $values;
				}
			}
		}
	}
	
	private function __parse($type, $data) {
		
		$value = null;
		
		switch($type) {
			case 'integer':
			case 'int':
				$value = intval($data);
				break;
			case 'float':
			case 'double':
			case 'decimal':
				$value = floatval($data);
				break;
			case 'string':
			case 'anyURI':
			case 'timestamp':
			case 'dateTime':
				$value = (string)$data;
				break;
			case 'boolean':
				$value = filter_var($data, FILTER_VALIDATE_BOOLEAN);
				break;
			default:
				$value = \WalmartSellerAPI\Library::getType($type);

				if(get_class($value) == get_class($this)) {
					$value->parse($data);
				} else {
					$value = $this->__parse($value->getType(), $data);
				}
				break;
		}
		
		return $value;
	}

	public function asXML($xml) {
		foreach($this->__type->getFields() as $key => $value) {
			if(isset($this->$key)) {
				$this->__xml($xml, $key, $this->$key);

			}
		}

		if($this->__type->hasElements()) {
			foreach($this->__type->getElements()['elements'] as $key => $element) {
				if(isset($this->$key)) {
					$this->__xml($xml, $key, $this->$key);
				}
			}
		
		}
	
		return $xml;
	}

	private function __xml($xml, $key, $val) {
		if(is_array($val)) {
			foreach($val as $v) {
				if(is_object($v)) {
					$v->asXML($xml->addChild($key));
				} else {
					$xml->addChild($key, $v);
				}
			}
		} else {
			if(is_object($this->$key)) {
				$this->$key->asXML($xml->addChild($key));
			} else {
				if($this->__type->isAttribute($key)) {
					$xml->addAttribute($key, $val);
				} else {
					$xml->addChild($key, $val);
				}
			}
		}
	}
}
