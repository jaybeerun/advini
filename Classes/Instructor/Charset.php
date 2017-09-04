<?php declare(strict_types=1);

namespace JBR\Advini\Instructor {

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
    use JBR\Advini\Exceptions\InvalidValue;
    use JBR\Advini\Interfaces\Converter;
    use JBR\Advini\Interfaces\Instructor;

    /**
     *
     *
     */
    class Charset implements Instructor, Converter
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
        public function canProcessKey($key): bool
        {
            return (true === is_array($key));
        }

        /**
         * @param int|string|array|float $value
         *
         * @return bool
         */
        public function canProcessValue($value): bool
        {
            return false;
        }

        /**
         * @param AdviniAdapter $adapter
         * @param array $configuration
         *
         * @return void
         * @throws InvalidValue
         */
        public function processKey(AdviniAdapter $adapter, array &$configuration): void
        {
            if (true === isset($configuration[self::PROCESS_TOKEN])) {
                $this->fromEncoding = $this->setEncoding($configuration[self::PROCESS_TOKEN]);

                if (null === $this->getEncoding()) {
                    throw new InvalidValue('Cannot convert from <%s> encoding without knowing where to convert!',
                        $this->fromEncoding);
                }

                unset($configuration[self::PROCESS_TOKEN]);
            }

            return null;
        }

        /**
         * @param string $charset
         *
         * @return string
         * @throws InvalidValue
         */
        protected function setEncoding(string $charset): string
        {
            $charsets = array_flip(mb_list_encodings());

            if (false === isset($charsets[$charset])) {
                throw new InvalidValue('Invalid or unknown charset <%s>!', $charset);
            }

            return $charset;
        }

        /**
         * @return string
         */
        public function getEncoding(): string
        {
            return $this->toEncoding;
        }

        /**
         * @param AdviniAdapter $adapter
         * @param string $value
         *
         * @return void
         */
        public function processValue(AdviniAdapter $adapter, string &$value): void
        {

        }

        /**
         * @param AdviniAdapter $adapter
         * @param string $value
         *
         * @return string
         */
        public function convert(AdviniAdapter $adapter, string $value): string
        {
            if (null !== $this->fromEncoding) {
                $value = mb_convert_encoding($value, $this->getEncoding(), $this->fromEncoding);
            }

            return $value;
        }

        /**
         * @return string
         */
        public function getProcessToken(): string
        {
            return self::PROCESS_TOKEN;
        }

        /**
         * @param int|string|array|float $key
         * @param int|string|array|float $value
         *
         * @return bool
         */
        public function canProcessKeyValue($key, $value): bool
        {
            return false;
        }

        /**
         * @param string $key
         * @param string $value
         *
         * @return void
         */
        public function processKeyValue(string $key, string $value): void
        {

        }
    }
}
