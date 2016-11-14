<?php
namespace WalmartSellerAPI\lib;

use WalmartSellerAPI\Library;

class Document {

	private $name;

	private $namespace;

	private $type;

	public function getName() {
		return $this->name;
	}

	public function getNamespace() {
		return $this->namespace;
	}

	public function getType() {
		return $this->type;
	}

	public function getXML($data) {
		return $data->asXML(new \SimpleXMLElement('<ns3:'.$this->name.' xmlns:ns3="'.$this->namespace.'"></ns3:'.$this->name.'>'));
	}

	public function __construct($name, $type) {
		$this->name = $name;
		$this->namespace = $type['namespace'];
		if(isset($type['type'])) {
			$this->type = Library::getType($type['type']);
		} else {
			$typeDef = new TypeDef($name, $type);
			$this->type = new Type($typeDef);
		}
	}
}