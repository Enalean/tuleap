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

use Mockery as M;
use PHPUnit\Framework\TestCase;
use ProjectUGroup;
use Tracker_FormElement;
use Tuleap\Tracker\PermissionsFunctionsWrapper;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;

final class PermissionsForGroupsBuilderTest extends TestCase
{
    use M\Adapter\Phpunit\MockeryPHPUnitIntegration;

    /**
     * @var M\MockInterface|\UGroupManager
     */
    private $ugroup_manager;
    /**
     * @var M\MockInterface|FrozenFieldDetector
     */
    private $frozen_detector;
    /**
     * @var PermissionsForGroupsBuilder
     */
    private $builder;
    /**
     * @var M\MockInterface|\PFUser
     */
    private $tracker_admin_user;
    /**
     * @var M\MockInterface|\Tracker
     */
    private $tracker;
    /**
     * @var M\MockInterface|PermissionsFunctionsWrapper
     */
    private $permissions_functions;
    /**
     * @var M\MockInterface|\Project
     */
    private $project;

    protected function setUp(): void
    {
        $this->tracker_admin_user = M::mock(\PFUser::class);

        $this->project = M::mock(\Project::class, ['getID' => 202]);

        $this->tracker = M::mock(\Tracker::class, ['getID' => 12, 'getProject' => $this->project]);
        $this->tracker->shouldReceive('userIsAdmin')->with($this->tracker_admin_user)->andReturnTrue();

        $this->ugroup_manager = M::mock(\UGroupManager::class);
        $this->frozen_detector = M::mock(FrozenFieldDetector::class);
        $this->permissions_functions = M::mock(PermissionsFunctionsWrapper::class);
        $this->builder = new PermissionsForGroupsBuilder($this->ugroup_manager, $this->frozen_detector, $this->permissions_functions);
    }

    public function testItDoesntReturnFieldsForNonAdminUsers(): void
    {
        $a_random_user = M::mock(\PFUser::class);

        $this->tracker->shouldReceive('userIsAdmin')->with($a_random_user)->andReturnFalse();

        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getTracker' => $this->tracker]);
        $this->assertNull($this->builder->getPermissionsForGroups($form_element, null, $a_random_user));
    }

