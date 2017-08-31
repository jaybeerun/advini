<?php

/************************************************************************************
 * Copyright (c) 2016, Jan Runte
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without modification,
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
use JBR\Advini\Instructor\Constant;
use JBR\Advini\Instructor\Import;
use JBR\Advini\Setter\DefaultCommands;
use PHPUnit\Framework\TestCase;

/**
 *
 */
class AdviniTest extends TestCase
{

    /**
     * @var string
     */
    protected $result = 'a:1:{s:8:"category";a:1:{s:11:"subcategory";a:1:{s:4:"key1";a:1:{s:4:"key2";s:5:"value";}}}}';

    /**
     * @var string
     */
    protected $resultWithMethod = 'a:1:{s:8:"category";a:1:{s:11:"subcategory";a:1:{s:4:"key1";a:1:{s:4:"key2";s:32:"2063c1608d6e0baf80249c42e2be5804";}}}}';

    /**
     * @var string
     */
    protected $resultWithKeyConstant = 'a:1:{s:8:"category";a:1:{s:16:"subcategory_temp";a:1:{s:4:"key1";a:1:{s:4:"key2";s:5:"value";}}}}';

    /**
     * @test
     */
    public function simpleFile(): void
    {
        $iniFile = new Advini();
        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simple.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     * @test
     */
    public function simpleFileWithImport(): void
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new Import());

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImport.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     * @test
     */
    public function simpleFileWithBasedOnImport(): void
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new Import());

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleBasedOnImport.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     * @test
     */
    public function simpleImportFileWithConstants(): void
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new Import());

        /** @var Constant $const */
        $const = new Constant();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImportForConstants.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     * @test
     */
    public function simpleFileWithConstants(): void
    {
        $iniFile = new Advini();

        /** @var Constant $const */
        $const = new Constant();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleForConstants.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     * @test
     */
    public function simpleFileWithConstantsAndMethods(): void
    {
        $iniFile = new Advini(new DefaultCommands());

        /** @var Constant $const */
        $const = new Constant();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleForConstantsWithMethod.ini');
        $this->assertEquals($this->resultWithMethod, serialize($configuration));
    }

    /**
     * @test
     */
    public function simpleImportFileWithConstantsAndMethods(): void
    {
        $iniFile = new Advini(new DefaultCommands());
        $iniFile->addInstructor(new Import());

        /** @var Constant $const */
        $const = new Constant();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImportForConstantsWithMethod.ini');
        $this->assertEquals($this->resultWithMethod, serialize($configuration));
    }

    /**
     * @test
     */
    public function simpleImportAsKeyFileWithConstantsAndMethods(): void
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new Import());

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImportAsKey.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     * @test
     */
    public function simpleImportAsKeyFileWithConstantsAndMethodsAndDynamicKey(): void
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new Import());

        /** @var Constant $const */
        $const = new Constant();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImportForConstantsAndDynamicKey.ini');
        $this->assertEquals($this->resultWithKeyConstant, serialize($configuration));
    }

    /**
     * @test
     * @expectedException \JBR\Advini\Exceptions\MissingReference
     * @expectedExceptionMessageRegExp /^Cannot find class <NotValid>/
     */
    public function invalidInstructorException(): void
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new Import(), 'NotValid');
    }

    /**
     * @test
     * @expectedException \JBR\Advini\Exceptions\InvalidValue
     * @expectedExceptionMessageRegExp /can process the token value <@import>$/
     */
    public function collisionInstructorException(): void
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new Import());
        $iniFile->addInstructor(new Import(), Constant::class);
    }

    /**
     * @test
     * @expectedException \JBR\Advini\Exceptions\MissingReference
     * @expectedExceptionMessageRegExp /^Cannot found method <not_exists>/
     */
    public function notFoundMethodsException(): void
    {
        $iniFile = new Advini();
        $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/failedSimple.ini');
    }

    /**
     * @test
     * @expectedException \JBR\Advini\Exceptions\MissingReference
     * @expectedExceptionMessageRegExp /Cannot find method <not_exists>!$/
     */
    public function notFoundMethodsForWrapperException(): void
    {
        $iniFile = new Advini(new DefaultCommands());
        $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/failedSimple.ini');
    }
}
