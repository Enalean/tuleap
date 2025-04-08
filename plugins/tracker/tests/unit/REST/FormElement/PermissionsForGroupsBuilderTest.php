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

namespace Tuleap\Tracker\REST\FormElement;

use PFUser;
use PHPUnit\Framework\Attributes\DisableReturnValueGenerationForTestDoubles;
use PHPUnit\Framework\MockObject\MockObject;
use Project;
use ProjectUGroup;
use Tracker;
use Tracker_FormElement;
use Tuleap\Test\Builders\ProjectTestBuilder;
use Tuleap\Test\Builders\UserTestBuilder;
use Tuleap\Test\PHPUnit\TestCase;
use Tuleap\Test\Stubs\UGroupRetrieverStub;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\Test\Builders\Fields\IntFieldBuilder;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

#[DisableReturnValueGenerationForTestDoubles]
final class PermissionsForGroupsBuilderTest extends TestCase
{
    private FrozenFieldDetector&MockObject $frozen_detector;
    private PFUser $tracker_admin_user;
    private Tracker&MockObject $tracker;
    private PermissionsFunctionsWrapper&MockObject $permissions_functions;
    private Project $project;

    protected function setUp(): void
    {
        $this->tracker_admin_user = UserTestBuilder::buildWithDefaults();

        $this->project = ProjectTestBuilder::aProject()->withId(202)->build();

        $this->tracker = $this->createMock(Tracker::class);
        $this->tracker->method('getID')->willReturn(12);
        $this->tracker->method('getProject')->willReturn($this->project);
        $this->tracker->method('userIsAdmin')->willReturnCallback(fn (PFUser $user) => $user === $this->tracker_admin_user);

        $this->frozen_detector       = $this->createMock(FrozenFieldDetector::class);
        $this->permissions_functions = $this->createMock(PermissionsFunctionsWrapper::class);
    }

    public function testItDoesntReturnFieldsForNonAdminUsers(): void
    {
        $a_random_user = UserTestBuilder::aRandomActiveUser()->build();

        $form_element = IntFieldBuilder::anIntField(1234)->inTracker($this->tracker)->build();

        $builder = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups(...[]),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $this->assertNull($builder->getPermissionsForGroups($form_element, null, $a_random_user));
    }

