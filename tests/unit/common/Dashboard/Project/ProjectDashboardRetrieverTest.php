<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Dashboard\Project;

use PHPUnit\Framework\MockObject\MockObject;

class ProjectDashboardRetrieverTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /** @var Project */
    private $project_with_a_dashboard;

    /** @var Project */
    private $project_without_dashboard;

    /** @var ProjectDashboardRetriever */
    private $project_retriever;
    private ProjectDashboardDao&MockObject $dao;

    protected function setUp(): void
    {
        $this->project_with_a_dashboard = $this->createMock(\Project::class);
        $this->project_with_a_dashboard->method('getID')->willReturn(1);
        $this->project_without_dashboard = $this->createMock(\Project::class);
        $this->project_without_dashboard->method('getID')->willReturn(2);

        $this->dao = $this->createMock(\Tuleap\Dashboard\Project\ProjectDashboardDao::class);

        $this->project_retriever = new ProjectDashboardRetriever($this->dao);
    }

    public function testItGetsAllDashboards()
    {
        $this->dao->method('searchAllProjectDashboards')->with(1)->willReturn(\TestHelper::arrayToDar([
            'id' => 1,
            'project_id' => 1,
            'name' => 'dashboard_one',
        ]));
        $result = $this->project_retriever->getAllProjectDashboards($this->project_with_a_dashboard);

        $expected_result = [
            new ProjectDashboard(1, 1, 'dashboard_one'),
        ];

        self::assertEquals($expected_result, $result);
    }

    public function testItReturnsAnEmptyIfThereAreNoDashboards()
    {
        $this->dao->method('searchAllProjectDashboards')->with(2)->willReturn(\TestHelper::emptyDar());
        $result = $this->project_retriever->getAllProjectDashboards($this->project_without_dashboard);

        self::assertEmpty($result);
    }
}
