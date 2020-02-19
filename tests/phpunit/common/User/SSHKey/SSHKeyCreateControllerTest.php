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

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Request\ForbiddenException;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\LayoutBuilder;
use Tuleap\Test\Builders\LayoutInspector;
use Tuleap\Test\Builders\UserTestBuilder;

final class SSHKeyCreateControllerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \CSRFSynchronizerToken|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $csrf_token;
    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|\UserManager
     */
    private $user_manager;
    /**
     * @var SSHKeyCreateController
     */
    private $controller;

    protected function setUp(): void
    {
        $this->csrf_token = M::mock(\CSRFSynchronizerToken::class);
        $this->user_manager = M::mock(\UserManager::class);
        $this->controller = new SSHKeyCreateController($this->csrf_token, $this->user_manager);
    }

    public function testItForbidsAnonymousUsers()
    {
        $this->expectException(ForbiddenException::class);
        $this->controller->process(
            HTTPRequestBuilder::get()->withAnonymousUser()->build(),
            LayoutBuilder::build(),
            []
        );
    }

    public function testItUpdatesUserSSHKey()
    {
        $user = UserTestBuilder::aUser()->withId(110)->build();
        $this->csrf_token->shouldReceive('check')->with('/account/keys-tokens')->once();

        $this->user_manager->shouldReceive('addSSHKeys')->with($user, 'ssh-rsa blabla')->once();

        $layout_inspector = new LayoutInspector();

        $this->controller->process(
            HTTPRequestBuilder::get()->withUser($user)->withParam('ssh-key', 'ssh-rsa blabla')->build(),
            LayoutBuilder::buildWithInspector($layout_inspector),
            []
        );

        $this->assertEquals('/account/keys-tokens', $layout_inspector->getRedirectUrl());
    }
}
