<?php

namespace spaceonfire\BMF;

use Exception;

class Module {
	private $MODULE_ID;
	private $MODULE_VERSION;
	private $MODULE_VERSION_DATE;

	public $options;

	/**
	 * spaceonfire\BMF\Module constructor.
	 * @param array $options array of module properties
	 * @throws Exception
	 */
	public function __construct(array $options = []) {
		if (!$options['MODULE_ID']) {
			throw new Exception('MODULE_ID is required');
		}

		$this->MODULE_ID = $options['MODULE_ID'];
		$this->MODULE_VERSION = $options['MODULE_VERSION'];
		$this->MODULE_VERSION_DATE = $options['MODULE_VERSION_DATE'];

		$this->options = new Options\Manager($this->MODULE_ID);
	}

	/**
	 * Get module id
	 * @return string MODULE_ID property
	 */
	public function getId() {
		return $this->MODULE_ID;
	}

	/**
	 * Get module version
	 * @return string
	 */
	public function getVersion() {
		return $this->MODULE_VERSION;
	}

	/**
	 * Set module version
	 * @param string $MODULE_VERSION
	 */
	public function setVersion($MODULE_VERSION): void {
		$this->MODULE_VERSION = $MODULE_VERSION;
	}

	/**
	 * Get module version date
	 * @return string MODULE_VERSION_DATE property
	 */
	public function getVersionDate() {
		return $this->MODULE_VERSION_DATE;
	}

	/**
	 * Set module version date
	 * @param string $MODULE_VERSION_DATE
	 */
	public function setVersionDate($MODULE_VERSION_DATE): void {
		$this->MODULE_VERSION_DATE = $MODULE_VERSION_DATE;
	}

	public function showOptionsForm(): void {
		$form = new Options\Form($this->options);
		$form->handleRequest();
		$form->write();
	}
}
