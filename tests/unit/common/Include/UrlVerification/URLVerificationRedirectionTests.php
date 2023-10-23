<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 */

declare(strict_types=1);

namespace Tuleap;

use EventManager;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Plugin;
use Project_AccessRestrictedException;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessVerifier;
use Tuleap\Request\RestrictedUsersAreHandledByPluginEvent;
use URL;
use URLVerification;

class URLVerificationRedirectionTests extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\URLVerification
     */
    private $url_verification;
    /**
     * @var \HTTPRequest|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();
        $this->url_verification = \Mockery::mock(
            \URLVerification::class . '[getUrlChunks, getProjectManager,userCanAccessProject,exitError]'
        )->makePartial();

        $this->request = \Mockery::spy(\HTTPRequest::class);
    }

    public function testGetRedirectionProtocolModified(): void
    {
        $server = [
            'HTTP_HOST'   => 'example.com',
            'REQUEST_URI' => '',
        ];
        $chunks = ['protocol' => 'https'];


        $this->url_verification->shouldReceive('getUrlChunks')->andReturns($chunks);

        $this->assertEquals($this->url_verification->getRedirectionURL($server), 'https://example.com');
    }

    public function testGetRedirectionProtocolAndHostModified(): void
    {
        $server = [
            'HTTP_HOST'   => 'test.example.com',
            'REQUEST_URI' => '/user.php',
        ];
        $chunks = ['protocol' => 'http', 'host' => 'secure.example.com'];

        $this->url_verification->shouldReceive('getUrlChunks')->andReturns($chunks);

        $this->assertEquals(
            'http://secure.example.com/user.php',
            $this->url_verification->getRedirectionURL($server)
        );
    }

    public function testGetRedirectionRequestModified(): void
    {
        $server = [
            'HTTP_HOST'   => 'secure.example.com',
            'REQUEST_URI' => '/user.php',
        ];
        $chunks = ['script' => '/project.php'];

        $this->url_verification->shouldReceive('getUrlChunks')->andReturns($chunks);

        $this->assertEquals('/project.php', $this->url_verification->getRedirectionURL($server));
    }

    public function testRestrictedUserCanAccessSearchOnTracker(): void
    {
        $this->url_verification = \Mockery::mock(\URLVerification::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $user = \Mockery::mock(\PFUser::class);
        $this->url_verification->shouldReceive('getCurrentUser')->andReturns($user);
        $user->shouldReceive('isRestricted')->andReturns(true);

        $server = [
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah',
        ];

        $url = Mockery::mock(URL::class);
        $this->url_verification->shouldReceive('getUrl')->andReturns($url);
        $this->url_verification->shouldReceive('restrictedUserCanAccessUrl')->with(
            $user,
            $url,
            '/search/',
            null
        )->andReturn(true)->once();
        $this->url_verification->shouldReceive('displayRestrictedUserError')->never();

        $this->url_verification->checkRestrictedAccess($server);
    }

    public function testRestrictedUserCanNotAccessSearchOnPeople(): void
    {
        $this->url_verification = \Mockery::mock(\URLVerification::class)
            ->makePartial()
            ->shouldAllowMockingProtectedMethods();

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturns(true);

        $this->url_verification->shouldReceive('getCurrentUser')->andReturns($user);
        $url = Mockery::mock(URL::class);
        $this->url_verification->shouldReceive('getUrl')->andReturns($url);
        $this->url_verification->shouldReceive('restrictedUserCanAccessUrl')->with(
            $user,
            $url,
            '/search/',
            null
        )->andReturn(false)->once();

        $server = [
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah',
        ];

        $this->url_verification->shouldReceive('displayRestrictedUserError')->once();

        $this->url_verification->checkRestrictedAccess($server);
    }

    public function testRestrictedUserCanNotAccessSearchOnLdapPeople(): void
    {
        $this->url_verification = \Mockery::mock(\URLVerification::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();

        $url = Mockery::mock(URL::class);
        $this->url_verification->shouldReceive('getUrl')->andReturns($url);

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturns(true);
        $this->url_verification->shouldReceive('getCurrentUser')->andReturns($user);

        $server = [
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah',
        ];

        $this->url_verification->shouldReceive('restrictedUserCanAccessUrl')->andReturn(false);
        $this->url_verification->shouldReceive('displayRestrictedUserError')->with($user)->once();

        $this->url_verification->checkRestrictedAccess($server);
    }

    public function testRestrictedUserCanNotAccessSearchOnSoftwareProjects(): void
    {
        $this->url_verification = \Mockery::mock(\URLVerification::class)->makePartial(
        )->shouldAllowMockingProtectedMethods();

        $this->url_verification->shouldReceive('getUrl')->andReturns(Mockery::mock(URL::class));

        $user = \Mockery::mock(\PFUser::class);
        $user->shouldReceive('isRestricted')->andReturns(true);
        $this->url_verification->shouldReceive('getCurrentUser')->andReturns($user);

        $server = [
            'REQUEST_URI' => '/search/',
            'SCRIPT_NAME' => 'blah',
        ];

        $this->url_verification->shouldReceive('restrictedUserCanAccessUrl')->andReturn(false);
        $this->url_verification->shouldReceive('displayRestrictedUserError')->once();

        $this->url_verification->checkRestrictedAccess($server);
    }

    public function testRestrictedUserCanAccessPluginManagedScripts(): void
    {
        $user = Mockery::spy(PFUser::class);

        $url_verification = Mockery::mock(URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $url = Mockery::mock(URL::class);
        $url->shouldReceive('getGroupIdFromUrl')->andReturn(101);

        $fake_plugin = new class extends Plugin
        {
            public function restrictedUsersAreHandledByPluginEvent(RestrictedUsersAreHandledByPluginEvent $event)
            {
                $event->setPluginHandleRestricted();
            }
        };

        $event_manager = new EventManager();
        $event_manager->addListener(
            RestrictedUsersAreHandledByPluginEvent::NAME,
            $fake_plugin,
            'restrictedUsersAreHandledByPluginEvent',
            false
        );

        $url_verification->shouldReceive('getEventManager')->andReturn($event_manager);

        $GLOBALS['Language']->shouldReceive('getContent')->andReturns(__DIR__ . '/_fixtures/empty.txt');

        $this->assertTrue($url_verification->restrictedUserCanAccessUrl($user, $url, '/blah'));
    }

    public function testRestrictedUserCanNotAccessForbiddenServiceInProjectWhichAllowsResticted(): void
    {
        $user = Mockery::Mock(PFUser::class);
        $user->shouldReceive('isRestricted')->andReturn(true);

        $project = \Mockery::spy(\Project::class);
        $project->shouldReceive('allowsRestricted')->andReturn(true);

        $verifier = Mockery::mock(RestrictedUserCanAccessVerifier::class);
        $verifier->shouldReceive('isRestrictedUserAllowedToAccess')->andReturn(false);

        $url_verification = Mockery::mock(URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();

        $GLOBALS['Language']->shouldReceive('getContent')->with(
            'include/restricted_user_permissions',
            'en_US'
        )->andReturns(__DIR__ . '/../../../../site-content/en_US/include/restricted_user_permissions.txt');

        $project->shouldReceive('isError')->andReturns(false);
        $project->shouldReceive('isActive')->andReturns(true);
        $project->shouldReceive('allowsRestricted')->andReturns(true);
        $user->shouldReceive('isSuperUser')->andReturns(false);
        $user->shouldReceive('isMember')->andReturns(false);
        $user->shouldReceive('isRestricted')->andReturns(true);

        $project_access_checker = Mockery::mock(ProjectAccessChecker::class);
        $project_access_checker->shouldReceive('checkUserCanAccessProject')->with($user, $project)->andThrow(
            Project_AccessRestrictedException::class
        );
        $url_verification->shouldReceive('getProjectAccessChecker')->andReturn($project_access_checker);


        $this->expectException(\Project_AccessRestrictedException::class);

        $url_verification->userCanAccessProject($user, $project);
    }
}
