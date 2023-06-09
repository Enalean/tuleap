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

use ParagonIE\EasyDB\EasyStatement;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;

final class QueryListFieldPresenter
{
    public int $field_id;
    public int $tracker_id;
    public string $changeset_value_list_alias;
    public string $changeset_value_alias;
    public string $list_value_alias;
    public string $bind_value_alias;
    public string $filter_alias;
    public string $tracker_changeset_value_table;
    public string $list_value_table;
    public string|EasyStatement $condition;
    public array $parameters;

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

    public function setCondition(string|EasyStatement $condition): void
    {
        $this->condition = $condition;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }

    public function setListValueTable(string $list_value_table): void
    {
        $this->list_value_table = $list_value_table;
    }
}
