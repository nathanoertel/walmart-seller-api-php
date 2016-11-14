<?php
namespace WalmartSellerAPI\lib;

class TypeDef {
	private $name;

	private $type;

	private $namespace;

	private $fields;

	private $elements;

	private $minOccurs;

	private $maxOccurs;

	private $attribute;

	private $default;

	public function isComplex() {
		return $this->type == null;
	}

	public function getName() {
		return $this->name;
	}

	public function getType() {
		return $this->type;
	}

	public function getNamespace() {
		return $this->namespace;
	}

	public function hasField($name) {
		return isset($this->fields[$name]);
	}

	public function getFields() {
		return $this->fields;
	}

	public function isValid($field, $value) {
		if(isset($this->fields[$field])) {
			if(isset($this->fields[$field]['options'])) {
				return in_array($value, $this->fields[$field]['options']);
			} else return true;
		} else return false;
	} 

	public function hasElements() {
		return $this->elements != null;
	}

	public function hasElement($name) {
		return isset($this->elements['elements'][$name]);
	}

	public function getElements() {
		return $this->elements;
	}

	public function isRequired() {
		return $this->minOccurs > 0;
	}

	public function isMultiple() {
		return $this->maxOccurs == 'unbounded' || $this->maxOccurs > 1;
	}

	public function isAttribute($field) {
		return isset($this->fields[$field]['attribute']) && $this->fields[$field]['attribute'];
	}

	public function hasFieldDefault($name) {
		return isset($this->fields[$name]['default']) ? true : false;
	}

	public function getFieldDefault() {
		return isset($this->fields[$name]['default']) ? $this->fields[$name]['default'] : null;
	}

	public function __construct($name, $type) {
		$this->name = $name;
		$this->type = isset($type['type']) ? $type['type'] : null;
		$this->namespace = isset($type['namespace']) ? $type['namespace'] : null;
		$this->fields = isset($type['_fields']) ? $type['_fields'] : array();
		$this->elements = isset($type['_elements']) ? $type['_elements'] : array();
		if(isset($this->fields['_elements'])) {
			$this->elements = array_merge_recursive($this->elements, $this->fields['_elements']);
			unset($this->fields['_elements']);
		}
		$this->minOccurs = isset($type['minOccurs']) ? $type['minOccurs'] : null;
		$this->maxOccurs = isset($type['maxOccurs']) ? $type['maxOccurs'] : null;
		$this->attribute = isset($type['attribute']) ? $type['attribute'] : false;
		$this->default = isset($type['default']) ? $type['default'] : null;
	}
}