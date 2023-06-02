<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

namespace Tuleap\Tracker\Report\Query\Advanced\QueryBuilder;

use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;

final class QueryListFieldPresenter
{
    public $field_id;
    public $tracker_id;
    public $changeset_value_list_alias;
    public $changeset_value_alias;
    public $list_value_alias;
    public $filter_alias;
    public $tracker_changeset_value_table;
    public $list_value_table;
    public $condition;

    public function __construct(Comparison $comparison, Tracker_FormElement_Field $field)
    {
        $suffix           = spl_object_hash($comparison);
        $this->field_id   = (int) $field->getId();
        $this->tracker_id = (int) $field->getTrackerId();

        $this->changeset_value_alias      = "CV_{$this->field_id}_{$suffix}";
        $this->changeset_value_list_alias = "CVList_{$this->field_id}_{$suffix}";
        $this->list_value_alias           = "ListValue_{$this->field_id}_{$suffix}";
        $this->bind_value_alias           = "BindValue_{$this->field_id}_{$suffix}";
        $this->filter_alias               = "Filter_{$this->field_id}_{$suffix}";

        $this->tracker_changeset_value_table = 'tracker_changeset_value_list';
        $this->list_value_table              = 'tracker_field_list_bind_static_value';

        $this->condition = '';
    }

    public function setCondition($condition)
    {
        $this->condition = $condition;
    }

    public function setListValueTable($list_value_table)
    {
        $this->list_value_table = $list_value_table;
    }
}
