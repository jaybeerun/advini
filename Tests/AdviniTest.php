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
use JBR\Advini\Instructor\ConstantInstructor;
use JBR\Advini\Instructor\ImportInstructor;
use JBR\Advini\Statements\ConstantStatement;
use JBR\Advini\Statements\ImportStatement;
use JBR\Advini\Wrapper\Base;

/**
 * Class AdviniTest
 */
class AdviniTest extends PHPUnit_Framework_TestCase
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
     *
     */
    public function testSimpleFile()
    {
        $iniFile = new Advini();
        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simple.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     *
     */
    public function testSimpleFileWithImport()
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new ImportInstructor());

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImport.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     *
     */
    public function testSimpleImportFileWithConstants()
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new ImportInstructor());

        /** @var ConstantInstructor $const */
        $const = new ConstantInstructor();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImportForConstants.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     *
     */
    public function testSimpleFileWithConstants()
    {
        $iniFile = new Advini();

        /** @var ConstantInstructor $const */
        $const = new ConstantInstructor();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleForConstants.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     *
     */
    public function testSimpleFileWithConstantsAndMethods()
    {
        $iniFile = new Advini(new Base());

        /** @var ConstantInstructor $const */
        $const = new ConstantInstructor();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleForConstantsWithMethod.ini');
        $this->assertEquals($this->resultWithMethod, serialize($configuration));
    }

    /**
     *
     */
    public function testSimpleImportFileWithConstantsAndMethods()
    {
        $iniFile = new Advini(new Base());
        $iniFile->addInstructor(new ImportInstructor());

        /** @var ConstantInstructor $const */
        $const = new ConstantInstructor();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImportForConstantsWithMethod.ini');
        $this->assertEquals($this->resultWithMethod, serialize($configuration));
    }

    /**
     *
     */
    public function testSimpleImportAsKeyFileWithConstantsAndMethods()
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new ImportInstructor());

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImportAsKey.ini');
        $this->assertEquals($this->result, serialize($configuration));
    }

    /**
     *
     */
    public function testSimpleImportAsKeyFileWithConstantsAndMethodsAndDynamicKey()
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new ImportInstructor());

        /** @var ConstantInstructor $const */
        $const = new ConstantInstructor();
        $const->setConstantsFromFile(__DIR__ . '/../Resources/Tests/constants.ini');
        $iniFile->addInstructor($const);

        $configuration = $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/simpleImportForConstantsAndDynamicKey.ini');
        $this->assertEquals($this->resultWithKeyConstant, serialize($configuration));
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /^Cannot find class <NotValid>/
     */
    public function testInvalidInstructorException()
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new ImportInstructor(), 'NotValid');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /can process the token value <@import>$/
     */
    public function testCollisionInstructorException()
    {
        $iniFile = new Advini();
        $iniFile->addInstructor(new ImportInstructor());
        $iniFile->addInstructor(new ImportInstructor(), ConstantInstructor::class);
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /^Cannot found method <not_exists>/
     */
    public function testNotFoundMethodsException()
    {
        $iniFile = new Advini();
        $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/failedSimple.ini');
    }

    /**
     * @expectedException Exception
     * @expectedExceptionMessageRegExp /Cannot find method <not_exists>!$/
     */
    public function testNotFoundMethodsForWrapperException()
    {
        $iniFile = new Advini(new Base());
        $iniFile->getFromFile(__DIR__ . '/../Resources/Tests/failedSimple.ini');
    }
}