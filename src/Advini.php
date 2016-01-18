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

	const TOKEN_CHARSET = '@charset';

	const TOKEN_IMPORT = '@import';

	const TOKEN_CONSTANT = '@const';

	const TOKEN_METHOD_SEPARATOR = ':';

	const TOKEN_MULTI_KEY_SEPARATOR = '/';

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
	 * @var string
	 */
	protected $toEncoding = null;

	/**
	 * @var string
	 */
	protected $fromEncoding = null;

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

			if (false !== strpos($key, self::TOKEN_MULTI_KEY_SEPARATOR)) {
				$key_arr = explode(self::TOKEN_MULTI_KEY_SEPARATOR, $key);
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
	protected function processConfiguration(&$configuration, $finalize = false) {
		if (true === is_array($configuration)) {
			$this->throughConfiguration($configuration, $finalize);
		} elseif (true === is_string($configuration)) {
			$this->processValue($configuration);
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
		$this->checkImportStatement($configuration);

		foreach ($configuration as $originKey => $value) {
			$methods = explode(self::TOKEN_METHOD_SEPARATOR, $originKey);

			if ((true === $finalize) && (1 < count($methods))) {
				$toSetKey = array_shift($methods);

				foreach ($methods as $method) {
					$value = $this->processMethod($method, $originKey, $value, $finalize);
				}

				$configuration[$toSetKey] = $value;

				unset($configuration[$originKey]);
			} else {
				$this->processConfiguration($value, true);
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

		$this->processConfiguration($value, $finalize);

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
		if ('@' === $value{0}) {
			if (false === $this->checkImportStatementForValue($value)) {
				$this->checkConstantStatement($value);
			}
		}
	}

	/**
	 * @param string $value
	 *
	 * @return boolean
	 */
	protected function checkImportStatementForValue(&$value) {
		$pattern = sprintf('/^%s (.+)$/', self::TOKEN_IMPORT);
		$result = false;

		if (0 < preg_match($pattern, $value, $matches)) {
			$value = $this->importFromFile($matches[1]);
			$result = true;
		}

		return $result;
	}

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	protected function checkConstantStatement(&$value) {
		$pattern = sprintf('/^%s (.+)$/', self::TOKEN_CONSTANT);

		if (0 < preg_match($pattern, $value, $matches)) {
			list ($key, $defaultValue) = explode(self::TOKEN_DEFAULT_VALUE, $matches[1], 2);

			if (true === isset($this->constants[$key])) {
				$value = $this->convert($this->constants[$key]);
			} else {
				$value = $this->convert($defaultValue);

				if (null === $value) {
					$value = '';
				}
			}
		}
	}

	/**
	 * @param string $value
	 *
	 * @return string
	 */
	protected function convert($value) {
		if (null !== $this->fromEncoding) {
			$value = mb_convert_encoding($value, $this->toEncoding, $this->fromEncoding);
		}

		return $value;
	}

	/**
	 * @param string $file
	 *
	 * @return array
	 */
	protected function importFromFile($file) {
		$importFile = sprintf('%s/%s', $this->cwd, $file);

		return $this->getFromFile($importFile, $this->toEncoding, true);
	}

	/**
	 * @param string $file
	 * @param string $charset
	 * @param bool   $finalize
	 *
	 * @return array
	 */
	public function getFromFile($file, $charset = null, $finalize = false) {
		$this->assertFile($file);
		$this->cwd = dirname($file);

		if (null !== $this->toEncoding) {
			$this->toEncoding = $this->setEncoding($charset);
		}

		$configuration = $this->parseIniFile($file);

		$this->checkImportStatement($configuration);
		$this->checkCharsetStatement($configuration);
		$this->extractKeys($configuration);
		$this->processConfiguration($configuration, $finalize);

		return $configuration;
	}

	/**
	 * @param string $file
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function parseIniFile($file) {
		$configuration = parse_ini_file($file, true);

		if (false === $configuration) {
			throw new Exception(sprintf('Cannot read ini file <%s>!', $file));
		}

		return $configuration;
	}

	/**
	 * @param array $configuration
	 *
	 * @return void
	 */
	protected function checkImportStatement(array &$configuration) {
		if (true === isset($configuration[self::TOKEN_IMPORT])) {
			$additionalConfiguration = $this->importFromFile($configuration[self::TOKEN_IMPORT]);
			$configuration = array_merge($configuration, $additionalConfiguration);

			unset($configuration[self::TOKEN_IMPORT]);
		}
	}

	/**
	 * @param array $configuration
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function checkCharsetStatement(array &$configuration) {
		if (true === isset($configuration[self::TOKEN_CHARSET])) {
			$this->fromEncoding = $this->setEncoding($configuration[self::TOKEN_CHARSET]);

			if (null === $this->toEncoding) {
				throw new Exception(
					sprintf('Cannot convert from <%s> encoding without knowing where to convert!', $this->fromEncoding)
				);
			}

			unset($configuration[self::TOKEN_CHARSET]);
		}
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

	/**
	 * @param string $charset
	 *
	 * @return string
	 */
	protected function setEncoding($charset) {
		$charsets = array_flip(mb_list_encodings());

		if (false === isset($charsets[$charset])) {
			throw new Exception(sprintf('Invalid or unknown charset <%s>!', $charset));
		}

		return $charset;
	}
}