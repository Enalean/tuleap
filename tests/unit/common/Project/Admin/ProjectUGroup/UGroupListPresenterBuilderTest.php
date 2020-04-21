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
 */

declare(strict_types=1);

namespace Tuleap\Project\Admin\ProjectUGroup;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\TestCase;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;

final class UGroupListPresenterBuilderTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use GlobalLanguageMock;

    /**
     * @var UGroupListPresenterBuilder
     */
    private $builder;
    /**
     * @var Mockery\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var Mockery\MockInterface|\Project
     */
    private $project;
    /**
     * @var Mockery\MockInterface|SynchronizedProjectMembershipDetector
     */
    private $detector;

    protected function setUp(): void
    {
        $this->ugroup_manager = Mockery::mock(\UGroupManager::class);
        $this->detector       = Mockery::mock(SynchronizedProjectMembershipDetector::class);

        $this->builder = new UGroupListPresenterBuilder($this->ugroup_manager, $this->detector);

        $this->ugroup_manager->shouldReceive('getStaticUgroups')->andReturn([]);
        $mock_ugroup = Mockery::mock(
            \ProjectUGroup::class,
            [
                'getId'                       => 15,
                'getTranslatedName'           => '',
                'getTranslatedDescription'    => '',
                'countStaticOrDynamicMembers' => 0
            ]
        );
        $this->ugroup_manager->shouldReceive('getUGroup')->andReturn($mock_ugroup);

        $this->project = Mockery::mock(
            \Project::class,
            [
                'getID'                   => 106,
                'isLegacyDefaultTemplate' => false,
                'usesWiki'                => false,
                'usesForum'               => false,
                'usesNews'                => false
            ]
        );
    }

    public function testItBuildsASynchronizedProjectMembershipPresenterForPublicProject(): void
    {
        $csrf = Mockery::mock(\CSRFSynchronizerToken::class);
        $this->project->shouldReceive('isPublic')->andReturnTrue();
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')->andReturnTrue();

        $result = $this->builder->build($this->project, $csrf, $csrf);

        $this->assertTrue($result->is_synchronized_project_membership);
        $this->assertNotNull($result->synchronized_project_membership_presenter);
        $this->assertTrue($result->synchronized_project_membership_presenter->is_enabled);
    }

    public function testItDoesNotBuildASynchronizedPresenterForPrivateProject(): void
    {
        $csrf = Mockery::mock(\CSRFSynchronizerToken::class);
        $this->project->shouldReceive('isPublic')->andReturnFalse();
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')->andReturnTrue();

        $result = $this->builder->build($this->project, $csrf, $csrf);

        $this->assertTrue($result->is_synchronized_project_membership);
        $this->assertNull($result->synchronized_project_membership_presenter);
    }

    public function testItBuildsASynchronizedProjectMembershipPresenterForPublicProjectWithoutSynchronization(): void
    {
        $csrf = Mockery::mock(\CSRFSynchronizerToken::class);
        $this->project->shouldReceive('isPublic')->andReturnTrue();
        $this->detector->shouldReceive('isSynchronizedWithProjectMembers')->andReturnFalse();

        $result = $this->builder->build($this->project, $csrf, $csrf);

        $this->assertFalse($result->is_synchronized_project_membership);
        $this->assertNotNull($result->synchronized_project_membership_presenter);
        $this->assertFalse($result->synchronized_project_membership_presenter->is_enabled);
    }
}
