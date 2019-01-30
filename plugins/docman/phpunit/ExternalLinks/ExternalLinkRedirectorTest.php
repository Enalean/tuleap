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
use PFUser;
use PHPUnit\Framework\TestCase;
use Project;

class ExternalLinkRedirectorTest extends TestCase
{
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

    protected function setUp()
    {
        parent::setUp();

        $this->user    = Mockery::mock(PFUser::class);
        $this->request = Mockery::mock(\HTTPRequest::class);
        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getId')->andReturn(102);

        $this->request->shouldReceive("getProject")->andReturn($this->project);
    }

    public function testItShouldNotRedirectUserIfItsPreferenceIsLegacyUI()
    {
        $folder_id = 10;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);
        $this->user->shouldReceive('getPreference')->with("plugin_docman_display_new_ui_102")->andReturn(false);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        $this->assertFalse($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldRedirectUserIfItsPreferenceIsNewUI()
    {
        $folder_id = 10;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);
        $this->user->shouldReceive('getPreference')->with("plugin_docman_display_new_ui_102")->andReturn(true);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        $this->assertTrue($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldDoNothingIfUserIsAnonymous()
    {
        $folder_id = 10;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(false);
        $this->user->shouldReceive('isAnonymous')->andReturn(true);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        $this->user->shouldReceive('getPreference')->never();
        $this->assertFalse($redirector->shouldRedirectUserOnNewUI());
    }

    public function testItShouldNotRedirectWhenUserPreferenceIsForNewDocmanAndRequestIsForDocmanAdministrationUI()
    {
        $folder_id = 10;
        $redirector = new ExternalLinkRedirector($this->user, $this->request, $folder_id);

        $this->request->shouldReceive("exist")->with("action")->andReturn(true);
        $this->user->shouldReceive('isAnonymous')->andReturn(false);

        $this->user->shouldReceive('getPreference')->with("plugin_docman_display_new_ui_102")->andReturn(true);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        $this->assertFalse($redirector->shouldRedirectUserOnNewUI());
    }
}
