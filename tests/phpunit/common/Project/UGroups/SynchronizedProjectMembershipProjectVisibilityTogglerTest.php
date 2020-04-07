<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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
 *
 */

declare(strict_types=1);

namespace Tuleap\Project\UGroups;

use Mockery as M;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Project;
use Tuleap\GlobalLanguageMock;

final class SynchronizedProjectMembershipProjectVisibilityTogglerTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var SynchronizedProjectMembershipProjectVisibilityToggler
     */
    private $toggler;
    /**
     * @var M\MockInterface|SynchronizedProjectMembershipDao
     */
    private $dao;

    protected function setUp(): void
    {
        $this->dao     = M::mock(SynchronizedProjectMembershipDao::class);
        $this->toggler = new SynchronizedProjectMembershipProjectVisibilityToggler($this->dao);
    }

    /**
     * @dataProvider toggleProvider
     */
    public function testToggleOfSynchroAccordingToVisibilityChange(string $old_visibility, string $new_visibility, bool $should_enable)
    {
        $project = new Project(['group_id' => 101]);

        if ($should_enable) {
            $this->dao->shouldReceive('enable')->with($project)->once();
        } else {
            $this->dao->shouldNotReceive('enable');
        }

        $this->toggler->enableAccordingToVisibility($project, $old_visibility, $new_visibility);
    }

    public function toggleProvider()
    {
        return [
            [ Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC, true ],
            [ Project::ACCESS_PRIVATE, Project::ACCESS_PUBLIC_UNRESTRICTED, true ],
            [ Project::ACCESS_PRIVATE, Project::ACCESS_PRIVATE_WO_RESTRICTED, false ],
            [ Project::ACCESS_PRIVATE_WO_RESTRICTED, Project::ACCESS_PUBLIC, true ],
            [ Project::ACCESS_PRIVATE_WO_RESTRICTED, Project::ACCESS_PUBLIC_UNRESTRICTED, true ],
            [ Project::ACCESS_PRIVATE_WO_RESTRICTED, Project::ACCESS_PRIVATE, false ],
            [ Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE, false ],
            [ Project::ACCESS_PUBLIC, Project::ACCESS_PRIVATE_WO_RESTRICTED, false ],
            [ Project::ACCESS_PUBLIC, Project::ACCESS_PUBLIC_UNRESTRICTED, false ],
            [ Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PRIVATE, false ],
            [ Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PRIVATE_WO_RESTRICTED, false ],
            [ Project::ACCESS_PUBLIC_UNRESTRICTED, Project::ACCESS_PUBLIC, false ],
        ];
    }
}
