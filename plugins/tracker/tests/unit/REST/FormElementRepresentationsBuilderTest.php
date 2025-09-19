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

declare(strict_types=1);

namespace Tuleap\Tracker\REST;

use Tracker_FormElementFactory;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Tracker\FormElement\Container\Fieldset\HiddenFieldsetChecker;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildPresenter;
use Tuleap\Tracker\FormElement\Field\String\StringField;
use Tuleap\Tracker\FormElement\TrackerFormElement;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsBuilder;
use Tuleap\Tracker\REST\FormElement\PermissionsForGroupsRepresentation;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;
use Tuleap\Tracker\Test\Stub\RetrieveAllUsableTypesInProjectStub;

#[\PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles]
final class FormElementRepresentationsBuilderTest extends \Tuleap\Test\PHPUnit\TestCase
{
    public function testItReturnsAnArrayEvenWhenFieldsAreNotReadable(): void
    {
        $user = UserTestBuilder::buildWithDefaults();

        $field1 = $this->createMock(StringField::class);
        $field1->method('getId')->willReturn(1);
        $field1->method('getName')->willReturn('field_01');
        $field1->method('getLabel')->willReturn('Field 01');
        $field1->method('isRequired')->willReturn(false);
        $field1->method('isCollapsed')->willReturn(false);
        $field1->method('getDefaultRESTValue')->willReturn(null);
        $field1->method('getRESTAvailableValues')->willReturn(null);
        $field1->method('userCanRead')->willReturn(true);
        $field1->method('getRESTBindingProperties')->willReturn([
            'bind_type' => null,
            'bind_list' => [],
        ]);

        $field2 = $this->createMock(StringField::class);
        $field2->method('getId')->willReturn(2);
        $field2->method('getName')->willReturn('field_02');
        $field2->method('getLabel')->willReturn('Field 02');
        $field2->method('isRequired')->willReturn(false);
        $field2->method('isCollapsed')->willReturn(false);
        $field2->method('getDefaultRESTValue')->willReturn(null);
        $field2->method('getRESTAvailableValues')->willReturn(null);
        $field2->method('userCanRead')->willReturn(false);
        $field2->method('getRESTBindingProperties')->willReturn([
            'bind_type' => null,
            'bind_list' => [],
        ]);

        $field3 = $this->createMock(StringField::class);
        $field3->method('getId')->willReturn(3);
        $field3->method('getName')->willReturn('field_03');
        $field3->method('getLabel')->willReturn('Field 03');
        $field3->method('isRequired')->willReturn(false);
        $field3->method('isCollapsed')->willReturn(false);
        $field3->method('getDefaultRESTValue')->willReturn(null);
        $field3->method('getRESTAvailableValues')->willReturn(null);
        $field3->method('userCanRead')->willReturn(true);
        $field3->method('getRESTBindingProperties')->willReturn([
            'bind_type' => null,
            'bind_list' => [],
        ]);

        $form_element_factory           = $this->createMock(Tracker_FormElementFactory::class);
        $permission_exporter            = $this->createMock(PermissionsExporter::class);
        $hidden_fieldset_checker        = $this->createMock(HiddenFieldsetChecker::class);
        $permissions_for_groups_builder = $this->createMock(PermissionsForGroupsBuilder::class);

        $permissions_for_groups_builder->expects($this->exactly(2))
            ->method('getPermissionsForGroups')
            ->willReturnCallback(
                static fn (TrackerFormElement $form_element) => match ($form_element) {
                    $field1, $field3 => new PermissionsForGroupsRepresentation([], [], []),
                }
            );

        $builder = new FormElementRepresentationsBuilder(
            $form_element_factory,
            $permission_exporter,
            $hidden_fieldset_checker,
            $permissions_for_groups_builder,
            RetrieveAllUsableTypesInProjectStub::withUsableTypes(
                new TypeIsChildPresenter()
            )
        );

        $form_element_factory->method('getAllUsedFormElementOfAnyTypesForTracker')
            ->willReturn([$field1, $field2, $field3]);

        $form_element_factory->method('getType')->willReturn('string');

        $permission_exporter->method('exportUserPermissionsForFieldWithoutWorkflowComputedPermissions')
            ->willReturn([]);

        $tracker = TrackerTestBuilder::aTracker()->build();

        $collection = $builder->buildRepresentationsInTrackerContext($tracker, $user);

        $this->assertCount(2, $collection);
    }
}
