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

namespace Tuleap\Tracker\Test\Builders\Fields\List;

use Tuleap\Test\Stubs\UGroupRetrieverStub;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindUgroupsValueDao;

final class ListUserGroupBindBuilder
{
    /**
     * @var \Tracker_FormElement_Field_List_Bind_UgroupsValue[]
     */
    private array $bind_values = [];
    /**
     * @var \ProjectUGroup[]
     */
    private array $user_groups = [];

    private function __construct(private readonly \Tracker_FormElement_Field_List $field)
    {
    }

    public static function aUserGroupBind(\Tracker_FormElement_Field_List $field): self
    {
        return new self($field);
    }

    /**
     * @psalm-param \ProjectUGroup[] $user_groups_list
     */
    public function withUserGroups(array $user_groups_list): self
    {
        $this->user_groups = $user_groups_list;

        foreach ($user_groups_list as $user_group) {
            $bind_value = \Tracker_FormElement_Field_List_Bind_UgroupsValue::fromUserGroup($user_group);

            $this->bind_values[] = $bind_value;
        }

        return $this;
    }

    public function build(): \Tracker_FormElement_Field_List_Bind_Ugroups
    {
        $bind = new \Tracker_FormElement_Field_List_Bind_Ugroups(
            $this->field,
            $this->bind_values,
            [],
            [],
            UGroupRetrieverStub::buildWithUserGroups(...$this->user_groups),
            (new class extends BindUgroupsValueDao {
            })
        );
        $this->field->setBind($bind);

        return $bind;
    }
}
