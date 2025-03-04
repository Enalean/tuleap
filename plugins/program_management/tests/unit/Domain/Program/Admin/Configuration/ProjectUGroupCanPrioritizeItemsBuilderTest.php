<?php
/**
 * Copyright (c) Enalean 2022 - Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Domain\Program\Admin\Configuration;

use Tuleap\ProgramManagement\Adapter\Program\Admin\CanPrioritizeItems\UGroupRepresentationBuilder;
use Tuleap\ProgramManagement\Domain\Program\Admin\ProgramForAdministrationIdentifier;
use Tuleap\ProgramManagement\Tests\Builder\ProgramForAdministrationIdentifierBuilder;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveProjectUgroupsCanPrioritizeItemsStub;
use Tuleap\ProgramManagement\Tests\Stub\RetrieveUGroupsStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class ProjectUGroupCanPrioritizeItemsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    private ProgramForAdministrationIdentifier $program;

    protected function setUp(): void
    {
        $this->program = ProgramForAdministrationIdentifierBuilder::build();
    }

    public function testBuildPresenterWithDynamicAndStaticProjectUGroup(): void
    {
        $ugroup_manager = RetrieveUGroupsStub::buildWithUGroups();

        $builder = new ProjectUGroupCanPrioritizeItemsBuilder(
            $ugroup_manager,
            RetrieveProjectUgroupsCanPrioritizeItemsStub::buildWithIds(3),
            new UGroupRepresentationBuilder()
        );

        $presenters = $builder->buildProjectUgroupCanPrioritizeItemsPresenters($this->program);

        self::assertCount(2, $presenters);
        self::assertSame('101_3', $presenters[0]->id);
        self::assertTrue($presenters[0]->is_selected);
        self::assertSame(105, $presenters[1]->id);
        self::assertFalse($presenters[1]->is_selected);
    }
}
