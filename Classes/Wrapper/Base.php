<?php namespace JBR\Advini\Wrapper;

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

/**
 *
 */
class Base extends AbstractWrapper
{
    /**
     * @param mixed $value
     *
     * @return string
     */
    public function stringCommand($value)
    {
        return (string)$value;
    }

    /**
     * @param mixed $value
     *
     * @return string
     * @throws Exception
     */
    public function octdecCommand($value)
    {
        return octdec($value);
    }

    /**
     * @param mixed $value
     *
     * @return string
     * @throws Exception
     */
    public function jsonCommand($value)
    {
        $json = json_decode($value);

        if (NULL === $json) {
            throw new Exception('Cannot decode json content!');
        }

        return json_encode($json);
    }

    /**
     * @param $value
     * @return array
     */
    public function arrayCommand($value)
    {
        if ((false === is_array($value)) && (true === empty($value))) {
            $value = [];
        } else {
            $value = [$value];
        }

        return $value;
    }

    /**
     * @param mixed $value
     *
     * @return string
     * @throws Exception
     */
    public function serializeCommand($value)
    {
        $result = serialize($value);
        if (false === $result) {
            throw new Exception('Cannot serialize value!');
        }

        return $result;
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    public function isIntegerCommand($value)
    {

        return floatval($value);
    }

    /**
     * Checks a given mail address of availability.
     *
     * @param string $mail
     *
     * @return string
     */
    public function checkMailCommand($mail)
    {
        return $mail;
    }

    /**
     * Checks a given url of availability.
     *
     * @param string $url
     *
     * @return string
     * @throws Exception
     */
    public function checkUrlCommand($url)
    {
        return $url;
    }

    /**
     * Checks a given path of availability.
     *
     * @param string $path
     *
     * @return string
     * @throws Exception
     */
    public function checkDirCommand($path)
    {
        if (false === is_dir($path)) {
            throw new Exception(sprintf('Path <%s> not found!', $path));
        }

        return $path;
    }

    /**
     * Checks a given file of availability.
     *
     * @param string $fileName
     *
     * @return string
     * @throws Exception
     */
    public function checkFileCommand($fileName)
    {
        if (false === is_file($fileName)) {
            throw new Exception(sprintf('Cannot find file <%s>!', $fileName));
        }

        return $fileName;
    }

    /**
     * Checks a given string of emptiness.
     *
     * @param string $value
     *
     * @return string
     * @throws Exception
     */
    public function notEmptyCommand($value)
    {
        if (true === empty($value)) {
            throw new Exception('The value cannot be empty!');
        }

        return $value;
    }


    /**
     * Checks a class name of availability.
     *
     * @param string $className
     *
     * @return string
     * @throws Exception
     */
    public function checkClassCommand($className)
    {
        if (false === class_exists($className)) {
            throw new Exception(sprintf('Cannot found class <%s>!', $className));
        }

        return $className;
    }
}
