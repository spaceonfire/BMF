<?php

namespace spaceonfire\BMF;

use CEventLog;

class Logger {
	private $moduleId = null;
	private $auditTypeId = null;
	private $itemId = null;

	public function __construct($moduleId) {
		$this->moduleId = $moduleId;
	}

	/**
	 * Get custom audit type id
	 * @return string | null
	 */
	public function getAuditTypeId() {
		return $this->auditTypeId;
	}

	/**
	 * Set custom audit type id
	 * @param string $auditTypeId
	 */
	public function setAuditTypeId($auditTypeId) {
		$this->auditTypeId = $auditTypeId;
	}

	/**
	 * Get predefined item id
	 * @return string | null
	 */
	public function getItemId() {
		return $this->itemId;
	}

	/**
	 * Set item id
	 * @param string $itemId
	 */
	public function setItemId($itemId) {
		$this->itemId = $itemId;
	}

	/**
	 * Add message to event log
	 * @param string | array $message log message string or array with fields to pass into CEventLog::Add
	 */
	public function log($message) {
		$defaults = [
			'SEVERITY' => 'INFO',
			'AUDIT_TYPE_ID' => $this->auditTypeId,
			'ITEM_ID' => $this->itemId ?: '',
		];

		$message = $this->logMessToArray($message);

		CEventLog::Add(array_merge($defaults, $message, ['MODULE_ID' => $this->moduleId]));
	}

	public function info($message) {
		$this->log($message);
	}

	public function debug($message) {
		$this->log([
			'SEVERITY' => 'DEBUG',
			'DESCRIPTION' => $message,
		]);
	}

	public function warning($message) {
		$message = $this->logMessToArray($message);

		$this->log(array_merge(['SEVERITY' => 'WARNING'], $message));
	}

	public function error($message) {
		$message = $this->logMessToArray($message);

		$this->log(array_merge(['SEVERITY' => 'ERROR'], $message));
	}

	public function security($message) {
		$message = $this->logMessToArray($message);

		$this->log(array_merge(['SEVERITY' => 'SECURITY'], $message));
	}

	private function logMessToArray($mess) {
		if (!is_array($mess)) {
			$mess = [
				'DESCRIPTION' => $mess,
			];
		}

		return $mess;
	}
}