    public function testItReturnsNullWhenThereAreNoPermissionsSet(): void
    {
        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getId' => 1234, 'getTracker' => $this->tracker]);

        $this->permissions_functions->shouldReceive('getFieldUGroupsPermissions')->with($form_element)->andReturn([]);

        $this->assertNull($this->builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user));
    }

    public function testItReturnsEmptyRepresentationWhenNoPermissionsMatches(): void
    {
        $field_id = 1234;
        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getId' => $field_id, 'getTracker' => $this->tracker]);

        $this->permissions_functions->shouldReceive('getFieldUGroupsPermissions')->with($form_element)->andReturn(
            [
                $field_id => [
                    'ugroups' => [
                    ]
                ]
            ]
        );

        $representation = $this->builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertEquals(new PermissionsForGroupsRepresentation(), $representation);
    }

    public function testItReturnsOneGroupThatCanRead(): void
    {
        $field_id = 1234;
        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getId' => $field_id, 'getTracker' => $this->tracker]);

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::ANONYMOUS)->andReturn($anonymous_ugroup);

        $this->permissions_functions->shouldReceive('getFieldUGroupsPermissions')->with($form_element)->andReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_READ => 1
                            ]
                        ]
                    ]
                ]
            ]
        );

        $representation = $this->builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_update);
        $this->assertEmpty($representation->can_submit);
        $this->assertCount(1, $representation->can_read);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_read[0]->id);
    }

    public function testItReturnsOneGroupThatCanSubmit(): void
    {
        $field_id = 1234;
        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getId' => $field_id, 'getTracker' => $this->tracker]);

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::ANONYMOUS)->andReturn($anonymous_ugroup);

        $this->permissions_functions->shouldReceive('getFieldUGroupsPermissions')->with($form_element)->andReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_SUBMIT => 1
                            ]
                        ]
                    ]
                ]
            ]
        );

        $representation = $this->builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_update);
        $this->assertEmpty($representation->can_read);
        $this->assertCount(1, $representation->can_submit);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_submit[0]->id);
    }

    public function testItReturnsOneGroupThatCanUpdate(): void
    {
        $field_id = 1234;
        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getId' => $field_id, 'getTracker' => $this->tracker]);

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::ANONYMOUS)->andReturn($anonymous_ugroup);

        $this->permissions_functions->shouldReceive('getFieldUGroupsPermissions')->with($form_element)->andReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_UPDATE => 1
                            ]
                        ]
                    ]
                ]
            ]
        );

        $representation = $this->builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertCount(1, $representation->can_update);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_update[0]->id);
    }

    public function testItExcludedFromUpdateGroupsThatAreFrozenWhenThereIsAnArtifact()
    {
        $field_id = 1234;
        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getId' => $field_id, 'getTracker' => $this->tracker]);

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::ANONYMOUS)->andReturn($anonymous_ugroup);

        $this->permissions_functions->shouldReceive('getFieldUGroupsPermissions')->with($form_element)->andReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_UPDATE => 1
                            ]
                        ]
                    ]
                ]
            ]
        );

        $artifact = M::mock(\Tracker_Artifact::class);
        $this->frozen_detector->shouldReceive('isFieldFrozen')->with($artifact, $form_element)->andReturnFalse();

        $representation = $this->builder->getPermissionsForGroups($form_element, $artifact, $this->tracker_admin_user);
        $this->assertCount(1, $representation->can_update);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_update[0]->id);
    }

    public function testItAllowUpdateWhenUseArifactButFieldIsNotFrozen()
    {
        $field_id = 1234;
        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getId' => $field_id, 'getTracker' => $this->tracker]);

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::ANONYMOUS)->andReturn($anonymous_ugroup);

        $this->permissions_functions->shouldReceive('getFieldUGroupsPermissions')->with($form_element)->andReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_UPDATE => 1
                            ]
                        ]
                    ]
                ]
            ]
        );

        $artifact = M::mock(\Tracker_Artifact::class);
        $this->frozen_detector->shouldReceive('isFieldFrozen')->with($artifact, $form_element)->andReturnTrue();

        $representation = $this->builder->getPermissionsForGroups($form_element, $artifact, $this->tracker_admin_user);
        $this->assertEmpty($representation->can_submit);
        $this->assertEmpty($representation->can_read);
        $this->assertEmpty($representation->can_update);
    }


    public function testItReturnsACompleteDefinitionOfGroups(): void
    {
        $field_id = 1234;
        $form_element  = M::mock(\Tracker_FormElement_Field::class, ['getId' => $field_id, 'getTracker' => $this->tracker]);

        $anonymous_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::ANONYMOUS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::ANONYMOUS],
            'group_id' => 202,
        ]);
        $project_members_ugroup = new ProjectUGroup([
            'ugroup_id' => ProjectUGroup::PROJECT_MEMBERS,
            'name' => ProjectUGroup::$normalized_names[ProjectUGroup::PROJECT_MEMBERS],
            'group_id' => 202,
        ]);
        $developers_id = 501;
        $static_ugroup = new ProjectUGroup([
            'ugroup_id' => $developers_id,
            'name' => 'Developers',
            'group_id' => 202,
        ]);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::ANONYMOUS)->andReturn($anonymous_ugroup);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, ProjectUGroup::PROJECT_MEMBERS)->andReturn($project_members_ugroup);
        $this->ugroup_manager->shouldReceive('getUGroup')->with($this->project, $developers_id)->andReturn($static_ugroup);

        $this->permissions_functions->shouldReceive('getFieldUGroupsPermissions')->with($form_element)->andReturn(
            [
                $field_id => [
                    'ugroups' => [
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::ANONYMOUS
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_READ => 1
                            ]
                        ],
                        [
                            'ugroup' => [
                                'id' => ProjectUGroup::PROJECT_MEMBERS
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_SUBMIT => 1,
                                Tracker_FormElement::PERMISSION_UPDATE => 1,
                            ]
                        ],
                        [
                            'ugroup' => [
                                'id' => $developers_id
                            ],
                            'permissions' => [
                                Tracker_FormElement::PERMISSION_SUBMIT => 1,
                                Tracker_FormElement::PERMISSION_UPDATE => 1,
                            ]
                        ]
                    ]
                ]
            ]
        );

        $representation = $this->builder->getPermissionsForGroups($form_element, null, $this->tracker_admin_user);
        $this->assertEquals(ProjectUGroup::ANONYMOUS, $representation->can_read[0]->id);

        $this->assertCount(2, $representation->can_submit);
        $this->assertEquals($this->project->getID() . '_' . ProjectUGroup::PROJECT_MEMBERS, $representation->can_submit[0]->id);
        $this->assertEquals($developers_id, $representation->can_submit[1]->id);

        $this->assertCount(2, $representation->can_update);
        $this->assertEquals($this->project->getID() . '_' . ProjectUGroup::PROJECT_MEMBERS, $representation->can_update[0]->id);
        $this->assertEquals($developers_id, $representation->can_update[1]->id);
    }
}
