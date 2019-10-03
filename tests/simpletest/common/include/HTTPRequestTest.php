<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 */

Mock::generatePartial('Valid', 'MockValid', array('isValid', 'getKey', 'validate', 'required'));
Mock::generate('Rule');
Mock::generatePartial('Valid_File', 'Valid_FileTest', array('getKey', 'validate'));

class HTTPRequestTest extends TuleapTestCase
{

    public function setUp()
    {
        parent::setUp();
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

    public function tearDown()
    {
        unset($_REQUEST['exists']);
        unset($_REQUEST['quote']);
        unset($_REQUEST['exists_empty']);
        unset($_SERVER['server_exists']);
        unset($_SERVER['server_quote']);
        unset($_REQUEST['testkey']);
        unset($_REQUEST['testarray']);
        unset($_REQUEST['testkey_array']);
        unset($_REQUEST['testkey_array_empty']);
        unset($_REQUEST['testkey_array_mixed1']);
        unset($_REQUEST['testkey_array_mixed2']);
        unset($_REQUEST['testkey_array_mixed3']);
        unset($_FILES['file1']);
        parent::tearDown();
    }

    function testGet()
    {
        $r = new HTTPRequest();
        $this->assertEqual($r->get('exists'), '1');
        $this->assertFalse($r->get('does_not_exist'));
    }

    function testExist()
    {
        $r = new HTTPRequest();
        $this->assertTrue($r->exist('exists'));
        $this->assertFalse($r->exist('does_not_exist'));
    }

    function testExistAndNonEmpty()
    {
        $r = new HTTPRequest();
        $this->assertTrue($r->existAndNonEmpty('exists'));
        $this->assertFalse($r->existAndNonEmpty('exists_empty'));
        $this->assertFalse($r->existAndNonEmpty('does_not_exist'));
    }

    function testQuotes()
    {
        $r = new HTTPRequest();
        $this->assertIdentical($r->get('quote'), "l'avion");
    }

    function testServerGet()
    {
        $r = new HTTPRequest();
        $this->assertEqual($r->getFromServer('server_exists'), '1');
        $this->assertFalse($r->getFromServer('does_not_exist'));
    }

    function testServerQuotes()
    {
        $r = new HTTPRequest();
        $this->assertIdentical($r->getFromServer('server_quote'), "l'avion du server");
    }

    function testSingleton()
    {
        $this->assertEqual(
            HTTPRequest::instance(),
            HTTPRequest::instance()
        );
        $this->assertIsA(HTTPRequest::instance(), 'HTTPRequest');
    }

    function testArray()
    {
        $r = new HTTPRequest();
        $this->assertIdentical($r->get('array'), array('quote_1' => "l'avion", 'quote_2' => array('quote_3' => "l'oiseau")));
    }

    function testValidKeyTrue()
    {
        $v = new MockRule($this);
        $v->setReturnValue('isValid', true);
        $r = new HTTPRequest();
        $this->assertTrue($r->validKey('testkey', $v));
    }

    function testValidKeyFalse()
    {
        $v = new MockRule($this);
        $v->setReturnValue('isValid', false);
        $r = new HTTPRequest();
        $this->assertFalse($r->validKey('testkey', $v));
    }

    function testValidKeyScalar()
    {
        $v = new MockRule($this);
        $v->expectOnce('isValid', array('testvalue'));
        $r = new HTTPRequest();
        $r->validKey('testkey', $v);
    }

    function testValid()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->setReturnValue('validate', true);
        $v->expectAtLeastOnce('getKey');
        $r = new HTTPRequest();
        $r->valid($v);
    }

