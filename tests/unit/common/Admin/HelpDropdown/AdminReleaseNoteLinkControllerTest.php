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
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\HelpDropdown\ReleaseNoteManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

final class AdminReleaseNoteLinkControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&ReleaseNoteManager
     */
    private $release_note_manager;
    private AdminReleaseNoteLinkController $admin_release_note_controller;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&BaseLayout
     */
    private $layout;
    private PFUser $admin_user;

    protected function setUp(): void
    {
        $this->admin_user = UserTestBuilder::buildSiteAdministrator();

        $this->layout               = $this->createMock(BaseLayout::class);
        $this->admin_page_renderer  = $this->createMock(AdminPageRenderer::class);
        $this->release_note_manager = $this->createMock(ReleaseNoteManager::class);
        $csrf_token                 = $this->createMock(CSRFSynchronizerToken::class);

        $this->admin_release_note_controller = new AdminReleaseNoteLinkController(
            $this->admin_page_renderer,
            $this->release_note_manager,
            $csrf_token,
            '11.18'
        );

        $csrf_token->method('getTokenName')->willReturn('challenge');
        $csrf_token->method('getToken')->willReturn('token');
    }

    public function testItDisplaysTheAdminPage(): void
    {
        $request = $this->createMock(HTTPRequest::class);
        $request->method('getCurrentUser')->willReturn($this->admin_user);

        $this->release_note_manager->expects(self::atLeastOnce())->method("getReleaseNoteLink");
        $this->admin_page_renderer->method("renderANoFramedPresenter");

        $this->admin_release_note_controller->process($request, $this->layout, []);
    }

    public function testNoSiteAdminUserIsNotAllowed(): void
    {
        $user = UserTestBuilder::anActiveUser()->withoutSiteAdministrator()->build();

        $request = $this->createMock(HTTPRequest::class);
        $request->method('getCurrentUser')->willReturn($user);

        $this->expectException(ForbiddenException::class);

        $this->release_note_manager->method("getReleaseNoteLink");
        $this->admin_page_renderer->method("renderANoFramedPresenter");

        $this->admin_release_note_controller->process($request, $this->layout, []);
    }
}
