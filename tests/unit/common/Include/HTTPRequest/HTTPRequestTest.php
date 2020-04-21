<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

final class HTTPRequestTest extends \PHPUnit\Framework\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    protected function setUp(): void
    {
        $_REQUEST['exists'] = '1';
        $_REQUEST['exists_empty'] = '';
        $_SERVER['server_exists'] = '1';
        $_SERVER['server_quote'] = "l'avion du server";
        $_REQUEST['quote'] = "l'avion";
        $_REQUEST['array'] = array('quote_1' => "l'avion", 'quote_2' => array('quote_3' => "l'oiseau"));
        $_REQUEST['testkey'] = 'testvalue';
        $_REQUEST['testarray'] = array('key1' => 'valuekey1');
        $_REQUEST['testkey_array'] = array('testvalue1', 'testvalue2', 'testvalue3');
        $_REQUEST['testkey_array_empty'] = array();
        $_REQUEST['testkey_array_mixed1'] = array('testvalue',1, 2);
        $_REQUEST['testkey_array_mixed2'] = array(1, 'testvalue', 2);
        $_REQUEST['testkey_array_mixed3'] = array(1, 2, 'testvalue');
        $_FILES['file1'] = array('name' => 'Test file 1');
    }

    protected function tearDown(): void
    {
        unset($_REQUEST['exists']);
        unset($_REQUEST['exists_empty']);
        unset($_SERVER['server_exists']);
        unset($_SERVER['server_quote']);
        unset($_REQUEST['quote']);
        unset($_REQUEST['array']);
        unset($_REQUEST['testkey']);
        unset($_REQUEST['testarray']);
        unset($_REQUEST['testkey_array']);
        unset($_REQUEST['testkey_array_empty']);
        unset($_REQUEST['testkey_array_mixed1']);
        unset($_REQUEST['testkey_array_mixed2']);
        unset($_REQUEST['testkey_array_mixed3']);
        unset($_FILES['file1']);
    }

    public function testGet()
    {
        $r = new HTTPRequest();
        $this->assertEquals('1', $r->get('exists'));
        $this->assertFalse($r->get('does_not_exist'));
    }

    public function testExist()
    {
        $r = new HTTPRequest();
        $this->assertTrue($r->exist('exists'));
        $this->assertFalse($r->exist('does_not_exist'));
    }

    public function testExistAndNonEmpty()
    {
        $r = new HTTPRequest();
        $this->assertTrue($r->existAndNonEmpty('exists'));
        $this->assertFalse($r->existAndNonEmpty('exists_empty'));
        $this->assertFalse($r->existAndNonEmpty('does_not_exist'));
    }

    public function testQuotes()
    {
        $r = new HTTPRequest();
        $this->assertSame($r->get('quote'), "l'avion");
    }

    public function testServerGet()
    {
        $r = new HTTPRequest();
        $this->assertEquals('1', $r->getFromServer('server_exists'));
        $this->assertFalse($r->getFromServer('does_not_exist'));
    }

    public function testServerQuotes()
    {
        $r = new HTTPRequest();
        $this->assertSame($r->getFromServer('server_quote'), "l'avion du server");
    }

    public function testSingleton()
    {
        $this->assertSame(
            HTTPRequest::instance(),
            HTTPRequest::instance()
        );
        $this->assertInstanceOf(HTTPRequest::class, HTTPRequest::instance());
    }

    public function testArray()
    {
        $r = new HTTPRequest();
        $this->assertSame($r->get('array'), array('quote_1' => "l'avion", 'quote_2' => array('quote_3' => "l'oiseau")));
    }

    public function testValidKeyTrue()
    {
        $v = \Mockery::spy(\Rule::class);
        $v->shouldReceive('isValid')->andReturns(true);
        $r = new HTTPRequest();
        $this->assertTrue($r->validKey('testkey', $v));
    }

    public function testValidKeyFalse()
    {
        $v = \Mockery::spy(\Rule::class);
        $v->shouldReceive('isValid')->andReturns(false);
        $r = new HTTPRequest();
        $this->assertFalse($r->validKey('testkey', $v));
    }

    public function testValidKeyScalar()
    {
        $v = \Mockery::spy(\Rule::class);
        $v->shouldReceive('isValid')->with('testvalue')->once();
        $r = new HTTPRequest();
        $r->validKey('testkey', $v);
    }

    public function testValid()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('validate')->andReturns(true);
        $v->shouldReceive('getKey')->once()->andReturns('testkey');
        $r = new HTTPRequest();
        $r->valid($v);
    }

    public function testValidTrue()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->andReturns('testkey');
        $v->shouldReceive('validate')->andReturns(true);
        $r = new HTTPRequest();
        $this->assertTrue($r->valid($v));
    }

    public function testValidFalse()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->andReturns('testkey');
        $v->shouldReceive('validate')->andReturns(false);
        $r = new HTTPRequest();
        $this->assertFalse($r->valid($v));
    }

    public function testValidScalar()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->once()->andReturns('testkey');
        $v->shouldReceive('validate')->with('testvalue')->once();
        $r = new HTTPRequest();
        $r->valid($v);
    }

    public function testValidArray()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('validate')->andReturns(true);
        $v->shouldReceive('getKey')->once()->andReturns('testkey_array');
        $r = new HTTPRequest();
        $r->validArray($v);
    }

    public function testValidArrayTrue()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->andReturns('testkey_array');
        $v->shouldReceive('validate')->andReturns(true);
        $r = new HTTPRequest();
        $this->assertTrue($r->validArray($v));
    }

    public function testValidArrayFalse()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->andReturns('testkey_array');
        $v->shouldReceive('validate')->andReturns(false);
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    public function testValidArrayScalar()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->once()->andReturns('testkey_array');
        $v->shouldReceive('validate')->with('testvalue1')->ordered();
        $v->shouldReceive('validate')->with('testvalue2')->ordered();
        $v->shouldReceive('validate')->with('testvalue3')->ordered();
        $r = new HTTPRequest();
        $r->validArray($v);
    }

    public function testValidArrayArgNotArray()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->once()->andReturns('testkey');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    public function testValidArrayArgEmptyArrayRequired()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->required();
        $v->shouldReceive('getKey')->once()->andReturns('testkey_array_empty');
        $v->shouldReceive('validate')->with(null)->andReturns(false)->once();
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    public function testValidArrayArgEmptyArrayNotRequired()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('required')->never();
        $v->shouldReceive('getKey')->once()->andReturns('testkey_array_empty');
        $v->shouldReceive('validate')->with(null)->andReturns(true)->once();
        $r = new HTTPRequest();
        $this->assertTrue($r->validArray($v));
    }

    public function testValidArrayArgNotEmptyArrayRequired()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->once()->andReturns('testkey_array');
        $v->shouldReceive('validate');
        $v->required();
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    public function testValidArrayFirstArgFalse()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->shouldReceive('getKey')->once()->andReturns('testkey_array_mixed1');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    public function testValidArrayMiddleArgFalse()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->shouldReceive('getKey')->once()->andReturns('testkey_array_mixed2');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    public function testValidArrayLastArgFalse()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->shouldReceive('getKey')->once()->andReturns('testkey_array_mixed3');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    public function testValidInArray()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->once()->andReturns('key1');
        $v->shouldReceive('validate')->with('valuekey1')->once();
        $r = new HTTPRequest();
        $r->validInArray('testarray', $v);
    }

    public function testValidFileNoFileValidator()
    {
        $v = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $r = new HTTPRequest();
        $this->assertFalse($r->validFile($v));
    }

    public function testValidFileOk()
    {
        $v = \Mockery::mock(\Valid_File::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v->shouldReceive('getKey')->times(2)->andReturns('file1');
        $v->shouldReceive('validate')->with(array('file1' => array('name' => 'Test file 1')), 'file1')->once();
        $r = new HTTPRequest();
        $r->validFile($v);
    }

    public function testGetValidated()
    {
        $v1 = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v1->shouldReceive('getKey')->andReturns('testkey');
        $v1->shouldReceive('validate')->andReturns(true);

        $v2 = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v2->shouldReceive('getKey')->andReturns('testkey');
        $v2->shouldReceive('validate')->andReturns(false);

        $v3 = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v3->shouldReceive('getKey')->andReturns('does_not_exist');
        $v3->shouldReceive('validate')->andReturns(false);

        $v4 = \Mockery::mock(\Valid::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $v4->shouldReceive('getKey')->andReturns('does_not_exist');
        $v4->shouldReceive('validate')->andReturns(true);

        $r = new HTTPRequest();
        //If valid, should return the submitted value...
        $this->assertEquals('testvalue', $r->getValidated('testkey', $v1));
        //...even if there is a defult value!
        $this->assertEquals('testvalue', $r->getValidated('testkey', $v1, 'default value'));
        //If not valid, should return the default value...
        $this->assertEquals('default value', $r->getValidated('testkey', $v2, 'default value'));
        //...or null if there is no default value!
        $this->assertNull($r->getValidated('testkey', $v2));
        //If the variable is not submitted, there is no incidence, the result depends on the validator...
        $this->assertEquals('default value', $r->getValidated('does_not_exist', $v3, 'default value'));
        $this->assertFalse($r->getValidated('does_not_exist', $v4, 'default value'));

        //Not really in the "unit" test spirit
        //(create dynamically a new instance of a validator inside the function. Should be mocked)
        $this->assertEquals('testvalue', $r->getValidated('testkey', 'string', 'default value'));
        $this->assertEquals('default value', $r->getValidated('testkey', 'uint', 'default value'));
    }
}
