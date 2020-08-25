<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
 *
 */
declare(strict_types=1);

namespace Tuleap\admin\HelpDropdown;

use CSRFSynchronizerToken;
use HTTPRequest;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\HelpDropdown\ReleaseNoteCustomLinkUpdater;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;

class PostAdminReleaseNoteLinkControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var PostAdminReleaseNoteLinkController
     */
    private $post_admin_release_note_controller;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReleaseNoteCustomLinkUpdater
     */
    private $custom_link_updater;

    /**
     * @var CSRFSynchronizerToken|Mockery\LegacyMockInterface|Mockery\MockInterface
     */
    private $csrf_token;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|PFUser
     */
    private $user;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->custom_link_updater = Mockery::mock(ReleaseNoteCustomLinkUpdater::class);
        $this->csrf_token          = Mockery::mock(CSRFSynchronizerToken::class);

        $this->post_admin_release_note_controller = new PostAdminReleaseNoteLinkController(
            $this->custom_link_updater,
            $this->csrf_token,
            '11.18'
        );

        $this->user   = Mockery::mock(PFUser::class);
        $this->layout = Mockery::mock(BaseLayout::class);
    }

    public function testItUpdatesTheLink()
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);
        $request->shouldReceive('get')->with('url')->once()->andReturn("https://example.com");

        $this->user->shouldReceive('isSuperUser')->andReturnTrue();

        $this->csrf_token->shouldReceive("check");
        $this->custom_link_updater->shouldReceive("updateReleaseNoteLink");

        $this->layout->shouldReceive('addFeedback')->once();
        $this->layout->shouldReceive('redirect')->once();

        $this->post_admin_release_note_controller->process($request, $this->layout, []);
    }

    public function testNoSiteAdminUserIsNotAllowed()
    {
        $this->user->shouldReceive('isSuperUser')->andReturnFalse();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->user);

        $this->expectException(ForbiddenException::class);

        $this->csrf_token->shouldNotReceive("check");
        $this->custom_link_updater->shouldNotReceive("updateReleaseNoteLink");

        $this->layout->shouldNotReceive('addFeedback');
        $this->layout->shouldNotReceive('redirect');

        $this->post_admin_release_note_controller->process($request, $this->layout, []);
    }
}
