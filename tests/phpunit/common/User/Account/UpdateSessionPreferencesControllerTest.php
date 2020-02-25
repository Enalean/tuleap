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
use Mockery as M;
use PFUser;
use PHPUnit\Framework\TestCase;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\UserTestBuilder;

final class UpdateSessionPreferencesControllerTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var CSRFSynchronizerToken|M\LegacyMockInterface|M\MockInterface
     */
    private $csrf_token;
    /**
     * @var M\LegacyMockInterface|M\MockInterface|\UserManager
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
        $this->csrf_token = M::mock(CSRFSynchronizerToken::class);
        $this->csrf_token->shouldReceive('check')->byDefault();
        $this->user_manager = M::mock(\UserManager::class);
        $this->user_manager->shouldReceive('updateDb')->byDefault();
        $this->controller = new UpdateSessionPreferencesController(
            $this->csrf_token,
            $this->user_manager,
        );
        $this->user = UserTestBuilder::aUser()->withId(120)->build();
    }

    public function testItThrowsExceptionWhenUserIsAnonymous(): void
    {
        $this->expectException(ForbiddenException::class);
        $this->user_manager->shouldNotReceive('updateDb');

        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItChecksCSRFToken(): void
    {
        $this->csrf_token->shouldReceive('check')->with('/account/security')->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItActivatesRememberMeWhenNoStickyLogin(): void
    {
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (PFUser $user) {
            return $user->getStickyLogin() === 1;
        })->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->withParam('account-remember-me', '1')->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItDeactivatesRememberMeWhenStickyLoginIsSet(): void
    {
        $this->user_manager->shouldReceive('updateDb')->withArgs(static function (PFUser $user) {
            return $user->getStickyLogin() === 0;
        })->once();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($this->user)->build(),
            LayoutBuilder::build(),
            []
        );
    }
}
