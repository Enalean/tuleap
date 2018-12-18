<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Docman\Tus;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class GenericTusEventDispatcherTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testSubscribersAreNotifiedOnTheSubjectOfTheirChoice()
    {
        $subscriber_1 = \Mockery::mock(TusEventSubscriber::class);
        $subscriber_1->shouldReceive('getInterestedBySubject')->andReturns('subject');
        $subscriber_2 = \Mockery::mock(TusEventSubscriber::class);
        $subscriber_2->shouldReceive('getInterestedBySubject')->andReturns('subject');

        $subscriber_1->shouldReceive('notify')->once();
        $subscriber_2->shouldReceive('notify')->once();

        $event_dispatcher = new GenericTusEventDispatcher($subscriber_1, $subscriber_2);
        $event_dispatcher->dispatch('subject', \Mockery::mock(\Psr\Http\Message\ServerRequestInterface::class));
    }

    public function testSubscriberIsNotNotifiedWhenTheSubjectIsNotTheOneItHasSubscribedFor()
    {
        $subscriber = \Mockery::mock(TusEventSubscriber::class);
        $subscriber->shouldReceive('getInterestedBySubject')->andReturns('subject');

        $subscriber->shouldReceive('notify')->never();

        $event_dispatcher = new GenericTusEventDispatcher($subscriber);
        $event_dispatcher->dispatch('other_subject', \Mockery::mock(\Psr\Http\Message\ServerRequestInterface::class));
    }
}
