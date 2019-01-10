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

    protected function setUp()
    {
        parent::setUp();

        $this->user    = Mockery::mock(PFUser::class);
        $this->project = Mockery::mock(Project::class);
        $this->project->shouldReceive('getId')->andReturn(102);
    }

    public function testItShouldNotRedirectUserIfItsPreferenceIsLegacyUI()
    {
        $folder_id = 10;
        $redirector = new ExternalLinkRedirector($this->user, $this->project, $folder_id);

        $this->user->shouldReceive('getPreference')->with("plugin_docman_display_legacy_ui_102")->andReturn(true);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        $this->assertFalse($redirector->shouldRedirectUser());
    }

    public function testItShouldRedirectUserIfItsPreferenceIsNewUI()
    {
        $folder_id = 10;
        $redirector = new ExternalLinkRedirector($this->user, $this->project, $folder_id);

        $this->user->shouldReceive('getPreference')->with("plugin_docman_display_legacy_ui_102")->andReturn(false);

        $redirector->checkAndStoreIfUserHasToBeenRedirected();
        $this->assertTrue($redirector->shouldRedirectUser());
    }
}
