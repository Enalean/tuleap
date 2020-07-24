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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class SystemEventManagerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testConcatParameters(): void
    {
        $sem = \Mockery::mock(\SystemEventManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $params = [
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        ];
        $this->assertEquals('', $sem->concatParameters($params, []));
        $this->assertEquals('value1', $sem->concatParameters($params, ['key1']));
        $this->assertEquals('value1::value3', $sem->concatParameters($params, ['key1', 'key3']));
        $this->assertEquals('value3::value1', $sem->concatParameters($params, ['key3', 'key1']));
        $this->assertEquals('value1::value2::value3', $sem->concatParameters($params, ['key1', 'key2', 'key3']));
    }

    /**
     * 'toto' can be renamed if he is not already scheduled for rename
     */
    public function testCanRenameUser(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $seDao = \Mockery::spy(\SystemEventDao::class);

        $dar = \Mockery::spy(\DataAccessResult::class);
        $dar->shouldReceive('rowCount')->andReturns(0);
        $seDao->shouldReceive('searchWithParam')->with('head', 102, [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING])->once()->andReturns($dar);

        $se = \Mockery::mock(\SystemEventManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $se->shouldReceive('_getDao')->andReturns($seDao);

        $this->assertTrue($se->canRenameUser($user));
    }

    public function testCanRenameUserWithUserAlreadyQueudedForRename(): void
    {
        $user = \Mockery::spy(\PFUser::class);
        $user->shouldReceive('getId')->andReturns(102);

        $seDao = \Mockery::spy(\SystemEventDao::class);

        $dar = \Mockery::spy(\DataAccessResult::class);
        $dar->shouldReceive('rowCount')->andReturns(1);
        $seDao->shouldReceive('searchWithParam')
            ->with('head', 102, [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING])
            ->once()
            ->andReturns($dar);

        $se = \Mockery::mock(\SystemEventManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $se->shouldReceive('_getDao')->andReturns($seDao);

        $this->assertFalse($se->canRenameUser($user));
    }

    /**
     * Test if string 'titi' is not already in system event queue as a futur
     * new username
     */
    public function testIsUserNameAvailable(): void
    {
        $seDao = \Mockery::spy(\SystemEventDao::class);

        $dar = \Mockery::spy(\DataAccessResult::class);
        $dar->shouldReceive('rowCount')->andReturns(0);
        $dar->shouldReceive('isError')->andReturnFalse();
        $seDao->shouldReceive('searchWithParam')
            ->once()
            ->with('tail', 'titi', [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING])
            ->andReturns($dar);

        $se = \Mockery::mock(\SystemEventManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $se->shouldReceive('_getDao')->andReturns($seDao);

        $this->assertTrue($se->isUserNameAvailable('titi'));
    }

    public function testIsUserNameAvailableWithStringAlreadyQueuded(): void
    {
        $seDao = \Mockery::spy(\SystemEventDao::class);

        $dar = \Mockery::spy(\DataAccessResult::class);
        $dar->shouldReceive('rowCount')->andReturns(1);
        $seDao->shouldReceive('searchWithParam')
            ->once()
            ->with('tail', 'titi', [SystemEvent::TYPE_USER_RENAME], [SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING])
            ->andReturns($dar);

        $se = \Mockery::mock(\SystemEventManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $se->shouldReceive('_getDao')->andReturns($seDao);

        $this->assertFalse($se->isUserNameAvailable('titi'));
    }

    public function testItDoesNotAccumulateSystemCheckEvents(): void
    {
        $system_event_manager = \Mockery::mock(\SystemEventManager::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $system_event_manager->shouldReceive('areThereMultipleEventsQueuedMatchingFirstParameter')->andReturns(false, true);

        $system_event_manager->shouldReceive('createEvent')->once();

        $system_event_manager->addSystemEvent(Event::SYSTEM_CHECK, null);
        $system_event_manager->addSystemEvent(Event::SYSTEM_CHECK, null);
    }
}
