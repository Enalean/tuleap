<?php
/**
 * Copyright Enalean (c) 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Project\REST;

use EventManager;
use Luracast\Restler\RestException;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\Project\REST\v1\GetProjectsQueryChecker;
use Tuleap\REST\Event\GetAdditionalCriteria;

class GetProjectsQueryCheckerTest extends TestCase
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
        $this->checker       = new GetProjectsQueryChecker($this->event_manager);
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

        $this->checker->checkQuery($json_query, false);
    }

    public function testItRaiseExeptionWhenNotSupportedQuery()
    {
        $this->event_manager->shouldReceive("processEvent");
        $json_query = ["whatever" => true];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->checker->checkQuery($json_query, false);
    }

    public function testProjectsYouAreNotMemberOfIsNotSupported()
    {
        $this->event_manager->shouldReceive("processEvent");
        $json_query = ["is_member_of" => false];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->checker->checkQuery($json_query, false);
    }

    public function testProjectsYouAreNotAdministratorOfAtLeastOneTrackerIsNotSupported()
    {
        $this->event_manager->shouldReceive("processEvent");
        $json_query = ["is_tracker_admin" => false];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->checker->checkQuery($json_query, false);
    }

    public function testItRaiseExeptionWhenWithStatusAndUserIsNotProjectManager()
    {
        $this->event_manager->shouldReceive("processEvent");
        $json_query = ["with_status" => true];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(403);

        $this->checker->checkQuery($json_query, false);
    }

    public function testItRaiseExeptionWhenWithStatusIsNotValid()
    {
        $this->event_manager->shouldReceive("processEvent");
        $json_query = ["with_status" => false];

        $this->expectException(RestException::class);
        $this->expectExceptionCode(400);

        $this->checker->checkQuery($json_query, true);
    }

    public function testItPassesWhenWithStatusIsValid()
    {
        $this->event_manager->shouldReceive("processEvent");
        $json_query = ["with_status" => "active"];
        $this->assertNull($this->checker->checkQuery($json_query, true));
    }
}
