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
        unset($GLOBALS['group_id']);
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
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('SCRIPT_NAME' => '/account/verify.php')));
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
        $GLOBALS['sys_allow_anon']     = 1;
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

        $GLOBALS['sys_allow_anon'] = 1;

        $user = new MockUser();
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

        $GLOBALS['sys_allow_anon'] = 1;

        $user = new MockUser();
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

        $GLOBALS['sys_allow_anon'] = 0;
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $user = new MockUser();
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

        $GLOBALS['sys_allow_anon'] = 0;
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $user = new MockUser();
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

        $GLOBALS['sys_allow_anon'] = 0;
        $GLOBALS['sys_https_host'] = 'secure.example.com';

        $user = new MockUser();
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

        $GLOBALS['sys_allow_anon'] = 0;

        $user = new MockUser();
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
        $urlVerification->setReturnValue('getUrlChunks', array('protocol' => 'https', 'host' => 'secure.example.com'));

        $urlVerification->expectOnce('header');
        $server = array();
        $urlVerification->assertValidUrl($server);
    }

    function testUserCanAccessProjectActive() {
        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $project = new MockProject();
        $project->setReturnValue('isActive', true);
        $this->assertTrue($urlVerification->userCanAccessProject($project));
    }

    function testUserCanAccessProjectSuperUser() {
        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $project = new MockProject();
        $project->setReturnValue('isActive', false);
        $user = new MockUser();
        $user->setReturnValue('isSuperUser', true);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $this->assertTrue($urlVerification->userCanAccessProject($project));
    }

    function testUserCanAccessProjectAccessDenied() {
        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $project = new MockProject();
        $project->setReturnValue('isActive', false);
        $user = new MockUser();
        $user->setReturnValue('isSuperUser', false);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        $this->assertFalse($urlVerification->userCanAccessProject($project));
    }

    function testCheckNotActiveProjectApi() {
        $urlVerification = new URLVerificationTestVersion2();
        $GLOBALS['group_id'] = 1;
        $project = new MockProject();
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);
        $urlVerification->setReturnValue('userCanAccessProject', true);
        $urlVerification->checkNotActiveProject(array('SCRIPT_NAME' => '/api/'));
        $urlVerification->expectOnce('getProjectManager');
        $urlVerification->expectNever('exitError');
    }

    function testCheckNotActiveProjectError() {
        $urlVerification = new URLVerificationTestVersion2();
        $GLOBALS['group_id'] = 1;
        $project = new MockProject();
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);
        $urlVerification->setReturnValue('userCanAccessProject', false);
        $urlVerification->checkNotActiveProject(array('SCRIPT_NAME' => '/my/'));
        $urlVerification->expectOnce('getProjectManager');
        $urlVerification->expectNever('exitError');
    }

    function testCheckNotActiveProjectNoError() {
        $urlVerification = new URLVerificationTestVersion2();
        $GLOBALS['group_id'] = 1;
        $project = new MockProject();
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);
        $urlVerification->setReturnValue('userCanAccessProject', true);
        $urlVerification->checkNotActiveProject(array('SCRIPT_NAME' => '/my/'));
        $urlVerification->expectOnce('getProjectManager');
        $urlVerification->expectNever('exitError');
    }
    
    function testUserCanAccessPrivateShouldLetUserPassWhenNotInAProject() {
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getProjectManager', 'getCurrentUser'));
        $GLOBALS['group_id'] = -1;
        $project = new MockProject();
        $project->setReturnValue('isError', true);
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);
        
        $this->assertTrue($urlVerification->userCanAccessPrivate(new MockUrl(), 'stuff'));
    }
    
    function testUserCanAccessPrivateShouldLetUserPassWhenProjectIsNotAnObject() {
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getProjectManager', 'getCurrentUser'));
        $GLOBALS['group_id'] = -1;
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', false);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);
        
        $this->assertTrue($urlVerification->userCanAccessPrivate(new MockUrl(), 'stuff'));
    }

    function testUserCanAccessPrivateShouldLetUserPassWhenProjectIsPublic() {
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getProjectManager', 'getCurrentUser'));
        $GLOBALS['group_id'] = 120;
        $project = new MockProject();
        $project->setReturnValue('isError', true);
        $project->setReturnValue('isPublic', true);
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project);
        $urlVerification->setReturnValue('getProjectManager', $projectManager);
        
        $this->assertTrue($urlVerification->userCanAccessPrivate(new MockUrl(), 'stuff'));
    }
    
    function testUserCanAccessPrivateShouldLetUserPassWhenUserIsMemberOfPrivateProject() {
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getProjectManager', 'getCurrentUser'));
        $GLOBALS['group_id'] = 120;
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isPublic', false);
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project, array(120));
        $urlVerification->setReturnValue('getProjectManager', $projectManager);
        $user = new MockUser();
        $user->setReturnValue('isMember', true, array(120));
        $urlVerification->setReturnValue('getCurrentUser', $user);
        
        $this->assertTrue($urlVerification->userCanAccessPrivate(new MockUrl(), 'stuff'));
    }
    
    function testUserCanAccessPrivateShouldBlockWhenUserIsNotMemberOfPrivateProject() {
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getProjectManager', 'getCurrentUser'));
        $GLOBALS['group_id'] = 120;
        $project = new MockProject();
        $project->setReturnValue('isError', false);
        $project->setReturnValue('isPublic', false);
        $projectManager = new MockProjectManager();
        $projectManager->setReturnValue('getProject', $project, array(120));
        $urlVerification->setReturnValue('getProjectManager', $projectManager);
        $user = new MockUser();
        $user->setReturnValue('isMember', false);
        $urlVerification->setReturnValue('getCurrentUser', $user);
        
        $this->assertFalse($urlVerification->userCanAccessPrivate(new MockUrl(), 'stuff'));
    }
}

?>