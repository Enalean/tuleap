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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class Tracker_UgroupMappingBuilderTest extends \PHPUnit\Framework\TestCase
{
    use \Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;

    public function testItBuildsAMappingBasedOnTheNames(): void
    {
        $template_ugroup_dev     = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('dev')->getMock();
        $template_ugroup_support = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('support')->getMock();
        $template_ugroup_staff   = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('staff')->getMock();
        $target_ugroup_dev       = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('DEV')->getMock();
        $target_ugroup_support   = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('support')->getMock();
        $target_ugroup_client    = Mockery::mock(\ProjectUGroup::class)->shouldReceive('getName')->andReturns('client')->getMock();

        $template_ugroup_support->shouldReceive('getId')->andReturns(1001);
        $target_ugroup_support->shouldReceive('getId')->andReturns(1002);

        $template_project      = Mockery::mock((\Project::class))->shouldReceive('getId')->andReturns(103)->getMock();
        $template_tracker      = Mockery::mock(Tracker::class)->shouldReceive('getProject')->andReturn($template_project)->getMock();
        $target_project        = Mockery::mock((\Project::class))->shouldReceive('getId')->andReturns(104)->getMock();
        $ugroup_manager        = \Mockery::spy(\UGroupManager::class);
        $permissions_retriever = \Mockery::spy(\Tracker_UgroupPermissionsGoldenRetriever::class);

        $permissions_retriever->shouldReceive('getListOfInvolvedStaticUgroups')->with($template_tracker)->andReturns([$template_ugroup_dev,
                                                                                                                           $template_ugroup_support, $template_ugroup_staff]);
        $ugroup_manager->shouldReceive('getStaticUGroups')->with($target_project)->andReturns([$target_ugroup_dev,
                                                                                                    $target_ugroup_support, $target_ugroup_client]);

        $builder = new Tracker_UgroupMappingBuilder($permissions_retriever, $ugroup_manager);

        $mapping = $builder->getMapping($template_tracker, $target_project);

        $this->assertEquals([1001 => 1002], $mapping);
    }
}
