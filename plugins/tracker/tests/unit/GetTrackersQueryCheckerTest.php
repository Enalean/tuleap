<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 *  Tuleap and Enalean names and logos are registrated trademarks owned by
 *  Enalean SAS. All other trademarks or names are properties of their respective
 *  owners.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

declare(strict_types=1);

namespace Tuleap\Tracker;

use EventManager;
use Luracast\Restler\RestException;
use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\REST\Event\GetAdditionalCriteria;
use Tuleap\Tracker\REST\v1\GetTrackersQueryChecker;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class GetTrackersQueryCheckerTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private GetTrackersQueryChecker $checker;

    private EventManager&MockObject $event_manager;

    #[\Override]
    public function setUp(): void
    {
        $this->event_manager = $this->createMock(EventManager::class);
        $this->checker       = new GetTrackersQueryChecker($this->event_manager);
    }

    public function testItDoesNotRaiseAnExceptionForCriterionProvidedByPlugin(): void
    {
        $this->event_manager->method('processEvent')->willReturnCallback(
            function (GetAdditionalCriteria $event) {
                $event->addCriteria('with_whatever', "'with_whatever': true");
                return $event;
            }
        );

        $json_query = ['with_whatever' => true];

        $this->expectNotToPerformAssertions();

        $this->checker->checkQuery($json_query);
    }

    public function testItRaiseAnExceptionForCriterionProvidedByPlugin(): void
    {
        $this->event_manager->method('processEvent')->willReturnCallback(
            function (GetAdditionalCriteria $event) {
                $event->addCriteria('with_whatever', "'with_whatever': true");

                return $event;
            }
        );

        $json_query = ['whatever' => true];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->checker->checkQuery($json_query);
    }

    public function testYouAreNotAdministratorOfAtLeastOneTrackerIsNotSupported(): void
    {
        $this->event_manager->method('processEvent');
        $json_query = ['is_tracker_admin' => false];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->checker->checkQuery($json_query);
    }

    public function testItPassesWhenIsTrackerAdminIsValid(): void
    {
        $this->event_manager->method('processEvent');
        $json_query = ['is_tracker_admin' => true];

        $this->expectNotToPerformAssertions();

        $this->checker->checkQuery($json_query);
    }
}
