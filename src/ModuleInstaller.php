<?php

namespace spaceonfire\BMF;

use Bitrix\Main\IO\FileDeleteException;
use Bitrix\Main\IO\FileOpenException;
use Bitrix\Main\ModuleManager;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

trait ModuleInstaller
{
	protected $DEV_LINKS = [];
	protected $INSTALL_PATHS = [];
	protected $IGNORE_PATTERNS = [];
	protected $INSTALLER_DIR = __DIR__;

	/**
	 * Process module install: register module in db, run install db hooks, install fs
	 * @throws FileOpenException
	 */
	public function doInstall(): void
	{
		ModuleManager::registerModule($this->MODULE_ID);
		$this->installDB();
		$this->installFiles();
	}

	/**
	 * Revert module installation
	 * @throws FileDeleteException
	 */
	public function doUninstall(): void
	{
		$this->uninstallDB();
		$this->uninstallFiles();
		ModuleManager::unRegisterModule($this->MODULE_ID);
	}

	/**
	 * Check is module install in development mode
	 * @return bool
	 */
	public function isDevelopmentMode(): bool
	{
		return $_ENV['ENVIRONMENT'] === 'development';
	}

	/**
	 * Install module files
	 *
	 * In production mode recursively copy all files in directories passed to $this->INSTALL_PATHS
	 * In development mode create symlink defined in $this->DEV_LINKS
	 *
	 * @throws FileOpenException when copy file failed
	 * @throws \RuntimeException when mkdir failed
	 */
	public function installFiles(): void
	{
		if (!$this->isDevelopmentMode()) {
			foreach ($this->INSTALL_PATHS as $path) {
				$files = $this->getRecursiveFiles($path);
				foreach ($files as $from => $to) {
					$dir = dirname($to);
					if (!file_exists($dir)) {
						if (!mkdir($dir, BX_DIR_PERMISSIONS, true) && !is_dir($dir)) {
							throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
						}
					}

					if (is_link($from)) {
						symlink(readlink($from), $to);
					} else {
						if (!copy($from, $to)) {
							throw new FileOpenException($to);
						}
					}
				}
			}
		} else {
			foreach ($this->DEV_LINKS as $link => $target) {
				$dir = dirname($link);
				if (!file_exists($dir)) {
					if (!mkdir($dir, BX_DIR_PERMISSIONS, true) && !is_dir($dir)) {
						throw new \RuntimeException(sprintf('Directory "%s" was not created', $dir));
					}
				}

				if (!file_exists($link)) {
					symlink($this->findRelativePath($link, $target), $link);
				}
			}
		}
	}

	/**
	 * Remove files and symlinks created by module
	 *
	 * @throws FileDeleteException
	 */
	public function uninstallFiles(): void
	{
		$isDirEmpty = function ($dir) {
			if (!file_exists($dir) || !is_readable($dir)) {
				return false;
			}
			return count(scandir($dir, SCANDIR_SORT_NONE)) === 2;
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

	private function getRecursiveFiles($path): array
	{
		$dirFromDocRoot = substr($this->INSTALLER_DIR, strlen($_SERVER['DOCUMENT_ROOT']));

		$iter = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($this->INSTALLER_DIR . '/' . $path, RecursiveDirectoryIterator::SKIP_DOTS),
			RecursiveIteratorIterator::SELF_FIRST,
			RecursiveIteratorIterator::CATCH_GET_CHILD // Ignore "Permission denied"
		);

		$result = [];
		/**
		 * @var string $filePath
		 * @var \SplFileInfo $item
		 */
		foreach ($iter as $filePath => $item) {
			if (!$item->isFile() && !$item->isLink()) {
				continue;
			}

			// Skip ignored files
			foreach ($this->IGNORE_PATTERNS as $pattern) {
				if (preg_match($pattern, $filePath) > 0) {
					continue 2;
				}
			}

			$result[$filePath] = str_replace($dirFromDocRoot, '', $filePath);
		}

		uasort($result, function ($a, $b) {
			return (strlen($a) > strlen($b)) ? -1 : 1;
		});

		return $result;
	}

	private function getRecursiveDirs($path): array
	{
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

		foreach ($iter as $filePath => $item) {
			if ($item->isDir()) {
				$result[$filePath] = str_replace($dirFromDocRoot, '', $filePath);
			}
		}

		uasort($result, function ($a, $b) {
			return (strlen($a) > strlen($b)) ? -1 : 1;
		});

		return $result;
	}

	/**
	 *
	 * Find the relative file system path between two file system paths
	 *
	 * @param  string $frompath Path to start from
	 * @param  string $topath Path we want to end up in
	 * @return string Path leading from $frompath to $topath
	 */
	private function findRelativePath($frompath, $topath): string
	{
		$from = explode(DIRECTORY_SEPARATOR, $frompath); // Folders/File
		$to = explode(DIRECTORY_SEPARATOR, $topath); // Folders/File
		$relpath = '';

		$i = 0;
		// Find how far the path is the same
		while (isset($from[$i], $to[$i])) {
			if ($from[$i] !== $to[$i]) {
				break;
			}
			$i++;
		}

		$j = count($from) - 1;
		// Add '..' until the path is the same
		while ($i <= $j) {
			if (!empty($from[$j])) {
				$relpath .= '..' . DIRECTORY_SEPARATOR;
			}
			$j--;
		}

		// Go to folder from where it starts differing
		while (isset($to[$i])) {
			if (!empty($to[$i])) {
				$relpath .= $to[$i] . DIRECTORY_SEPARATOR;
			}
			$i++;
		}

		// Strip last separator
		return substr($relpath, 3, -1);
	}
}
