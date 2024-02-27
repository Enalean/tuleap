<?php
/**
 * Copyright (c) Enalean, 2012 — Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

declare(strict_types=1);

namespace Tuleap\SystemEvent;

use DataAccessResult;
use Event;
use SystemEvent;
use SystemEventDao;
use SystemEventManager;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

class SystemEventManagerTest extends TestCase
{
    public function testConcatParameters(): void
    {
        $sem    = $this->createPartialMock(SystemEventManager::class, []);
        $params = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        self::assertEquals('', $sem->concatParameters($params, []));
        self::assertEquals('value1', $sem->concatParameters($params, ['key1']));
        self::assertEquals('value1::value3', $sem->concatParameters($params, ['key1', 'key3']));
        self::assertEquals('value3::value1', $sem->concatParameters($params, ['key3', 'key1']));
        self::assertEquals('value1::value2::value3', $sem->concatParameters($params, ['key1', 'key2', 'key3']));
    }

    /**
     * 'toto' can be renamed if he is not already scheduled for rename
     */
    public function testCanRenameUser(): void
    {
        $user = UserTestBuilder::buildWithId(102);

        $seDao = $this->createMock(SystemEventDao::class);

        $dar = $this->createMock(DataAccessResult::class);
        $dar->method('rowCount')->willReturn(0);
        $dar->method('isError');
        $seDao->expects(self::once())->method('searchWithParam')
            ->with('head', 102, [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING])
            ->willReturn($dar);

        $se = $this->createPartialMock(SystemEventManager::class, ['_getDao']);
        $se->method('_getDao')->willReturn($seDao);

        self::assertTrue($se->canRenameUser($user));
    }

    public function testCanRenameUserWithUserAlreadyQueudedForRename(): void
    {
        $user = UserTestBuilder::buildWithId(102);

        $seDao = $this->createMock(SystemEventDao::class);

        $dar = $this->createMock(DataAccessResult::class);
        $dar->method('rowCount')->willReturn(1);
        $dar->method('isError');
        $seDao->expects(self::once())->method('searchWithParam')
            ->with('head', 102, [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING])
            ->willReturn($dar);

        $se = $this->createPartialMock(SystemEventManager::class, ['_getDao']);
        $se->method('_getDao')->willReturn($seDao);

        self::assertFalse($se->canRenameUser($user));
    }

    /**
     * Test if string 'titi' is not already in system event queue as a futur
     * new username
     */
    public function testIsUserNameAvailable(): void
    {
        $seDao = $this->createMock(SystemEventDao::class);

        $dar = $this->createMock(DataAccessResult::class);
        $dar->method('rowCount')->willReturn(0);
        $dar->method('isError')->willReturn(false);
        $seDao->expects(self::once())->method('searchWithParam')
            ->with('tail', 'titi', [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING])
            ->willReturn($dar);

        $se = $this->createPartialMock(SystemEventManager::class, ['_getDao']);
        $se->method('_getDao')->willReturn($seDao);

        self::assertTrue($se->isUserNameAvailable('titi'));
    }

    public function testIsUserNameAvailableWithStringAlreadyQueuded(): void
    {
        $seDao = $this->createMock(SystemEventDao::class);

        $dar = $this->createMock(DataAccessResult::class);
        $dar->method('rowCount')->willReturn(1);
        $dar->method('isError');
        $seDao->expects(self::once())->method('searchWithParam')
            ->with('tail', 'titi', [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING])
            ->willReturn($dar);

        $se = $this->createPartialMock(SystemEventManager::class, ['_getDao']);
        $se->method('_getDao')->willReturn($seDao);

        self::assertFalse($se->isUserNameAvailable('titi'));
    }

    public function testItDoesNotAccumulateSystemCheckEvents(): void
    {
        $system_event_manager = $this->createPartialMock(SystemEventManager::class, [
            'areThereMultipleEventsQueuedMatchingFirstParameter',
            'createEvent',
        ]);
        $system_event_manager->method('areThereMultipleEventsQueuedMatchingFirstParameter')->willReturnOnConsecutiveCalls(false, true);

        $system_event_manager->expects(self::once())->method('createEvent');

        $system_event_manager->addSystemEvent(Event::SYSTEM_CHECK, null);
        $system_event_manager->addSystemEvent(Event::SYSTEM_CHECK, null);
    }
}
