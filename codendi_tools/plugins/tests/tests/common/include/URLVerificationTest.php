<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('common/user/User.class.php');
Mock::generate('User');
require_once('common/include/URLVerification.class.php');
Mock::generatePartial(
    'URLVerification',
    'URLVerificationTestVersion',
    array('getCurrentUser', 'getEventManager')
);

Mock::generatepartial('URLVerification', 'URLVerificationTestVersion2', array('getUrlChunks'));

Mock::generatePartial(
    'URLVerification',
    'URLVerificationTestVersion3',
    array('isException', 'verifyProtocol', 'verifyHost', 'verifyRequest', 'getUrlChunks', 'checkRestrictedAccess', 'checkPrivateAccess', 'getRedirectionURL', 'header')
);

require_once('common/event/EventManager.class.php');
Mock::generate('EventManager');

class MockEM4Anonymous extends MockEventManager {
   function processEvent($event, $params) {
       foreach(parent::processEvent($event, $params) as $key => $value) {
           $params[$key] = $value;
       }
   }
}

require_once('common/language/BaseLanguage.class.php');
Mock::generate('BaseLanguage');

class URLVerificationTest extends UnitTestCase {

    function setUp() {
        $this->fixtures = dirname(__FILE__).'/_fixtures';
        $GLOBALS['Language'] = new MockBaseLanguage($this);
    }

    function tearDown() {
        unset($GLOBALS['Language']);
        $GLOBALS['sys_allow_anon'] = 1;
        $GLOBALS['sys_default_domain'] = 1;
        $GLOBALS['sys_force_ssl'] = 1;
        $GLOBALS['sys_https_host'] = 1;
    }

    function testIsScriptAllowedForAnonymous() {
        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEM4Anonymous($this);
        $em->setReturnValue('processEvent', array('anonymous_allowed' => false));
        $urlVerification->setReturnValue('getEventManager', $em);
        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/current_css.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/login.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/register.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/change_pw.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/include/check_pw.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/lostpw.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/lostlogin.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/lostpw-confirm.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/pending-resend.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/verify.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/scripts/check_pw.js.php')));

        $this->assertFalse($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/foobar')));
    }

    function testIsScriptAllowedForAnonymousFromHook() {
        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEM4Anonymous($this);
        $em->setReturnValue('processEvent', array('anonymous_allowed' => true));
        $urlVerification->setReturnValue('getEventManager', $em);
        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/foobar')));
    }

    function testIsScriptAllowedForAnonymousFromSiteContent() {
        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEM4Anonymous($this);
        $em->setReturnValue('processEvent', array('anonymous_allowed' => false));
        $urlVerification->setReturnValue('getEventManager', $em);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/allowed_url_anonymous.txt');

        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/foobar')));
    }

    function testIsException() {
        $urlVerification = new URLVerification();

        $this->assertTrue($urlVerification->isException(array('SERVER_NAME'  => 'localhost',    'SCRIPT_NAME' => '/projects/foobar')));
        $this->assertFalse($urlVerification->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME'  => '/projects/foobar')));

        $this->assertTrue($urlVerification->isException(array('SERVER_NAME'  => 'codendi.org',  'SCRIPT_NAME' => '/api/reference/extractCross')));
        $this->assertTrue($urlVerification->isException(array('SERVER_NAME'  => 'codendi.org',  'SCRIPT_NAME' => '/soap/index.php')));
        $this->assertFalse($urlVerification->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME'  => '/plugins/tracker')));
        $this->assertTrue($urlVerification->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME'  => '/plugins/tracker/soap/')));
        $this->assertFalse($urlVerification->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME'  => '/forged/url?q=/plugins/tracker/soap/')));
        $this->assertTrue($urlVerification->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME'  => '/plugins/docman/soap/')));
        $this->assertFalse($urlVerification->isException(array('SERVER_NAME' => 'codendi.org', 'SCRIPT_NAME'  => '/projects/foobar')));
    }

    function testVerifyProtocolHTTPAndForceSslEquals1() {
        $server = array();
        $GLOBALS['sys_force_ssl'] = 1;
        $urlVerification = new URLVerification();

        $urlVerification->verifyProtocol($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['protocol'], 'https');
    }

    function testVerifyProtocolHTTPSAndForceSslEquals1() {
        $server = array('HTTPS' => 'on');
        $GLOBALS['sys_force_ssl'] = 1;
        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['protocol'], null);
    }

    function testVerifyProtocolHTTPAndForceSslEquals0() {
        $server = array();
        $GLOBALS['sys_force_ssl'] = 0;
        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['protocol'], null);
    }

    function testVerifyProtocolHTTPSAndForceSslEquals0() {
        $server = array('HTTPS' => 'on');
        $GLOBALS['sys_force_ssl'] = 0;
        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['protocol'], null);
    }

    function testVerifyHostExceptionAndForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'localhost',
                        'SERVER_NAME' => 'localhost',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl'] = 0;

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostExceptionAndForceSslEquals1() {
        $server = array('HTTP_HOST'   => 'localhost',
                        'SERVER_NAME' => 'localhost',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl'] = 1;

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

   function testVerifyHostHTTPSAndForceSslEquals1() {
        $server = array('HTTP_HOST'   => 'secure.codendi.org',
                        'SERVER_NAME' => 'secure.codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_https_host']     = 'secure.codendi.org';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostHTTPAndForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'codendi.org',
                        'SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'codendi.org';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostHTTPSAndForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'secure.codendi.org',
                        'SERVER_NAME' => 'secure.codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_https_host'] = 'secure.codendi.org';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostHTTPAndForceSslEquals1() {
        $server = array('HTTP_HOST'   => 'codendi.org',
                        'SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $GLOBALS['sys_https_host'] = 'secure.codendi.org';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], 'secure.codendi.org');
    }

    function testVerifyHostInvalidHostHTTPForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'test.codendi.org',
                        'SERVER_NAME' => 'test.codendi.org',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_allow_anon']     = 1;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $GLOBALS['sys_https_host'] = 'secure.codendi.org';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], 'codendi.org');
    }

    function testVerifyHostInvalidHostHTTPSForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'test.codendi.org',
                        'SERVER_NAME' => 'test.codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $GLOBALS['sys_https_host'] = 'secure.codendi.org';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], 'secure.codendi.org');
    }

    function testVerifyHostInvalidHostForceSslEquals1() {
        $server = array('HTTP_HOST'   => 'test.codendi.org',
                        'SERVER_NAME' => 'test.codendi.org',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'codendi.org';
        $GLOBALS['sys_https_host'] = 'secure.codendi.org';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], 'secure.codendi.org');
    }

    function testVerifyRequestAnonymousWhenScriptException() {
        $server = array('SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '/account/login.php');

        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], null);
    }

    function testVerifyRequestAnonymousWhenAllowed() {
        $server = array('SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_allow_anon'] = 1;

        $user = new MockUser();
        $user->setReturnValue('isAnonymous', true);

        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], null);
    }

