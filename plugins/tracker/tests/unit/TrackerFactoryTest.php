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

declare(strict_types=1);

namespace Tuleap\Tracker;

use TrackerFactory;
use Tuleap\Tracker\Creation\TrackerCreationDataChecker;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class TrackerFactoryTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItCollectErrors(): void
    {
        $trackers = [
            TrackerTestBuilder::aTracker()->withName('Tracker name')->build(),
        ];

        $checker = $this->createMock(TrackerCreationDataChecker::class);
        $checker->method('areMandatoryCreationInformationValid')->willReturn(false);

        $tracker_factory = $this->createPartialMock(TrackerFactory::class, ['getTrackerChecker']);
        $tracker_factory->method('getTrackerChecker')->willReturn($checker);

        $result = $tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo($trackers, 101);

        $this->assertEquals(['Tracker name'], $result);
    }

    public function testItDoesNotHaveErrorIfEverythingIsValid(): void
    {
        $trackers = [
            TrackerTestBuilder::aTracker()->withName('Tracker name')->build(),
        ];

        $checker = $this->createMock(TrackerCreationDataChecker::class);
        $checker->method('areMandatoryCreationInformationValid')->willReturn(true);

        $tracker_factory = $this->createPartialMock(TrackerFactory::class, ['getTrackerChecker']);
        $tracker_factory->method('getTrackerChecker')->willReturn($checker);

        $result = $tracker_factory->collectTrackersNameInErrorOnMandatoryCreationInfo($trackers, 101);

        $this->assertEquals([], $result);
    }
}
