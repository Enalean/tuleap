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

use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\FormElement\Field\List\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\List\Bind\Static\ListFieldStaticBind;
use Tuleap\Tracker\FormElement\Field\List\Bind\BindVisitor;
use Tuleap\Tracker\FormElement\Field\List\Bind\ListFieldNullBind;
use Tuleap\Tracker\FormElement\Field\List\ListField;
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
    public function getFromWhereBuilder(ListField $field)
    {
        return $field->getBind()->accept($this, new BindParameters($field));
    }

    #[\Override]
    public function visitListBindStatic(ListFieldStaticBind $bind, BindParameters $parameters)
    {
        return $this->static_builder;
    }

    #[\Override]
    public function visitListBindUsers(Tracker_FormElement_Field_List_Bind_Users $bind, BindParameters $parameters)
    {
        return $this->users_builder;
    }

    #[\Override]
    public function visitListBindUgroups(Tracker_FormElement_Field_List_Bind_Ugroups $bind, BindParameters $parameters)
    {
        return $this->ugroups_builder;
    }

    #[\Override]
    public function visitListBindNull(ListFieldNullBind $bind, BindParameters $parameters)
    {
    }
}
