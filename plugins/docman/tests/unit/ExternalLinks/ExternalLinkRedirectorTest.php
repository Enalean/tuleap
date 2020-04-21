<?php
/**
 * Copyright (c) Enalean, 2019. All Rights Reserved.
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

namespace Tuleap\Docman\ExternalLinks;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;

class ExternalLinkRedirectorTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Project
     */
    private $project;
    /**
     * @var PFUser
     */
    private $user;

    /**
     * @var /HTTPRequest
     */
    private $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user    = Mockery::mock(PFUser::class);
        $this->request = Mockery::mock(\HTTPRequest::class);
        $this->project = Mockery::mock(Project::class);

        $this->request->shouldReceive("getProject")->andReturn($this->project);
    }

    public function testItShouldDoNothingIfUserIsAnonymous(): void
    {
        $folder_id = 10;
        $root_folder_id = 3;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id, $root_folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->user->shouldReceive('isAnonymous')->andReturn(true);

        $redirector->checkAndStoreIfUserHasToBeenRedirected(true);
        $this->assertFalse($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldNotRedirectWhenUserPreferenceIsForNewDocmanAndRequestIsForDocmanAdministrationUI(): void
    {
        $folder_id = 10;
        $root_folder_id = 3;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id, $root_folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(true);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $redirector->checkAndStoreIfUserHasToBeenRedirected(true);
        $this->assertFalse($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldRedirectUserWhenShouldRedirectUserIsSetToTrue(): void
    {
        $folder_id = 10;
        $root_folder_id = 3;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id, $root_folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->request->shouldReceive("exist")->with("group_id")->andReturn(false);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $this->project->shouldReceive('getUnixNameLowerCase')->once()->andReturn("project-short-name");

        $redirector->checkAndStoreIfUserHasToBeenRedirected(true);
        $this->assertTrue($redirector->shouldRedirectUserOnNewUI());
        $this->assertEquals("/plugins/document/project-short-name/10", $redirector->getUrlRedirection());
    }

    public function testItShouldNotRedirectUserWhenShouldRedirectUserIsSetToFalse(): void
    {
        $folder_id = 10;
        $root_folder_id = 3;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id, $root_folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->request->shouldReceive("exist")->with("group_id")->andReturn(false);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $redirector->checkAndStoreIfUserHasToBeenRedirected(false);
        $this->assertFalse($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldStoreDocumentIdWhenUrlIsForAccessingToASpecificDocument(): void
    {
        $folder_id = 10;
        $root_folder_id = 3;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id, $root_folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->request->shouldReceive("exist")->with("group_id")->andReturn(102);
        $this->request->shouldReceive("exist")->with("id")->andReturn($folder_id);
        $this->request->shouldReceive("get")->with("id")->andReturn($folder_id);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $this->project->shouldReceive('getUnixNameLowerCase')->once()->andReturn("project-short-name");

        $redirector->checkAndStoreIfUserHasToBeenRedirected(true);

        $this->assertTrue($redirector->shouldRedirectUserOnNewUI());
        $this->assertEquals("/plugins/document/project-short-name/preview/10", $redirector->getUrlRedirection());
    }

    public function testItDoesNotUseUserPReferencyWhenUrlIsForAccessingToASpecificDocument(): void
    {
        $folder_id      = 10;
        $root_folder_id = 3;
        $redirector     = new ExternalLinkRedirector($this->user, $this->request, $folder_id, $root_folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->request->shouldReceive("exist")->with("group_id")->andReturn(102);
        $this->request->shouldReceive("exist")->with("id")->andReturn($folder_id);
        $this->request->shouldReceive("get")->with("id")->andReturn($folder_id);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $this->project->shouldReceive('getUnixNameLowerCase')->once()->andReturn("project-short-name");

        $redirector->checkAndStoreIfUserHasToBeenRedirected(false);

        $this->assertTrue($redirector->shouldRedirectUserOnNewUI());
        $this->assertEquals("/plugins/document/project-short-name/preview/10", $redirector->getUrlRedirection());
    }

    public function testItShouldStoreDocumentIdAndRedirectToRootWhenUrlIsForAccessingRootDocument(): void
    {
        $folder_id = 0;
        $root_folder_id = 3;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id, $root_folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->request->shouldReceive("exist")->with("group_id")->andReturn(102);
        $this->request->shouldReceive("exist")->with("id")->andReturn($root_folder_id);
        $this->request->shouldReceive("get")->with("id")->andReturn($root_folder_id);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $this->project->shouldReceive('getUnixNameLowerCase')->once()->andReturn("project-short-name");

        $redirector->checkAndStoreIfUserHasToBeenRedirected(true);

        $this->assertTrue($redirector->shouldRedirectUserOnNewUI());
        $this->assertEquals("/plugins/document/project-short-name/", $redirector->getUrlRedirection());
    }
}
