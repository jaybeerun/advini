<?php namespace JBR\Advini\Traits;

/**********************************************************************
 *  Copyright notice
 *
 *  (c) 2016 Jan Runte (jan.runte@hmmh.de), hmmh AG
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify it under
 *  the terms of the GNU General Public License as published by the
 *  Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 **********************************************************************/

use Exception;

/**
 *
 */
trait FileUtility {

	/**
	 * Current working directory
	 *
	 * @var string
	 */
	private $cwd = null;

	/**
	 * @param $path
	 *
	 * @return void
	 */
	public function setCwd($path) {
		$this->assertPath($path);
		$this->cwd = $path;
	}

	/**
	 * @return string
	 */
	public function getCwd() {
		return $this->cwd;
	}

	/**
	 * @param string $pathName
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function assertPath($pathName) {
		if (false === is_dir($pathName)) {
			throw new Exception(sprintf('Cannot find path <%s>.', $pathName));
		}
	}

	/**
	 * @param string $fileName
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function assertFile($fileName) {
		if (false === is_file($fileName)) {
			throw new Exception(sprintf('Cannot find file <%s>.', $fileName));
		}
	}

	/**
	 * @param string $file
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getArrayFromIniFile($file) {
		$configuration = parse_ini_file($file, true);

		if (false === $configuration) {
			throw new Exception(sprintf('Cannot read ini file <%s>!', $file));
		}

		return $configuration;
	}
}