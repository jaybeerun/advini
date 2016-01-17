<?php

/************************************************************************************
 * Copyright (c) 2016, Jan Runte
 * All rights reserved.
 *
 * Redistributionv and use in source and binary forms, with or without modification,
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
use JBR\Advini\Wrapper\Base;

/**
 * Class AdviniTest
 */
class AdviniTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var string
	 */
	protected $result = 'a:1:{s:8:"category";a:1:{s:11:"subcategory";a:1:{s:4:"key1";a:1:{s:4:"key2";s:5:"value";}}}}';

	/**
	 * @var string
	 */
	protected $resultWithMethod = 'a:1:{s:8:"category";a:1:{s:11:"subcategory";a:1:{s:4:"key1";a:1:{s:4:"key2";s:32:"2063c1608d6e0baf80249c42e2be5804";}}}}';

	/**
	 *
	 */
	public function testSimpleFile() {
		$iniFile = new Advini();
		$configuration = $iniFile->getFromFile(__DIR__ . '/res/simple.ini');
		$this->assertEquals($this->result, serialize($configuration));
	}

	/**
	 *
	 */
	public function testSimpleFileWithImport() {
		$iniFile = new Advini();
		$configuration = $iniFile->getFromFile(__DIR__ . '/res/simpleImport.ini');
		$this->assertEquals($this->result, serialize($configuration));
	}

	/**
	 *
	 */
	public function testSimpleImportFileWithConstants() {
		$iniFile = new Advini();
		$iniFile->setConstantsFromFile(__DIR__ . '/res/constants.ini');
		$configuration = $iniFile->getFromFile(__DIR__ . '/res/simpleImportForConstants.ini');
		$this->assertEquals($this->result, serialize($configuration));
	}

	/**
	 *
	 */
	public function testSimpleFileWithConstants() {
		$iniFile = new Advini();
		$iniFile->setConstantsFromFile(__DIR__ . '/res/constants.ini');
		$configuration = $iniFile->getFromFile(__DIR__ . '/res/simpleForConstants.ini');
		$this->assertEquals($this->result, serialize($configuration));
	}

	/**
	 *
	 */
	public function testSimpleFileWithConstantsAndMethods() {
		$iniFile = new Advini(new Base());
		$iniFile->setConstantsFromFile(__DIR__ . '/res/constants.ini');
		$configuration = $iniFile->getFromFile(__DIR__ . '/res/simpleForConstantsWithMethod.ini');
		$this->assertEquals($this->resultWithMethod, serialize($configuration));
	}

	/**
	 *
	 */
	public function testSimpleImportFileWithConstantsAndMethods() {
		$iniFile = new Advini(new Base());
		$iniFile->setConstantsFromFile(__DIR__ . '/res/constants.ini');
		$configuration = $iniFile->getFromFile(__DIR__ . '/res/simpleImportForConstantsWithMethod.ini');
		$this->assertEquals($this->resultWithMethod, serialize($configuration));
	}
}