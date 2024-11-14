<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Progress\Events;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

class GetSemanticProgressUsageTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var GetSemanticProgressUsageEvent
     */
    private $event;

    protected function setUp(): void
    {
        $this->event = new GetSemanticProgressUsageEvent(
            TrackerTestBuilder::aTracker()->build(),
        );
    }

    public function testItReturnsAnEmptyStateMessageWhenNoPluginsIsUsingTheSemanticOrPlansToUseItInTheFuture(): void
    {
        $this->assertEquals(
            'This semantic is unused at the moment.',
            $this->event->getSemanticUsage()
        );
    }

    public function testItOnlyMentionsPluginsUsingTheSemanticWhenThereIsNoPluginsPlanningToUseItInTheFuture(): void
    {
        $this->event->addUsageLocation('Plugin 1');
        $this->assertEquals(
            'This semantic is only used in Plugin 1 at the moment.',
            $this->event->getSemanticUsage()
        );
    }

    public function testItOnlyMentionsPluginsPlanningToUseTheSemanticInTheFutureWhenThereIsNoPluginsUsingIt(): void
    {
        $this->event->addFutureUsageLocation('Plugin 2');
        $this->assertEquals(
            'This semantic is unused at the moment. In longer-term, we plan to use it in Plugin 2.',
            $this->event->getSemanticUsage()
        );
    }

    public function testItMentionsPluginsUsingTheSemanticAndTheOnesPlanningToUseItInTheFuture(): void
    {
        $this->event->addUsageLocation('Plugin 1');
        $this->event->addFutureUsageLocation('Plugin 2');
        $this->assertEquals(
            'This semantic is only used in Plugin 1 at the moment. In longer-term, we plan to use it in Plugin 2 as well.',
            $this->event->getSemanticUsage()
        );
    }

    public function testItHandlesProperlySeveralPlugins(): void
    {
        $this->event->addUsageLocation('Plugin 1');
        $this->event->addUsageLocation('Plugin 2');
        $this->event->addUsageLocation('Plugin 3');
        $this->event->addFutureUsageLocation('Plugin 4');
        $this->event->addFutureUsageLocation('Plugin 5');

        $this->assertEquals(
            'This semantic is only used in Plugin 1, Plugin 2, Plugin 3 at the moment. In longer-term, we plan to use it in Plugin 4, Plugin 5 as well.',
            $this->event->getSemanticUsage()
        );
    }
}
