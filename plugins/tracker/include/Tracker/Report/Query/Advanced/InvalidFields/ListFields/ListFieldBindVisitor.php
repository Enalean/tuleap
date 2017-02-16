<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

namespace Tuleap\Tracker\Report\Query\Advanced\InvalidFields\ListFields;

use Tracker_FormElement_Field_List;
use Tracker_FormElement_Field_List_Bind_Null;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;
use Tuleap\Tracker\Report\Query\Advanced\InvalidFields\InvalidFieldChecker;

class ListFieldBindVisitor implements BindVisitor
{
    /**
     * @var ListFieldChecker
     */
    private $list_field_checker;

    public function __construct(ListFieldChecker $list_field_checker)
    {
        $this->list_field_checker = $list_field_checker;
    }

    /**
     * @param Tracker_FormElement_Field_List $field
     * @return InvalidFieldChecker
     */
    public function getInvalidFieldChecker(Tracker_FormElement_Field_List $field)
    {
        return $field->getBind()->accept($this);
    }

    public function visitListBindStatic(Tracker_FormElement_Field_List_Bind_Static $bind)
    {
        return new ListFieldBindStaticChecker(
            $this->list_field_checker
        );
    }

    public function visitListBindUsers(Tracker_FormElement_Field_List_Bind_Users $bind)
    {
        return new ListFieldBindUsersChecker(
            $this->list_field_checker
        );
    }

    public function visitListBindUgroups(Tracker_FormElement_Field_List_Bind_Ugroups $bind)
    {
        return new ListFieldBindUgroupsChecker(
            $this->list_field_checker
        );
    }

    public function visitListBindNull(Tracker_FormElement_Field_List_Bind_Null $bind)
    {
    }
}
