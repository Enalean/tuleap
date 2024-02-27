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
use ColinODell\PsrTestLogger\TestLogger;
use DataAccessResult;
use Exception;
use ForgeConfig;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;
use SiteCache;
use SystemEvent;
use SystemEventDao;
use SystemEventManager;
use SystemEventProcessor_Root;
use SystemEventProcessRootDefaultQueue;
use Tuleap\DB\DBConnection;
use Tuleap\ForgeConfigSandbox;
use Tuleap\SVNCore\ApacheConfGenerator;
use Tuleap\Test\PHPUnit\TestCase;

final class SystemEventProcessorRootTest extends TestCase
{
    use ForgeConfigSandbox;

    private SystemEventManager&MockObject $system_event_manager;
    private SystemEventDao&MockObject $system_event_dao;
    private SystemEventProcessor_Root&MockObject $processor;
    private string $sys_http_user = 'www-data';
    private TestLogger $logger;
    private SiteCache&MockObject $site_cache;

    protected function setUp(): void
    {
        $this->system_event_manager = $this->createMock(SystemEventManager::class);
        $this->system_event_dao     = $this->createMock(SystemEventDao::class);
        $this->logger               = new TestLogger();
        $this->site_cache           = $this->createMock(SiteCache::class);

        $backend_aliases = $this->createMock(BackendAliases::class);
        $backend_aliases->method('aliasesNeedUpdate')->willReturn(false);
        $backend_svn = $this->createMock(BackendSVN::class);
        $backend_svn->method('getSVNApacheConfNeedUpdate')->willReturn(false);
        $this->processor = $this->getMockBuilder(SystemEventProcessor_Root::class)
            ->setConstructorArgs([
                new SystemEventProcessRootDefaultQueue(),
                $this->system_event_manager,
                $this->system_event_dao,
                $this->logger,
                $backend_aliases,
                $backend_svn,
                $this->createMock(BackendSystem::class),
                $this->site_cache,
                $this->createMock(ApacheConfGenerator::class),
                $this->createMock(DBConnection::class),
            ])
            ->onlyMethods([
                'launchAs',
            ])
            ->getMock();

        ForgeConfig::set('codendi_dir', '/usr/share/codendi');
        ForgeConfig::set('sys_http_user', $this->sys_http_user);
    }

    public function testItFetchesEventsForRoot(): void
    {
        $category = SystemEvent::DEFAULT_QUEUE;

        $types = ['some_type'];
        $this->system_event_manager->expects(self::once())->method('getTypesForQueue')->willReturn($types);

        $this->system_event_dao->expects(self::once())->method('checkOutNextEvent')->with('root', $types)->willReturn(false);
        $this->processor->execute($category);
    }

    public function testItCatchExceptionsInSystemEvents(): void
    {
        $system_event = $this->createPartialMock(SystemEvent::class, [
            'verbalizeParameters',
            'notify',
            'process',
        ]);
        $system_event->expects(self::once())->method('notify');
        $system_event->expects(self::once())->method('process')->willThrowException(new RuntimeException('Something wrong happened'));

        $types = ['some_type'];
        $this->system_event_manager->method('getTypesForQueue')->willReturn($types);

        $this->system_event_dao->expects(self::once())->method('getElapsedTime');
        $this->system_event_dao->expects(self::once())->method('close');
        $dar = $this->createMock(DataAccessResult::class);
        $dar->method('getRow')->willReturn(['whatever']);
        $this->system_event_dao->expects(self::exactly(2))->method('checkOutNextEvent')
            ->willReturnOnConsecutiveCalls(
                $dar,
                null
            );
        $this->system_event_manager->expects(self::once())->method('getInstanceFromRow')->willReturn($system_event);

        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);

        self::assertEquals(SystemEvent::STATUS_ERROR, $system_event->getStatus());
        self::assertEquals('Something wrong happened', $system_event->getLog());
    }

    public function testItProcessApplicationOwnerEvents(): void
    {
        $this->site_cache->expects(self::once())->method('restoreOwnership');

        $system_event = $this->createPartialMock(SystemEvent::class, [
            'verbalizeParameters',
            'notify',
        ]);
        $system_event->expects(self::once())->method('notify');
        $system_event->setStatus(SystemEvent::STATUS_DONE);

        $this->system_event_manager->method('getTypesForQueue')->willReturn(['SOME_EVENT']);
        $this->system_event_manager->expects(self::once())->method('getInstanceFromRow')->willReturn($system_event);

        $this->system_event_dao->expects(self::once())->method('getElapsedTime');
        $this->system_event_dao->expects(self::once())->method('close');
        $dar = $this->createMock(DataAccessResult::class);
        $dar->method('getRow')->willReturn(['whatever']);
        $this->system_event_dao->expects(self::exactly(2))->method('checkOutNextEvent')
            ->willReturnOnConsecutiveCalls(
                $dar,
                null
            );

        $command = '/usr/bin/tuleap process-system-events ' . SystemEvent::OWNER_APP;
        $this->processor->expects(self::once())->method('launchAs')->with($this->sys_http_user, $command);
        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);
    }

    public function testItCatchesExceptionsThrownInPostActions(): void
    {
        $this->site_cache->expects(self::once())->method('restoreOwnership');

        $system_event = $this->createPartialMock(SystemEvent::class, [
            'verbalizeParameters',
            'notify',
        ]);
        $system_event->expects(self::once())->method('notify');
        $system_event->setStatus(SystemEvent::STATUS_DONE);

        $this->system_event_manager->method('getTypesForQueue')->willReturn(['SOME_EVENT']);
        $this->system_event_manager->expects(self::once())->method('getInstanceFromRow')->willReturn($system_event);

        $this->system_event_dao->expects(self::once())->method('getElapsedTime');
        $this->system_event_dao->expects(self::once())->method('close');
        $dar = $this->createMock(DataAccessResult::class);
        $dar->method('getRow')->willReturn(['whatever']);
        $this->system_event_dao->expects(self::exactly(2))->method('checkOutNextEvent')
            ->willReturnOnConsecutiveCalls(
                $dar,
                null
            );

        $this->processor->method('launchAs')->willThrowException(new Exception('Something weird happend'));

        $category = SystemEvent::DEFAULT_QUEUE;
        $this->processor->execute($category);
        self::assertTrue($this->logger->hasErrorRecords());
    }
}
