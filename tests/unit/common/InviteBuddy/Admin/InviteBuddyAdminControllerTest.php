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

use Tuleap\Admin\AdminPageRenderer;
use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InviteBuddyAdminControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private InviteBuddyAdminController $controller;
    private AdminPageRenderer&\PHPUnit\Framework\MockObject\MockObject $admin_page_renderer;
    private \PHPUnit\Framework\MockObject\MockObject&InviteBuddyConfiguration $configuration;
    private CSRFSynchronizerTokenStub $csrf_token;

    protected function setUp(): void
    {
        $this->admin_page_renderer = $this->createMock(AdminPageRenderer::class);
        $this->configuration       = $this->createMock(InviteBuddyConfiguration::class);
        $this->csrf_token          = CSRFSynchronizerTokenStub::buildSelf();

        $this->controller = new InviteBuddyAdminController(
            $this->admin_page_renderer,
            $this->configuration,
            $this->csrf_token
        );
    }

    public function testItThrowsExceptionIfUserIsNotSuperUser(): void
    {
        $user = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();

        $this->configuration->method('canSiteAdminConfigureTheFeature')->willReturn(true);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItThrowsExceptionIfPlatformsCannotInvite(): void
    {
        $user = UserTestBuilder::anActiveUser()->withSiteAdministrator()->build();

        $this->configuration->method('canSiteAdminConfigureTheFeature')->willReturn(false);

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItRendersThePage(): void
    {
        $user = UserTestBuilder::anActiveUser()->withSiteAdministrator()->build();

        $this->configuration->method('canSiteAdminConfigureTheFeature')->willReturn(true);

        $this->configuration
            ->expects($this->once())
            ->method('getNbMaxInvitationsByDay')
            ->willReturn(42);

        $this->admin_page_renderer
            ->expects($this->once())
            ->method('renderANoFramedPresenter')
            ->with(
                'Invitations',
                realpath(__DIR__ . '/../../../../../src/templates/admin/invitations'),
                'invitations',
                [
                    'max_invitations_by_day' => 42,
                    'csrf_token'             => $this->csrf_token,
                ],
            );

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