    function testVerifyRequestAuthenticatedWhenAnonymousAllowed() {
        $server = array('SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_allow_anon'] = 1;

        $user = new MockUser();
        $user->setReturnValue('isAnonymous', false);

        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], null);
    }

    function testVerifyRequestAnonymousWhenNotAllowedAtRoot() {
        $server = array('SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/');

        $GLOBALS['sys_allow_anon'] = 0;
        $GLOBALS['sys_https_host'] = 'secure.codendi.org';

        $user = new MockUser();
        $user->setReturnValue('isAnonymous', true);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fmy%2F');
    }

    function testVerifyRequestAnonymousWhenNotAllowedWithScript() {
        $server = array('SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/script/');

        $GLOBALS['sys_allow_anon'] = 0;
        $GLOBALS['sys_https_host'] = 'secure.codendi.org';

        $user = new MockUser();
        $user->setReturnValue('isAnonymous', true);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fscript%2F');
    }

    function testVerifyRequestAnonymousWhenNotAllowedWithLightView() {
        $server = array('SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/script?pv=2');

        $GLOBALS['sys_allow_anon'] = 0;
        $GLOBALS['sys_https_host'] = 'secure.codendi.org';

        $user = new MockUser();
        $user->setReturnValue('isAnonymous', true);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2');
    }

    function testVerifyRequestAuthenticatedWhenAnonymousNotAllowed() {
        $server = array('SERVER_NAME' => 'codendi.org',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_allow_anon'] = 0;

        $user = new MockUser();
        $user->setReturnValue('isAnonymous', false);

        $urlVerification = new URLVerificationTestVersion($this);
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], null);
    }
    
    function testGetRedirectionProtocolModified() {
        $server = array('HTTP_HOST' => 'codendi.org',
                        'REQUEST_URI' => '');
        $chunks =  array('protocol'=> 'https');
        
        $urlVerification = new URLVerificationTestVersion2($this);
        
        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($server), 'https://codendi.org');
    }
    
    function testGetRedirectionProtocolAndHostModified() {
        $server = array('HTTP_HOST' => 'test.codendi.org',
                        'REQUEST_URI' => '/user.php');
        $chunks =  array('protocol'=> 'http', 'host' =>'secure.codendi.org');
        
        $urlVerification = new URLVerificationTestVersion2($this);
        
        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($server), 'http://secure.codendi.org/user.php');
    }
    
    function testGetRedirectionRequestModified() {
        $server = array('HTTP_HOST' => 'secure.codendi.org',
                        'REQUEST_URI' => '/user.php',
                        'HTTPS'       => 'on',);
        $chunks =  array('script'=> '/project.php');
        
        $urlVerification = new URLVerificationTestVersion2($this);
        
        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($server), 'https://secure.codendi.org/project.php');
    }

    function testAssertValidUrlWithException() {
        $urlVerification = new URLVerificationTestVersion3($this);
        $urlVerification->setReturnValue('isException', true);

        $urlVerification->expectNever('header');
        $server = array();
        $urlVerification->assertValidUrl($server);
    }

    function testAssertValidUrlWithNoRedirection() {
        $urlVerification = new URLVerificationTestVersion3($this);
        $urlVerification->setReturnValue('isException', false);
        $urlVerification->setReturnValue('getUrlChunks', null);

        $urlVerification->expectNever('header');
        $server = array();
        $urlVerification->assertValidUrl($server);
    }

    function testAssertValidUrlWithRedirection() {
        $urlVerification = new URLVerificationTestVersion3($this);
        $urlVerification->setReturnValue('isException', false);
        $urlVerification->setReturnValue('getUrlChunks', array('protocol' => 'https', 'host' => 'secure.codendi.org'));

        $urlVerification->expectOnce('header');
        $server = array();
        $urlVerification->assertValidUrl($server);
    }

}

?>