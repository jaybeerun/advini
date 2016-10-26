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

use JBR\Advini\AdviniAdapter;
use JBR\Advini\Interfaces\InstructorInterface;

/**
 *
 *
 */
class ImportInstructor implements InstructorInterface
{
    const PROCESS_TOKEN = '@import';

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
        return (
            (true === is_string($value))
            && (self::PROCESS_TOKEN === substr($value, 0, strlen(self::PROCESS_TOKEN)))
        );
    }

    /**
     * @param AdviniAdapter $adapter
     * @param string $value
     *
     * @return void
     */
    public function processValue(AdviniAdapter $adapter, &$value)
    {
        $matches = $adapter->matchValue($value, self::PROCESS_TOKEN, '\\[( *[^\\[\\]]+ *)\\]');
        $value = $this->importFromFile($adapter, trim($matches[1]));
    }

    /**
     * @param AdviniAdapter $adapter
     * @param string $file
     *
     * @return array
     */
    protected function importFromFile(AdviniAdapter $adapter, $file)
    {
        $importFile = sprintf('%s/%s', $adapter->getCwd(), $file);

        return $adapter->getFromFile($importFile);
    }

    /**
     * @param AdviniAdapter $adapter
     * @param array $configuration
     *
     * @return string
     */
    public function processKey(AdviniAdapter $adapter, array &$configuration)
    {
        if (true === isset($configuration[self::PROCESS_TOKEN])) {
            $basedOnConfiguration = $this->importFromFile($adapter, $configuration[self::PROCESS_TOKEN]);
            unset($configuration[self::PROCESS_TOKEN]);
            $configuration = array_merge($basedOnConfiguration, $configuration);
        }

        return null;
    }

    /**
     * @return string
     */
    public function getProcessToken()
    {
        return self::PROCESS_TOKEN;
    }
}
