<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
Mock::generate('PFUser');
require_once('common/project/Project.class.php');
Mock::generate('Project');
require_once('common/project/ProjectManager.class.php');
Mock::generate('ProjectManager');
require_once('common/include/URLVerification.class.php');

Mock::generatepartial('URLVerification',
                      'URLVerificationTestVersion2',
                      array('getUrlChunks',
                            'getProjectManager',
                            'userCanAccessProject',
                            'exitError'));

Mock::generatePartial(
    'URLVerification',
    'URLVerificationTestVersion3',
    array('isException',
          'verifyProtocol',
          'verifyHost',
          'verifyRequest',
          'getUrlChunks',
          'checkRestrictedAccess',
          'checkPrivateAccess',
          'getRedirectionURL',
          'header',
          'checkNotActiveProject')
);

Mock::generatePartial(
    'URLVerification',
    'URLVerificationTestVersion4',
    array('isException',
          'verifyProtocol',
          'verifyHost',
          'verifyRequest',
          'getUrlChunks',
          'checkRestrictedAccess',
          'checkPrivateAccess',
          'getRedirectionURL',
          'header',
          'checkNotActiveProject',
          'getUrl')
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

Mock::generate('URL');

class URLVerificationTest extends TuleapTestCase {

    private $user_manager;
    private $user;

    function setUp() {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $GLOBALS['Response']           = mock('Layout');
        $GLOBALS['sys_default_domain'] = 'default';
        $GLOBALS['sys_https_host']     = 'default';
        $GLOBALS['sys_force_ssl']      = 0;
        unset($GLOBALS['group_id']);

        $this->fixtures = dirname(__FILE__).'/_fixtures';
        $GLOBALS['Language'] = new MockBaseLanguage($this);

        $user = mock('PFUser');
        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getCurrentUser()->returns($user);

        UserManager::setInstance($this->user_manager);
    }

    function tearDown() {
        UserManager::clearInstance();
        unset($GLOBALS['Language']);
        unset($GLOBALS['Response']);
        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['sys_force_ssl']);
        unset($GLOBALS['sys_https_host']);
        unset($GLOBALS['group_id']);
        unset($_REQUEST['type_of_search']);
        ForgeConfig::restore();
        parent::tearDown();
    }

    function testIsScriptAllowedForAnonymous() {
        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
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
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/scripts/check_pw.js.php')));

        $this->assertFalse($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/foobar')));
    }

    function itDoesNotTreatRegularUrlsAsExceptions() {
        $urlVerification = new URLVerification();
        $this->assertFalse($urlVerification->isException(array('SCRIPT_NAME' => '/projects/foobar')));
    }

    function itDoesNotTreatRegularUrlsWhichContainsSOAPAsExceptions() {
        $urlVerification = new URLVerification();
        $this->assertFalse($urlVerification->isException(array('SCRIPT_NAME' => '/projects/foobar/?p=/soap/index.php')));
    }

    function itDoesNotTreatRegularUrlsWhichContainsAPIAsExceptions() {
        $urlVerification = new URLVerification();
        $this->assertFalse($urlVerification->isException(array('SCRIPT_NAME' => '/projects/foobar/?p=/api/reference/extractCross')));
    }

    function itTreatsSOAPApiAsException() {
        $urlVerification = new URLVerification();
        $this->assertTrue($urlVerification->isException(array('SCRIPT_NAME' => '/soap/index.php')));
    }

    function itTreatsSOAPApiOfPluginsAsException() {
        $urlVerification = new URLVerification();
        $this->assertTrue($urlVerification->isException(array('SCRIPT_NAME' => '/plugins/docman/soap/index.php')));
    }

    function itTreatsExtractionOfCrossReferencesApiAsException() {
        $urlVerification = new URLVerification();
        $this->assertTrue($urlVerification->isException(array('SCRIPT_NAME' => '/api/reference/extractCross')));
    }

    function testIsScriptAllowedForAnonymousFromHook() {
        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEM4Anonymous($this);
        $em->setReturnValue('processEvent', array('anonymous_allowed' => true));
        $urlVerification->setReturnValue('getEventManager', $em);
        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/foobar')));
    }

    function testIsScriptAllowedForAnonymousFromSiteContent() {
        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEM4Anonymous($this);
        $em->setReturnValue('processEvent', array('anonymous_allowed' => false));
        $urlVerification->setReturnValue('getEventManager', $em);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/allowed_url_anonymous.txt');

        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/foobar')));
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

   function testVerifyHostHTTPSAndForceSslEquals1() {
        $server = array('HTTP_HOST'   => 'secure.example.com',
                        'SERVER_NAME' => 'secure.example.com',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_https_host']     = 'secure.example.com';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostHTTPAndForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'example.com',
                        'SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'example.com';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostHTTPSAndForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'secure.example.com',
                        'SERVER_NAME' => 'secure.example.com',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostHTTPAndForceSslEquals1() {
        $server = array('HTTP_HOST'   => 'example.com',
                        'SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], 'secure.example.com');
    }

    function testVerifyHostInvalidHostHTTPForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'test.example.com',
                        'SERVER_NAME' => 'test.example.com',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostInvalidHostHTTPSForceSslEquals0() {
        $server = array('HTTP_HOST'   => 'test.example.com',
                        'SERVER_NAME' => 'test.example.com',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 0;
        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyHostInvalidHostForceSslEquals1() {
        $server = array('HTTP_HOST'   => 'test.example.com',
                        'SERVER_NAME' => 'test.example.com',
                        'HTTPS'       => 'on',
                        'SCRIPT_NAME' => '');

        $GLOBALS['sys_force_ssl']      = 1;
        $GLOBALS['sys_default_domain'] = 'example.com';
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $urlVerification = new URLVerification();
        $urlVerification->verifyHost($server);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], null);
    }

    function testVerifyRequestAnonymousWhenScriptException() {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '/account/login.php');

        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], null);
    }

    function testVerifyRequestAnonymousWhenAllowed() {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '');

        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);

        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], null);
    }

    function testVerifyRequestAuthenticatedWhenAnonymousAllowed() {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '');

        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);

        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], null);
    }

    function testVerifyRequestAnonymousWhenNotAllowedAtRoot() {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fmy%2F');
    }

    function testVerifyRequestAnonymousWhenNotAllowedWithScript() {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/script/');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fscript%2F');
    }

    function testVerifyRequestAnonymousWhenNotAllowedWithLightView() {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/script?pv=2');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', true);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2');
    }

    function testVerifyRequestAuthenticatedWhenAnonymousNotAllowed() {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '');

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $user = mock('PFUser');
        $user->setReturnValue('isAnonymous', false);

        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = new MockEventManager();
        $urlVerification->setReturnValue('getEventManager', $em);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $urlVerification->verifyRequest($server);
        $chunks = $urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], null);
    }

    function testGetRedirectionProtocolModified() {
        $server = array('HTTP_HOST' => 'example.com',
                        'REQUEST_URI' => '');
        $chunks =  array('protocol'=> 'https');

        $urlVerification = new URLVerificationTestVersion2($this);

        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($server), 'https://example.com');
    }

    function testGetRedirectionProtocolAndHostModified() {
        $server = array('HTTP_HOST' => 'test.example.com',
                        'REQUEST_URI' => '/user.php');
        $chunks =  array('protocol'=> 'http', 'host' =>'secure.example.com');

        $urlVerification = new URLVerificationTestVersion2($this);

        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($server), 'http://secure.example.com/user.php');
    }

    function testGetRedirectionRequestModified() {
        $server = array('HTTP_HOST' => 'secure.example.com',
                        'REQUEST_URI' => '/user.php',
                        'HTTPS'       => 'on',);
        $chunks =  array('script'=> '/project.php');

        $urlVerification = new URLVerificationTestVersion2($this);

        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($server), 'https://secure.example.com/project.php');
    }

    function testAssertValidUrlWithException() {
        $urlVerification = new URLVerificationTestVersion3($this);
        $urlVerification->setReturnValue('isException', true);

        $urlVerification->expectNever('header');
        $server = array();
        $urlVerification->assertValidUrl($server);
    }

    function testAssertValidUrlWithNoRedirection() {
        $urlVerification = new URLVerificationTestVersion4($this);

        stub($urlVerification)->getUrl()->returns(mock('URL'));

        $urlVerification->setReturnValue('isException', false);
        $urlVerification->setReturnValue('getUrlChunks', null);

        $urlVerification->expectNever('header');
        $server = array(
            'REQUEST_URI' => '/'
        );
        $urlVerification->assertValidUrl($server);
    }

    function testAssertValidUrlWithRedirection() {
        $urlVerification = new URLVerificationTestVersion4($this);

        stub($urlVerification)->getUrl()->returns(mock('URL'));

        $urlVerification->setReturnValue('isException', false);
        $urlVerification->setReturnValue('getUrlChunks', array('protocol' => 'https', 'host' => 'secure.example.com'));

        $urlVerification->expectOnce('header');
        $server = array(
            'REQUEST_URI' => '/'
        );
        $urlVerification->assertValidUrl($server);
    }

    function testCheckNotActiveProjectApi() {
        $urlVerification = partial_mock('URLVerification', array('getProjectManager', 'exitError', 'displayRestrictedUserError', 'displayPrivateProjectError'));
        $GLOBALS['group_id'] = 1;
        $project = new MockProject();
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);

        $urlVerification->expectNever('exitError');
        $urlVerification->expectNever('displayRestrictedUserError');
        $urlVerification->expectNever('displayPrivateProjectError');

        $urlVerification->assertValidUrl(array('SCRIPT_NAME' => '/api/'));
    }

    function testCheckNotActiveProjectError() {
        $urlVerification = partial_mock('URLVerification', array('getProjectManager', 'exitError', 'displayRestrictedUserError', 'displayPrivateProjectError'));
        $GLOBALS['group_id'] = 1;
        $project = new MockProject();
        stub($project)->isActive()->returns(false);
        stub($project)->isPublic()->returns(true);
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);

        $urlVerification->expectOnce('exitError');
        $urlVerification->expectNever('displayRestrictedUserError');
        $urlVerification->expectNever('displayPrivateProjectError');

        $urlVerification->assertValidUrl(array('SCRIPT_NAME' => '/some_service/?group_id=1', 'REQUEST_URI' => '/some_service/?group_id=1'));
    }

    function testCheckNotActiveProjectNoError() {
        $urlVerification = partial_mock('URLVerification', array('getProjectManager', 'exitError', 'displayRestrictedUserError', 'displayPrivateProjectError'));
        $GLOBALS['group_id'] = 1;
        $project = new MockProject();
        stub($project)->isPublic()->returns(true);
        stub($project)->isActive()->returns(true);
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);

        $urlVerification->expectNever('exitError');
        $urlVerification->expectNever('displayRestrictedUserError');
        $urlVerification->expectNever('displayPrivateProjectError');

        $urlVerification->assertValidUrl(array('SCRIPT_NAME' => '/some_service/?group_id=1', 'REQUEST_URI' => '/some_service/?group_id=1'));
    }

    function testUserCanAccessPrivateShouldLetUserPassWhenNotInAProject() {
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getProjectManager', 'exitError', 'displayRestrictedUserError', 'displayPrivateProjectError', 'getUrl'));

        stub($urlVerification)->getUrl()->returns(mock('URL'));

        $urlVerification->expectNever('exitError');
        $urlVerification->expectNever('displayRestrictedUserError');
        $urlVerification->expectNever('displayPrivateProjectError');

        $urlVerification->assertValidUrl(array('SCRIPT_NAME' => '/stuff', 'REQUEST_URI' => '/stuff'));
    }

    function testUserCanAccessPrivateShouldLetUserPassWhenProjectIsPublic() {
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getProjectManager', 'exitError', 'displayRestrictedUserError', 'displayPrivateProjectError'));
        $GLOBALS['group_id'] = 120;
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isActive', true);
        $project->setReturnValue('isPublic', true);
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);

        $urlVerification->expectNever('exitError');
        $urlVerification->expectNever('displayRestrictedUserError');
        $urlVerification->expectNever('displayPrivateProjectError');

        $urlVerification->assertValidUrl(array('SCRIPT_NAME' => '/stuff', 'REQUEST_URI' => '/stuff'));
    }

    function testRestrictedUserCanAccessSearchOnTracker() {
        $_REQUEST['type_of_search'] = 'tracker';
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $GLOBALS['group_id'] = 120;

        $urlVerification->setReturnValue('getUrl', '/search/');

        $user = new MockPFUser();
        $user->setReturnValue('isRestricted', true);
        $urlVerification->setReturnValue('getCurrentUser', $user);

        $server = array(
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah'
        );

        stub($urlVerification)->displayRestrictedUserError()->never();
        stub($GLOBALS['Language'])->getContent()->returns(dirname(__FILE__) . '/_fixtures/empty.txt');

        $urlVerification->checkRestrictedAccess($server, 'stuff');
    }

    function testRestrictedUserCanNotAccessSearchOnPeople() {
        $_REQUEST['type_of_search'] = 'people';
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $GLOBALS['group_id'] = 120;

        $urlVerification->setReturnValue('getUrl', '/search/');

        $user = new MockPFUser();
        $user->setReturnValue('isRestricted', true);
        $urlVerification->setReturnValue('getCurrentUser', $user);

        $server = array(
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah'
        );

        stub($urlVerification)->displayRestrictedUserError()->once();
        stub($GLOBALS['Language'])->getContent()->returns(dirname(__FILE__) . '/_fixtures/empty.txt');

        $urlVerification->checkRestrictedAccess($server, 'stuff');
    }

    function testRestrictedUserCanNotAccessSearchOnLdapPeople() {
        $_REQUEST['type_of_search'] = 'people_ldap';
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $GLOBALS['group_id'] = 120;

        $urlVerification->setReturnValue('getUrl', '/search/');

        $user = new MockPFUser();
        $user->setReturnValue('isRestricted', true);
        $urlVerification->setReturnValue('getCurrentUser', $user);

        $server = array(
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah'
        );

        stub($urlVerification)->displayRestrictedUserError()->once();
        stub($GLOBALS['Language'])->getContent()->returns(dirname(__FILE__) . '/_fixtures/empty.txt');

        $urlVerification->checkRestrictedAccess($server, 'stuff');
    }

    function testRestrictedUserCanNotAccessSearchOnSoftwareProjects() {
        $_REQUEST['type_of_search'] = 'soft';
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $GLOBALS['group_id'] = 120;

        $urlVerification->setReturnValue('getUrl', '/search/');

        $user = new MockPFUser();
        $user->setReturnValue('isRestricted', true);
        $urlVerification->setReturnValue('getCurrentUser', $user);

        $server = array(
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah'
        );

        stub($urlVerification)->displayRestrictedUserError()->once();
        stub($GLOBALS['Language'])->getContent()->returns(dirname(__FILE__) . '/_fixtures/empty.txt');

        $urlVerification->checkRestrictedAccess($server, 'stuff');
    }

    function testRestrictedUserCanNotAccessSearchOnCodeSnippets() {
        $_REQUEST['type_of_search'] = 'snippets';
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $GLOBALS['group_id'] = 120;

        $urlVerification->setReturnValue('getUrl', '/search/');

        $user = new MockPFUser();
        $user->setReturnValue('isRestricted', true);
        $urlVerification->setReturnValue('getCurrentUser', $user);

        $server = array(
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah'
        );

        stub($urlVerification)->displayRestrictedUserError()->once();
        stub($GLOBALS['Language'])->getContent()->returns(dirname(__FILE__) . '/_fixtures/empty.txt');

        $urlVerification->checkRestrictedAccess($server, 'stuff');
    }

    public function testRestrictedUserCanAccessPluginManagedScripts() {
        $user = new MockPFUser();

        $url_verification = TestHelper::getPartialMock('TestURL_VERIFACTION', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $url_verification->setReturnValue('getUrl', '/plugins/lamas');

        EventManager::instance()->addListener(
            Event::IS_SCRIPT_HANDLED_FOR_RESTRICTED,
            new URL_VERIFACTION_FakeLamaPlugin(),
            'hook',
            false,
            0
        );

        $url = mock('URL');
        stub($GLOBALS['Language'])->getContent()->returns(dirname(__FILE__) . '/_fixtures/empty.txt');

        $this->assertTrue($url_verification->restrictedUserCanAccessUrl($user, $url, '/blah', 'blah'));
    }

    public function testRestrictedUserCanNotAccessProjectWhichDoesntAllowResticted() {
        $user            = new MockPFUser();
        $project         = mock('Project');
        $url_verification = new URLVerification();

        stub($project)->isError()->returns(false);
        stub($project)->isActive()->returns(true);
        stub($project)->allowsRestricted()->returns(false);
        stub($user)->isSuperUser()->returns(false);
        stub($user)->isMember()->returns(false);
        stub($user)->isRestricted()->returns(true);

        $this->expectException('Project_AccessRestrictedException');

        $url_verification->userCanAccessProject($user, $project);
    }

    public function testRestrictedUserCanNotAccessForbiddenServiceInProjectWhichAllowsResticted() {
        $user            = new MockPFUser();
        $project         = mock('Project');
        $url_verification = partial_mock('URLVerification', array('getUrl', 'restrictedUserCanAccessUrl'));

        stub($url_verification)->restrictedUserCanAccessUrl()->returns(false);

        stub($project)->isError()->returns(false);
        stub($project)->isActive()->returns(true);
        stub($project)->allowsRestricted()->returns(true);
        stub($user)->isSuperUser()->returns(false);
        stub($user)->isMember()->returns(false);
        stub($user)->isRestricted()->returns(true);

        $this->expectException('Project_AccessRestrictedException');

        $url_verification->userCanAccessProject($user, $project);
    }
}