    function testValidTrue()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->setReturnValue('validate', true);
        $r = new HTTPRequest();
        $this->assertTrue($r->valid($v));
    }

    function testValidFalse()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->setReturnValue('validate', false);
        $r = new HTTPRequest();
        $this->assertFalse($r->valid($v));
    }

    function testValidScalar()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->expectAtLeastOnce('getKey');
        $v->expectOnce('validate', array('testvalue'));
        $r = new HTTPRequest();
        $r->valid($v);
    }

    function testValidArray()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey_array');
        $v->setReturnValue('validate', true);
        $v->expectAtLeastOnce('getKey');
        $r = new HTTPRequest();
        $r->validArray($v);
    }

    function testValidArrayTrue()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey_array');
        $v->setReturnValue('validate', true);
        $r = new HTTPRequest();
        $this->assertTrue($r->validArray($v));
    }

    function testValidArrayFalse()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey_array');
        $v->setReturnValue('validate', false);
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayScalar()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey_array');
        $v->expectAtLeastOnce('getKey');
        $v->expectAt(0, 'validate', array('testvalue1'));
        $v->expectAt(1, 'validate', array('testvalue2'));
        $v->expectAt(2, 'validate', array('testvalue3'));
        $v->expectCallCount('validate', 3);
        $r = new HTTPRequest();
        $r->validArray($v);
    }

    function testValidArrayArgNotArray()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'testkey');
        $v->expectAtLeastOnce('getKey');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayArgEmptyArrayRequired()
    {
        $v = new MockValid($this);
        $v->required();
        $v->expectAtLeastOnce('required');
        $v->setReturnValue('getKey', 'testkey_array_empty');
        $v->expectAtLeastOnce('getKey');
        $v->setReturnValue('validate', false, array(null));
        $v->expectAtLeastOnce('validate', array(null));
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayArgEmptyArrayNotRequired()
    {
        $v = new MockValid($this);
        $v->expectNever('required');
        $v->setReturnValue('getKey', 'testkey_array_empty');
        $v->expectAtLeastOnce('getKey');
        $v->setReturnValue('validate', true, array(null));
        $v->expectAtLeastOnce('validate', array(null));
        $r = new HTTPRequest();
        $this->assertTrue($r->validArray($v));
    }

    function testValidArrayArgNotEmptyArrayRequired()
    {
        $v = new MockValid($this);
        $v->expectAtLeastOnce('required');
        $v->required();
        $v->setReturnValue('getKey', 'testkey_array');
        $v->expectAtLeastOnce('getKey');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayFirstArgFalse()
    {
        $v = new MockValid($this);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expectAtLeastOnce('required');
        $v->setReturnValue('getKey', 'testkey_array_mixed1');
        $v->expectAtLeastOnce('getKey');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayMiddleArgFalse()
    {
        $v = new MockValid($this);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expectAtLeastOnce('required');
        $v->setReturnValue('getKey', 'testkey_array_mixed2');
        $v->expectAtLeastOnce('getKey');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidArrayLastArgFalse()
    {
        $v = new MockValid($this);
        $v->addRule(new Rule_Int());
        $v->addRule(new Rule_GreaterOrEqual(0));
        $v->required();
        $v->expectAtLeastOnce('required');
        $v->setReturnValue('getKey', 'testkey_array_mixed3');
        $v->expectAtLeastOnce('getKey');
        $r = new HTTPRequest();
        $this->assertFalse($r->validArray($v));
    }

    function testValidInArray()
    {
        $v = new MockValid($this);
        $v->setReturnValue('getKey', 'key1');
        $v->expectAtLeastOnce('getKey');
        $v->expectOnce('validate', array('valuekey1'));
        $r = new HTTPRequest();
        $r->validInArray('testarray', $v);
    }

    function testValidFileNoFileValidator()
    {
        $v = new MockValid($this);
        $r = new HTTPRequest();
        $this->assertFalse($r->validFile($v));
    }

    function testValidFileOk()
    {
        $v = new Valid_FileTest($this);
        $v->setReturnValue('getKey', 'file1');
        $v->expectAtLeastOnce('getKey');
        $v->expectOnce('validate', array(array('file1' => array('name' => 'Test file 1')), 'file1'));
        $r = new HTTPRequest();
        $r->validFile($v);
    }

    function testGetValidated()
    {
        $v1 = new MockValid($this);
        $v1->setReturnValue('getKey', 'testkey');
        $v1->setReturnValue('validate', true);

        $v2 = new MockValid($this);
        $v2->setReturnValue('getKey', 'testkey');
        $v2->setReturnValue('validate', false);

        $v3 = new MockValid($this);
        $v3->setReturnValue('getKey', 'does_not_exist');
        $v3->setReturnValue('validate', false);

        $v4 = new MockValid($this);
        $v4->setReturnValue('getKey', 'does_not_exist');
        $v4->setReturnValue('validate', true);

        $r = new HTTPRequest();
        //If valid, should return the submitted value...
        $this->assertEqual($r->getValidated('testkey', $v1), 'testvalue');
        //...even if there is a defult value!
        $this->assertEqual($r->getValidated('testkey', $v1, 'default value'), 'testvalue');
        //If not valid, should return the default value...
        $this->assertEqual($r->getValidated('testkey', $v2, 'default value'), 'default value');
        //...or null if there is no default value!
        $this->assertNull($r->getValidated('testkey', $v2));
        //If the variable is not submitted, there is no incidence, the result depends on the validator...
        $this->assertEqual($r->getValidated('does_not_exist', $v3, 'default value'), 'default value');
        $this->assertEqual($r->getValidated('does_not_exist', $v4, 'default value'), false);

        //Not really in the "unit" test spirit
        //(create dynamically a new instance of a validator inside the function. Should be mocked)
        $this->assertEqual($r->getValidated('testkey', 'string', 'default value'), 'testvalue');
        $this->assertEqual($r->getValidated('testkey', 'uint', 'default value'), 'default value');
    }
}

class HTTPRequest_BrowserTests extends TuleapTestCase
{

    /** @var HTTPRequest */
    private $request;

    /** @var PFUser */
    private $user;

    private $msg_ie_deprecated        = 'ie warning message';
    private $msg_ie_deprecated_button = 'disable ie warning';

    public function setUp()
    {
        parent::setUp();
        $this->preserveServer('HTTP_USER_AGENT');

        $this->setText($this->msg_ie_deprecated, array('*', 'ie_deprecated'));
        $this->setText($this->msg_ie_deprecated_button, array('*', 'ie_deprecated_button'));

        $this->user   = mock('PFUser');
        $user_manager = stub('UserManager')->getCurrentUser()->returns($this->user);
        UserManager::setInstance($user_manager);

        $this->request = new HTTPRequest();
        $this->request->setCurrentUser($this->user);

        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', '/usr/share/tuleap');
    }

    public function tearDown()
    {
        UserManager::clearInstance();
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function testNoNoticesWhenNoUserAgent()
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->request->getBrowser();
    }

    public function testIE9CompatibilityModeIsDeprected()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/5.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)';
        $browser = $this->request->getBrowser();

        $this->assertPattern('/ie warning message/', $browser->getDeprecatedMessage());
    }

    public function testIE10CompatibilityModeIsDeprected()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; WOW64; Trident/6.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0; .NET4.0C; .NET4.0E)';
        $browser = $this->request->getBrowser();

        $this->assertPattern('/ie warning message/', $browser->getDeprecatedMessage());
    }

    public function testIE11CompatibilityModeIsDeprected()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.1; Trident/7.0; SLCC2; .NET CLR 2.0.50727; .NET CLR 3.5.30729; .NET CLR 3.0.30729; Media Center PC 6.0)';
        $browser = $this->request->getBrowser();

        $this->assertPattern('/ie warning message/', $browser->getDeprecatedMessage());
    }

    public function testIE9IsDeprecated()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 9.0; Windows NT 6.1; Trident/5.0)';
        $browser = $this->request->getBrowser();

        $this->assertPattern('/ie warning message/', $browser->getDeprecatedMessage());
    }

    public function testFirefoxIsNotDeprecated()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:27.0) Gecko/20100101 Firefox/27.0';
        $browser = $this->request->getBrowser();

        expect($GLOBALS['Language'])->getText()->never();

        $browser->getDeprecatedMessage();
    }

    public function testIE8IsDeprecated()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (compatible; MSIE 8.0; Windows NT 6.1; Trident/4.0; GTB7.4; InfoPath.2; SV1; .NET CLR 3.3.69573; WOW64; en-US)';
        $browser = $this->request->getBrowser();

        $this->assertPattern('/ie warning message/', $browser->getDeprecatedMessage());
    }

    public function testIE7IsDeprecated()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertPattern('/ie warning message/', $browser->getDeprecatedMessage());
    }

    public function testIE7IsDeprecatedButUserChoseToNotDisplayTheWarning()
    {
        stub($this->user)->getPreference(PFUser::PREFERENCE_DISABLE_IE7_WARNING)->returns(1);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertNoPattern('/ie warning message/', $browser->getDeprecatedMessage());
    }

    public function itDisplaysOkButtonToDisableIE7Warning()
    {
        stub($this->user)->isAnonymous()->returns(false);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertPattern('/disable ie warning/', $browser->getDeprecatedMessage());
    }

    public function itDoesNotDisplayOkButtonForAnonymousUser()
    {
        stub($this->user)->isAnonymous()->returns(true);

        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 5.0)';
        $browser = $this->request->getBrowser();

        $this->assertNoPattern('/disable ie warning/', $browser->getDeprecatedMessage());
    }
}

