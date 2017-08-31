<?php namespace JBR\Advini\Setter;

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
use JBR\Advini\Exceptions\MissingReference;

/**
 *
 */
class DefaultCommands extends Conversion
{
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function stringCommand(mixed $value): string
    {
        return (string) $value;
    }

    /**
     * @param array $value
     *
     * @return string
     */
    public function linesSeparatedCommand(array $value): string
    {
        return implode("\n", $value);
    }

    /**
     * @param array $value
     *
     * @return string
     */
    public function commaSeparatedCommand(array $value): string
    {
        return implode(',', $value);
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function octdecCommand(mixed $value): string
    {
        return octdec($value);
    }

    /**
     * @param mixed $value
     *
     * @return string
     * @throws InvalidValue
     */
    public function jsonCommand(mixed $value): string
    {
        $json = json_decode($value);

        if (null === $json) {
            throw new InvalidValue('Cannot decode json content!');
        }

        return json_encode($json);
    }

    /**
     * @param mixed $value
     * @return array
     */
    public function arrayCommand(mixed $value): array
    {
        if ((false === is_array($value)) && (true === empty($value))) {
            $value = [];
        } elseif (true === is_string($value)) {
            if (1 < strpos($value, ',')) {
                $value = explode(',', $value);
            } elseif (1 < strpos($value, "\n")) {
                $value = explode("\n", $value);
            } else {
                $value = [$value];
            }
        } else {
            $value = [$value];
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return string
     * @throws InvalidValue
     */
    public function serializeCommand(mixed $value): string
    {
        $result = serialize($value);

        if (false === $result) {
            throw new InvalidValue('Cannot serialize value!');
        }

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function isIntegerCommand(mixed $value): string
    {
        return floatval($value);
    }

    /**
     * Checks a given mail address of availability.
     *
     * @param mixed $mail
     *
     * @return string
     */
    public function checkMailCommand(mixed $mail): string
    {
        return $mail;
    }

    /**
     * Checks a given url of availability.
     *
     * @param string $url
     *
     * @return string
     */
    public function checkUrlCommand(string $url): string
    {
        return $url;
    }

    /**
     * Checks a given path of availability.
     *
     * @param mixed $path
     *
     * @return string
     * @throws MissingReference
     */
    public function checkDirCommand(mixed $path): string
    {
        if (false === is_dir($path)) {
            throw new MissingReference('Path <%s> not found!', $path);
        }

        return $path;
    }

    /**
     * Checks a given file of availability.
     *
     * @param mixed $fileName
     *
     * @return string
     * @throws MissingReference
     */
    public function checkFileCommand(mixed $fileName): string
    {
        if (false === is_file($fileName)) {
            throw new MissingReference('Cannot find file <%s>!', $fileName);
        }

        return $fileName;
    }

    /**
     * Checks a given string of emptiness.
     *
     * @param mixed $value
     *
     * @return string
     * @throws InvalidValue
     */
    public function notEmptyCommand(mixed $value): string
    {
        if (true === empty($value)) {
            throw new InvalidValue('The value cannot be empty!');
        }

        return $value;
    }


    /**
     * Checks a class name of availability.
     *
     * @param mixed $className
     *
     * @return string
     * @throws MissingReference
     */
    public function checkClassCommand(mixed $className): string
    {
        if (false === class_exists($className)) {
            throw new MissingReference('Cannot found class <%s>!', $className);
        }

        return $className;
    }
}
