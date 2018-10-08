<?php

namespace goldencode\Helpers\Bitrix;

use Bitrix\Main\Event;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use CAdminMessage;
use CAdminTabControl;
use Bitrix\Main\Application;

class OptionsManager {
	private $options = [];
	private $tabs = [];
	private $tabControl = null;
	private $context = null;

	public function __construct($options, $tabs) {
		$this->context = Application::getInstance()->getContext();
		$this->options = $options;
		$this->tabs = $tabs;
	}

	private function getRequest() {
		return $this->context->getRequest();
	}

	public function getDefaultValues() {
		return array_map(function ($val) { return $val['default']; }, $this->options);
	}

	public function handleRequest(callable $callback = null) {
		global $USER, $APPLICATION, $save, $restore;

		if (!$USER->isAdmin()) {
			$APPLICATION->authForm('Nope');
		}

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
					foreach ($this->options as $id => $opt) {
						switch ($opt['type']) {
							case 'checkbox':
								$value = $request->getPost($id) ? true : false;
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

					// TODO: deprecate callback. use event instead
					if (!is_null($callback)) {
						$callback($request);
					}

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

	public function writeForm() {
		global $USER, $APPLICATION, $mid;

		if (!$USER->isAdmin()) {
			$APPLICATION->authForm('Nope');
		}

		$this->tabControl = new CAdminTabControl('tabControl', $this->tabs);

		$this->tabControl->begin();
		?>
		<form method="post" action="<?=sprintf('%s?mid=%s&lang=%s', $this->getRequest()->getRequestedPage(), urlencode($mid), LANGUAGE_ID)?>">
			<?php
			echo bitrix_sessid_post();
			foreach ($this->tabControl->tabs as $tab) {
				$this->tabControl->beginNextTab();
				$filteredOpts = array_filter($this->options, function ($opt) use ($tab) { return $opt['tab'] === $tab['DIV']; });
				foreach ($filteredOpts as $opt_name=>$opt) { ?>
					<tr>
						<? if ($opt['type'] === 'html') { ?>
						<td colspan="2">
							<?
							if ($opt['html']) {
								echo $opt['html'];
							} else if (file_exists($opt['path'])) {
								include $opt['path'];
							}
							?>
						</td>
						<? } else { ?>
						<td width="40%" style="vertical-align: top; line-height: 25px;">
							<label for="<?=$opt_name?>"><?=$opt['label'] . ($opt['required'] ? ' *' : '')?>:</label>
						<td width="60%">
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
												<?=Option::get(ADMIN_MODULE_NAME, $opt_name, $opt['default']) === $value ? 'selected="selected"' : ''?>
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
									><?=htmlspecialchars(Option::get(ADMIN_MODULE_NAME, $opt_name) ?: $opt['default']);?></textarea>
									<?
									break;

								case 'text':
								default:
									?>
									<input
										type="<?=$opt['type']?>"
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
						<? } ?>
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
				onclick="return confirm('<?= AddSlashes(GetMessage('MAIN_HINT_RESTORE_DEFAULTS_WARNING')) ?>')"
				value="<?=Loc::getMessage('MAIN_RESTORE_DEFAULTS') ?>"
			/>
			<?php
			$this->tabControl->end();
			?>
		</form>
		<?
	}
}
