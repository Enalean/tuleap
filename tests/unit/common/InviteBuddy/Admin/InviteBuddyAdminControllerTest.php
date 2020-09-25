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
 */

declare(strict_types=1);

namespace Tuleap\InviteBuddy\Admin;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;

class InviteBuddyAdminControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var InviteBuddyAdminController
     */
    private $controller;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|AdminPageRenderer
     */
    private $admin_page_renderer;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|InviteBuddyConfiguration
     */
    private $configuration;
    /**
     * @var \CSRFSynchronizerToken|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $csrf_token;

    protected function setUp(): void
    {
        $this->admin_page_renderer = Mockery::mock(AdminPageRenderer::class);
        $this->configuration       = Mockery::mock(InviteBuddyConfiguration::class);
        $this->csrf_token          = Mockery::mock(\CSRFSynchronizerToken::class);

        $this->controller = new InviteBuddyAdminController(
            $this->admin_page_renderer,
            $this->configuration,
            $this->csrf_token
        );
    }

    public function testItThrowsExceptionIfUserIsNotSuperUser(): void
    {
        $user = Mockery::mock(\PFUser::class)->shouldReceive(['isSuperUser' => false])->getMock();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRendersThePage(): void
    {
        $user = Mockery::mock(\PFUser::class)->shouldReceive(['isSuperUser' => true])->getMock();

        $this->configuration
            ->shouldReceive('getNbMaxInvitationsByDay')
            ->once()
            ->andReturn(42);

        $this->admin_page_renderer
            ->shouldReceive('renderANoFramedPresenter')
            ->with(
                "Invitations",
                realpath(__DIR__ . '/../../../../../src/templates/admin/invitations'),
                'invitations',
                [
                    'max_invitations_by_day' => 42,
                    'csrf_token'             => $this->csrf_token,
                ],
            )
            ->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