class TestURL_VERIFACTION extends URLVerification {
    public function restrictedUserCanAccessUrl($user, $url, $request_uri, $script_name) {
        return parent::restrictedUserCanAccessUrl($user, $url, $request_uri, $script_name);
    }
}

class URL_VERIFACTION_FakeLamaPlugin {

    public function hook($params) {
        $params['allow_restricted'] = true;
    }
}

class URLVerification_PrivateRestrictedTest extends TuleapTestCase {

    private $url_verification;
    private $user;
    private $project;

    public function setUp() {
        parent::setUp();
        $this->url_verification = new URLVerification();
        $this->user             = mock('PFUser');
        $this->project          = mock('Project');

        $GLOBALS['sys_default_domain'] = 'default';
        $GLOBALS['sys_https_host']     = 'default';
    }

    public function tearDown() {
        parent::tearDown();

        unset($GLOBALS['sys_default_domain']);
        unset($GLOBALS['sys_https_host']);
    }

    public function itGrantsAccessToProjectMembers() {
        stub($this->project)->getID()->returns(110);
        stub($this->user)->isMember(110)->returns(true);
        stub($this->project)->isActive()->returns(true);

        $this->assertTrue(
            $this->url_verification->userCanAccessProject($this->user, $this->project)
        );
    }

