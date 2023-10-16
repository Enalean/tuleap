<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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

namespace Tuleap\User\SSHKey;

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;

final class SSHKeyDeleteControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private \CSRFSynchronizerToken&MockObject $csrf_token;
    private \UserManager&MockObject $user_manager;
    private SSHKeyDeleteController $controller;

    protected function setUp(): void
    {
        $this->csrf_token   = $this->createMock(\CSRFSynchronizerToken::class);
        $this->user_manager = $this->createMock(\UserManager::class);
        $this->controller   = new SSHKeyDeleteController($this->csrf_token, $this->user_manager);
    }

    public function testItForbidsAnonymousUsers(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesUserSSHKey(): void
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->csrf_token->expects(self::once())->method('check')->with('/account/keys-tokens');

        $this->user_manager->expects(self::once())->method('deleteSSHKeys')->with($user, ['1', '3']);


        $this->expectExceptionObject(new LayoutInspectorRedirection('/account/keys-tokens'));
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('ssh_key_selected', ['1', '3'])->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
