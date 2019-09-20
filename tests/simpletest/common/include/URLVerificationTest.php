<?php
/**
 * Copyright (c) STMicroelectronics, 2010. All Rights Reserved.
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
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

use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;

Mock::generate('PFUser');

Mock::generatepartial(
    'URLVerification',
    'URLVerificationTestVersion2',
    array('getUrlChunks',
                            'getProjectManager',
                            'userCanAccessProject',
    'exitError')
);

class URLVerificationBaseTest extends TuleapTestCase
{

    protected $user_manager;
    protected $user;
    protected $request;

    public function setUp()
    {
        parent::setUp();
        ForgeConfig::store();
        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);

        $GLOBALS['Response'] = mock('Layout');
        ForgeConfig::set('sys_default_domain', 'default');
        ForgeConfig::set('sys_https_host', 'default');
        unset($GLOBALS['group_id']);

        $this->fixtures = dirname(__FILE__).'/_fixtures';
        $GLOBALS['Language'] = new MockBaseLanguage($this);

        $this->user = mock('PFUser');
        $this->user_manager = mock('UserManager');
        stub($this->user_manager)->getCurrentUser()->returns($this->user);

        UserManager::setInstance($this->user_manager);

        $this->request = mock('HTTPRequest');
        stub($this->request)->isSecure()->returns(true);
    }

    function tearDown()
    {
        UserManager::clearInstance();
        unset($GLOBALS['Language']);
        unset($GLOBALS['Response']);
        unset($GLOBALS['group_id']);
        unset($_REQUEST['type_of_search']);
        ForgeConfig::restore();
        parent::tearDown();
    }
}

class URLVerificationTest extends URLVerificationBaseTest
{

    function testIsScriptAllowedForAnonymous()
    {
        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));

        $em = Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with(Mockery::any(), Mockery::on(function (array $params) {
            $params['anonymous_allowed'] = false;
            return true;
        }));

        $urlVerification->setReturnValue('getEventManager', $em);
        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/account/login.php', 'SCRIPT_NAME' => '/account/login.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/account/register.php', 'SCRIPT_NAME' => '/account/register.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/account/change_pw.php', 'SCRIPT_NAME' => '/account/change_pw.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/include/check_pw.php', 'SCRIPT_NAME' => '/include/check_pw.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/account/lostpw.php', 'SCRIPT_NAME' => '/account/lostpw.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/account/lostlogin.php', 'SCRIPT_NAME' => '/account/lostlogin.php')));
        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/account/lostpw-confirm.php', 'SCRIPT_NAME' => '/account/lostpw-confirm.php')));

        $this->assertFalse($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/foobar', 'SCRIPT_NAME' => '/foobar')));
    }

    function itDoesNotTreatRegularUrlsAsExceptions()
    {
        $urlVerification = new URLVerification();
        $this->assertFalse($urlVerification->isException(array('SCRIPT_NAME' => '/projects/foobar')));
    }

    function itDoesNotTreatRegularUrlsWhichContainsSOAPAsExceptions()
    {
        $urlVerification = new URLVerification();
        $this->assertFalse($urlVerification->isException(array('SCRIPT_NAME' => '/projects/foobar/?p=/soap/index.php')));
    }

    function itDoesNotTreatRegularUrlsWhichContainsAPIAsExceptions()
    {
        $urlVerification = new URLVerification();
        $this->assertFalse($urlVerification->isException(array('SCRIPT_NAME' => '/projects/foobar/?p=/api/reference/extractCross')));
    }

    function itTreatsSOAPApiAsException()
    {
        $urlVerification = new URLVerification();
        $this->assertTrue($urlVerification->isException(array('SCRIPT_NAME' => '/soap/index.php')));
    }

    function itTreatsSOAPApiOfPluginsAsException()
    {
        $urlVerification = new URLVerification();
        $this->assertTrue($urlVerification->isException(array('SCRIPT_NAME' => '/plugins/docman/soap/index.php')));
    }

    function itTreatsExtractionOfCrossReferencesApiAsException()
    {
        $urlVerification = new URLVerification();
        $this->assertTrue($urlVerification->isException(array('SCRIPT_NAME' => '/api/reference/extractCross')));
    }

    function testIsScriptAllowedForAnonymousFromHook()
    {
        $urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        $em = Mockery::mock(EventManager::class);
        $em->shouldReceive('processEvent')->with(Mockery::any(), Mockery::on(function (array $params) {
            $params['anonymous_allowed'] = true;
            return true;
        }));
        $urlVerification->setReturnValue('getEventManager', $em);
        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $this->assertTrue($urlVerification->isScriptAllowedForAnonymous(array('REQUEST_URI' => '/foobar', 'SCRIPT_NAME' => '/foobar')));
    }

    public function testVerifyProtocolHTTPAndHTTPSIsAvailable()
    {
        $urlVerification = new URLVerification();

        $request = mock('HTTPRequest');
        stub($request)->isSecure()->returns(false);

        $urlVerification->verifyProtocol($request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['protocol'], 'https');
    }

    public function testVerifyProtocolHTTPSAndHTTPSIsAvailable()
    {
        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($this->request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertFalse(isset($chunks['protocol']));
    }

    public function testVerifyProtocolHTTPAndHTTPSIsNotAvailable()
    {
        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($this->request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertFalse(isset($chunks['protocol']));
    }

    public function testVerifyHostHTTPSAndHTTPSIsAvailable()
    {
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($this->request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertFalse(isset($chunks['host']));
    }

    public function testVerifyHostHTTPAndHTTPSIsAvailable()
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $request = mock('HTTPRequest');
        stub($request)->isSecure()->returns(false);

        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertEqual($chunks['host'], 'secure.example.com');
    }

    public function testVerifyHostInvalidHostAndHTTPSIsAvailable()
    {
        ForgeConfig::set('sys_default_domain', 'example.com');
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $urlVerification = new URLVerification();
        $urlVerification->verifyProtocol($this->request);
        $chunks = $urlVerification->getUrlChunks();
        $this->assertFalse(isset($chunks['host']));
    }
}

class URLVerification_WithAnonymousTest extends URLVerificationBaseTest
{

    private $urlVerification;
    private $em;
    private $overrider_manager;

    public function setUp()
    {
        parent::setUp();

        $this->em = \Mockery::spy(\EventManager::class);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $this->overrider_manager = mock('PermissionsOverrider_PermissionsOverriderManager');
        stub($this->overrider_manager)->doesOverriderAllowUserToAccessPlatform()->returns(false);

        $this->urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager', 'getPermissionsOverriderManager'));
        stub($this->urlVerification)->getEventManager()->returns($this->em);
        stub($this->urlVerification)->getCurrentUser()->returns($this->user);
        stub($this->urlVerification)->getPermissionsOverriderManager()->returns($this->overrider_manager);
    }

    function testVerifyRequestAnonymousWhenScriptException()
    {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '/account/login.php');
        stub($this->user)->isAnonymous()->returns(true);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertFalse(isset($chunks['script']));
    }

    function testVerifyRequestAnonymousWhenAllowed()
    {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '');
        stub($this->user)->isAnonymous()->returns(true);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertFalse(isset($chunks['script']));
    }

    function testVerifyRequestAuthenticatedWhenAnonymousAllowed()
    {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '');
        stub($this->user)->isAnonymous()->returns(false);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertFalse(isset($chunks['script']));
    }

    function testVerifyRequestAnonymousWhenNotAllowedAtRoot()
    {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/');
        stub($this->user)->isAnonymous()->returns(true);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fmy%2F');
    }

    function testVerifyRequestAnonymousWhenNotAllowedWithScript()
    {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/script/');
        stub($this->user)->isAnonymous()->returns(true);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fscript%2F');
    }

    function testVerifyRequestAnonymousWhenNotAllowedWithLightView()
    {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '',
                        'REQUEST_URI' => '/script?pv=2');
        stub($this->user)->isAnonymous()->returns(true);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        ForgeConfig::set('sys_https_host', 'secure.example.com');

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertEqual($chunks['script'], '/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2');
    }

    function testVerifyRequestAuthenticatedWhenAnonymousNotAllowed()
    {
        $server = array('SERVER_NAME' => 'example.com',
                        'SCRIPT_NAME' => '');
        stub($this->user)->isAnonymous()->returns(false);

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);

        $this->urlVerification->verifyRequest($server);
        $chunks = $this->urlVerification->getUrlChunks();

        $this->assertFalse(isset($chunks['script']));
    }
}

class URLVerification_RedirectionTests extends URLVerificationBaseTest
{

    function testGetRedirectionProtocolModified()
    {
        $server = array('HTTP_HOST' => 'example.com',
                        'REQUEST_URI' => '');
        $chunks =  array('protocol'=> 'https');

        $urlVerification = new URLVerificationTestVersion2($this);

        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($this->request, $server), 'https://example.com');
    }

    function testGetRedirectionProtocolAndHostModified()
    {
        $server = array('HTTP_HOST' => 'test.example.com',
                        'REQUEST_URI' => '/user.php');
        $chunks =  array('protocol'=> 'http', 'host' =>'secure.example.com');

        $urlVerification = new URLVerificationTestVersion2($this);

        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($this->request, $server), 'http://secure.example.com/user.php');
    }

    function testGetRedirectionRequestModified()
    {
        $server = array('HTTP_HOST' => 'secure.example.com',
                        'REQUEST_URI' => '/user.php');
        $chunks =  array('script'=> '/project.php');

        $urlVerification = new URLVerificationTestVersion2($this);

        $urlVerification->setReturnValue('getUrlChunks', $chunks);

        $this->assertEqual($urlVerification->getRedirectionURL($this->request, $server), '/project.php');
    }

    function testRestrictedUserCanAccessSearchOnTracker()
    {
        $_REQUEST['type_of_search'] = 'tracker';
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getCurrentUser', 'displayRestrictedUserProjectError'));
        $GLOBALS['group_id'] = 120;

        $user = new MockPFUser();
        $user->setReturnValue('isRestricted', true);
        $urlVerification->setReturnValue('getCurrentUser', $user);

        $server = array(
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah'
        );

        stub($urlVerification)->displayRestrictedUserProjectError()->never();
        stub($GLOBALS['Language'])->getContent()->returns(dirname(__FILE__) . '/_fixtures/empty.txt');

        $urlVerification->checkRestrictedAccess($server, 'stuff');
    }

    function testRestrictedUserCanNotAccessSearchOnPeople()
    {
        $_REQUEST['type_of_search'] = 'people';

        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $GLOBALS['group_id'] = 120;

        $urlVerification->setReturnValue('getUrl', Mockery::mock(URL::class));

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

    function testRestrictedUserCanNotAccessSearchOnLdapPeople()
    {
        $_REQUEST['type_of_search'] = 'people_ldap';
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $GLOBALS['group_id'] = 120;

        $urlVerification->setReturnValue('getUrl', Mockery::mock(URL::class));

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

    function testRestrictedUserCanNotAccessSearchOnSoftwareProjects()
    {
        $_REQUEST['type_of_search'] = 'soft';
        $urlVerification = TestHelper::getPartialMock('URLVerification', array('getUrl', 'getCurrentUser', 'displayRestrictedUserError'));
        $GLOBALS['group_id'] = 120;

        $urlVerification->setReturnValue('getUrl', Mockery::mock(URL::class));

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

    public function testRestrictedUserCanAccessPluginManagedScripts()
    {
        $user = Mockery::spy(PFUser::class);

        $url_verification = Mockery::mock(URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $url = Mockery::mock(URL::class);
        $url->shouldReceive('getGroupIdFromUrl')->andReturn(101);

        $fake_plugin = new class {
            public function restrictedUsersAreHandledByPluginEvent(RestrictedUsersAreHandledByPluginEvent $event)
            {
                $event->setPluginHandleRestricted();
            }
        };

        $event_manager = new EventManager();
        $event_manager->addListener(RestrictedUsersAreHandledByPluginEvent::NAME, $fake_plugin, 'restrictedUsersAreHandledByPluginEvent', false);

        $url_verification->shouldReceive('getEventManager')->andReturn($event_manager);

        stub($GLOBALS['Language'])->getContent()->returns(__DIR__ . '/_fixtures/empty.txt');

        $this->assertTrue($url_verification->restrictedUserCanAccessUrl($user, $url, '/blah'));
    }

    public function testRestrictedUserCanNotAccessForbiddenServiceInProjectWhichAllowsResticted()
    {
        $user             = new MockPFUser();
        $project          = mock('Project');
        $url_verification = new URLVerification();

        stub($GLOBALS['Language'])
            ->getContent('include/restricted_user_permissions', 'en_US')
            ->returns(__DIR__ . '/../../../../site-content/en_US/include/restricted_user_permissions.txt');

        stub($project)->isError()->returns(false);
        stub($project)->isActive()->returns(true);
        stub($project)->allowsRestricted()->returns(true);
        stub($user)->isSuperUser()->returns(false);
        stub($user)->isMember()->returns(false);
        stub($user)->isRestricted()->returns(true);

        $this->expectException('Project_AccessRestrictedException');

        $request_uri = null;
        if (isset($_SERVER['REQUEST_URI'])) {
            $request_uri = $_SERVER['REQUEST_URI'];
        } else {
            $_SERVER['REQUEST_URI'] = '/';
        }
        $url_verification->userCanAccessProject($user, $project);
        if ($request_uri) {
            $_SERVER['REQUEST_URI'] = $request_uri;
        } else {
            unset($_SERVER['REQUEST_URI']);
        }
    }
}

class URLVerification_PrivateRestrictedTest extends TuleapTestCase
{

    private $url_verification;
    private $user;
    private $project;

    public function setUp()
    {
        parent::setUp();
        $this->url_verification = new URLVerification();
        $this->user             = mock('PFUser');
        $this->project          = mock('Project');

        ForgeConfig::store();
        ForgeConfig::set('sys_default_domain', 'default');
        ForgeConfig::set('sys_https_host', 'default');
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        parent::tearDown();
    }

    public function itChecksUriInternal()
    {
        $this->assertFalse($this->url_verification->isInternal('http://evil.example.com/'));
        $this->assertFalse($this->url_verification->isInternal('https://evil.example.com/'));
        $this->assertFalse($this->url_verification->isInternal('javascript:alert(1)'));
        $this->assertTrue($this->url_verification->isInternal('/path/to/feature'));
        $this->assertFalse(
            $this->url_verification->isInternal('http://' . ForgeConfig::get('sys_default_domain') . '/smthing')
        );
        $this->assertFalse(
            $this->url_verification->isInternal('https://' . ForgeConfig::get('sys_https_host') . '/smthing')
        );

        $this->assertFalse($this->url_verification->isInternal('//example.com'));
        $this->assertFalse($this->url_verification->isInternal('/\example.com'));
        $this->assertFalse($this->url_verification->isInternal(
            'https://' . ForgeConfig::get('sys_https_host') . '@evil.example.com'
        ));
    }
}

class URLVerification_PermissionsOverriderTest extends URLVerificationBaseTest
{

    protected $urlVerification;
    protected $event_manager;
    protected $overrider_manager;
    protected $server;

    public function setUp()
    {
        parent::setUp();

        ForgeConfig::store();

        $this->event_manager     = \Mockery::spy(\EventManager::class);
        $this->overrider_manager = mock('PermissionsOverrider_PermissionsOverriderManager');

        $this->urlVerification = partial_mock('URLVerification', array('getCurrentUser', 'getEventManager'));
        stub($this->urlVerification)->getEventManager()->returns($this->event_manager);
        stub($this->urlVerification)->getCurrentUser()->returns($this->user);
        PermissionsOverrider_PermissionsOverriderManager::setInstance($this->overrider_manager);

        $GLOBALS['Language']->setReturnValue('getContent', $this->fixtures.'/empty.txt');

        $this->server = array('SERVER_NAME' => 'example.com');
    }

    public function tearDown()
    {
        ForgeConfig::restore();
        PermissionsOverrider_PermissionsOverriderManager::clearInstance();
        parent::tearDown();
    }

    protected function getScriptChunk()
    {
        $this->urlVerification->verifyRequest($this->server);
        $chunks = $this->urlVerification->getUrlChunks();
        return $chunks['script'] ?? null;
    }
}

class URLVerification_PermissionsOverrider_AnonymousPlatformAndNoOverriderTest extends URLVerification_PermissionsOverriderTest
{

    public function setUp()
    {
        parent::setUp();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::ANONYMOUS);
        stub($this->overrider_manager)->doesOverriderAllowUserToAccessPlatform()->returns(false);
    }

    function itLetAnonymousAccessLogin()
    {
        $this->server['SCRIPT_NAME'] = '/account/login.php';
        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), null);
    }

    function itLetAuthenticatedAccessPages()
    {
        $this->server['SCRIPT_NAME'] = '';
        stub($this->user)->isAnonymous()->returns(false);

        $this->assertEqual($this->getScriptChunk(), null);
    }
}

class URLVerification_PermissionsOverrider_RegularPlatformAndNoOverriderTest extends URLVerification_PermissionsOverriderTest
{

    public function setUp()
    {
        parent::setUp();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::REGULAR);
        stub($this->overrider_manager)->doesOverriderAllowUserToAccessPlatform()->returns(false);
    }

    function itForceAnonymousToLoginToAccessRoot()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fmy%2F');
    }

    function itForceAnonymousToLoginToAccessScript()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script/';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fscript%2F');
    }

    function itForceAnonymousToLoginToAccessScriptInLightView()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script?pv=2';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2');
    }
}

class URLVerification_PermissionsOverrider_RestrictedPlatformAndNoOverriderTest extends URLVerification_PermissionsOverriderTest
{

    public function setUp()
    {
        parent::setUp();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->overrider_manager)->doesOverriderAllowUserToAccessPlatform()->returns(false);
    }

    function itForceAnonymousToLoginToAccessRoot()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fmy%2F');
    }

    function itForceAnonymousToLoginToAccessScript()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script/';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fscript%2F');
    }

    function itForceAnonymousToLoginToAccessScriptInLightView()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script?pv=2';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2');
    }
}

// Bug when platform use forceAnonymous & reverse proxy...
class URLVerification_PermissionsOverrider_RestrictedPlatformAndOverriderForceAnonymousTest extends URLVerification_PermissionsOverriderTest
{

    public function setUp()
    {
        parent::setUp();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->overrider_manager)->doesOverriderAllowUserToAccessPlatform()->returns(false);
        stub($this->overrider_manager)->doesOverriderForceUsageOfAnonymous()->returns(true);
    }

    function itForceAnonymousToLoginToAccessRoot()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fmy%2F');
    }

    function itForceAnonymousToLoginToAccessScript()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script/';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fscript%2F');
    }

    function itForceAnonymousToLoginToAccessScriptInLightView()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script?pv=2';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), '/account/login.php?return_to=%2Fscript%3Fpv%3D2&pv=2');
    }
}

// follow-up of URLVerification_PermissionsOverrider_RestrictedPlatformAndOverriderForceAnonymousTest but
// PermissionOverrider enter in action
class URLVerification_PermissionsOverrider_RestrictedPlatformAndOverriderForceAnonymousButOverriderForceGrantTest extends URLVerification_PermissionsOverriderTest
{

    public function setUp()
    {
        parent::setUp();

        ForgeConfig::set(ForgeAccess::CONFIG, ForgeAccess::RESTRICTED);
        stub($this->overrider_manager)->doesOverriderAllowUserToAccessPlatform()->returns(true);
        stub($this->overrider_manager)->doesOverriderForceUsageOfAnonymous()->returns(true);
    }

    function itLetAnonymousAccessLogin()
    {
        $this->server['SCRIPT_NAME'] = '/account/login.php';
        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), null);
    }

    function itLetAuthenticatedAccessPages()
    {
        $this->server['SCRIPT_NAME'] = '';
        stub($this->user)->isAnonymous()->returns(false);

        $this->assertEqual($this->getScriptChunk(), null);
    }

    function itLetAnonymousAccessRoot()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), null);
    }

    function itLetAnonymousAccessScript()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script/';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), null);
    }

    function itLetAnonymousAccessScriptInLightView()
    {
        $this->server['SCRIPT_NAME'] = '';
        $this->server['REQUEST_URI'] = '/script?pv=2';

        stub($this->user)->isAnonymous()->returns(true);

        $this->assertEqual($this->getScriptChunk(), null);
    }
}