abstract class HTTPRequest_getServerURLTests extends TuleapTestCase
{
    protected $request;

    public function setUp()
    {
        parent::setUp();
        $this->preserveServer('HTTPS');
        $this->preserveServer('HTTP_X_FORWARDED_PROTO');
        $this->preserveServer('HTTP_HOST');
        $this->preserveServer('REMOTE_ADDR');

        $_SERVER['REMOTE_ADDR'] = '17.18.19.20';

        ForgeConfig::store();

        $this->request = new HTTPRequest();
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }
}

// Tests inspired from From Symfony\Component\HttpFoundation\Tests\IpUtilsTest @ 3.2-dev
class HTTPRequest_getServerURL_TrustedProxyTests extends HTTPRequest_getServerURLTests
{

    public function setUp()
    {
        parent::setUp();

        $_SERVER['HTTPS']       = 'on';

        ForgeConfig::set('sys_default_domain', 'meow.bzh');
        ForgeConfig::set('sys_https_host', 'meow.bzh');
    }

    public function tearDown()
    {
        unset($_SERVER['HTTP_HOST']);
        unset($_SERVER['HTTP_X_FORWARDED_PROTO']);
        unset($_SERVER['REMOTE_ADDR']);
        unset($_SERVER['HTTPS']);
        parent::tearDown();
    }

