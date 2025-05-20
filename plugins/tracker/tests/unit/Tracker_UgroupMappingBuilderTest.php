<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class Tracker_UgroupMappingBuilderTest extends \Tuleap\Test\PHPUnit\TestCase // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function testItBuildsAMappingBasedOnTheNames(): void
    {
        $template_ugroup_dev = $this->createMock(\ProjectUGroup::class);
        $template_ugroup_dev->method('getName')->willReturn('dev');

        $template_ugroup_support = $this->createMock(\ProjectUGroup::class);
        $template_ugroup_support->method('getName')->willReturn('support');

        $template_ugroup_staff = $this->createMock(\ProjectUGroup::class);
        $template_ugroup_staff->method('getName')->willReturn('staff');

        $target_ugroup_dev = $this->createMock(\ProjectUGroup::class);
        $target_ugroup_dev->method('getName')->willReturn('DEV');

        $target_ugroup_support = $this->createMock(\ProjectUGroup::class);
        $target_ugroup_support->method('getName')->willReturn('support');

        $target_ugroup_client = $this->createMock(\ProjectUGroup::class);
        $target_ugroup_client->method('getName')->willReturn('client');


        $template_ugroup_support->method('getId')->willReturn(1001);
        $target_ugroup_support->method('getId')->willReturn(1002);

        $template_project      = ProjectTestBuilder::aProject()->withId(103)->build();
        $template_tracker      = TrackerTestBuilder::aTracker()->withProject($template_project)->build();
        $target_project        = ProjectTestBuilder::aProject()->withId(104)->build();
        $ugroup_manager        = $this->createMock(\UGroupManager::class);
        $permissions_retriever = $this->createMock(\Tracker_UgroupPermissionsGoldenRetriever::class);

        $permissions_retriever->method('getListOfInvolvedStaticUgroups')->with($template_tracker)->willReturn([$template_ugroup_dev,
            $template_ugroup_support, $template_ugroup_staff,
        ]);
        $ugroup_manager->method('getStaticUGroups')->with($target_project)->willReturn([$target_ugroup_dev,
            $target_ugroup_support, $target_ugroup_client,
        ]);

        $builder = new Tracker_UgroupMappingBuilder($permissions_retriever, $ugroup_manager);

        $mapping = $builder->getMapping($template_tracker, $target_project);

        $this->assertEquals([1001 => 1002], $mapping);
    }
}
