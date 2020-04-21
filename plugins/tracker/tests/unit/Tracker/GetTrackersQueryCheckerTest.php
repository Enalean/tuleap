<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
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

namespace Tuleap\Tracker;

use EventManager;
use Luracast\Restler\RestException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\REST\Event\GetAdditionalCriteria;
use Tuleap\Tracker\REST\v1\GetTrackersQueryChecker;

class GetTrackersQueryCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * GetProjectsQueryChecker
     */
    private $checker;

    /**
     * EventManager
     */
    private $event_manager;

    public function setUp(): void
    {
        $this->event_manager = \Mockery::mock(EventManager::class);
        $this->checker       = new GetTrackersQueryChecker($this->event_manager);
    }

    public function testItDoesNotRaiseAnExceptionForCriterionProvidedByPlugin()
    {
        $this->event_manager->shouldReceive("processEvent")->with(
            \Mockery::on(
                function (GetAdditionalCriteria $event) {
                    $event->addCriteria("with_whatever", "'with_whatever': true");
                    return true;
                }
            )
        );

        $json_query = ["with_whatever" => true];
        $this->assertNull($this->checker->checkQuery($json_query, false));
    }

    public function testItRaiseAnExceptionForCriterionProvidedByPlugin()
    {
        $this->event_manager->shouldReceive("processEvent")->with(
            \Mockery::on(
                function (GetAdditionalCriteria $event) {
                    $event->addCriteria("with_whatever", "'with_whatever': true");
                    return true;
                }
            )
        );

        $json_query = ["whatever" => true];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->checker->checkQuery($json_query);
    }

    public function testYouAreNotAdministratorOfAtLeastOneTrackerIsNotSupported()
    {
        $this->event_manager->shouldReceive("processEvent");
        $json_query = ["is_tracker_admin" => false];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->checker->checkQuery($json_query);
    }

    public function testItPassesWhenIsTrackerAdminIsValid()
    {
        $this->event_manager->shouldReceive("processEvent");
        $json_query = ["is_tracker_admin" => true];
        $this->assertNull($this->checker->checkQuery($json_query));
    }
}
