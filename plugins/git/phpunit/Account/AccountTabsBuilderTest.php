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
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\User\Account\AccountTabPresenterCollection;

class AccountTabsBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var Git_RemoteServer_GerritServerFactory|\Mockery\LegacyMockInterface|\Mockery\MockInterface
     */
    private $factory;
    /**
     * @var AccountTabsBuilder
     */
    private $builder;
    /**
     * @var Mockery\LegacyMockInterface|Mockery\MockInterface|AccountTabPresenterCollection
     */
    private $collection;

    protected function setUp(): void
    {
        $this->factory = \Mockery::mock(Git_RemoteServer_GerritServerFactory::class);
        $this->builder = new AccountTabsBuilder($this->factory);

        $this->collection = Mockery::mock(AccountTabPresenterCollection::class);
        $this->collection
            ->shouldReceive(
                [
                    'getUser'        => Mockery::mock(\PFUser::class),
                    'getCurrentHref' => '/account'
                ]
            );
    }

    public function testItAddsTabs(): void
    {
        $this->factory
            ->shouldReceive(
                [
                    'getRemoteServersForUser' => [Mockery::mock(Git_RemoteServer_GerritServer::class)],
                    'hasRemotesSetUp'         => true
                ]
            );

        $this->collection
            ->shouldReceive('add')
            ->once();

        $this->builder->addTabs($this->collection);
    }

    public function testItAddsTabIfThereIsNoRemoteServersForUser(): void
    {
        $this->factory
            ->shouldReceive(
                [
                    'getRemoteServersForUser' => [],
                    'hasRemotesSetUp'         => true
                ]
            );

        $this->collection
            ->shouldReceive('add')
            ->once();

        $this->builder->addTabs($this->collection);
    }

    public function testItAddsTabIfThereIsNoRemoteSetup(): void
    {
        $this->factory
            ->shouldReceive(
                [
                    'getRemoteServersForUser' => [Mockery::mock(Git_RemoteServer_GerritServer::class)],
                    'hasRemotesSetUp'         => false
                ]
            );

        $this->collection
            ->shouldReceive('add')
            ->once();

        $this->builder->addTabs($this->collection);
    }

    public function testItDoesNotAddTabIfThereIsNoRemoteSetupNorRemoteServersForUsers(): void
    {
        $this->factory
            ->shouldReceive(
                [
                    'getRemoteServersForUser' => [],
                    'hasRemotesSetUp'         => false
                ]
            );

        $this->collection
            ->shouldReceive('add')
            ->never();

        $this->builder->addTabs($this->collection);
    }
}
