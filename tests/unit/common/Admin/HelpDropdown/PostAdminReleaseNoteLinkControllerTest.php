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
use PFUser;
use Tuleap\HelpDropdown\ReleaseNoteCustomLinkUpdater;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

final class PostAdminReleaseNoteLinkControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private PostAdminReleaseNoteLinkController $post_admin_release_note_controller;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReleaseNoteCustomLinkUpdater
     */
    private $custom_link_updater;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&CSRFSynchronizerToken
     */
    private $csrf_token;

    private PFUser $user;

    private PFUser $admin_user;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BaseLayout
     */
    private $layout;

    protected function setUp(): void
    {
        parent::setUp();

        $this->custom_link_updater = $this->createMock(ReleaseNoteCustomLinkUpdater::class);
        $this->csrf_token          = $this->createMock(CSRFSynchronizerToken::class);

        $this->post_admin_release_note_controller = new PostAdminReleaseNoteLinkController(
            $this->custom_link_updater,
            $this->csrf_token,
            '11.18'
        );

        $this->user       = UserTestBuilder::anActiveUser()->withoutSiteAdministrator()->build();
        $this->admin_user = UserTestBuilder::buildSiteAdministrator();
        $this->layout     = $this->createMock(BaseLayout::class);
    }

    public function testItUpdatesTheLink(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $request->method('getCurrentUser')->willReturn($this->admin_user);
        $request->expects(self::once())->method('get')->with('url')->willReturn("https://example.com");

        $this->csrf_token->method("check");
        $this->custom_link_updater->method("updateReleaseNoteLink");

        $this->layout->expects(self::once())->method('addFeedback');
        $this->layout->expects(self::once())->method('redirect');

        $this->post_admin_release_note_controller->process($request, $this->layout, []);
    }

    public function testNoSiteAdminUserIsNotAllowed(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $request->method('getCurrentUser')->willReturn($this->user);

        $this->expectException(ForbiddenException::class);

        $this->csrf_token->expects(self::never())->method("check");
        $this->custom_link_updater->expects(self::never())->method("updateReleaseNoteLink");

        $this->layout->expects(self::never())->method('addFeedback');
        $this->layout->expects(self::never())->method('redirect');

        $this->post_admin_release_note_controller->process($request, $this->layout, []);
    }
}
