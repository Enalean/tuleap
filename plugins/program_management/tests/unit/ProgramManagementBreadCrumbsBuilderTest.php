<?php
/**
 * Copyright (c) Enalean, 2021 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement;

use Project;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProgramManagementBreadCrumbsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    /**
     * @var \PFUser&\PHPUnit\Framework\MockObject\Stub
     */
    private $user;
    private Project $project;

    #[\Override]
    protected function setUp(): void
    {
        $this->user = $this->createStub(\PFUser::class);

        $this->project = new Project(['unix_group_name' => 'my_project', 'group_id' => 101]);
    }

    public function testBreadcrumbHasAdministrationLinkIfUserIsProjectAdmin(): void
    {
        $this->user->method('isAdmin')->willReturn(true);

        $builder    = new ProgramManagementBreadCrumbsBuilder();
        $breadcrumb = $builder->build($this->project, $this->user);

        self::assertCount(1, $breadcrumb->getBreadcrumbs());
        self::assertEquals('Program', $breadcrumb->getBreadcrumbs()[0]->getLink()->getLabel());
        self::assertCount(1, $breadcrumb->getBreadcrumbs()[0]->getSubItems()->getSections());
        self::assertSame('Administration', $breadcrumb->getBreadcrumbs()[0]->getSubItems()->getSections()[0]->getLinks()[0]->getLabel());
    }

    public function testBreadcrumbHasNotAdministrationLinkIfUserIsNotProjectAdmin(): void
    {
        $this->user->method('isAdmin')->willReturn(false);

        $builder    = new ProgramManagementBreadCrumbsBuilder();
        $breadcrumb = $builder->build($this->project, $this->user);

        self::assertCount(1, $breadcrumb->getBreadcrumbs());
        self::assertEquals('Program', $breadcrumb->getBreadcrumbs()[0]->getLink()->getLabel());
        self::assertCount(0, $breadcrumb->getBreadcrumbs()[0]->getSubItems()->getSections());
    }
}
