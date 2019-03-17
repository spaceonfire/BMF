<?php

namespace spaceonfire\BMF\Options;

use Bitrix\Main\Config\Option;

class Manager
{
	private $moduleId;
	private $optionFields = [];
	private $optionTabs = [];
	private $optionValues = [];

	/**
	 * Manager constructor.
	 * @param string $moduleId module id
	 */
	public function __construct(string $moduleId)
	{
		$this->moduleId = $moduleId;
	}

	/**
	 * Add one option
	 * @param string $id option id
	 * @param array $option an array of option params
	 */
	public function addOption(string $id, array $option): void
	{
		$this->optionFields[$id] = $option;
	}

	/**
	 * Add multiple options at once
	 * @param array $options an associative array [string $id => array $optionParams]
	 */
	public function addOptions(array $options): void
	{
		foreach ($options as $key => $option) {
			$this->addOption($key, $option);
		}
	}

	/**
	 * Add options tab
	 * @param string $id tab id
	 * @param array $tab an array of tab params
	 */
	public function addTab(string $id, array $tab): void
	{
		$this->optionTabs[] = array_merge($tab, ['DIV' => $id]);
	}

	/**
	 * Add multiple tabs at once
	 * @param array $tabs an associative array [string $id => array $tabParams]
	 */
	public function addTabs(array $tabs): void
	{
		foreach ($tabs as $key => $tab) {
			$this->addTab($key, $tab);
		}
	}

	/**
	 * Load module options values fallback to defaults
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	private function loadOptionValues(): void
	{
		$this->optionValues = array_merge(
			Option::getDefaults($this->moduleId),
			Option::getForModule($this->moduleId)
		);
	}

	/**
	 * Get module option value
	 * @param string $name option id
	 * @return mixed option value
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function get(string $name)
	{
		if (empty($this->optionValues)) {
			$this->loadOptionValues();
		}
		return $this->optionValues[$name];
	}

	/**
	 * Get all module options values
	 * @return array
	 * @throws \Bitrix\Main\ArgumentOutOfRangeException
	 */
	public function getAll(): array
	{
		if (empty($this->optionValues)) {
			$this->loadOptionValues();
		}
		return $this->optionValues;
	}

	/**
	 * Get default options values
	 * @return array
	 */
	public function getDefaults(): array
	{
		return array_map(function ($val) {
			return $val['default'];
		}, $this->optionFields);
	}

	/**
	 * Get tabs
	 * @return array
	 */
	public function getTabs(): array
	{
		return $this->optionTabs;
	}

	/**
	 * Get option
	 * @return array
	 */
	public function getFields(): array
	{
		return $this->optionFields;
	}

	/**
	 * Getter for moduleId field
	 * @return string
	 */
	public function getModuleId(): string
	{
		return $this->moduleId;
	}
}
