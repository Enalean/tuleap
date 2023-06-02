<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Null;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\Report\Query\Advanced\FieldFromWhereBuilder;

final class ListFieldBindVisitor implements BindVisitor
{
    /**
     * @var ListBindStaticFromWhereBuilder
     */
    private $static_builder;
    /**
     * @var ListBindUsersFromWhereBuilder
     */
    private $users_builder;
    /**
     * @var ListBindUgroupsFromWhereBuilder
     */
    private $ugroups_builder;

    public function __construct(
        ListBindStaticFromWhereBuilder $static_builder,
        ListBindUsersFromWhereBuilder $users_builder,
        ListBindUgroupsFromWhereBuilder $ugroups_builder,
    ) {
        $this->static_builder  = $static_builder;
        $this->users_builder   = $users_builder;
        $this->ugroups_builder = $ugroups_builder;
    }

    /** @return FieldFromWhereBuilder */
    public function getFromWhereBuilder(Tracker_FormElement_Field_List $field)
    {
        return $field->getBind()->accept($this, new BindParameters($field));
    }

    public function visitListBindStatic(Tracker_FormElement_Field_List_Bind_Static $bind, BindParameters $parameters)
    {
        return $this->static_builder;
    }

    public function visitListBindUsers(Tracker_FormElement_Field_List_Bind_Users $bind, BindParameters $parameters)
    {
        return $this->users_builder;
    }

    public function visitListBindUgroups(Tracker_FormElement_Field_List_Bind_Ugroups $bind, BindParameters $parameters)
    {
        return $this->ugroups_builder;
    }

    public function visitListBindNull(Tracker_FormElement_Field_List_Bind_Null $bind, BindParameters $parameters)
    {
    }
}
