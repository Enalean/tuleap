<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Test\Builders;

use Tracker;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;
use Tracker_FormElement_Field_OpenList;
use Tuleap\Test\Stubs\UGroupRetrieverStub;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUgroupsValueDao;

/**
 * @property
 */
final class TrackerFormElementOpenListBuilder
{
    /**
     * @var \Tracker_FormElement_Field_List_Bind_UsersValue[] array
     */
    private array $user_bind_values = [];
    /**
     * @var \Tracker_FormElement_Field_List_Bind_UgroupsValue[]
     */
    private array $user_group_bind_values = [];
    /**
     * @var \Tracker_FormElement_Field_List_Bind_StaticValue[]
     */
    private array $bind_static_values = [];
    /**
     * @var \ProjectUGroup[]
     */
    private array $user_groups = [];
    private int $field_id      = 123;
    private string $name       = "A field";
    private ?Tracker $tracker  = null;

    public static function aBind(): self
    {
        return new self();
    }

    public function withId(int $field_id): self
    {
        $this->field_id = $field_id;
        return $this;
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @psalm-param \ProjectUGroup[] $user_groups_list
     */
    public function withUserGroups(array $user_groups_list): self
    {
        $this->user_groups = $user_groups_list;

        foreach ($user_groups_list as $user_group) {
            $bind_value = \Tracker_FormElement_Field_List_Bind_UgroupsValue::fromUserGroup($user_group);

            $this->user_group_bind_values[] = $bind_value;
        }

        return $this;
    }

    public function withTracker(Tracker $tracker): self
    {
        $this->tracker = $tracker;

        return $this;
    }

    /**
     * @psalm-param \PFUser[] $user_list
     */
    public function withUsers(array $user_list): self
    {
        foreach ($user_list as $user) {
            $bind_value = \Tracker_FormElement_Field_List_Bind_UsersValue::fromUser($user, $user->getUserName());

            $this->user_bind_values[] = $bind_value;
        }

        return $this;
    }

    /**
     * @psalm-param string[] $static_values
     */
    public function withStaticValues(array $static_values): self
    {
        foreach ($static_values as $key => $value) {
            $bind_value = new \Tracker_FormElement_Field_List_Bind_StaticValue($key, $value, '', $key, false);

            $this->bind_static_values[] = $bind_value;
        }

        return $this;
    }

    public function buildUserBind(): \Tracker_FormElement_Field_List_Bind_Users
    {
        $field = new Tracker_FormElement_Field_OpenList($this->field_id, 1, 1, $this->name, "open_list_field", "", true, "P", false, false, 1);
        $bind  = new Tracker_FormElement_Field_List_Bind_Users(
            $field,
            $this->user_bind_values,
            [],
            [],
        );

        $field->setBind($bind);
        $this->setFieldTracker($field);

        return $bind;
    }

    public function buildUserGroupBind(): \Tracker_FormElement_Field_List_Bind_Ugroups
    {
        $field = new Tracker_FormElement_Field_OpenList($this->field_id, 1, 1, $this->name, "open_list_field", "", true, "P", false, false, 1);
        $bind  = new Tracker_FormElement_Field_List_Bind_Ugroups(
            $field,
            $this->user_group_bind_values,
            [],
            [],
            UGroupRetrieverStub::buildWithUserGroups(...$this->user_groups),
            (new class extends BindUgroupsValueDao {
            })
        );
        $field->setBind($bind);
        $this->setFieldTracker($field);

        return $bind;
    }

    public function buildStaticBind(): \Tracker_FormElement_Field_List_Bind_Static
    {
        $field = new Tracker_FormElement_Field_OpenList($this->field_id, 1, 1, $this->name, "open_list_field", "", true, "P", false, false, 1);
        $bind  = new \Tracker_FormElement_Field_List_Bind_Static(
            $field,
            $this->bind_static_values,
            [],
            [],
            []
        );
        $field->setBind($bind);
        $this->setFieldTracker($field);

        return $bind;
    }

    private function setFieldTracker(Tracker_FormElement_Field_OpenList $field): void
    {
        if ($this->tracker === null) {
            $this->tracker = TrackerTestBuilder::aTracker()->build();
        }

        $field->setTracker($this->tracker);
    }
}
