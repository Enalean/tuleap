<?php
/**
 * Copyright (c) Enalean, 2020 - present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
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

declare(strict_types = 1);

namespace Tuleap\Tracker;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use TrackerFactory;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;

class TrackerFactoryTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\Mock | TrackerFactory
     */
    private $tracker_factory;

    protected function setUp(): void
    {
        $this->tracker_factory = \Mockery::mock(TrackerFactory::class)->makePartial()->shouldAllowMockingProtectedMethods();
    }

    public function testItCollectErrors(): void
    {
        $tracker     = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive("getName")->andReturn("Tracker name");
        $tracker->shouldReceive("getItemName")->andReturn("Item name");
        $trackers = [
            $tracker
        ];

        $checker = \Mockery::mock(TrackerCreationDataChecker::class);
        $checker->shouldReceive('areMandatoryCreationInformationValid')->andReturnFalse();
        $this->tracker_factory->shouldReceive('getTrackerChecker')->andReturn($checker);

        $result = $this->tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo($trackers, 101);

        $this->assertEquals(["Tracker name"], $result);
    }

    public function testItDoesNotHaveErrorIfEverythingIsValid(): void
    {
        $tracker     = \Mockery::mock(\Tracker::class);
        $tracker->shouldReceive("getName")->andReturn("Tracker name");
        $tracker->shouldReceive("getItemName")->andReturn("Item name");
        $trackers = [
            $tracker
        ];

        $checker = \Mockery::mock(TrackerCreationDataChecker::class);
        $checker->shouldReceive('areMandatoryCreationInformationValid')->andReturnTrue();
        $this->tracker_factory->shouldReceive('getTrackerChecker')->andReturn($checker);

        $result = $this->tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo($trackers, 101);

        $this->assertEquals([], $result);
    }
}
