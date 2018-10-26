<?php

namespace spaceonfire\BMF\Options;

use Bitrix\Main\Config\Option;

class Manager {
	private $moduleId = null;
	private $optionFields = [];
	private $optionTabs = [];
	private $optionValues = [];

	public function __construct($moduleId) {
		$this->moduleId = $moduleId;
	}

	public function addOption($key, array $option) {
		$this->optionFields[$key] = $option;
	}

	public function addOptions(array $options) {
		foreach ($options as $key => $option) {
			$this->addOption($key, $option);
		}
	}

	public function addTab($key, array $tab) {
		$this->optionTabs[] = array_merge($tab, ['DIV' => $key]);
	}

	public function addTabs(array $tabs) {
		foreach ($tabs as $key => $tab) {
			$this->addTab($key, $tab);
		}
	}

	private function loadOptionValues() {
		$this->optionValues = array_merge(
			Option::getDefaults($this->moduleId),
			Option::getForModule($this->moduleId)
		);
	}

	public function get($name) {
		if (empty($this->optionValues)) {
			$this->loadOptionValues();
		}
		return $this->optionValues[$name];
	}

	public function getAll() {
		if (empty($this->optionValues)) {
			$this->loadOptionValues();
		}
		return $this->optionValues;
	}

	public function getDefaults() {
		return array_map(function ($val) { return $val['default']; }, $this->optionFields);
	}

	public function getTabs() {
		return $this->optionTabs;
	}

	public function getFields() {
		return $this->optionFields;
	}

	/**
	 * @return string
	 */
	public function getModuleId()
	{
		return $this->moduleId;
	}
}
