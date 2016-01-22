<?php namespace JBR\Advini;

/************************************************************************************
 * Copyright (c) 2016, Jan Runte
 * All rights reserved.
 *
 * Redistribution and use in source and  binary forms, with or without modification,
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
use JBR\Advini\Interfaces\StatementInterface;
use JBR\Advini\Traits\ArrayUtility;
use JBR\Advini\Traits\Encoding;
use JBR\Advini\Traits\FileUtility;
use JBR\Advini\Wrapper\AbstractWrapper;

/**
 *
 */
class Advini {

	use Encoding, ArrayUtility, FileUtility;

	const TOKEN_METHOD_SEPARATOR = ':';

	const TOKEN_MULTI_KEY_SEPARATOR = '/';

	/**
	 * @var AbstractWrapper
	 */
	protected $wrapper;

	/**
	 * @var array
	 */
	protected $statements = [];

	/**
	 * @var string
	 */
	protected $encoding = null;

	/**
	 * @var AdviniAdapter
	 */
	protected $adapter;

	/**
	 * Advini constructor.
	 *
	 * @param AbstractWrapper $methodsObject
	 */
	public function __construct(AbstractWrapper $methodsObject = null) {
		$this->wrapper = $methodsObject;
	}

	/**
	 * @param StatementInterface $statementObject
	 * @param string             $key
	 *
	 * @return void
	 */
	public function addStatement(StatementInterface $statementObject, $key = NULL) {
		if (null === $key) {
			$key = $statementObject->getKey();
		}

		if (true === isset($this->statements[$key])) {
			$this->statements[$key] = null;
			unset($this->statements[$key]);
		}

		$this->statements[$key] = $statementObject;
	}

	/**
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function hasStatement($key) {
		return (true === isset($this->statements[$key]));
	}

	/**
	 * @param string $key
	 *
	 * @return StatementInterface
	 * @throws Exception
	 */
	public function getStatement($key) {
		if (false === isset($this->statements[$key])) {
			throw new Exception(sprintf('Cannot find statement <%s>!', $key));
		}

		return $this->statements[$key];
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
		$this->setCwd(dirname($file));

		if (null !== $this->encoding) {
			$this->encoding = $this->setEncoding($charset);
		}

		$this->adapter = new AdviniAdapter($this, $this->getCwd(), $this->encoding);

		$configuration = $this->getArrayFromIniFile($file);

		$this->processKeyStatements($configuration);
		$this->extractKeys($configuration, self::TOKEN_MULTI_KEY_SEPARATOR);
		$this->processConfiguration($configuration, $finalize);

		return $configuration;
	}

	/**
	 * @return string
	 */
	public function getEncoding() {
		return $this->encoding;
	}

	/**
	 * @param mixed $configuration
	 * @param boolean $finalize
	 *
	 * @throws Exception
	 * @return void
	 */
	public function processConfiguration(&$configuration, $finalize = false) {
		if (true === is_array($configuration)) {
			$this->throughConfiguration($configuration, $finalize);
		} elseif (true === is_string($configuration)) {
			$this->processValueStatements($configuration);
		}
	}

	/**
	 * @param array $configuration
	 *
	 * @return void
	 */
	protected function processKeyStatements(array &$configuration) {
		foreach ($this->statements as $statement /** @var StatementInterface $statement */) {
			if (true === $statement->canProcessKey($configuration)) {
				$statement->processKey(new AdviniAdapter($this), $configuration);
			}
		}
	}

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	protected function processValueStatements(&$value) {
		foreach ($this->statements as $statement /** @var StatementInterface $statement */) {
			if (true === $statement->canProcessValue($value)) {
				$statement->processValue(new AdviniAdapter($this), $value);
			}
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
		$this->processKeyStatements($configuration);

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
}