    public function itDoesntTakeHostWhenForwardedProtoIsSetByAnUntrustedProxy()
    {
        $_SERVER['HTTP_HOST']              = 'h4cker.backhat';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';

        $this->assertEqual('https://meow.bzh', $this->request->getServerUrl());
    }

    public function itTrustsProxy()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('192.168.1.1'));
        $this->assertEqual('https://woof.bzh', $this->request->getServerUrl());
    }

    public function itAllowsCIDRNotation()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('192.168.1.1/1'));
        $this->assertEqual('https://woof.bzh', $this->request->getServerUrl());
    }

    public function itAllowsCIDRNotationWithSlash24()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('192.168.1.1/24'));
        $this->assertEqual('https://woof.bzh', $this->request->getServerUrl());
    }

    public function itDoesntAllowsNotMatchingCIDRNotation()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('1.2.3.4/1'));
        $this->assertEqual('https://meow.bzh', $this->request->getServerUrl());
    }

    public function itDoesntAllowsInvalidSubnet()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('192.168.1.1/33'));
        $this->assertEqual('https://meow.bzh', $this->request->getServerUrl());
    }

    public function itAllowsWhenAtLeastOneSubnetMatches()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('1.2.3.4/1', '192.168.1.0/24'));
        $this->assertEqual('https://woof.bzh', $this->request->getServerUrl());
    }

    public function itDoesntAllowsWhenNoSubnetMatches()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.168.1.1';

        $this->request->setTrustedProxies(array('1.2.3.4/1', '4.3.2.1/1'));
        $this->assertEqual('https://meow.bzh', $this->request->getServerUrl());
    }

    public function itDoesntAllowsInvalidCIDRNotation()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '1.2.3.4';

        $this->request->setTrustedProxies(array('256.256.256/0'));
        $this->assertEqual('https://meow.bzh', $this->request->getServerUrl());
    }

    public function itAllowsWithExtremCIDRNotation1()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '1.2.3.4';

        $this->request->setTrustedProxies(array('0.0.0.0/0'));
        $this->assertEqual('https://woof.bzh', $this->request->getServerUrl());
    }

    public function itAllowsWithExtremCIDRNotation2()
    {
        $_SERVER['HTTP_HOST']              = 'woof.bzh';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '1.2.3.4';

        $this->request->setTrustedProxies(array('192.168.1.0/0'));
        $this->assertEqual('https://woof.bzh', $this->request->getServerUrl());
    }
}