    public function itGrantsAccessToNonProjectMembersForPublicProjects() {
        stub($this->project)->getID()->returns(110);
        stub($this->user)->isMember()->returns(false);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->isActive()->returns(true);

        $this->assertTrue(
            $this->url_verification->userCanAccessProject($this->user, $this->project)
        );
    }

    public function itForbidsAccessToRestrictedUsersNotProjectMembers() {
        stub($this->project)->getID()->returns(110);
        stub($this->user)->isMember()->returns(false);
        stub($this->user)->isRestricted()->returns(true);
        stub($this->project)->isActive()->returns(true);

        $this->expectException('Project_AccessRestrictedException');
        $this->url_verification->userCanAccessProject($this->user, $this->project);
    }

    public function itGrantsAccessToRestrictedUsersThatAreProjectMembers() {
        stub($this->project)->getID()->returns(110);
        stub($this->user)->isMember()->returns(true);
        stub($this->user)->isRestricted()->returns(true);
        stub($this->project)->isActive()->returns(true);

        $this->assertTrue(
            $this->url_verification->userCanAccessProject($this->user, $this->project)
        );
    }

    public function itForbidsAccessToActiveUsersThatAreNotPrivateProjectMembers() {
        stub($this->project)->getID()->returns(110);
        stub($this->project)->isPublic()->returns(false);
        stub($this->user)->isRestricted()->returns(false);
        stub($this->project)->isActive()->returns(true);

        $this->expectException('Project_AccessPrivateException');
        $this->url_verification->userCanAccessProject($this->user, $this->project);
    }

