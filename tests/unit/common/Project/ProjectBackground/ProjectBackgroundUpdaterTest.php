<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
 */

declare(strict_types=1);

namespace Tuleap\Project\ProjectBackground;

use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;

final class ProjectBackgroundUpdaterTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    /**
     * @var \Mockery\LegacyMockInterface|\Mockery\MockInterface|ProjectBackgroundDao
     */
    private $project_background_dao;
    /**
     * @var ProjectBackgroundUpdater
     */
    private $project_background_updater;

    protected function setUp(): void
    {
        $this->project_background_dao     = \Mockery::mock(ProjectBackgroundDao::class);
        $this->project_background_updater = new ProjectBackgroundUpdater($this->project_background_dao);
    }

    public function testUpdatesAProjectBackground(): void
    {
        $this->project_background_dao->shouldReceive('setBackgroundByProjectID')->once();

        $this->project_background_updater->updateProjectBackground(
            $this->buildPermission(),
            ProjectBackgroundName::fromIdentifier('beach-daytime')
        );
    }

    public function testDeletesAProjectBackground(): void
    {
        $this->project_background_dao->shouldReceive('deleteBackgroundByProjectID')->once();

        $this->project_background_updater->deleteProjectBackground($this->buildPermission());
    }

    private function buildPermission(): UserCanModifyProjectBackgroundPermission
    {
        return new UserCanModifyProjectBackgroundPermission(\Project::buildForTest());
    }
}
