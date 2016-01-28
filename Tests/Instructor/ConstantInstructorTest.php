<?php
use JBR\Advini\Advini;
use JBR\Advini\AdviniAdapter;
use JBR\Advini\Instructor\ConstantInstructor;

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
class ConstantInstructorTest extends PHPUnit_Framework_TestCase{

	const SERIALIZE_KEYS = 'a:2:{s:10:"all_things";s:5:"space";s:11:"are_awesome";s:3:"ace";}';

	/**
	 *
	 */
	public function testProcessValues() {
		$const = new ConstantInstructor();
		$const->setConstants([
			'this' => 'all',
			'ugly' => 'awesome'
		]);

		$value = '<this>> is <ugly>>';
		$this->assertEquals(false, $const->canProcessValue($value));

		$value = '<<this>> is <<ugly>>';
		$this->assertEquals(true, $const->canProcessValue($value));

		$advini = new Advini();
		$adapter = new AdviniAdapter($advini);

		$const->processValue($adapter, $value);
		$this->assertEquals('all is awesome', $value);
	}

	/**
	 *
	 */
	public function testProcessKey() {
		$const = new ConstantInstructor();
		$const->setConstants([
			'this' => 'all',
			'ugly' => 'awesome'
		]);

		$keys = [
			'<this>>_things' => 'space',
			'are_<ugly>>' => 'ace'
		];
		// yes, its true because its simply an array
		$this->assertEquals(true, $const->canProcessKey($keys));

		$keys = [
			'<<this>>_things' => 'space',
			'are_<<ugly>>' => 'ace'
		];
		$this->assertEquals(true, $const->canProcessKey($keys));

		$advini = new Advini();
		$adapter = new AdviniAdapter($advini);

		$const->processKey($adapter, $keys);
		$hash = serialize($keys);
		$this->assertEquals($hash, self::SERIALIZE_KEYS);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Cannot parse instructor for <<<this> is <<ugly>
	 */
	public function testIncompletedConstantInValueException() {
		$advini = new Advini();
		$adapter = new AdviniAdapter($advini);

		$const = new ConstantInstructor();
		$const->setConstants([
			'this' => 'all',
			'ugly' => 'awesome'
		]);

		$value = '<<this> is <<ugly>';
		$const->processValue($adapter, $value);
	}

	/**
	 * @expectedException Exception
	 * @expectedExceptionMessage Cannot parse instructor for <<<this>_things>
	 */
	public function testIncompletedConstantInKeyException() {
		$advini = new Advini();
		$adapter = new AdviniAdapter($advini);

		$const = new ConstantInstructor();
		$const->setConstants([
			'this' => 'all',
			'ugly' => 'awesome'
		]);

		$keys = [
			'<<this>_things' => 'space',
			'are_<<ugly>' => 'ace'
		];

		$const->processKey($adapter, $keys);
	}
}