    public function itForbidsRestrictedUsersToAccessProjectsTheyAreNotMemberOf() {
        stub($this->project)->getID()->returns(110);
        stub($this->user)->isMember()->returns(false);
        stub($this->project)->isPublic()->returns(true);
        stub($this->user)->isRestricted()->returns(true);
        stub($this->project)->isActive()->returns(true);

        $this->expectException('Project_AccessRestrictedException');
        $this->url_verification->userCanAccessProject($this->user, $this->project);
    }

    public function itForbidsAccessToDeletedProjects() {
        stub($this->project)->getID()->returns(110);
        stub($this->user)->isMember()->returns(true);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->isActive()->returns(false);

        $this->expectException('Project_AccessDeletedException');
        $this->url_verification->userCanAccessProject($this->user, $this->project);
    }

    public function itForbidsAccessToNonExistantProject() {
        stub($this->project)->getID()->returns(110);
        stub($this->project)->isError()->returns(true);
        stub($this->project)->isPublic()->returns(true);
        stub($this->project)->isActive()->returns(true);

        $this->expectException('Project_AccessProjectNotFoundException');
        $this->url_verification->userCanAccessProject($this->user, $this->project);
    }

    public function itBlindlyGrantAccessForSiteAdmin() {
        stub($this->project)->getID()->returns(110);
        stub($this->user)->isSuperUser()->returns(true);
        stub($this->user)->isMember()->returns(false);
        stub($this->project)->isPublic()->returns(false);
        stub($this->project)->isActive()->returns(false);

        $this->assertTrue(
            $this->url_verification->userCanAccessProject($this->user, $this->project)
        );
    }

    public function itChecksUriInternal() {
        $this->assertFalse($this->url_verification->isInternal('http://evil.tld/'));
        $this->assertFalse($this->url_verification->isInternal('https://evil.tld/'));
        $this->assertFalse($this->url_verification->isInternal('javascript:alert(1)'));
        $this->assertTrue($this->url_verification->isInternal('/path/to/feature'));
        $this->assertTrue(
                $this->url_verification->isInternal('http://' . $GLOBALS['sys_default_domain'] . '/smthing')
            );
        $this->assertTrue(
                $this->url_verification->isInternal('https://' . $GLOBALS['sys_https_host'] . '/smthing')
            );

    }
}
