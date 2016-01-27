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
use JBR\Advini\Interfaces\InstructorInterface;
use JBR\Advini\Traits\ArrayUtility;
use JBR\Advini\Traits\FileUtility;
use JBR\Advini\Wrapper\AbstractWrapper;

/**
 *
 */
class Advini {

	use ArrayUtility, FileUtility;

	const TOKEN_METHOD_SEPARATOR = ':';

	const TOKEN_MULTI_KEY_SEPARATOR = '/';

	/**
	 * @var AbstractWrapper
	 */
	protected $wrapper;

	/**
	 * @var array
	 */
	protected $instructions = [];

	/**
	 * @var AdviniAdapter
	 */
	protected $adapter;

	/**
	 * @var boolean
	 */
	protected $disableExtractKeys;

	/**
	 * Advini constructor.
	 *
	 * @param AbstractWrapper $methodsObject
	 */
	public function __construct(AbstractWrapper $methodsObject = null) {
		$this->wrapper = $methodsObject;
	}

	/**
	 * @return void
	 */
	public function disableExtractKeys() {
		$this->disableExtractKeys = true;
	}

	/**
	 * @return void
	 */
	public function enableExtractKeys() {
		$this->disableExtractKeys = false;
	}

	/**
	 * @param InstructorInterface $instructor
	 * @param string              $namespace
	 *
	 * @throws Exception
	 * @return void
	 */
	public function addInstructor(InstructorInterface $instructor, $namespace = null) {
		if (null === $namespace) {
			$namespace = get_class($instructor);
		}

		if (false === class_exists($namespace)) {
			throw new Exception(sprintf('Cannot find class <%s> with dependency injection for instructor!', $namespace));
		}

		if (true === isset($this->instructions[$namespace])) {
			$this->instructions[$namespace] = null;
			unset($this->instructions[$namespace]);
		}

		$this->instructions[$namespace] = $instructor;
	}

	/**
	 * @param string $key
	 *
	 * @return boolean
	 */
	public function hasInstructor($key) {
		return (true === isset($this->instructions[$key]));
	}

	/**
	 * @param string $key
	 *
	 * @return InstructorInterface
	 * @throws Exception
	 */
	public function getInstructor($key) {
		if (false === isset($this->instructions[$key])) {
			throw new Exception(sprintf('Cannot find instructor <%s>!', $key));
		}

		return $this->instructions[$key];
	}

	/**
	 * @param string $file
	 * @param bool   $finalize
	 *
	 * @return array
	 */
	public function getFromFile($file, $finalize = false) {
		$this->assertFile($file);
		$this->setCwd(dirname($file));

		$this->adapter = new AdviniAdapter($this);

		$configuration = $this->getArrayFromIniFile($file);

		$this->processKeyInstructions($configuration);

		if (false === $this->disableExtractKeys) {
			$this->extractKeys($configuration, self::TOKEN_MULTI_KEY_SEPARATOR);
		}

		$this->processConfiguration($configuration, $finalize);

		return $configuration;
	}

	/**
	 * @param mixed   $configuration
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
	protected function processKeyInstructions(array &$configuration) {
		foreach ($this->instructions as $instructor /** @var InstructorInterface $instructor */) {
			if (true === $instructor->canProcessKey($configuration)) {
				$instructor->processKey(new AdviniAdapter($this), $configuration);
			}
		}
	}

	/**
	 * @param string $value
	 *
	 * @return void
	 */
	protected function processValueStatements(&$value) {
		foreach ($this->instructions as $instructor /** @var InstructorInterface $instructor */) {
			if (true === $instructor->canProcessValue($value)) {
				$instructor->processValue(new AdviniAdapter($this), $value);
			}
		}
	}

	/**
	 * @param array   $configuration
	 * @param boolean $finalize
	 *
	 * @throws Exception
	 * @return void
	 */
	protected function throughConfiguration(array &$configuration, $finalize = false) {
		$this->processKeyInstructions($configuration);

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

		if (null !== $this->wrapper) {
			try {
				$result = $this->wrapper->execute($methodName, $value);
			} catch (Exception $e) {
				throw new Exception(
					sprintf('Invalid configuration settings for <%s>! %s', $key, $e->getMessage())
				);
			}
		}

		return $result;
	}
}
