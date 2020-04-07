<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\ProjectAccessSuspendedException;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
class URLVerification_AssertValidUrlTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private $request;
    private $url_verification;

    public function setUp(): void
    {
        parent::setUp();

        $this->request = Mockery::mock(HTTPRequest::class);
        $GLOBALS['Language'] = Mockery::spy(BaseLanguage::class);

        $this->url_verification = Mockery::mock(URLVerification::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $this->url_verification->shouldReceive('verifyProtocol')->andReturn(false);
        $this->url_verification->shouldReceive('verifyRequest')->andReturn(false);
        $this->url_verification->shouldReceive('getCurrentUser')->andReturn(Mockery::spy(PFUser::class));
    }

    public function tearDown(): void
    {
        parent::tearDown();

        unset($GLOBALS['group_id']);
        unset($GLOBALS['Language']);
    }

    public function testAssertValidUrlWithException()
    {
        $this->url_verification->shouldReceive('isException')->andReturn(true);

        $this->url_verification->shouldReceive('header')->never();

        $this->url_verification->assertValidUrl([], $this->request);
    }

    public function testAssertValidUrlWithNoRedirection()
    {
        $this->url_verification->shouldReceive('getUrl')->andReturn(Mockery::spy(URL::class));
        $this->url_verification->shouldReceive('isException')->andReturn(false);
        $this->url_verification->shouldReceive('getUrlChunks')->andReturn(null);
        $this->url_verification->shouldReceive('header')->never();

        $server = array(
            'REQUEST_URI' => '/'
        );
        $this->url_verification->assertValidUrl($server, $this->request);
    }

    public function testAssertValidUrlWithRedirection()
    {
        $this->url_verification->shouldReceive('getUrl')->andReturn(Mockery::spy(URL::class));

        $this->url_verification->shouldReceive('isException')->andReturn(false);
        $this->url_verification->shouldReceive('getUrlChunks')->andReturn(array('protocol' => 'https', 'host' => 'secure.example.com'));
        $this->url_verification->shouldReceive('header')->once();

        $server = array(
            'REQUEST_URI' => '/'
        );
        $this->url_verification->assertValidUrl($server, $this->request);
    }

    public function testCheckNotActiveProjectApi()
    {
        $this->url_verification->shouldReceive('exitError')->never();
        $this->url_verification->shouldReceive('displayRestrictedUserProjectError')->never();
        $this->url_verification->shouldReceive('displayPrivateProjectError')->never();

        $this->url_verification->assertValidUrl(array('SCRIPT_NAME' => '/api/'), $this->request);
    }

    public function testCheckNotActiveAndNotSuspendedProjectError()
    {
        $GLOBALS['group_id'] = 1;
        $project = Mockery::mock(Project::class, ['getStatus' => 'S']);
        $project_manager = Mockery::mock(ProjectManager::class);
        $project_manager->shouldReceive('getProject')->andReturn($project);
        $this->url_verification->shouldReceive('getProjectManager')->andReturn($project_manager);
        $this->url_verification->shouldReceive('userCanAccessProject')->andThrow(new Project_AccessDeletedException($project));

        $this->url_verification->shouldReceive('exitError')->once();
        $this->url_verification->shouldReceive('displayRestrictedUserProjectError')->never();
        $this->url_verification->shouldReceive('displayPrivateProjectError')->never();

        $this->url_verification->assertValidUrl(array('SCRIPT_NAME' => '/some_service/?group_id=1', 'REQUEST_URI' => '/some_service/?group_id=1'), $this->request);
    }

    public function testCheckNotActiveBecauseSuspendedProjectError()
    {
        $GLOBALS['group_id'] = 1;
        $project = Mockery::mock(Project::class, ['isActive' => false, 'isSuspended' => true, 'isPublic' => true, 'isError' => false, 'getStatus' => 'H']);
        $project_manager = Mockery::mock(ProjectManager::class);
        $project_manager->shouldReceive('getProject')->andReturn($project);
        $this->url_verification->shouldReceive('getProjectManager')->andReturn($project_manager);
        $this->url_verification->shouldReceive('userCanAccessProject')->andThrow(new ProjectAccessSuspendedException($project));

        $this->url_verification->shouldReceive('displaySuspendedProjectError')->once();

        $this->url_verification->assertValidUrl(array('SCRIPT_NAME' => '/some_service/?group_id=1', 'REQUEST_URI' => '/some_service/?group_id=1'), $this->request);
    }

    public function testCheckActiveProjectNoError()
    {
        $GLOBALS['group_id'] = 1;
        $project = Mockery::mock(Project::class, ['isActive' => true, 'isPublic' => true, 'isError' => false, 'getID' => 101]);
        $project_manager = Mockery::mock(ProjectManager::class);
        $project_manager->shouldReceive('getProject')->andReturn($project);
        $this->url_verification->shouldReceive('getProjectManager')->andReturn($project_manager);
        $this->url_verification->shouldReceive('userCanAccessProject');

        $this->url_verification->shouldReceive('exitError')->never();
        $this->url_verification->shouldReceive('displayRestrictedUserProjectError')->never();
        $this->url_verification->shouldReceive('displayPrivateProjectError')->never();

        $this->url_verification->assertValidUrl(array('SCRIPT_NAME' => '/some_service/?group_id=1', 'REQUEST_URI' => '/some_service/?group_id=1'), $this->request);
    }

    public function testUserCanAccessPrivateShouldLetUserPassWhenNotInAProject()
    {
        $this->url_verification->shouldReceive('getUrl')->andReturn(Mockery::spy(URL::class));

        $this->url_verification->shouldReceive('exitError')->never();
        $this->url_verification->shouldReceive('displayRestrictedUserProjectError')->never();
        $this->url_verification->shouldReceive('displayPrivateProjectError')->never();

        $this->url_verification->assertValidUrl(array('SCRIPT_NAME' => '/stuff', 'REQUEST_URI' => '/stuff'), $this->request);
    }

    public function testNoGroupIdFallsbackOnUserAccessCheck()
    {
        $this->url_verification->shouldReceive('getUrl')->andReturn(Mockery::mock(URL::class, ['getGroupIdFromUrl' => false]));

        $this->url_verification->shouldReceive('checkRestrictedAccess')->once();

        $this->url_verification->assertValidUrl(array('SCRIPT_NAME' => '/stuff', 'REQUEST_URI' => '/stuff'), $this->request);
    }

    public function testGiveAProjectByPassUrlAnalyis()
    {
        $project = Mockery::mock(Project::class);

        $this->url_verification->shouldReceive('userCanAccessProject')->with(Mockery::any(), $project)->once();

        $this->url_verification->assertValidUrl(array('SCRIPT_NAME' => '/stuff', 'REQUEST_URI' => '/stuff'), $this->request, $project);
    }
}
