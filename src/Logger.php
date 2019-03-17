<?php

namespace spaceonfire\BMF;

use CEventLog;

class Logger
{
	private $moduleId;
	private $auditTypeId;
	private $itemId;

	public function __construct($moduleId)
	{
		$this->moduleId = $moduleId;
	}

	/**
	 * Get custom audit type id
	 * @return string|null
	 */
	public function getAuditTypeId(): ?string
	{
		return $this->auditTypeId;
	}

	/**
	 * Set custom audit type id
	 * @param string $auditTypeId
	 */
	public function setAuditTypeId($auditTypeId): void
	{
		$this->auditTypeId = $auditTypeId;
	}

	/**
	 * Get predefined item id
	 * @return string|null
	 */
	public function getItemId(): ?string
	{
		return $this->itemId;
	}

	/**
	 * Set item id
	 * @param string $itemId
	 */
	public function setItemId($itemId): void
	{
		$this->itemId = $itemId;
	}

	/**
	 * Add message to event log
	 * @param string|array $message log message string or array with fields to pass into CEventLog::Add
	 */
	public function log($message): void
	{
		$defaults = [
			'SEVERITY' => 'INFO',
			'AUDIT_TYPE_ID' => $this->auditTypeId,
			'ITEM_ID' => $this->itemId ?: '',
		];

		$message = $this->logMessToArray($message);

		CEventLog::Add(array_merge($defaults, $message, ['MODULE_ID' => $this->moduleId]));
	}

	/**
	 * Add message to log with INFO severity
	 * @see Logger::log()
	 * @param string|array $message same as $message param for Logger::log()
	 */
	public function info($message): void
	{
		$this->log($message);
	}

	/**
	 * Add message to log with DEBUG severity
	 * @see Logger::log()
	 * @param string|array $message same as $message param for Logger::log()
	 */
	public function debug($message): void
	{
		$message = $this->logMessToArray($message);
		$this->log(array_merge(['SEVERITY' => 'DEBUG'], $message));
	}

	/**
	 * Add message to log with WARNING severity
	 * @see Logger::log()
	 * @param string|array $message same as $message param for Logger::log()
	 */
	public function warning($message): void
	{
		$message = $this->logMessToArray($message);
		$this->log(array_merge(['SEVERITY' => 'WARNING'], $message));
	}

	/**
	 * Add message to log with ERROR severity
	 * @see Logger::log()
	 * @param string|array $message same as $message param for Logger::log()
	 */
	public function error($message): void
	{
		$message = $this->logMessToArray($message);
		$this->log(array_merge(['SEVERITY' => 'ERROR'], $message));
	}

	/**
	 * Add message to log with SECURITY severity
	 * @see Logger::log()
	 * @param string|array $message same as $message param for Logger::log()
	 */
	public function security($message): void
	{
		$message = $this->logMessToArray($message);
		$this->log(array_merge(['SEVERITY' => 'SECURITY'], $message));
	}

	private function logMessToArray($mess): array
	{
		if (!is_array($mess)) {
			$mess = [
				'DESCRIPTION' => $mess,
			];
		}

		return $mess;
	}
}
