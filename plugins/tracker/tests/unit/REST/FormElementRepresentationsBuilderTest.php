<?php
/**
 * Copyright (c) Enalean, 2019 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST;

use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PFUser;
use Tracker;
use Tracker_FormElement_Field_String;
use Tracker_FormElementFactory;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsRepresentation;
use Tuleap\Tracker\Test\Stub\RetrieveAllUsableTypesInProjectStub;

class FormElementRepresentationsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    use MockeryPHPUnitIntegration;

    public function testItReturnsAnArrayEvenWhenFieldsAreNotReadable()
    {
        $user = Mockery::mock(PFUser::class);

        $field1 = Mockery::mock(Tracker_FormElement_Field_String::class);
        $field1->shouldReceive('getId')->andReturn(1);
        $field1->shouldReceive('getName')->andReturn('field_01');
        $field1->shouldReceive('getLabel')->andReturn('Field 01');
        $field1->shouldReceive('isRequired')->andReturnFalse();
        $field1->shouldReceive('isCollapsed')->andReturnFalse();
        $field1->shouldReceive('getDefaultRESTValue')->andReturnNull();
        $field1->shouldReceive('getRESTAvailableValues')->andReturnNull();
        $field1->shouldReceive('userCanRead')->andReturnTrue();
        $field1->shouldReceive('getRESTBindingProperties')->andReturn([
            'bind_type' => null,
            'bind_list' => [],
        ]);

        $field2 = Mockery::mock(Tracker_FormElement_Field_String::class);
        $field2->shouldReceive('getId')->andReturn(2);
        $field2->shouldReceive('getName')->andReturn('field_02');
        $field2->shouldReceive('getLabel')->andReturn('Field 02');
        $field2->shouldReceive('isRequired')->andReturnFalse();
        $field2->shouldReceive('isCollapsed')->andReturnFalse();
        $field2->shouldReceive('getDefaultRESTValue')->andReturnNull();
        $field2->shouldReceive('getRESTAvailableValues')->andReturnNull();
        $field2->shouldReceive('userCanRead')->andReturnFalse();
        $field2->shouldReceive('getRESTBindingProperties')->andReturn([
            'bind_type' => null,
            'bind_list' => [],
        ]);

        $field3 = Mockery::mock(Tracker_FormElement_Field_String::class);
        $field3->shouldReceive('getId')->andReturn(3);
        $field3->shouldReceive('getName')->andReturn('field_03');
        $field3->shouldReceive('getLabel')->andReturn('Field 03');
        $field3->shouldReceive('isRequired')->andReturnFalse();
        $field3->shouldReceive('isCollapsed')->andReturnFalse();
        $field3->shouldReceive('getDefaultRESTValue')->andReturnNull();
        $field3->shouldReceive('getRESTAvailableValues')->andReturnNull();
        $field3->shouldReceive('userCanRead')->andReturnTrue();
        $field3->shouldReceive('getRESTBindingProperties')->andReturn([
            'bind_type' => null,
            'bind_list' => [],
        ]);

        $form_element_factory           = Mockery::mock(Tracker_FormElementFactory::class);
        $permission_exporter            = Mockery::mock(PermissionsExporter::class);
        $hidden_fieldset_checker        = Mockery::mock(HiddenFieldsetChecker::class);
        $permissions_for_groups_builder = Mockery::mock(PermissionsForGroupsBuilder::class);

        $permissions_for_groups_builder->shouldReceive('getPermissionsForGroups')->with($field1, null, $user)->once()->andReturn(new PermissionsForGroupsRepresentation([], [], []));
        $permissions_for_groups_builder->shouldReceive('getPermissionsForGroups')->with($field3, null, $user)->once()->andReturn(new PermissionsForGroupsRepresentation([], [], []));

        $builder = new FormElementRepresentationsBuilder(
            $form_element_factory,
            $permission_exporter,
            $hidden_fieldset_checker,
            $permissions_for_groups_builder,
            RetrieveAllUsableTypesInProjectStub::withUsableTypes(
                new TypeIsChildPresenter()
            )
        );

        $form_element_factory->shouldReceive('getAllUsedFormElementOfAnyTypesForTracker')
            ->andReturn([$field1, $field2, $field3]);

        $form_element_factory->shouldReceive('getType')->andReturn('string');

        $permission_exporter->shouldReceive('exportUserPermissionsForFieldWithoutWorkflowComputedPermissions')
            ->andReturn([]);

        $tracker = Mockery::mock(Tracker::class);

        $collection = $builder->buildRepresentationsInTrackerContext($tracker, $user);

        $this->assertCount(2, $collection);
    }
}
