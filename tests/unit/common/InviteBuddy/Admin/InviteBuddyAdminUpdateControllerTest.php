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

use Tuleap\InviteBuddy\InviteBuddyConfiguration;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\CSRFSynchronizerTokenStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class InviteBuddyAdminUpdateControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private InviteBuddyAdminUpdateController $controller;
    private \PHPUnit\Framework\MockObject\MockObject&InviteBuddyConfiguration $configuration;
    private CSRFSynchronizerTokenStub $csrf_token;
    private \Tuleap\Config\ConfigDao&\PHPUnit\Framework\MockObject\MockObject $config_dao;

    #[\Override]
    protected function setUp(): void
    {
        $this->configuration = $this->createMock(InviteBuddyConfiguration::class);
        $this->csrf_token    = CSRFSynchronizerTokenStub::buildSelf();
        $this->config_dao    = $this->createMock(\Tuleap\Config\ConfigDao::class);

        $this->controller = new InviteBuddyAdminUpdateController(
            $this->csrf_token,
            $this->configuration,
            $this->config_dao,
        );
    }

    public function testItThrowsExceptionIfUserIsNotSuperUser(): void
    {
        $user = UserTestBuilder::aUser()->withoutSiteAdministrator()->build();

        $this->expectException(ForbiddenException::class);

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItSavesNothingIfThereIsNoChange(): void
    {
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $this->configuration
            ->method('getNbMaxInvitationsByDay')
            ->willReturn(42);

        $this->config_dao
            ->expects($this->never())
            ->method('save');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($user)->withParam('max_invitations_by_day', '42')->build(),
                LayoutBuilder::build(),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        self::assertTrue($this->csrf_token->hasBeenChecked());
    }

    public function testItSavesNothingIfValueIsNegative(): void
    {
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $this->configuration
            ->method('getNbMaxInvitationsByDay')
            ->willReturn(42);

        $this->config_dao
            ->expects($this->never())
            ->method('save');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($user)->withParam('max_invitations_by_day', '-10')->build(),
                LayoutBuilder::build(),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        self::assertTrue($this->csrf_token->hasBeenChecked());
    }

    public function testItSavesNothingIfValueIsZero(): void
    {
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $this->configuration
            ->method('getNbMaxInvitationsByDay')
            ->willReturn(42);

        $this->config_dao
            ->expects($this->never())
            ->method('save');

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($user)->withParam('max_invitations_by_day', '0')->build(),
                LayoutBuilder::build(),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);

        self::assertTrue($this->csrf_token->hasBeenChecked());
    }

    public function testItSavesTheNewValue(): void
    {
        $user = UserTestBuilder::aUser()->withSiteAdministrator()->build();

        $this->configuration
            ->method('getNbMaxInvitationsByDay')
            ->willReturn(42);

        $this->config_dao
            ->expects($this->once())
            ->method('saveInt')
            ->with('max_invitations_by_day', 10);

        $has_been_redirected = false;
        try {
            $this->controller->process(
                HTTPRequestBuilder::get()->withUser($user)->withParam('max_invitations_by_day', '10')->build(),
                LayoutBuilder::build(),
                []
            );
        } catch (LayoutInspectorRedirection $ex) {
            $has_been_redirected = true;
        }

        self::assertTrue($has_been_redirected);
        self::assertTrue($this->csrf_token->hasBeenChecked());
    }
}
