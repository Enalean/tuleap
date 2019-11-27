<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

class ProjectDashboardRetrieverTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /** @var Project */
    private $project_with_a_dashboard;

    /** @var Project */
    private $project_without_dashboard;

    /** @var ProjectDashboardRetriever */
    private $project_retriever;

    protected function setUp(): void
    {
        $this->project_with_a_dashboard  = \Mockery::spy(\Project::class, ['getID' => 1, 'getUnixName' => false, 'isPublic' => false]);
        $this->project_without_dashboard = \Mockery::spy(\Project::class, ['getID' => 2, 'getUnixName' => false, 'isPublic' => false]);

        $dao = \Mockery::spy(\Tuleap\Dashboard\Project\ProjectDashboardDao::class);

        $dao->shouldReceive('searchAllProjectDashboards')->with(1)->andReturns(\TestHelper::arrayToDar(array(
            'id'         => 1,
            'project_id' => 1,
            'name'       => 'dashboard_one'
        )));
        $dao->shouldReceive('searchAllProjectDashboards')->with(2)->andReturns(\TestHelper::emptyDar());

        $this->project_retriever = new ProjectDashboardRetriever($dao);
    }

    public function testItGetsAllDashboards()
    {
        $result = $this->project_retriever->getAllProjectDashboards($this->project_with_a_dashboard);

        $expected_result = array(
            new ProjectDashboard(1, 1, 'dashboard_one')
        );

        $this->assertEquals($expected_result, $result);
    }

    public function testItReturnsAnEmptyIfThereAreNoDashboards()
    {
        $result = $this->project_retriever->getAllProjectDashboards($this->project_without_dashboard);

        $this->assertEmpty($result);
    }
}
