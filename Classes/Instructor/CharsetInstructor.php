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
use JBR\Advini\AdviniAdapter;
use JBR\Advini\Interfaces\ConvertInterface;
use JBR\Advini\Interfaces\InstructorInterface;

/**
 *
 *
 */
class CharsetInstructor implements InstructorInterface, ConvertInterface
{

    const PROCESS_TOKEN = '@charset';

    /**
     * @var string
     */
    protected $fromEncoding = null;

    /**
     * @var string
     */
    protected $toEncoding = null;

    /**
     * @param mixed $key
     *
     * @return bool
     */
    public function canProcessKey($key)
    {
        return (true === is_array($key));
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    public function canProcessValue($value)
    {
        return false;
    }

    /**
     * @param AdviniAdapter $adapter
     * @param array $configuration
     *
     * @return void
     * @throws Exception
     */
    public function processKey(AdviniAdapter $adapter, array &$configuration)
    {
        if (true === isset($configuration[self::PROCESS_TOKEN])) {
            $this->fromEncoding = $this->setEncoding($configuration[self::PROCESS_TOKEN]);

            if (null === $this->getEncoding()) {
                throw new Exception(
                    sprintf('Cannot convert from <%s> encoding without knowing where to convert!', $this->fromEncoding)
                );
            }

            unset($configuration[self::PROCESS_TOKEN]);
        }

        return null;
    }

    /**
     * @param string $charset
     *
     * @return string
     * @throws Exception
     */
    protected function setEncoding($charset)
    {
        $charsets = array_flip(mb_list_encodings());

        if (false === isset($charsets[$charset])) {
            throw new Exception(sprintf('Invalid or unknown charset <%s>!', $charset));
        }

        return $charset;
    }

    /**
     * @return string
     */
    public function getEncoding()
    {
        return $this->toEncoding;
    }

    /**
     * @param AdviniAdapter $adapter
     * @param string $value
     *
     * @return void
     */
    public function processValue(AdviniAdapter $adapter, &$value)
    {

    }

    /**
     * @param AdviniAdapter $adapter
     * @param string $value
     *
     * @return string
     */
    public function convert(AdviniAdapter $adapter, $value)
    {
        if (null !== $this->fromEncoding) {
            $value = mb_convert_encoding($value, $this->getEncoding(), $this->fromEncoding);
        }

        return $value;
    }

    /**
     * @return string
     */
    public function getProcessToken()
    {
        return self::PROCESS_TOKEN;
    }
}