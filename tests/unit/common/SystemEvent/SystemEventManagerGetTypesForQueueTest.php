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

use Event;
use EventManager;
use SystemEvent;
use SystemEventManager;
use Tuleap\Test\PHPUnit\TestCase;

require_once __DIR__ . '/../../bootstrap.php';

final class SystemEventManagerGetTypesForQueueTest extends TestCase
{
    public const CUSTOM_QUEUE = 'custom_queue';

    protected function setUp(): void
    {
        parent::setUp();

        $event_manager = new class extends EventManager {
            public function processEvent($event, $params = []): void
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

        EventManager::setInstance($event_manager);
    }

    protected function tearDown(): void
    {
        EventManager::clearInstance();

        parent::tearDown();
    }

    public function testItReturnsEmptyArrayIfFantasyQueueIsPassed(): void
    {
        $manager = $this->createPartialMock(SystemEventManager::class, []);

        $types = $manager->getTypesForQueue('Unicorne');

        self::assertEmpty($types);
    }

    public function testItReturnsEmptyArrayIfNoQueueIsPassed(): void
    {
        $manager = $this->createPartialMock(SystemEventManager::class, []);

        $types = $manager->getTypesForQueue(null);

        self::assertEmpty($types);
    }

    public function testItReturnsTypesForDefaultQueue(): void
    {
        $manager = $this->createPartialMock(SystemEventManager::class, []);

        $types = $manager->getTypesForQueue(SystemEvent::DEFAULT_QUEUE);

        self::assertTrue(in_array('feed_mini', $types));
        self::assertTrue(in_array('search_wiki', $types));
    }

    public function testItReturnsTypesForAppOwner(): void
    {
        $manager = $this->createPartialMock(SystemEventManager::class, []);

        $types = $manager->getTypesForQueue(SystemEvent::APP_OWNER_QUEUE);

        self::assertTrue(in_array('feed_mini', $types));
        self::assertTrue(in_array('search_wiki', $types));
    }

    public function testItReturnsTypesForCustomQueue(): void
    {
        $manager = $this->createPartialMock(SystemEventManager::class, []);

        $types = $manager->getTypesForQueue(self::CUSTOM_QUEUE);

        self::assertTrue(in_array('track_me', $types));
        self::assertTrue(in_array('track_you', $types));
    }

    public function testItDoesNotReturnDefaultTypesForCustomQueue(): void
    {
        $manager = $this->createPartialMock(SystemEventManager::class, []);

        $types = $manager->getTypesForQueue(self::CUSTOM_QUEUE);

        self::assertFalse(in_array('feed_mini', $types));
        self::assertFalse(in_array('search_wiki', $types));
    }

    public function testItDoesNotReturnCustomTypesForDefaultQueue(): void
    {
        $manager = $this->createPartialMock(SystemEventManager::class, []);

        $types = $manager->getTypesForQueue(SystemEvent::DEFAULT_QUEUE);

        self::assertFalse(in_array('track_me', $types));
        self::assertFalse(in_array('track_you', $types));
    }
}
