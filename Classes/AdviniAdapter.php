<?php declare(strict_types=1); namespace JBR\Advini;

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

use JBR\Advini\Exceptions\InvalidValue;
use JBR\Advini\Interfaces\Instructor;

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
     *
     * @return Instructor
     */
    public function getInstructor(string $key): ?Instructor
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
    public function getCwd(): string
    {
        return $this->object->getCwd();
    }

    /**
     * @param string $file
     *
     * @return array
     */
    public function getFromFile(string $file): array
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
    public function matchNextValue(string $value, string $token, string $pattern): array
    {
        $pattern = sprintf('/%s%s/', $this->escapedTokenPattern($token), $pattern);

        return $this->match($pattern, $value);
    }

    /**
     * @param string $value
     *
     * @return mixed
     */
    protected function escapedTokenPattern(string $value)
    {
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
     * @throws InvalidValue
     */
    protected function match(string $pattern, string $value): array
    {
        if (1 !== preg_match($pattern, $value, $matches)) {
            throw new InvalidValue('Cannot parse instructor for: %s', $value);
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
    public function matchValue(string $value, string $token, string $pattern): array
    {
        $pattern = sprintf('/^%s%s$/', $this->escapedTokenPattern($token), $pattern);

        return $this->match($pattern, $value);
    }
}
