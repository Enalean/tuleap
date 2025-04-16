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

namespace Tuleap\Git\Account;

use Git_RemoteServer_GerritServer;
use Git_RemoteServer_GerritServerFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\User\Account\AccountTabPresenterCollection;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
class AccountTabsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private readonly MockObject&Git_RemoteServer_GerritServerFactory $factory;
    private readonly AccountTabsBuilder $builder;
    private readonly MockObject&AccountTabPresenterCollection $collection;

    protected function setUp(): void
    {
        $this->factory = $this->createMock(Git_RemoteServer_GerritServerFactory::class);
        $this->builder = new AccountTabsBuilder($this->factory);

        $this->collection = $this->createMock(AccountTabPresenterCollection::class);
        $this->collection
            ->method('getUser')->willReturn(UserTestBuilder::buildWithDefaults());
        $this->collection
            ->method('getCurrentHref')->willReturn('/account');
    }

    public function testItAddsTabs(): void
    {
        $this->factory
            ->method('getRemoteServersForUser')->willReturn([$this->createMock(Git_RemoteServer_GerritServer::class)]);
        $this->factory
            ->method('hasRemotesSetUp')->willReturn(true);

        $this->collection
            ->expects($this->once())
            ->method('add');

        $this->builder->addTabs($this->collection);
    }

    public function testItAddsTabIfThereIsNoRemoteServersForUser(): void
    {
        $this->factory
            ->method('getRemoteServersForUser')->willReturn([]);
        $this->factory
            ->method('hasRemotesSetUp')->willReturn(true);

        $this->collection
            ->expects($this->once())
            ->method('add');

        $this->builder->addTabs($this->collection);
    }

    public function testItAddsTabIfThereIsNoRemoteSetup(): void
    {
        $this->factory
            ->method('getRemoteServersForUser')->willReturn([$this->createMock(Git_RemoteServer_GerritServer::class)]);
        $this->factory
            ->method('hasRemotesSetUp')->willReturn(false);

        $this->collection
            ->expects($this->once())
            ->method('add');

        $this->builder->addTabs($this->collection);
    }

    public function testItDoesNotAddTabIfThereIsNoRemoteSetupNorRemoteServersForUsers(): void
    {
        $this->factory
            ->method('getRemoteServersForUser')->willReturn([]);
        $this->factory
            ->method('hasRemotesSetUp')->willReturn(false);
        $this->collection
            ->expects($this->never())
            ->method('add');

        $this->builder->addTabs($this->collection);
    }
}
