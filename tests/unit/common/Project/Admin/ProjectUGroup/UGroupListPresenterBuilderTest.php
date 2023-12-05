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

use PHPUnit\Framework\MockObject\MockObject;
use Tuleap\GlobalLanguageMock;
use Tuleap\Project\UGroups\SynchronizedProjectMembershipDetector;
use Tuleap\Test\Builders\ProjectTestBuilder;

final class UGroupListPresenterBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use GlobalLanguageMock;

    private UGroupListPresenterBuilder $builder;
    private \UGroupManager&MockObject $ugroup_manager;
    private SynchronizedProjectMembershipDetector&MockObject $detector;

    protected function setUp(): void
    {
        $this->ugroup_manager = $this->createMock(\UGroupManager::class);
        $this->detector       = $this->createMock(SynchronizedProjectMembershipDetector::class);

        $this->builder = new UGroupListPresenterBuilder($this->ugroup_manager, $this->detector);

        $this->ugroup_manager->method('getStaticUgroups')->willReturn([]);
        $mock_ugroup = $this->createMock(\ProjectUGroup::class);
        $mock_ugroup->method('getId')->willReturn(15);
        $mock_ugroup->method('getTranslatedName')->willReturn('');
        $mock_ugroup->method('getTranslatedDescription')->willReturn('');
        $mock_ugroup->method('countStaticOrDynamicMembers')->willReturn(0);
        $this->ugroup_manager->method('getUGroup')->willReturn($mock_ugroup);

        $GLOBALS['Language']->method('getText')->willReturn('whatever');
    }

    public function testItBuildsASynchronizedProjectMembershipPresenterForPublicProject(): void
    {
        $csrf    = $this->createMock(\CSRFSynchronizerToken::class);
        $project = ProjectTestBuilder::aProject()
            ->withId(106)
            ->withAccessPublic()
            ->withoutServices()
            ->build();
        $this->detector->method('isSynchronizedWithProjectMembers')->willReturn(true);

        $result = $this->builder->build($project, $csrf, $csrf);

        self::assertTrue($result->is_synchronized_project_membership);
        self::assertNotNull($result->synchronized_project_membership_presenter);
        self::assertTrue($result->synchronized_project_membership_presenter->is_enabled);
    }

    public function testItDoesNotBuildASynchronizedPresenterForPrivateProject(): void
    {
        $csrf    = $this->createMock(\CSRFSynchronizerToken::class);
        $project = ProjectTestBuilder::aProject()
            ->withId(106)
            ->withAccessPrivate()
            ->withoutServices()
            ->build();
        $this->detector->method('isSynchronizedWithProjectMembers')->willReturn(true);

        $result = $this->builder->build($project, $csrf, $csrf);

        self::assertTrue($result->is_synchronized_project_membership);
        self::assertNull($result->synchronized_project_membership_presenter);
    }

    public function testItBuildsASynchronizedProjectMembershipPresenterForPublicProjectWithoutSynchronization(): void
    {
        $csrf    = $this->createMock(\CSRFSynchronizerToken::class);
        $project = ProjectTestBuilder::aProject()
            ->withId(106)
            ->withAccessPublic()
            ->withoutServices()
            ->build();
        $this->detector->method('isSynchronizedWithProjectMembers')->willReturn(false);

        $result = $this->builder->build($project, $csrf, $csrf);

        self::assertFalse($result->is_synchronized_project_membership);
        self::assertNotNull($result->synchronized_project_membership_presenter);
        self::assertFalse($result->synchronized_project_membership_presenter->is_enabled);
    }
}
