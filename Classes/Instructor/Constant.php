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

use Exception;
use JBR\Advini\Advini;
use JBR\Advini\AdviniAdapter;
use JBR\Advini\Interfaces\Converter;
use JBR\Advini\Interfaces\Instructor;
use JBR\Advini\Traits\ArrayUtility;

/**
 *
 *
 */
class Constant implements Instructor
{
    use ArrayUtility;

    const PROCESS_TOKEN = '<<';

    const TOKEN_DEFAULT_VALUE = ':';

    /**
     * @var array
     */
    protected $constants = [];

    /**
     * @var bool
     */
    protected $automaticallyAddConstants = false;

    /**
     * @param bool $automaticallyAddConstants
     */
    public function __construct(bool $automaticallyAddConstants = false)
    {
        $this->automaticallyAddConstants = $automaticallyAddConstants;
    }

    /**
     * @param mixed $key
     *
     * @return bool
     */
    public function canProcessKey(mixed $key): bool
    {
        return (true === is_array($key));
    }

    /**
     * @param string $value
     *
     * @return bool
     */
    public function canProcessValue(string $value): bool
    {
        return ((true === is_string($value)) && (false !== strpos($value, self::PROCESS_TOKEN)));
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return bool
     */
    public function canProcessKeyValue(string $key, string $value): bool
    {
        return ((true === $this->automaticallyAddConstants) && (true === is_string($value)));
    }

    /**
     * @param string $key
     * @param string $value
     *
     * @return void
     */
    public function processKeyValue(string $key, string $value): void
    {
        $this->setConstant($key, $value);
    }

    /**
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function setConstant(string $key, mixed $value): void
    {
        $this->constants[$key] = $value;
    }

    /**
     * @param AdviniAdapter $adapter
     * @param array $configuration
     *
     * @return void
     */
    public function processKey(AdviniAdapter $adapter, array &$configuration): void
    {
        $newConfiguration = [];

        foreach ($configuration as $keyValue => &$value) {
            while (false !== strpos($keyValue, self::PROCESS_TOKEN)) {
                // You cannot define these chars: $ { } ( )
                $matches = $adapter->matchNextValue($keyValue, self::PROCESS_TOKEN, '( *[^<>]+ *)>>');
                $parts = explode(self::TOKEN_DEFAULT_VALUE, trim($matches[1]), 2);
                $key = $parts[0];

                if (true === isset($this->constants[$key])) {
                    $keyValue = str_replace($matches[0], $this->convert($adapter, $this->constants[$key]), $keyValue);
                } elseif (true === isset($parts[1])) {
                    $keyValue = str_replace($matches[0], $this->convert($adapter, $parts[1]), $keyValue);
                } else {
                    $keyValue = $matches[1];
                }
            }

            $newConfiguration[$keyValue] = $value;
        }

        $configuration = $newConfiguration;
    }

    /**
     * @param AdviniAdapter $adapter
     * @param string $value
     *
     * @return string
     */
    protected function convert(AdviniAdapter $adapter, string $value): string
    {
        if (null !== ($statement = $adapter->getInstructor(Charset::class))) {
            /** @var Converter $statement */
            $value = $statement->convert($adapter, $value);
        }

        return $value;
    }

    /**
     * @param AdviniAdapter $adapter
     * @param string $value
     *
     * @throws Exception
     * @return void
     */
    public function processValue(AdviniAdapter $adapter, string &$value): void
    {
        while (false !== strpos($value, self::PROCESS_TOKEN)) {
            // You cannot define these chars: $ { } ( )
            $matches = $adapter->matchNextValue($value, self::PROCESS_TOKEN, '( *[^<>]+ *)>>');
            $parts = explode(self::TOKEN_DEFAULT_VALUE, trim($matches[1]), 2);
            $key = $parts[0];

            if (true === isset($this->constants[$key])) {
                $value = str_replace($matches[0], $this->convert($adapter, $this->constants[$key]), $value);
            } elseif (true === isset($parts[1])) {
                $value = str_replace($matches[0], $this->convert($adapter, $parts[1]), $value);

                if (null === $value) {
                    $value = '';
                }
            } else {
                throw new Exception(sprintf('Cannot found constant <%s>', $key));
            }
        }
    }

    /**
     * @param string $file
     *
     * @return void
     */
    public function setConstantsFromFile(string $file): void
    {
        $constants = parse_ini_file($file, true);
        $this->extractKeys($constants, Advini::TOKEN_MULTI_KEY_SEPARATOR);
        $this->constants = array_merge($this->constants, $constants);
    }

    /**
     * @param array $constants
     *
     * @return void
     */
    public function setConstants(array $constants): void
    {
        $this->constants = array_merge($this->constants, $constants);
    }

    /**
     * @return string
     */
    public function getProcessToken(): string
    {
        return self::PROCESS_TOKEN;
    }
}