class HTTPRequest_getServerURLSSLTests extends HTTPRequest_getServerURLTests
{

    public function setUp()
    {
        parent::setUp();

        $this->request->setTrustedProxies(array('17.18.19.20'));
        $_SERVER['HTTP_HOST'] = 'example.com';
        ForgeConfig::set('sys_default_domain', 'example.com');
    }

    public function itReturnsHttpsWhenHTTPSIsTerminatedBySelf()
    {
        $_SERVER['HTTPS'] = 'on';

        $this->assertEqual('https://example.com', $this->request->getServerUrl());
    }

    public function itReturnsHttpWhenHTTPSIsNotEnabled()
    {
        $this->assertEqual('http://example.com', $this->request->getServerUrl());
    }

    public function itReturnsHTTPSWhenReverseProxyTerminateSSLAndCommunicateInClearWithTuleap()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $this->assertEqual('https://example.com', $this->request->getServerUrl());
    }

    public function itReturnsHTTPWhenReverseProxyDoesntTerminateSSLAndCommunicateInClearWithTuleap()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';

        $this->assertEqual('http://example.com', $this->request->getServerUrl());
    }

    public function itReturnsHTTPWhenReverseProxyDoesntTerminateSSLAndCommunicateInSSLWithTuleap()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';

        $this->assertEqual('http://example.com', $this->request->getServerUrl());
    }

    public function itReturnsHTTPSWhenEverythingIsSSL()
    {
        $_SERVER['HTTPS'] = 'on';
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';

        $this->assertEqual('https://example.com', $this->request->getServerUrl());
    }

    public function itReturnsHTTPSURLWhenHTTPSIsAvailableAndRequestDoesNotFromATrustedProxy()
    {
        ForgeConfig::set('sys_https_host', 'example.com');
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['REMOTE_ADDR']            = '192.0.2.1';

        $this->assertEqual('https://example.com', $this->request->getServerUrl());
    }

    public function itReturnsHTTPSURLWhenHTTPSIsAvailableAndProxyIsMisconfigured()
    {
        ForgeConfig::set('sys_https_host', 'example.com');
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'http';

        $this->assertEqual('https://example.com', $this->request->getServerUrl());
    }
}

class HTTPRequest_getServerURL_ConfigFallbackTests extends HTTPRequest_getServerURLTests
{

    public function setUp()
    {
        parent::setUp();

        $this->request->setTrustedProxies(array('17.18.19.20'));
        ForgeConfig::set('sys_default_domain', 'example.clear.test');
        ForgeConfig::set('sys_https_host', 'example.ssl.test');
    }

    public function itReturnsHostNameOfProxyWhenBehindAProxy()
    {
        $_SERVER['HTTP_X_FORWARDED_PROTO'] = 'https';
        $_SERVER['HTTP_HOST'] = 'meow.test';

        $this->assertEqual('https://meow.test', $this->request->getServerUrl());
    }

    public function itReturnsTheConfiguredHTTPNameWhenInHTTP()
    {
        ForgeConfig::set('sys_https_host', '');
        $this->assertEqual('http://example.clear.test', $this->request->getServerUrl());
    }

    public function itReturnsTheConfiguredHTTPSNameWhenInHTTPS()
    {
        $_SERVER['HTTPS'] = 'on';

        $this->assertEqual('https://example.ssl.test', $this->request->getServerUrl());
    }

    public function itReturnsTheDefaultDomainNameWhenInHTTPButNothingConfiguredAsHTTPSHost()
    {
        $_SERVER['HTTPS'] = 'on';
        ForgeConfig::set('sys_https_host', '');

        $this->assertEqual('https://example.clear.test', $this->request->getServerUrl());
    }

    public function itReturnsHTTPSURLWhenHTTPSIsAvailable()
    {
        ForgeConfig::set('sys_https_host', 'example.clear.test');

        $this->assertEqual('https://example.clear.test', $this->request->getServerUrl());
    }
}
