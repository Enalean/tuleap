<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Report\CSV\Format;

use Tracker_FormElement_Field_List_Bind_Null;
use Tracker_FormElement_Field_List_Bind_Static;
use Tracker_FormElement_Field_List_Bind_StaticValue;
use Tracker_FormElement_Field_List_Bind_Ugroups;
use Tracker_FormElement_Field_List_Bind_Users;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindParameters;
use Tuleap\Tracker\FormElement\Field\ListFields\Bind\BindVisitor;

class BindToValueVisitor implements BindVisitor
{
    public function visitListBindStatic(Tracker_FormElement_Field_List_Bind_Static $bind, BindParameters $parameters)
    {
        /** @var BindToValueParameters $bind_to_value_parameters */
        $bind_to_value_parameters = $parameters;

        $changeset_value = $bind_to_value_parameters->getChangesetValue();
        $selected_bind_value_ids = $changeset_value->getValue();
        if (empty($selected_bind_value_ids)) {
            return new EmptyValue();
        }
        $bind_value_id = $selected_bind_value_ids[0];
        if ($bind_value_id === \Tracker_FormElement_Field_List_Bind_StaticValue_None::VALUE_ID) {
            return new EmptyValue();
        }

        try {
            /** @var Tracker_FormElement_Field_List_Bind_StaticValue $list_value */
            $list_value = $bind->getValue($bind_value_id);
            return new TextValue($list_value->getLabel());
        } catch (\Tracker_FormElement_InvalidFieldValueException $e) {
            return new EmptyValue();
        }
    }

    public function visitListBindUsers(Tracker_FormElement_Field_List_Bind_Users $bind, BindParameters $parameters)
    {
        return new EmptyValue();
    }

    public function visitListBindUgroups(Tracker_FormElement_Field_List_Bind_Ugroups $bind, BindParameters $parameters)
    {
        return new EmptyValue();
    }

    public function visitListBindNull(Tracker_FormElement_Field_List_Bind_Null $bind, BindParameters $parameters)
    {
        return new EmptyValue();
    }
}
