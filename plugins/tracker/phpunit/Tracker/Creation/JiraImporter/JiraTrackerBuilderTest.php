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

namespace Tuleap\Tracker\Creation\JiraImporter;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class JiraTrackerBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItBuildsAListOfTracker(): void
    {
        $builder = new JiraTrackerBuilder();

        $wrapper = \Mockery::mock(ClientWrapper::class);
        $project_key = "IE";

        $tracker_one = new \stdClass();
        $tracker_one->id = "epic";
        $tracker_one->name = "Epics";

        $tracker_two = new \stdClass();
        $tracker_two->id = "issue";
        $tracker_two->name = "Issues";

        $project_details = new \stdClass();
        $project_details->issueTypes = [
            $tracker_one,
            $tracker_two
        ];

        $wrapper->shouldReceive('getUrl')->with("/project/" . $project_key)->andReturn($project_details);

        $expected_tracker_list = [
            ['id' => 'epic', 'name' => 'Epics'],
            ['id' => 'issue', 'name' => 'Issues'],
        ];

        $result = $builder->build($wrapper, $project_key);

        $this->assertEquals($expected_tracker_list, $result);
    }
}
