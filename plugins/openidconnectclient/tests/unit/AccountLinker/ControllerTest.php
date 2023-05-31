<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\OpenIDConnectClient\AccountLinker;

use Tuleap\GlobalLanguageMock;
use Tuleap\GlobalResponseMock;
use Tuleap\OpenIDConnectClient\Login\ConnectorPresenterBuilder;
use Tuleap\OpenIDConnectClient\Provider\Provider;
use Tuleap\OpenIDConnectClient\Provider\ProviderManager;
use Tuleap\OpenIDConnectClient\UserMapping\UserMappingManager;
use Tuleap\Test\Builders\HTTPRequestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\Stubs\EventDispatcherStub;

final class ControllerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalResponseMock;
    use GlobalLanguageMock;

    public function testItLinksAnExistingAccount(): void
    {
        $account_to_be_linked = new UnlinkedAccount(1, 2, 102);

        $unlinked_account_manager = $this->createMock(UnlinkedAccountManager::class);
        $unlinked_account_manager->method('getbyId')->willReturn($account_to_be_linked);

        $provider = $this->createMock(Provider::class);
        $provider->method('getId')->willReturn(2);
        $provider->method('getName')->willReturn('My provider');

        $provider_manager = $this->createMock(ProviderManager::class);
        $provider_manager->method('getById')->willReturn($provider);

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::anAnonymousUser()->build());

        $session_storage = [];

        $user_mapping_manager = $this->createMock(UserMappingManager::class);

        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs([
                $user_manager,
                $provider_manager,
                $user_mapping_manager,
                $unlinked_account_manager,
                $this->createMock(ConnectorPresenterBuilder::class),
                EventDispatcherStub::withIdentityCallback(),
                &$session_storage,
            ])
            ->onlyMethods(['redirectAfterLogin'])
            ->getMock();

        $user_manager
            ->expects(self::once())
            ->method('login')
            ->willReturn(UserTestBuilder::buildWithDefaults());
        $user_mapping_manager
            ->expects(self::once())
            ->method('create');
        $unlinked_account_manager
            ->expects(self::once())
            ->method('removeById');
        $controller
            ->expects(self::once())
            ->method('redirectAfterLogin');

        $request = HTTPRequestBuilder::get()
            ->withParams([
                'loginname' => 'jdoe',
                'password'  => 'secret',
                'return_to' => '/',
            ])->build();

        $controller->linkExistingAccount($request);
    }

    public function testLinkAnExistingAccountDisplayLoginFormIfUserCannotBeLoggedIn(): void
    {
        $account_to_be_linked = new UnlinkedAccount(1, 2, 102);

        $unlinked_account_manager = $this->createMock(UnlinkedAccountManager::class);
        $unlinked_account_manager->method('getbyId')->willReturn($account_to_be_linked);

        $provider = $this->createMock(Provider::class);
        $provider->method('getId')->willReturn(2);
        $provider->method('getName')->willReturn('My provider');

        $provider_manager = $this->createMock(ProviderManager::class);
        $provider_manager->method('getById')->willReturn($provider);

        $user_manager = $this->createMock(\UserManager::class);
        $user_manager->method('getCurrentUser')->willReturn(UserTestBuilder::anAnonymousUser()->build());

        $session_storage = [];

        $user_mapping_manager = $this->createMock(UserMappingManager::class);

        $controller = $this->getMockBuilder(Controller::class)
            ->setConstructorArgs([
                $user_manager,
                $provider_manager,
                $user_mapping_manager,
                $unlinked_account_manager,
                $this->createMock(ConnectorPresenterBuilder::class),
                EventDispatcherStub::withIdentityCallback(),
                &$session_storage,
            ])
            ->onlyMethods(['showIndex'])
            ->getMock();

        $user_manager
            ->expects(self::once())
            ->method('login')
            ->willReturn(UserTestBuilder::anAnonymousUser()->build());
        $user_mapping_manager
            ->expects(self::never())
            ->method('create');
        $unlinked_account_manager
            ->expects(self::never())
            ->method('removeById');
        $controller
            ->expects(self::once())
            ->method('showIndex');

        $request = HTTPRequestBuilder::get()
            ->withParams([
                'loginname' => 'jdoe',
                'password'  => 'secret',
                'return_to' => '/',
            ])->build();

        $controller->linkExistingAccount($request);
    }
}
