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
Mock::generatePartial('SystemEventManager', 'SystemEventManagerTestVersion', array('_getDao'));

Mock::generate('SystemEventDao');

Mock::generate('PFUser');

Mock::generate('DataAccessResult');

class SystemEventManagerTest extends TuleapTestCase
{

    public function testConcatParameters()
    {
        $sem = new SystemEventManagerTestVersion($this);
        $params = array(
            'key1' => 'value1',
            'key2' => 'value2',
            'key3' => 'value3',
        );
        $this->assertEqual($sem->concatParameters($params, array()), '');
        $this->assertEqual($sem->concatParameters($params, array('key1')), 'value1');
        $this->assertEqual($sem->concatParameters($params, array('key1', 'key3')), 'value1::value3');
        $this->assertEqual($sem->concatParameters($params, array('key3', 'key1')), 'value3::value1');
        $this->assertEqual($sem->concatParameters($params, array('key1', 'key2', 'key3')), 'value1::value2::value3');
    }

    /**
     * 'toto' can be renamed if he is not already scheduled for rename
     */
    public function testCanRenameUser()
    {
        $user = mock('PFUser');
        $user->setReturnValue('getId', 102);

        $seDao = new MockSystemEventDao($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('rowCount', 0);
        $seDao->setReturnValue('searchWithParam', $dar);
        $seDao->expectOnce('searchWithParam', array('head', 102, array(SystemEvent::TYPE_USER_RENAME), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)));

        $se = new SystemEventManagerTestVersion($this);
        $se->setReturnValue('_getDao', $seDao);

        $this->assertTrue($se->canRenameUser($user));
    }

    public function testCanRenameUserWithUserAlreadyQueudedForRename()
    {
        $user = mock('PFUser');
        $user->setReturnValue('getId', 102);

        $seDao = new MockSystemEventDao($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('rowCount', 1);
        $seDao->setReturnValue('searchWithParam', $dar);
        $seDao->expectOnce('searchWithParam', array('head', 102, array(SystemEvent::TYPE_USER_RENAME), array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)));

        $se = new SystemEventManagerTestVersion($this);
        $se->setReturnValue('_getDao', $seDao);

        $this->assertFalse($se->canRenameUser($user));
    }

    /**
     * Test if string 'titi' is not already in system event queue as a futur
     * new username
     */
    public function testIsUserNameAvailable()
    {
        $seDao = new MockSystemEventDao($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('rowCount', 0);
        $seDao->ExpectOnce('searchWithParam', array('tail', 'titi', array(SystemEvent::TYPE_USER_RENAME) , array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)));
        $seDao->setReturnValue('searchWithParam', $dar);

        $se = new SystemEventManagerTestVersion($this);
        $se->setReturnValue('_getDao', $seDao);

        $this->assertTrue($se->isUserNameAvailable('titi'));
    }

    public function testIsUserNameAvailableWithStringAlreadyQueuded()
    {
        $seDao = new MockSystemEventDao($this);

        $dar = new MockDataAccessResult($this);
        $dar->setReturnValue('rowCount', 1);
        $seDao->ExpectOnce('searchWithParam', array('tail', 'titi', array(SystemEvent::TYPE_USER_RENAME) , array(SystemEvent::STATUS_NEW, SystemEvent::STATUS_RUNNING)));
        $seDao->setReturnValue('searchWithParam', $dar);

        $se = new SystemEventManagerTestVersion($this);
        $se->setReturnValue('_getDao', $seDao);

        $this->assertFalse($se->isUserNameAvailable('titi'));
    }

    public function itDoesNotAccumulateSystemCheckEvents()
    {
        $system_event_manager = partial_mock('SystemEventManager', array('areThereMultipleEventsQueuedMatchingFirstParameter', 'createEvent'));
        stub($system_event_manager)->areThereMultipleEventsQueuedMatchingFirstParameter()->returnsAt(0, false);
        stub($system_event_manager)->areThereMultipleEventsQueuedMatchingFirstParameter()->returns(true);

        $system_event_manager->expectOnce('createEvent');

        $system_event_manager->addSystemEvent(Event::SYSTEM_CHECK, null);
        $system_event_manager->addSystemEvent(Event::SYSTEM_CHECK, null);
    }
}

class SystemEventManagerGetTypesForQueueTest extends TuleapTestCase
{

    private $event_manager;

    public const CUSTOM_QUEUE = 'custom_queue';

    public function setUp()
    {
        parent::setUp();

        $this->event_manager = new class extends EventManager {
            function processEvent($event, $params = [])
            {
                switch ($event) {
                    case Event::SYSTEM_EVENT_GET_TYPES_FOR_CUSTOM_QUEUE:
                        if ($params['queue'] === SystemEventManagerGetTypesForQueueTest::CUSTOM_QUEUE) {
                            $params['types'][] = 'track_me';
                            $params['types'][] = 'track_you';
                        }
                        break;
                    case Event::SYSTEM_EVENT_GET_TYPES_FOR_DEFAULT_QUEUE:
                        $params['types'][] = 'feed_mini';
                        $params['types'][] = 'search_wiki';
                        break;
                    default:
                        break;
                }
            }
        };

        EventManager::setInstance($this->event_manager);
    }

    public function itReturnsEmptyArrayIfFantasyQueueIsPassed()
    {
        $manager = partial_mock('SystemEventManager', array());

        $types = $manager->getTypesForQueue('Unicorne');

        $this->assertArrayEmpty($types);
    }

    public function itReturnsEmptyArrayIfNoQueueIsPassed()
    {
        $manager = partial_mock('SystemEventManager', array());

        $types = $manager->getTypesForQueue(null);

        $this->assertArrayEmpty($types);
    }

    public function itReturnsTypesForDefaultQueue()
    {
        $manager = partial_mock('SystemEventManager', array());

        $types = $manager->getTypesForQueue(SystemEvent::DEFAULT_QUEUE);

        $this->assertTrue(in_array('feed_mini', $types));
        $this->assertTrue(in_array('search_wiki', $types));
    }

    public function itReturnsTypesForAppOwner()
    {
        $manager = partial_mock('SystemEventManager', array());

        $types = $manager->getTypesForQueue(SystemEvent::APP_OWNER_QUEUE);

        $this->assertTrue(in_array('feed_mini', $types));
        $this->assertTrue(in_array('search_wiki', $types));
    }

    public function itReturnsTypesForCustomQueue()
    {
        $manager = partial_mock('SystemEventManager', array());

        $types = $manager->getTypesForQueue(self::CUSTOM_QUEUE);

        $this->assertTrue(in_array('track_me', $types));
        $this->assertTrue(in_array('track_you', $types));
    }

    public function itDoesNotReturnDefaultTypesForCustomQueue()
    {
        $manager = partial_mock('SystemEventManager', array());

        $types = $manager->getTypesForQueue(self::CUSTOM_QUEUE);

        $this->assertFalse(in_array('feed_mini', $types));
        $this->assertFalse(in_array('search_wiki', $types));
    }

    public function itDoesNotReturnCustomTypesForDefaultQueue()
    {
        $manager = partial_mock('SystemEventManager', array());

        $types = $manager->getTypesForQueue(SystemEvent::DEFAULT_QUEUE);

        $this->assertFalse(in_array('track_me', $types));
        $this->assertFalse(in_array('track_you', $types));
    }
}