    public function testItReturnsNullWhenThereAreNoPermissionsSet(): void
    {
        $form_element = IntFieldBuilder::anIntField(1234)->inTracker($this->tracker)->build();

        $this->permissions_functions->method('getFieldUGroupsPermissions')->with($form_element)->willReturn([]);

        $builder = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups(...[]),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $this->assertNull($builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user));
    }

    public function testItReturnsEmptyRepresentationWhenNoPermissionsMatches(): void
    {
        $field_id     = 1234;
        $form_element = IntFieldBuilder::anIntField($field_id)->inTracker($this->tracker)->build();

        $this->permissions_functions->method('getFieldUGroupsPermissions')->with($form_element)->willReturn(
            [
                $field_id => [
                    'ugroups' => [
                    ],
                ],
            ]
        );

        $builder        = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups(...[]),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $representation = $builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertEquals(new PermissionsForGroupsRepresentation([], [], []), $representation);
    }

    public function testItReturnsOneGroupThatCanRead(): void
    {
        $field_id     = 1234;
        $form_element = IntFieldBuilder::anIntField($field_id)->inTracker($this->tracker)->build();

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);

        $this->permissions_functions->method('getFieldUGroupsPermissions')->with($form_element)->willReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS,
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_READ => 1,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $builder        = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups($anonymous_ugroup),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $representation = $builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_update);
        $this->assertEmpty($representation->can_submit);
        $this->assertCount(1, $representation->can_read);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_read[0]->id);
    }

    public function testItReturnsOneGroupThatCanSubmit(): void
    {
        $field_id     = 1234;
        $form_element = IntFieldBuilder::anIntField($field_id)->inTracker($this->tracker)->build();

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);

        $this->permissions_functions->method('getFieldUGroupsPermissions')->with($form_element)->willReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS,
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_SUBMIT => 1,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $builder        = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups($anonymous_ugroup),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $representation = $builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_update);
        $this->assertEmpty($representation->can_read);
        $this->assertCount(1, $representation->can_submit);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_submit[0]->id);
    }

    public function testItReturnsOneGroupThatCanUpdate(): void
    {
        $field_id     = 1234;
        $form_element = IntFieldBuilder::anIntField($field_id)->inTracker($this->tracker)->build();

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);

        $this->permissions_functions->method('getFieldUGroupsPermissions')->with($form_element)->willReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS,
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_UPDATE => 1,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $builder        = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups($anonymous_ugroup),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $representation = $builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertCount(1, $representation->can_update);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_update[0]->id);
    }

    public function testItExcludedFromUpdateGroupsThatAreFrozenWhenThereIsAnArtifact(): void
    {
        $field_id     = 1234;
        $form_element = IntFieldBuilder::anIntField($field_id)->inTracker($this->tracker)->build();

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);

        $this->permissions_functions->method('getFieldUGroupsPermissions')->with($form_element)->willReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS,
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_UPDATE => 1,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $artifact = $this->createMock(Artifact::class);
        $this->frozen_detector->method('isFieldFrozen')->with($artifact, $form_element)->willReturn(false);

        $builder        = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups($anonymous_ugroup),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $representation = $builder->getPermissionsForGroups($form_element, $artifact, $this->tracker_admin_user);
        $this->assertCount(1, $representation->can_update);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_update[0]->id);
    }

    public function testItAllowUpdateWhenUseArifactButFieldIsNotFrozen(): void
    {
        $field_id     = 1234;
        $form_element = IntFieldBuilder::anIntField($field_id)->inTracker($this->tracker)->build();

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);

        $this->permissions_functions->method('getFieldUGroupsPermissions')->with($form_element)->willReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS,
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_UPDATE => 1,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $artifact = $this->createMock(Artifact::class);
        $this->frozen_detector->method('isFieldFrozen')->with($artifact, $form_element)->willReturn(true);

        $builder        = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups($anonymous_ugroup),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $representation = $builder->getPermissionsForGroups($form_element, $artifact, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_submit);
        $this->assertEmpty($representation->can_read);
        $this->assertEmpty($representation->can_update);
    }

    public function testItReturnsACompleteDefinitionOfGroups(): void
    {
        $field_id     = 1234;
        $form_element = IntFieldBuilder::anIntField($field_id)->inTracker($this->tracker)->build();

        $anonymous_ugroup       = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);
        $project_members_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::NORMALIZED_NAMES[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => 202,
        ]);
        $developers_id          = 501;
        $static_ugroup          = new ProjectUGroup([
            'ugroup_id' => $developers_id,
            'name' => 'Developers',
            'group_id' => 202,
        ]);

        $this->permissions_functions->method('getFieldUGroupsPermissions')->with($form_element)->willReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS,
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_READ => 1,
                            ],
                        ],
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::PROJECT_MEMBERS,
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_SUBMIT => 1,
                                Tracker_FormElement::PERMISSION_UPDATE => 1,
                            ],
                        ],
                        [
                            'ugroup' => [
                                'id' => $developers_id,
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_SUBMIT => 1,
                                Tracker_FormElement::PERMISSION_UPDATE => 1,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $builder        = new PermissionsForGroupsBuilder(
            UGroupRetrieverStub::buildWithUserGroups($anonymous_ugroup, $project_members_ugroup, $static_ugroup),
            $this->frozen_detector,
            $this->permissions_functions,
        );
        $representation = $builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_read[0]->id);

        $this->assertCount(2, $representation->can_submit);
        $this->assertEquals($this->project->getID() . '_' . ProjectUGroup::PROJECT_MEMBERS, $representation->can_submit[0]->id);
        $this->assertEquals($developers_id, $representation->can_submit[1]->id);

        $this->assertCount(2, $representation->can_update);
        $this->assertEquals($this->project->getID() . '_' . ProjectUGroup::PROJECT_MEMBERS, $representation->can_update[0]->id);
        $this->assertEquals($developers_id, $representation->can_update[1]->id);
    }
}
