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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\project\REST\v1\GetProjectsQueryChecker;

class GetProjectsQueryCheckerTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * GetProjectsQueryChecker
     */
    private $checker;

    public function setUp()
    {
        $this->checker = new GetProjectsQueryChecker();
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     */
    public function testItRaiseExeptionWhenNotSupportedQuery()
    {
        $json_query = ["whatever" => true];
        $this->checker->checkQuery($json_query, false);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     */
    public function testProjectsYouAreNotMemberOfIsNotSupported()
    {
        $json_query = ["is_member_of" => false];
        $this->checker->checkQuery($json_query, false);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     */
    public function testProjectsYouAreNotAdministratorOfAtLeastOneTrackerIsNotSupported()
    {
        $json_query = ["is_tracker_admin" => false];
        $this->checker->checkQuery($json_query, false);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 403
     */
    public function testItRaiseExeptionWhenWithStatusAndUserIsNotProjectManager()
    {
        $json_query = ["with_status" => true];
        $this->checker->checkQuery($json_query, false);
    }

    /**
     * @expectedException \Luracast\Restler\RestException
     * @expectedExceptionCode 400
     */
    public function testItRaiseExeptionWhenWithStatusIsNotValid()
    {
        $json_query = ["with_status" => false];
        $this->checker->checkQuery($json_query, true);
    }

    public function testItPassesWhenWithStatusIsValid()
    {
        $json_query = ["with_status" => "active"];
        $this->assertNull($this->checker->checkQuery($json_query, true));
    }
}
