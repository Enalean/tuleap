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
 */

declare(strict_types=1);

namespace Tuleap\Queue\Redis;

use PHPUnit\Framework\TestCase;

final class RedisEventMessageForPersistentQueueTest extends TestCase
{
    public function testBuildMessageFromTopicAndPayload(): void
    {
        $message = RedisEventMessageForPersistentQueue::fromTopicAndPayload('mytopic', 'mycontent');

        $this->assertEquals('mytopic', $message->getTopic());
        $this->assertEquals('mycontent', $message->getPayload());
        $this->assertEquals(0, $message->getNumberOfTimesMessageHasBeenQueued());
        $this->assertJson($message->toSerializedEventMessageValue());
    }

    public function testEventValueCanBeUnserialized(): void
    {
        $event_value_serialized = '{"event_name":"testtopic","payload":"testcontent","_enqueue_ts":12.1,"_queued_total":2}';

        $message = RedisEventMessageForPersistentQueue::fromSerializedEventMessageValue($event_value_serialized);
        $this->assertEquals('testtopic', $message->getTopic());
        $this->assertEquals('testcontent', $message->getPayload());
        $this->assertEquals(12.1, $message->getEnqueueTime());
        $this->assertEquals(3, $message->getNumberOfTimesMessageHasBeenQueued());
    }

    public function testEventValueCanBeUnserializedWithDefaultValues(): void
    {
        $message = RedisEventMessageForPersistentQueue::fromSerializedEventMessageValue('{}');
        $this->assertMessageDefaultValues($message);
    }

    public function testBrokenEventValueIsUnserializedWithDefaultValues(): void
    {
        $message = RedisEventMessageForPersistentQueue::fromSerializedEventMessageValue('{corrupted_data');
        $this->assertMessageDefaultValues($message);
    }

    private function assertMessageDefaultValues(RedisEventMessageForPersistentQueue $message): void
    {
        $this->assertEquals('notopic', $message->getTopic());
        $this->assertEquals('', $message->getPayload());
        $this->assertEquals(0, $message->getEnqueueTime());
        $this->assertEquals(1, $message->getNumberOfTimesMessageHasBeenQueued());
    }
}
