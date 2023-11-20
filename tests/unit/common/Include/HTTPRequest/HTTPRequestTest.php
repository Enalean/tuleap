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

final class HTTPRequestTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    use \Tuleap\ForgeConfigSandbox;

    protected function setUp(): void
    {
        $_REQUEST['exists']               = '1';
        $_REQUEST['exists_empty']         = '';
        $_SERVER['server_exists']         = '1';
        $_SERVER['server_quote']          = "l'avion du server";
        $_REQUEST['quote']                = "l'avion";
        $_REQUEST['array']                = ['quote_1' => "l'avion", 'quote_2' => ['quote_3' => "l'oiseau"]];
        $_REQUEST['testkey']              = 'testvalue';
        $_REQUEST['testarray']            = ['key1' => 'valuekey1'];
        $_REQUEST['testkey_array']        = ['testvalue1', 'testvalue2', 'testvalue3'];
        $_REQUEST['testkey_array_empty']  = [];
        $_REQUEST['testkey_array_mixed1'] = ['testvalue', 1, 2];
        $_REQUEST['testkey_array_mixed2'] = [1, 'testvalue', 2];
        $_REQUEST['testkey_array_mixed3'] = [1, 2, 'testvalue'];
        $_FILES['file1']                  = ['name' => 'Test file 1'];
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

    public function testGet(): void
    {
        $r = new HTTPRequest();
        self::assertEquals('1', $r->get('exists'));
        self::assertFalse($r->get('does_not_exist'));
    }

    public function testExist(): void
    {
        $r = new HTTPRequest();
        self::assertTrue($r->exist('exists'));
        self::assertFalse($r->exist('does_not_exist'));
    }

    public function testExistAndNonEmpty(): void
    {
        $r = new HTTPRequest();
        self::assertTrue($r->existAndNonEmpty('exists'));
        self::assertFalse($r->existAndNonEmpty('exists_empty'));
        self::assertFalse($r->existAndNonEmpty('does_not_exist'));
    }

    public function testQuotes(): void
    {
        $r = new HTTPRequest();
        self::assertSame($r->get('quote'), "l'avion");
    }

    public function testServerGet(): void
    {
        $r = new HTTPRequest();
        self::assertEquals('1', $r->getFromServer('server_exists'));
        self::assertFalse($r->getFromServer('does_not_exist'));
    }

    public function testServerQuotes(): void
    {
        $r = new HTTPRequest();
        self::assertSame($r->getFromServer('server_quote'), "l'avion du server");
    }

    public function testSingleton(): void
    {
        $this->assertSame(
            HTTPRequest::instance(),
            HTTPRequest::instance()
        );
        self::assertInstanceOf(HTTPRequest::class, HTTPRequest::instance());
    }

    public function testArray(): void
    {
        $r = new HTTPRequest();
        self::assertSame($r->get('array'), ['quote_1' => "l'avion", 'quote_2' => ['quote_3' => "l'oiseau"]]);
    }

    public function testValidKeyTrue(): void
    {
        $v = $this->createMock(\Rule::class);
        $v->method('isValid')->willReturn(true);
        $r = new HTTPRequest();
        self::assertTrue($r->validKey('testkey', $v));
    }

    public function testValidKeyFalse(): void
    {
        $v = $this->createMock(\Rule::class);
        $v->method('isValid')->willReturn(false);
        $r = new HTTPRequest();
        self::assertFalse($r->validKey('testkey', $v));
    }

    public function testValidKeyScalar(): void
    {
        $v = $this->createMock(\Rule::class);
        $v->expects(self::once())->method('isValid')->with('testvalue');
        $r = new HTTPRequest();
        $r->validKey('testkey', $v);
    }

    public function testValid(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'validate',
            'getKey',
        ]);
        $v->method('validate')->willReturn(true);
        $v->expects(self::once())->method('getKey')->willReturn('testkey');
        $r = new HTTPRequest();
        $r->valid($v);
    }

    public function testValidTrue(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->method('getKey')->willReturn('testkey');
        $v->method('validate')->willReturn(true);
        $r = new HTTPRequest();
        self::assertTrue($r->valid($v));
    }

    public function testValidFalse(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->method('getKey')->willReturn('testkey');
        $v->method('validate')->willReturn(false);
        $r = new HTTPRequest();
        self::assertFalse($r->valid($v));
    }

    public function testValidScalar(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->expects(self::once())->method('getKey')->willReturn('testkey');
        $v->expects(self::once())->method('validate')->with('testvalue');
        $r = new HTTPRequest();
        $r->valid($v);
    }

    public function testValidArray(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'validate',
            'getKey',
        ]);
        $v->method('validate')->willReturn(true);
        $v->expects(self::once())->method('getKey')->willReturn('testkey_array');
        $r = new HTTPRequest();
        $r->validArray($v);
    }

    public function testValidArrayTrue(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->method('getKey')->willReturn('testkey_array');
        $v->method('validate')->willReturn(true);
        $r = new HTTPRequest();
        self::assertTrue($r->validArray($v));
    }

    public function testValidArrayFalse(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->method('getKey')->willReturn('testkey_array');
        $v->method('validate')->willReturn(false);
        $r = new HTTPRequest();
        self::assertFalse($r->validArray($v));
    }

    public function testValidArrayScalar(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->expects(self::once())->method('getKey')->willReturn('testkey_array');
        $v->method('validate')->withConsecutive(
            ['testvalue1'],
            ['testvalue2'],
            ['testvalue3']
        );
        $r = new HTTPRequest();
        $r->validArray($v);
    }

    public function testValidArrayArgNotArray(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
        ]);
        $v->expects(self::once())->method('getKey')->willReturn('testkey');
        $r = new HTTPRequest();
        self::assertFalse($r->validArray($v));
    }

    public function testValidArrayArgEmptyArrayRequired(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->required();
        $v->expects(self::once())->method('getKey')->willReturn('testkey_array_empty');
        $v->expects(self::once())->method('validate')->with(null)->willReturn(false);
        $r = new HTTPRequest();
        self::assertFalse($r->validArray($v));
    }

    public function testValidArrayArgEmptyArrayNotRequired(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'required',
            'getKey',
            'validate',
        ]);
        $v->expects(self::never())->method('required');
        $v->expects(self::once())->method('getKey')->willReturn('testkey_array_empty');
        $v->expects(self::once())->method('validate')->with(null)->willReturn(true);
        $r = new HTTPRequest();
        self::assertTrue($r->validArray($v));
    }

    public function testValidArrayArgNotEmptyArrayRequired(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->expects(self::once())->method('getKey')->willReturn('testkey_array');
        $v->method('validate');
        $v->required();
        $r = new HTTPRequest();
        self::assertFalse($r->validArray($v));
    }

    public function testValidArrayFirstArgFalse(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
        ]);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expects(self::once())->method('getKey')->willReturn('testkey_array_mixed1');
        $r = new HTTPRequest();
        self::assertFalse($r->validArray($v));
    }

    public function testValidArrayMiddleArgFalse(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
        ]);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expects(self::once())->method('getKey')->willReturn('testkey_array_mixed2');
        $r = new HTTPRequest();
        self::assertFalse($r->validArray($v));
    }

    public function testValidArrayLastArgFalse(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
        ]);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expects(self::once())->method('getKey')->willReturn('testkey_array_mixed3');
        $r = new HTTPRequest();
        self::assertFalse($r->validArray($v));
    }

    public function testValidInArray(): void
    {
        $v = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v->expects(self::once())->method('getKey')->willReturn('key1');
        $v->expects(self::once())->method('validate')->with('valuekey1');
        $r = new HTTPRequest();
        $r->validInArray('testarray', $v);
    }

    public function testValidFileNoFileValidator(): void
    {
        $v = $this->createPartialMock(\Valid::class, []);
        $r = new HTTPRequest();
        self::assertFalse($r->validFile($v));
    }

    public function testValidFileOk(): void
    {
        $v = $this->createPartialMock(\Valid_File::class, [
            'getKey',
            'validate',
        ]);
        $v->expects(self::once())->method('getKey')->willReturn('file1');
        $v->expects(self::once())->method('validate')->with(['file1' => ['name' => 'Test file 1']], 'file1');
        $r = new HTTPRequest();
        $r->validFile($v);
    }

    public function testGetValidated(): void
    {
        $v1 = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v1->method('getKey')->willReturn('testkey');
        $v1->method('validate')->willReturn(true);

        $v2 = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v2->method('getKey')->willReturn('testkey');
        $v2->method('validate')->willReturn(false);

        $v3 = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v3->method('getKey')->willReturn('does_not_exist');
        $v3->method('validate')->willReturn(false);

        $v4 = $this->createPartialMock(\Valid::class, [
            'getKey',
            'validate',
        ]);
        $v4->method('getKey')->willReturn('does_not_exist');
        $v4->method('validate')->willReturn(true);

        $r = new HTTPRequest();
        //If valid, should return the submitted value...
        self::assertEquals('testvalue', $r->getValidated('testkey', $v1));
        //...even if there is a defult value!
        self::assertEquals('testvalue', $r->getValidated('testkey', $v1, 'default value'));
        //If not valid, should return the default value...
        self::assertEquals('default value', $r->getValidated('testkey', $v2, 'default value'));
        //...or null if there is no default value!
        self::assertNull($r->getValidated('testkey', $v2));
        //If the variable is not submitted, there is no incidence, the result depends on the validator...
        self::assertEquals('default value', $r->getValidated('does_not_exist', $v3, 'default value'));
        self::assertFalse($r->getValidated('does_not_exist', $v4, 'default value'));

        //Not really in the "unit" test spirit
        //(create dynamically a new instance of a validator inside the function. Should be mocked)
        self::assertEquals('testvalue', $r->getValidated('testkey', 'string', 'default value'));
        self::assertEquals('default value', $r->getValidated('testkey', 'uint', 'default value'));
    }

    public function testGetServerUrl(): void
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        $request = new HTTPRequest();
        self::assertEquals('https://example.com', $request->getServerUrl());
    }
}
