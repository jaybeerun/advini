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

/**
 *
 */
class AdviniAdapter
{

    /**
     * @var Advini
     */
    private $object;

    /**
     * @param Advini $object
     */
    public function __construct(Advini $object)
    {
        $this->object = $object;
    }

    /**
     * @param string $key
     * @return InstructorInterface
     */
    public function getInstructor($key)
    {
        $instructor = null;

        if (true === $this->object->hasInstructor($key)) {
            $instructor = $this->object->getInstructor($key);
        }

        return $instructor;
    }

    /**
     * @return string
     */
    public function getCwd()
    {
        return $this->object->getCwd();
    }

    /**
     * @param string $file
     *
     * @return Advini
     */
    public function getFromFile($file)
    {
        return $this->object->getFromFile($file, true);
    }

    /**
     * @param string $value
     * @param string $token
     * @param string $pattern
     *
     * @return array
     */
    public function matchNextValue($value, $token, $pattern)
    {
        $pattern = sprintf('/%s%s/', $this->escapedTokenPattern($token), $pattern);

        return $this->match($pattern, $value);
    }

    /**
     * @param string $value
     *
     * @return mixed
     * @throws Exception
     */
    protected function escapedTokenPattern($value)
    {
        if (0 < preg_match('/(\\(|\\)|\\$|"|=)/', $value, $invalidMatches)) {
            throw new Exception(sprintf('Usage of an invalid token char: %s', $invalidMatches[1]));
        }

        return preg_replace(
            '/(\\{|\\(|\\[|\\$|\\+|\\*|\\?|\\.|\\^|\\]|\\)|\\})/',
            '\\$1',
            $value
        );
    }

    /**
     * @param string $pattern
     * @param string $value
     *
     * @return array
     * @throws Exception
     */
    protected function match($pattern, $value)
    {
        if (0 < preg_match('/(\\\\(|\\\\)|\\\\$|"|=)/', $value, $invalidMatches)) {
            throw new Exception(sprintf('Usage of an invalid pattern char: %s', $invalidMatches[1]));
        }

        if (1 !== preg_match($pattern, $value, $matches)) {
            throw new Exception(sprintf('Cannot parse instructor for: %s', $value));
        }

        return $matches;
    }

    /**
     * @param string $value
     * @param string $token
     * @param string $pattern
     *
     * @return array
     */
    public function matchValue($value, $token, $pattern)
    {
        $pattern = sprintf('/^%s%s$/', $this->escapedTokenPattern($token), $pattern);

        return $this->match($pattern, $value);
    }
}
