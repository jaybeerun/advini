<?php namespace JBR\Advini;

/************************************************************************************
 * Copyright (c) 2016, Jan Runte
 * All rights reserved.
 *
 * Redistribution  and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * 1. Redistributions  of source code must retain the above copyright notice,  this
 * list of conditions and the following disclaimer.
 *
 * 2. Redistributions  in  binary  form  must  reproduce the above copyright notice,
 * this list of conditions and the following disclaimer in the documentation and/or
 * other materials provided with the distribution.
 *
 * THIS  SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND
 * ANY  EXPRESS OR IMPLIED WARRANTIES,  INCLUDING, BUT NOT LIMITED TO, THE  IMPLIED
 * WARRANTIES  OF  MERCHANTABILITY  AND   FITNESS  FOR  A  PARTICULAR  PURPOSE  ARE
 * DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR
 * ANY  DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL  DAMAGES
 * (INCLUDING,  BUT  NOT LIMITED TO,  PROCUREMENT OF SUBSTITUTE GOODS  OR  SERVICES;
 * LOSS  OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND  ON
 * ANY  THEORY  OF  LIABILITY,  WHETHER  IN  CONTRACT,  STRICT  LIABILITY,  OR TORT
 * (INCLUDING  NEGLIGENCE OR OTHERWISE)  ARISING IN ANY WAY OUT OF THE USE OF  THIS
 * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 ************************************************************************************/

use Exception;
use JBR\Advini\Wrapper\AbstractWrapper;

/**
 *
 */
class Advini {

	const TOKEN_IMPORT = '/^@import (.+)$/';

	const TOKEN_CONSTANT = '/^@const (.+)$/';

	const TOKEN_METHOD_SEPERATOR = ':';

	const TOKEN_MULTI_KEY_SEPERATOR = '/';

	const TOKEN_DEFAULT_VALUE = ':';

	/**
	 * @var AbstractWrapper
	 */
	protected $wrapper;

	/**
	 * @var array
	 */
	protected $constants;

	/**
	 * Current working directory
	 *
	 * @var string
	 */
	protected $cwd;

	/**
	 * Advini constructor.
	 *
	 * @param AbstractWrapper $methodsObject
	 */
	public function __construct(AbstractWrapper $methodsObject = null) {
		$this->wrapper = $methodsObject;
	}

	/**
	 * Extract multi named keys like "key1/key2".
	 *
	 * @param array $source
	 *
	 * @return void
	 */
	protected function extractKeys(array &$source) {
		foreach ($source as $key => &$value) {
			if (true === is_array($value)) {
				$this->extractKeys($value);
			}

			if (false !== strpos($key, self::TOKEN_MULTI_KEY_SEPERATOR)) {
				$key_arr = explode(self::TOKEN_MULTI_KEY_SEPERATOR, $key);
				$last_key = array_pop($key_arr);
				$cur_elem = &$source;

				foreach ($key_arr as $key_step) {
					if (false !== isset($cur_elem[$key_step])) {
						$cur_elem[$key_step] = [];
					}

					$cur_elem = &$cur_elem[$key_step];
				}

				$cur_elem[$last_key] = $value;
				unset($source[$key]);
			}
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
	 * @param mixed $configuration
	 * @param boolean $finalize
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function compileConfiguration(&$configuration, $finalize = false) {
		if (true === is_array($configuration)) {
			$this->throughConfiguration($configuration, $finalize);
		} elseif (true === is_string($configuration)) {
			$this->processValue($configuration);
		} else {
			var_dump($configuration);
		}
	}

	/**
	 * @param array $configuration
	 * @param boolean $finalize
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function throughConfiguration(array &$configuration, $finalize = false) {
		foreach ($configuration as $originKey => $value) {
			$methods = explode(self::TOKEN_METHOD_SEPERATOR, $originKey);

			if ((true === $finalize) && (1 < count($methods))) {
				$toSetKey = array_shift($methods);

				foreach ($methods as $method) {
					$value = $this->processMethod($method, $originKey, $value, $finalize);
				}

				$configuration[$toSetKey] = $value;
				unset($configuration[$originKey]);
			} else {
				$this->compileConfiguration($value, true);
				$configuration[$originKey] = $value;
			}
		}
	}

	/**
	 * @param string  $methodName
	 * @param string  $key
	 * @param array   $value
	 * @param boolean $finalize
	 *
	 * @return mixed
	 * @throws Exception
	 */
	protected function processMethod($methodName, $key, $value, $finalize = false) {
		$result = null;

		$this->compileConfiguration($value, $finalize);

		try {
			$result = $this->wrapper->execute($methodName, $value);
		} catch (Exception $e) {
			throw new Exception(
				sprintf('Invalid configuration settings for <%s>! %s', $key, $e->getMessage())
			);
		}

		return $result;
	}

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	protected function processValue(&$value) {
		if (0 < preg_match(self::TOKEN_IMPORT, $value, $matches)) {
			$importFile = sprintf('%s/%s', $this->cwd, $matches[1]);
			$value = $this->getFromFile($importFile, true);
		} elseif (0 < preg_match(self::TOKEN_CONSTANT, $value, $matches)) {
			list ($key, $defaultValue) = explode(self::TOKEN_DEFAULT_VALUE, $matches[1], 2);

			if (true === isset($this->constants[$key])) {
				$value = $this->constants[$key];
			} else {
				$value = $defaultValue;

				if (null === $value) {
					$value = '';
				}
			}
		}
	}

	/**
	 * @param string $file
	 * @param bool $finalize
	 *
	 * @throws Exception
	 * @return array
	 */
	public function getFromFile($file, $finalize = false) {
		$this->assertFile($file);
		$this->cwd = dirname($file);

		$configuration = parse_ini_file($file, true);
		$this->extractKeys($configuration);
		$this->compileConfiguration($configuration, $finalize);

		return $configuration;
	}

	/**
	 * @param string $file
	 *
	 * @return void
	 */
	public function setConstantsFromFile($file) {
		$constants = parse_ini_file($file, true);
		$this->extractKeys($constants);
		$this->constants = $constants;
	}
}