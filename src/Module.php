<?php

namespace spaceonfire\BMF;

use Exception;

class Module
{
	private $MODULE_ID;
	private $MODULE_VERSION;
	private $MODULE_VERSION_DATE;
	private $ADMIN_FORM_ID;

	public $options;
	public $logger;

	/**
	 * spaceonfire\BMF\Module constructor.
	 * @param array $options array of module properties
	 * @throws Exception
	 */
	public function __construct(array $options = [])
	{
		if (!$options['MODULE_ID']) {
			throw new Exception('MODULE_ID is required');
		}

		$this->MODULE_ID = $options['MODULE_ID'];
		$this->MODULE_VERSION = $options['MODULE_VERSION'];
		$this->MODULE_VERSION_DATE = $options['MODULE_VERSION_DATE'];
		$this->ADMIN_FORM_ID = $options['ADMIN_FORM_ID'];

		$this->options = new Options\Manager($this->MODULE_ID);
		$this->logger = new Logger($this->MODULE_ID);
	}

	/**
	 * Get module id
	 * @return string MODULE_ID property
	 */
	public function getId(): string
	{
		return $this->MODULE_ID;
	}

	/**
	 * Get module version
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->MODULE_VERSION;
	}

	/**
	 * Set module version
	 * @param string $MODULE_VERSION
	 */
	public function setVersion($MODULE_VERSION): void
	{
		$this->MODULE_VERSION = $MODULE_VERSION;
	}

	/**
	 * Get module version date
	 * @return string MODULE_VERSION_DATE property
	 */
	public function getVersionDate(): string
	{
		return $this->MODULE_VERSION_DATE;
	}

	/**
	 * Set module version date
	 * @param string $MODULE_VERSION_DATE
	 */
	public function setVersionDate($MODULE_VERSION_DATE): void
	{
		$this->MODULE_VERSION_DATE = $MODULE_VERSION_DATE;
	}

	/**
	 * Output admin options form
	 */
	public function showOptionsForm(): void
	{
		$form = new Options\Form($this->options, $this->ADMIN_FORM_ID);
		$form->handleRequest();
		$form->write();
	}
}
