<?php

namespace spaceonfire\BMF;

use Bitrix\Main\IO\FileDeleteException;
use Bitrix\Main\IO\FileOpenException;
use Bitrix\Main\ModuleManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait ModuleInstaller {
	protected $DEV_LINKS = [];
	protected $INSTALL_PATHS = [];
	protected $INSTALLER_DIR = __DIR__;

	public function doInstall() {
		ModuleManager::registerModule($this->MODULE_ID);
		$this->installDB();
		$this->installFiles();
	}

	public function doUninstall() {
		$this->uninstallDB();
		$this->uninstallFiles();
		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	public function isDevelopmentMode() {
		return $_ENV['ENVIRONMENT'] === 'development';
	}

	/**
	 * @throws FileOpenException
	 */
	public function installFiles() {
		if (!$this->isDevelopmentMode()) {
			foreach ($this->INSTALL_PATHS as $path) {
				$files = $this->getRecursiveFiles($path);
				foreach ($files as $from => $to) {
					$dir = dirname($to);
					if (!file_exists($dir)) {
						mkdir($dir, 0777, true);
					}
					if (!copy($from, $to)) {
						throw new FileOpenException($to);
					}
				}
			}
		} else {
			foreach ($this->DEV_LINKS as $link => $target) {
				if (!file_exists(dirname($link))) {
					mkdir(dirname($link), 0777, true);
				}

				if (!file_exists($link)) {
					symlink($this->findRelativePath($link, $target), $link);
				}
			}
		}
	}

	/**
	 * @throws FileDeleteException
	 */
	public function uninstallFiles() {
		$isDirEmpty = function ($dir) {
			if (!file_exists($dir) || !is_readable($dir)) return false;
			return count(scandir($dir)) === 2;
		};

		if (!$this->isDevelopmentMode()) {
			foreach ($this->INSTALL_PATHS as $path) {
				$files = $this->getRecursiveFiles($path);
				$dirs = $this->getRecursiveDirs($path);

				foreach ($files as $from => $to) {
					unlink($to);
				}

				foreach ($dirs as $dir) {
					if ($isDirEmpty($dir)) {
						if (!rmdir($dir)) {
							throw new FileDeleteException($dir);
						}
					}
				}
			}
		} else {
			foreach ($this->DEV_LINKS as $link => $target) {
				unlink($link);
			}
		}
	}

	private function getRecursiveFiles($path) {
		$dirFromDocRoot = substr($this->INSTALLER_DIR, strlen($_SERVER['DOCUMENT_ROOT']));

		$iter = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->INSTALLER_DIR . '/' . $path, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST,
			RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
		);

		$result = [];
		foreach ($iter as $path => $item) {
			if ($item->isFile()) {
				$result[$path] = str_replace($dirFromDocRoot, '', $path);
			}
		}

		uasort($result, function($a, $b) {
			return (strlen($a) > strlen($b)) ? -1 : 1;
		});

		return $result;
	}

	private function getRecursiveDirs($path) {
		$dirFromDocRoot = substr($this->INSTALLER_DIR, strlen($_SERVER['DOCUMENT_ROOT']));

		$iter = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->INSTALLER_DIR . '/' . $path, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST,
			RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
		);

		$result = [];

		if (!in_array($path, ['bitrix', 'local', 'upload'])) {
			$result[] = str_replace($dirFromDocRoot, '', $this->INSTALLER_DIR . '/' . $path);
		}

		foreach ($iter as $path => $item) {
			if ($item->isDir()) {
				$result[$path] = str_replace($dirFromDocRoot, '', $path);
			}
		}

		uasort($result, function($a, $b) {
			return (strlen($a) > strlen($b)) ? -1 : 1;
		});

		return $result;
	}

	/**
	 *
	 * Find the relative file system path between two file system paths
	 *
	 * @param  string  $frompath  Path to start from
	 * @param  string  $topath    Path we want to end up in
	 *
	 * @return string             Path leading from $frompath to $topath
	 */
	private function findRelativePath($frompath, $topath) {
		$from = explode(DIRECTORY_SEPARATOR, $frompath); // Folders/File
		$to = explode(DIRECTORY_SEPARATOR, $topath); // Folders/File
		$relpath = '';

		$i = 0;
		// Find how far the path is the same
		while (isset($from[$i]) && isset($to[$i])) {
			if ($from[$i] != $to[$i]) break;
			$i++;
		}
		$j = count($from) - 1;
		// Add '..' until the path is the same
		while ($i <= $j) {
			if (!empty($from[$j])) $relpath .= '..'.DIRECTORY_SEPARATOR;
			$j--;
		}
		// Go to folder from where it starts differing
		while (isset($to[$i])) {
			if (!empty($to[$i])) $relpath .= $to[$i].DIRECTORY_SEPARATOR;
			$i++;
		}

		// Strip last separator
		return substr($relpath, 3, -1);
	}
}
