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
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\HelpDropdown\ReleaseNoteManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\UserTestBuilder;

class AdminReleaseNoteLinkControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|ReleaseNoteManager
     */
    private $release_note_manager;
    /**
     * @var AdminReleaseNoteLinkController
     */
    private $admin_release_note_controller;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|BaseLayout
     */
    private $layout;
    /**
     * @var UserTestBuilder
     */
    private $admin_user;

    protected function setUp(): void
    {
        $this->admin_user = Mockery::mock(PFUser::class);
        $this->admin_user->shouldReceive("isSuperUser")->andReturn(true);

        $this->layout               = Mockery::mock(BaseLayout::class);
        $this->admin_page_renderer  = Mockery::mock(AdminPageRenderer::class);
        $this->release_note_manager = Mockery::mock(ReleaseNoteManager::class);
        $this->csrf_token           = Mockery::mock(CSRFSynchronizerToken::class);

        $this->admin_release_note_controller = new AdminReleaseNoteLinkController(
            $this->admin_page_renderer,
            $this->release_note_manager,
            $this->csrf_token,
            '11.18'
        );

        $this->csrf_token->shouldReceive('getTokenName')->andReturn('challenge');
        $this->csrf_token->shouldReceive('getToken')->andReturn('token');
    }

    public function testItDisplaysTheAdminPage()
    {
        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($this->admin_user);

        $this->release_note_manager->shouldReceive("getReleaseNoteLink");
        $this->admin_page_renderer->shouldReceive("renderANoFramedPresenter");

        $this->admin_release_note_controller->process($request, $this->layout, []);
    }

    public function testNoSiteAdminUserIsNotAllowed()
    {
        $user = Mockery::mock(PFUser::class);
        $user->shouldReceive("isSuperUser")->andReturnFalse();

        $request = Mockery::mock(HTTPRequest::class);
        $request->shouldReceive('getCurrentUser')->andReturn($user);

        $this->expectException(ForbiddenException::class);

        $this->release_note_manager->shouldNotReceive("getReleaseNoteLink");
        $this->admin_page_renderer->shouldNotReceive("renderANoFramedPresenter");

        $this->admin_release_note_controller->process($request, $this->layout, []);
    }
}
