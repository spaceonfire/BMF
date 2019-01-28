<?php

namespace spaceonfire\BMF\Options;

use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use CAdminMessage;
use CAdminTabControl;
use Bitrix\Main\Application;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/options.php');

class Form {
	/**
	 * @var Manager
	 */
	private $options = null;

	private $tabControl = null;
	private $context = null;
	private $formId = null;

	public function __construct($options, $formId) {
		global $USER, $APPLICATION;
		if (!$USER->isAdmin()) {
			$APPLICATION->authForm('Access denied.');
		}

		$this->options = $options;
		$this->context = Application::getInstance()->getContext();
		$this->formId = $formId ?: 'module_settings_form';

		defined('ADMIN_MODULE_NAME') or define('ADMIN_MODULE_NAME', $this->options->getModuleId());
	}

	private function getRequest() {
		return $this->context->getRequest();
	}

	public function handleRequest() {
		global $save, $restore;

		$request = $this->getRequest();
		if ((!empty($save) || !empty($restore)) && $request->isPost() && check_bitrix_sessid()) {
			if (!empty($restore)) {
				// Restore defaults
				Option::delete(ADMIN_MODULE_NAME);
				CAdminMessage::showMessage([
					'MESSAGE' => Loc::getMessage('REFERENCES_OPTIONS_RESTORED'),
					'TYPE' => 'OK',
				]);
			} else {
				try {
					// Save options
					foreach ($this->options->getFields() as $id => $opt) {
						switch ($opt['type']) {
							case 'checkbox':
								$value = $request->getPost($id) ? true : false;
								break;

							case 'number':
								$value = (int) $request->getPost($id);
								break;

							case 'text':
							default:
								$value = $request->getPost($id);
								break;
						}

						$event = new Event(ADMIN_MODULE_NAME, 'OnBeforeSetOption_' . $id, ['value' => &$value]);
						$event->send();

						$event = new Event(ADMIN_MODULE_NAME, 'OnBeforeSetOption', ['value' => &$value, 'name' => $id]);
						$event->send();

						Option::set(ADMIN_MODULE_NAME, $id, $value);
					}

					$event = new Event(ADMIN_MODULE_NAME, 'OnAfterSaveOptions');
					$event->send();

					CAdminMessage::showMessage([
						'MESSAGE' => Loc::getMessage('REFERENCES_OPTIONS_SAVED'),
						'TYPE' => 'OK',
					]);
				} catch (\Exception $exception) {
					CAdminMessage::showMessage($exception->getMessage());
				}
			}
		}
	}

	public function write() {
		global $mid;

		$this->tabControl = new CAdminTabControl('tabControl', $this->options->getTabs());

		$fields = $this->options->getFields();

		$this->tabControl->begin();
		?>
		<form method="post" action="<?=sprintf('%s?mid=%s&lang=%s', $this->getRequest()->getRequestedPage(), urlencode($mid), LANGUAGE_ID)?>" name="<?=$this->formId?>" id="<?=$this->formId?>">
			<?php
			echo bitrix_sessid_post();
			foreach ($this->tabControl->tabs as $tab) {
				$this->tabControl->beginNextTab();
				$filteredOpts = array_filter($fields, function ($opt) use ($tab) { return $opt['tab'] === $tab['DIV']; });
				foreach ($filteredOpts as $opt_name=>$opt) { ?>
				<tr id="<?=$opt_name?>_row">
					<?
					switch ($opt['type']) {
						case 'html':
							if (strlen($opt['label'])) {
								?>
								<td width="30%" style="vertical-align: top; line-height: 25px;">
									<label for="<?=$opt_name?>"><?=$opt['label'] . ($opt['required'] ? ' *' : '')?>:</label>
								<td width="70%">
								<?
							} else {
								?>
								<td colspan="2">
								<?
							}
								if ($opt['html']) {
									echo $opt['html'];
								} else if (file_exists($opt['path'])) {
									include $opt['path'];
								}
								?>
							</td>
							<?
							break;

						default:
							?>
							<td width="30%" style="vertical-align: top; line-height: 25px;">
								<label for="<?=$opt_name?>"><?=$opt['label'] . ($opt['required'] ? ' *' : '')?>:</label>
							<td width="70%">
								<?php
								switch ($opt['type']) {
									case 'select':
										?>
										<select
											name="<?=$opt_name?>"
											id="<?=$opt_name?>"
											<?= $opt['required'] ? 'required="required"' : ''?>
										>
											<option
												value=""
												<?=$opt['required'] ? ' disabled' : ''?>
												<?=!Option::get(ADMIN_MODULE_NAME, $opt_name, $opt['default']) ? 'selected="selected"' : ''?>
											>-</option>
											<? foreach ($opt['values'] as $value => $display) { ?>
												<option
													value="<?=$value?>"
													<?=Option::get(ADMIN_MODULE_NAME, $opt_name, $opt['default']) == $value ? 'selected="selected"' : ''?>
												><?=$display?></option>
											<? } ?>
										</select>
										<?
										break;

									case 'checkbox':
										?>
										<input
											type="<?=$opt['type']?>"
											name="<?=$opt_name?>"
											id="<?=$opt_name?>"
											<?=Option::get(ADMIN_MODULE_NAME, $opt_name) ? 'checked="checked"' : ''?>
											<?= $opt['required'] ? 'required="required"' : ''?>
										/>
										<?php
										break;

									case 'textarea':
										?>
										<textarea
											cols="<?=$opt['cols'] ?: '80'?>"
											rows="<?=$opt['rows'] ?: '20'?>"
											name="<?=$opt_name?>"
											id="<?=$opt_name?>"
											<?= $opt['required'] ? 'required="required"' : ''?>
											<?= $opt['placeholder'] ? 'placeholder="' . htmlspecialchars($opt['placeholder']) . '"' : ''?>
										><?=htmlspecialchars(Option::get(ADMIN_MODULE_NAME, $opt_name, $opt['default']));?></textarea>
										<?
										break;

									case 'text':
									default:
										?>
										<input
											type="<?=$opt['type']?>"
											size="<?=$opt['size'] ?: '50'?>"
											name="<?=$opt_name?>"
											id="<?=$opt_name?>"
											value="<?=htmlspecialchars(Option::get(ADMIN_MODULE_NAME, $opt_name));?>"
											<?= $opt['required'] ? 'required="required"' : ''?>
											<?= $opt['placeholder'] ? 'placeholder="' . htmlspecialchars($opt['placeholder']) . '"' : ''?>
										/>
										<?php
										break;
								}
								?>
								<? if ($opt['description']) echo '<p>' . $opt['description'] . '</p>';?>
							</td>
							<?
							break;
					} ?>
				</tr>
				<? }
			}

			$this->tabControl->buttons();
			?>
			<input
				type="submit"
				name="save"
				value="<?=Loc::getMessage('MAIN_SAVE') ?>"
				title="<?=Loc::getMessage('MAIN_OPT_SAVE_TITLE') ?>"
				class="adm-btn-save"
			/>

			<input
				type="submit"
				name="restore"
				title="<?=Loc::getMessage('MAIN_HINT_RESTORE_DEFAULTS') ?>"
				onclick="return confirm('<?= addslashes(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING')) ?>')"
				value="<?=Loc::getMessage('MAIN_RESTORE_DEFAULTS') ?>"
			/>
			<?php
			$this->tabControl->end();
			?>
		</form>
		<?
	}
}
