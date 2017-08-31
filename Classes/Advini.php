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
use JBR\Advini\Exceptions\InvalidValue;
use JBR\Advini\Exceptions\MissingReference;
use JBR\Advini\Interfaces\Instructor;
use JBR\Advini\Traits\ArrayUtility;
use JBR\Advini\Traits\FileUtility;
use JBR\Advini\Setter\Conversion;

/**
 *
 */
class Advini
{
    use ArrayUtility, FileUtility;

    const TOKEN_METHOD_SEPARATOR = ':';

    const TOKEN_MULTI_KEY_SEPARATOR = '/';

    /**
     * @var Conversion
     */
    protected $conversionSetter;

    /**
     * @var Instructor[]
     */
    protected $instructions = [];

    /**
     * @var AdviniAdapter
     */
    protected $adapter;

    /**
     * @var boolean
     */
    protected $disableExtractKeys = false;

    /**
     * Advini constructor.
     *
     * @param Conversion $methodsObject
     */
    public function __construct(Conversion $methodsObject = null)
    {
        $this->conversionSetter = $methodsObject;
    }

    /**
     * @return void
     */
    public function disableExtractKeys(): void
    {
        $this->disableExtractKeys = true;
    }

    /**
     * @return void
     */
    public function enableExtractKeys(): void
    {
        $this->disableExtractKeys = false;
    }

    /**
     * @param Instructor $instructor
     * @param string $namespace
     *
     * @throws InvalidValue|MissingReference
     * @return void
     */
    public function addInstructor(Instructor $instructor, $namespace = null): void
    {
        if (null === $namespace) {
            $namespace = get_class($instructor);
        }

        if (false === class_exists($namespace)) {
            throw new MissingReference('Cannot find class <%s> with dependency injection for instructor!', $namespace);
        }

        $tokenValue = $instructor->getProcessToken();

        foreach ($this->instructions as $instruction/** @var Instructor $instruction */) {
            if ($instruction->canProcessValue($tokenValue)) {
                throw new InvalidValue(
                    'Cannot add instructor <%s> because the instructor <%s> can process the token value <%s>',
                    get_class($instructor),
                    get_class($instruction),
                    $tokenValue
                );
            }
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
    public function hasInstructor(string $key): bool
    {
        return (true === isset($this->instructions[$key]));
    }

    /**
     * @param string $key
     *
     * @return Instructor
     * @throws Exception
     */
    public function getInstructor(string $key): Instructor
    {
        $instructor = null;

        if (true === isset($this->instructions[$key])) {
            $instructor = $this->instructions[$key];
        }

        return $instructor;
    }

    /**
     * @param string $file
     * @param bool $finalize
     *
     * @return array
     */
    public function getFromFile(string $file, bool $finalize = false): array
    {
        $this->assertFile($file);
        $this->setCwd(dirname($file));

        $this->adapter = new AdviniAdapter($this);

        $configuration = $this->getArrayFromIniFile($file);

        if (false === $this->disableExtractKeys) {
            $this->extractKeys($configuration, self::TOKEN_MULTI_KEY_SEPARATOR);
        }

        $this->processKeyInstructions($configuration);
        $this->processConfiguration($configuration, $finalize);

        return $configuration;
    }

    /**
     * @param array $configuration
     *
     * @return void
     */
    protected function processKeyInstructions(array &$configuration): void
    {
        foreach ($this->instructions as $instructor/** @var Instructor $instructor */) {
            if (true === $instructor->canProcessKey($configuration)) {
                $instructor->processKey(new AdviniAdapter($this), $configuration);
            }
        }
    }

    /**
     * @param mixed $configuration
     * @param boolean $finalize
     *
     * @throws Exception
     * @return void
     */
    public function processConfiguration(mixed &$configuration, bool $finalize = false): void
    {
        if (true === is_array($configuration)) {
            $this->throughConfiguration($configuration, $finalize);
        } elseif (true === is_string($configuration)) {
            $this->processValueStatements($configuration);
        }
    }

    /**
     * @param array $configuration
     * @param boolean $finalize
     *
     * @throws Exception
     * @return void
     */
    protected function throughConfiguration(array &$configuration, bool $finalize = false)
    {
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

                foreach ($this->instructions as $instructor) {
                    if (true === $instructor->canProcessKeyValue($originKey, $value)) {
                        $instructor->processKeyValue($originKey, $value);
                    }
                }
            }
        }
    }

    /**
     * @param string $methodName
     * @param string $key
     * @param array $value
     * @param boolean $finalize
     *
     * @return mixed
     * @throws MissingReference|InvalidValue
     */
    protected function processMethod(string $methodName, string $key, array $value, bool $finalize = false): mixed
    {
        $result = null;

        $this->processConfiguration($value, $finalize);

        if (null !== $this->conversionSetter) {
            try {
                $result = $this->conversionSetter->execute($methodName, $value);
            } catch (Exception $e) {
                throw new InvalidValue('Invalid configuration settings for <%s>! %s', $key, $e->getMessage());
            }
        } elseif (true === function_exists($methodName)) {
            $result = $methodName($value);
        } else {
            throw new MissingReference('Cannot found method <%s> for <%s>!', $methodName, $key);
        }

        return $result;
    }

    /**
     * @param string $value
     *
     * @return void
     */
    protected function processValueStatements(string &$value): void
    {
        foreach ($this->instructions as $instructor/** @var Instructor $instructor */) {
            if (true === $instructor->canProcessValue($value)) {
                $instructor->processValue(new AdviniAdapter($this), $value);
            }
        }
    }
}
