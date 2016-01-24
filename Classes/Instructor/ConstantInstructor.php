<?php namespace JBR\Advini\Instructor;

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

use JBR\Advini\Advini;
use JBR\Advini\AdviniAdapter;
use JBR\Advini\Interfaces\ConvertInterface;
use JBR\Advini\Interfaces\InstructorInterface;
use JBR\Advini\Traits\ArrayUtility;

/**
 *
 *
 */
class ConstantInstructor implements InstructorInterface {

	use ArrayUtility;

	const TOKEN = '@const';

	const TOKEN_DEFAULT_VALUE = ':';

	/**
	 * @var array
	 */
	protected $constants = [];

	/**
	 * @param mixed $key
	 *
	 * @return bool
	 */
	public function canProcessKey($key) {
		return false;
	}

	/**
	 * @param mixed $value
	 *
	 * @return bool
	 */
	public function canProcessValue($value) {
		return (true === is_string($value));
	}

	/**
	 * @param AdviniAdapter $adapter
	 * @param array         $configuration
	 *
	 * @return void
	 */
	public function processKey(AdviniAdapter $adapter, array &$configuration) {

	}

	/**
	 * @param AdviniAdapter $adapter
	 * @param string        $value
	 *
	 * @return string
	 */
	protected function convert(AdviniAdapter $adapter, $value) {
		if (null !== ($statement = $adapter->getInstructor(CharsetInstructor::class))) {
			/** @var ConvertInterface $statement */
			$value = $statement->convert($adapter, $value);
		}

		return $value;
	}

	/**
	 * @param AdviniAdapter $adapter
	 * @param string        $value
	 *
	 * @return void
	 */
	public function processValue(AdviniAdapter $adapter, &$value) {
		$pattern = sprintf('/^%s (.+)$/', self::TOKEN);

		if (0 < preg_match($pattern, $value, $matches)) {
			$parts = explode(self::TOKEN_DEFAULT_VALUE, $matches[1], 2);
			$key = $parts[0];

			if (true === isset($this->constants[$key])) {
				$value = $this->convert($adapter, $this->constants[$key]);
			} elseif (true === isset($parts[1])) {
				$value = $this->convert($adapter, $parts[1]);

				if (null === $value) {
					$value = '';
				}
			}
		}
	}

	/**
	 * @param string $file
	 *
	 * @return void
	 */
	public function setConstantsFromFile($file) {
		$constants = parse_ini_file($file, true);
		$this->extractKeys($constants, Advini::TOKEN_MULTI_KEY_SEPARATOR);
		$this->constants = array_merge($this->constants, $constants);
	}

	/**
 	 * @param array $constants
 	 *
 	 * @return void
 	 */
	public function setConstants(array $constants) {
		$this->constants = array_merge($this->constants, $constants);
	}
}