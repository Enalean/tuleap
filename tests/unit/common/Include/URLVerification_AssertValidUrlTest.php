<?php
/**
 * Copyright (c) Enalean, 2018 - Present. All Rights Reserved.
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

use Tuleap\Project\ProjectAccessSuspendedException;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class URLVerification_AssertValidUrlTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private $request;
    private $url_verification;

    public function setUp(): void
    {
        parent::setUp();

        $this->request       = $this->createMock(HTTPRequest::class);
        $GLOBALS['Language'] = $this->createMock(BaseLanguage::class);
        $GLOBALS['Language']->method('getText');

        $this->url_verification = $this->createPartialMock(URLVerification::class, [
            'verifyRequest',
            'getCurrentUser',
            'isException',
            'header',
            'getUrl',
            'getUrlChunks',
            'exitError',
            'displayRestrictedUserProjectError',
            'displayPrivateProjectError',
            'getProjectManager',
            'userCanAccessProject',
            'displaySuspendedProjectError',
            'checkRestrictedAccess',
        ]);
        $this->url_verification->method('verifyRequest')->willReturn(false);
        $this->url_verification->method('getCurrentUser')->willReturn(
            \Tuleap\User\CurrentUserWithLoggedInInformation::fromLoggedInUser(\Tuleap\Test\Builders\UserTestBuilder::anActiveUser()->build())
        );
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['group_id']);
        unset($GLOBALS['Language']);
    }

    public function testAssertValidUrlWithException(): void
    {
        $this->url_verification->method('isException')->willReturn(true);

        $this->url_verification->expects(self::never())->method('header');

        $this->url_verification->assertValidUrl([], $this->request);
    }

    public function testAssertValidUrlWithNoRedirection(): void
    {
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl');
        $this->url_verification->method('getUrl')->willReturn($url);
        $this->url_verification->method('isException')->willReturn(false);
        $this->url_verification->method('getUrlChunks')->willReturn(null);
        $this->url_verification->expects(self::never())->method('header');
        $this->url_verification->method('checkRestrictedAccess');

        $server = [
            'REQUEST_URI' => '/',
        ];
        $this->url_verification->assertValidUrl($server, $this->request);
    }

    public function testAssertValidUrlWithRedirection(): void
    {
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl');
        $this->url_verification->method('getUrl')->willReturn($url);

        $this->url_verification->method('isException')->willReturn(false);
        $this->url_verification->method('getUrlChunks')->willReturn(['protocol' => 'https', 'host' => 'secure.example.com']);
        $this->url_verification->expects(self::once())->method('header');
        $this->url_verification->method('checkRestrictedAccess');

        $server = [
            'REQUEST_URI' => '/',
        ];
        $this->url_verification->assertValidUrl($server, $this->request);
    }

    public function testCheckNotActiveProjectApi(): void
    {
        $this->url_verification->expects(self::never())->method('exitError');
        $this->url_verification->expects(self::never())->method('displayRestrictedUserProjectError');
        $this->url_verification->expects(self::never())->method('displayPrivateProjectError');
        $this->url_verification->method('isException');
        $this->url_verification->method('getUrlChunks');
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl');
        $this->url_verification->method('getUrl')->willReturn($url);
        $this->url_verification->method('checkRestrictedAccess');
        $this->url_verification->method('checkRestrictedAccess');

        $this->url_verification->assertValidUrl(['SCRIPT_NAME' => '/api/', 'REQUEST_URI' => ''], $this->request);
    }

    public function testCheckNotActiveAndNotSuspendedProjectError(): void
    {
        $GLOBALS['group_id'] = 1;
        $project             = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withStatusSuspended()->build();
        $project_manager     = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->willReturn($project);
        $this->url_verification->method('getProjectManager')->willReturn($project_manager);
        $this->url_verification->method('userCanAccessProject')->willThrowException(new Project_AccessDeletedException());

        $this->url_verification->expects(self::once())->method('exitError');
        $this->url_verification->expects(self::never())->method('displayRestrictedUserProjectError');
        $this->url_verification->expects(self::never())->method('displayPrivateProjectError');
        $this->url_verification->method('isException');
        $this->url_verification->method('getUrlChunks');
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl');
        $this->url_verification->method('getUrl')->willReturn($url);

        $this->url_verification->assertValidUrl(['SCRIPT_NAME' => '/some_service/?group_id=1', 'REQUEST_URI' => '/some_service/?group_id=1'], $this->request);
    }

    public function testCheckNotActiveBecauseSuspendedProjectError(): void
    {
        $GLOBALS['group_id'] = 1;
        $project             = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()
            ->withStatusSuspended()
            ->build();
        $project_manager     = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->willReturn($project);
        $this->url_verification->method('getProjectManager')->willReturn($project_manager);
        $this->url_verification->method('userCanAccessProject')->willThrowException(new ProjectAccessSuspendedException($project));

        $this->url_verification->expects(self::once())->method('displaySuspendedProjectError');
        $this->url_verification->method('isException');
        $this->url_verification->method('getUrlChunks');
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl');
        $this->url_verification->method('getUrl')->willReturn($url);

        $this->url_verification->assertValidUrl(['SCRIPT_NAME' => '/some_service/?group_id=1', 'REQUEST_URI' => '/some_service/?group_id=1'], $this->request);
    }

    public function testCheckActiveProjectNoError(): void
    {
        $GLOBALS['group_id'] = 1;
        $project             = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->withId(101)->build();
        $project_manager     = $this->createMock(ProjectManager::class);
        $project_manager->method('getProject')->willReturn($project);
        $this->url_verification->method('getProjectManager')->willReturn($project_manager);
        $this->url_verification->method('userCanAccessProject');

        $this->url_verification->expects(self::never())->method('exitError');
        $this->url_verification->expects(self::never())->method('displayRestrictedUserProjectError');
        $this->url_verification->expects(self::never())->method('displayPrivateProjectError');
        $this->url_verification->method('isException');
        $this->url_verification->method('getUrlChunks');
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl');
        $this->url_verification->method('getUrl')->willReturn($url);

        $this->url_verification->assertValidUrl(['SCRIPT_NAME' => '/some_service/?group_id=1', 'REQUEST_URI' => '/some_service/?group_id=1'], $this->request);
    }

    public function testUserCanAccessPrivateShouldLetUserPassWhenNotInAProject(): void
    {
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl');
        $this->url_verification->method('getUrl')->willReturn($url);

        $this->url_verification->expects(self::never())->method('exitError');
        $this->url_verification->expects(self::never())->method('displayRestrictedUserProjectError');
        $this->url_verification->expects(self::never())->method('displayPrivateProjectError');
        $this->url_verification->method('isException');
        $this->url_verification->method('getUrlChunks');
        $this->url_verification->method('checkRestrictedAccess');

        $this->url_verification->assertValidUrl(['SCRIPT_NAME' => '/stuff', 'REQUEST_URI' => '/stuff'], $this->request);
    }

    public function testNoGroupIdFallsbackOnUserAccessCheck(): void
    {
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl')->willReturn(false);
        $this->url_verification->method('getUrl')->willReturn($url);

        $this->url_verification->expects(self::once())->method('checkRestrictedAccess');
        $this->url_verification->method('isException');
        $this->url_verification->method('getUrlChunks');

        $this->url_verification->assertValidUrl(['SCRIPT_NAME' => '/stuff', 'REQUEST_URI' => '/stuff'], $this->request);
    }

    public function testGiveAProjectByPassUrlAnalysis(): void
    {
        $project = \Tuleap\Test\Builders\ProjectTestBuilder::aProject()->build();

        $this->url_verification->expects(self::once())->method('userCanAccessProject')->with(self::anything(), $project);
        $this->url_verification->method('isException');
        $this->url_verification->method('getUrlChunks');
        $url = $this->createMock(URL::class);
        $url->method('getGroupIdFromUrl');
        $this->url_verification->method('getUrl')->willReturn($url);

        $this->url_verification->assertValidUrl(['SCRIPT_NAME' => '/stuff', 'REQUEST_URI' => '/stuff'], $this->request, $project);
    }
}
