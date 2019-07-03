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
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDao;

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
     * @var Mockery\MockInterface|SynchronizedProjectMembershipDao
     */
    private $synchronized_dao;
    /**
     * @var Mockery\MockInterface|\Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->ugroup_manager   = Mockery::mock(\UGroupManager::class);
        $this->synchronized_dao = Mockery::mock(SynchronizedProjectMembershipDao::class);

        $this->builder = new UGroupListPresenterBuilder($this->ugroup_manager, $this->synchronized_dao);

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

    public function testItBuildsASynchronizedProjectMembershipPresenterForPublicProject()
    {
        $csrf = Mockery::mock(\CSRFSynchronizerToken::class);
        $this->project->shouldReceive('isPublic')->andReturnTrue();
        $this->synchronized_dao->shouldReceive('isEnabled')->andReturnTrue();

        $result = $this->builder->build($this->project, $csrf);

        $this->assertNotNull($result->synchronized_project_membership_presenter);
        $this->assertTrue($result->synchronized_project_membership_presenter->is_enabled);
    }

    public function testItDoesNotBuildASynchronizedPresenterForPrivateProject()
    {
        $csrf = Mockery::mock(\CSRFSynchronizerToken::class);
        $this->project->shouldReceive('isPublic')->andReturnFalse();

        $result = $this->builder->build($this->project, $csrf);

        $this->assertNull($result->synchronized_project_membership_presenter);
    }
}
