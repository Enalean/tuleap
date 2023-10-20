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

declare(strict_types=1);

namespace Tuleap\User\Account;

use CSRFSynchronizerToken;
use PFUser;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspectorRedirection;
use Tuleap\Test\Builders\UserTestBuilder;

final class UpdateSessionPreferencesControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var CSRFSynchronizerToken&\PHPUnit\Framework\MockObject\MockObject
     */
    private $csrf_token;
    /**
     * @var \PHPUnit\Framework\MockObject\MockObject&\UserManager
     */
    private $user_manager;
    /**
     * @var UpdateSessionPreferencesController
     */
    private $controller;
    /**
     * @var PFUser
     */
    private $user;

    protected function setUp(): void
    {
        $this->csrf_token   = $this->createMock(CSRFSynchronizerToken::class);
        $this->user_manager = $this->createMock(\UserManager::class);

        $this->controller = new UpdateSessionPreferencesController(
            $this->csrf_token,
            $this->user_manager,
        );
        $this->user       = UserTestBuilder::aUser()->withId(120)->build();
    }

    public function testItThrowsExceptionWhenUserIsAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->user_manager->expects(self::never())->method('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItChecksCSRFToken(): void
    {
        $this->user_manager->method('updateDb');

        $this->csrf_token->expects(self::once())->method('check')->with('/account/security');

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItActivatesRememberMeWhenNoStickyLogin(): void
    {
        $this->csrf_token->method('check');

        $this->user_manager->expects(self::once())->method('updateDb')->willReturnCallback(static function (PFUser $user): bool {
            return $user->getStickyLogin() === 1;
        });

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('account-remember-me', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDeactivatesRememberMeWhenStickyLoginIsSet(): void
    {
        $this->csrf_token->method('check');

        $this->user_manager->expects(self::once())->method('updateDb')->willReturnCallback(static function (PFUser $user): bool {
            return $user->getStickyLogin() === 0;
        });

        $this->expectException(LayoutInspectorRedirection::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
