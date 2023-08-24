<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

namespace Tuleap\SystemEvent;

use BackendAliases;
use BackendSVN;
use BackendSystem;
use Exception;
use ForgeConfig;
use Mockery as M;
use Mockery\MockInterface;
use RuntimeException;
use SiteCache;
use SystemEvent;
use SystemEventDao;
use SystemEventManager;
use SystemEventProcessor_Root;
use SystemEventProcessRootDefaultQueue;
use Tuleap\DB\DBConnection;
use Tuleap\GlobalSVNPollution;
use Tuleap\SVNCore\ApacheConfGenerator;

class SystemEventProcessorRootTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;
    use GlobalSVNPollution;

    private $system_event_manager;
    /**
     * @var MockInterface|SystemEventDao
     */
    private $system_event_dao;
    /**
     * @var SystemEventProcessor_Root
     */
    private $processor;
    private $sys_http_user = 'www-data';
    private $logger;
    private $site_cache;

    protected function setUp(): void
    {
        $this->system_event_manager = M::mock(SystemEventManager::class);
        $this->system_event_dao     = M::mock(SystemEventDao::class);
        $this->logger               = M::spy(\Psr\Log\LoggerInterface::class);
        $this->site_cache           = M::mock(SiteCache::class);

        $this->processor = M::mock(
            SystemEventProcessor_Root::class,
            [
                new SystemEventProcessRootDefaultQueue(),
                $this->system_event_manager,
                $this->system_event_dao,
                $this->logger,
                M::spy(BackendAliases::class),
                M::spy(BackendSVN::class),
                M::spy(BackendSystem::class),
                $this->site_cache,
                M::spy(ApacheConfGenerator::class),
                M::mock(DBConnection::class),
            ]
        )->makePartial()->shouldAllowMockingProtectedMethods();

        ForgeConfig::store();
        ForgeConfig::set('codendi_dir', '/usr/share/codendi');
        ForgeConfig::set('sys_http_user', $this->sys_http_user);
    }

    protected function tearDown(): void
    {
        ForgeConfig::restore();
    }

    public function testItFetchesEventsForRoot()
    {
        $category = SystemEvent::DEFAULT_QUEUE;

        $types = ['some_type'];
        $this->system_event_manager->shouldReceive('getTypesForQueue')->once()->andReturn($types);

        $this->system_event_dao->shouldReceive('checkOutNextEvent')->with('root', $types)->once()->andReturn(false);
        $this->processor->execute($category);
    }

    public function testItCatchExceptionsInSystemEvents()
    {
        $system_event = M::mock(SystemEvent::class)->makePartial();
        $system_event->shouldReceive('notify')->once();
        $system_event->shouldReceive('process')->once()->andThrow(new RuntimeException('Something wrong happened'));

        $types = ['some_type'];
        $this->system_event_manager->shouldReceive('getTypesForQueue')->andReturn($types);

        $this->system_event_dao->shouldReceive('getElapsedTime')->once();
        $this->system_event_dao->shouldReceive('close')->once();
        $this->system_event_dao->shouldReceive('checkOutNextEvent')->once()->andReturn(M::mock(\DataAccessResult::class, ['getRow' => ['whatever']]))->ordered();
        $this->system_event_dao->shouldReceive('checkOutNextEvent')->once()->andReturn(null)->ordered();
        $this->system_event_manager->shouldReceive('getInstanceFromRow')->once()->andReturns($system_event);

        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);

        $this->assertEquals($system_event->getStatus(), SystemEvent::STATUS_ERROR);
        $this->assertEquals($system_event->getLog(), 'Something wrong happened');
    }

    public function testItProcessApplicationOwnerEvents()
    {
        $this->site_cache->shouldReceive('restoreOwnership')->once();

        $system_event = M::mock(SystemEvent::class)->makePartial();
        $system_event->shouldReceive('notify')->once();
        $system_event->setStatus(SystemEvent::STATUS_DONE);

        $this->system_event_manager->shouldReceive('getTypesForQueue')->andReturn(['SOME_EVENT']);
        $this->system_event_manager->shouldReceive('getInstanceFromRow')->once()->andReturns($system_event);

        $this->system_event_dao->shouldReceive('getElapsedTime')->once();
        $this->system_event_dao->shouldReceive('close')->once();
        $this->system_event_dao->shouldReceive('checkOutNextEvent')->once()->andReturn(M::mock(\DataAccessResult::class, ['getRow' => ['whatever']]))->ordered();
        $this->system_event_dao->shouldReceive('checkOutNextEvent')->once()->andReturn(null)->ordered();

        $command = '/usr/bin/tuleap process-system-events ' . SystemEvent::OWNER_APP;
        $this->processor->shouldReceive('launchAs')->with($this->sys_http_user, $command)->once();
        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);
    }

    public function testItCatchesExceptionsThrownInPostActions()
    {
        $this->site_cache->shouldReceive('restoreOwnership')->once();

        $system_event = M::mock(SystemEvent::class)->makePartial();
        $system_event->shouldReceive('notify')->once();
        $system_event->setStatus(SystemEvent::STATUS_DONE);

        $this->system_event_manager->shouldReceive('getTypesForQueue')->andReturn(['SOME_EVENT']);
        $this->system_event_manager->shouldReceive('getInstanceFromRow')->once()->andReturns($system_event);

        $this->system_event_dao->shouldReceive('getElapsedTime')->once();
        $this->system_event_dao->shouldReceive('close')->once();
        $this->system_event_dao->shouldReceive('checkOutNextEvent')->once()->andReturn(M::mock(\DataAccessResult::class, ['getRow' => ['whatever']]))->ordered();
        $this->system_event_dao->shouldReceive('checkOutNextEvent')->once()->andReturn(null)->ordered();

        $this->processor->shouldReceive('launchAs')->andThrow(new Exception('Something weird happend'));

        $this->logger->shouldReceive('error')->once();

        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);
    }
}
