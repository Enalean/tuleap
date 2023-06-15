<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

namespace Tuleap\Tracker\Report;

use Tracker_FormElement;

class ExpertModePresenter
{
    public $id;
    public $class_toggler;
    public $is_in_expert_mode;
    public $expert_query;
    /**
     * @var array
     */
    public $allowed_fields;

    public $allowed_fields_label;
    public $query_label;
    public $query_placeholder;
    public $title;
    public $btn_report_normal_mode;
    public $btn_search;
    public $query_tooltip;
    public $allowed_fields_names_json_encoded;
    public $is_normal_mode_button_displayed;
    public $is_query_modifiable;

    public function __construct(
        $id,
        $class_toggler,
        $is_in_expert_mode,
        $expert_query,
        array $allowed_fields,
        $is_normal_mode_button_displayed,
        $is_query_modifiable,
    ) {
        $this->id                              = $id;
        $this->class_toggler                   = $class_toggler;
        $this->is_in_expert_mode               = $is_in_expert_mode;
        $this->expert_query                    = $expert_query;
        $this->is_normal_mode_button_displayed = $is_normal_mode_button_displayed;
        $this->is_query_modifiable             = $is_query_modifiable;

        $this->initAllowedFields($allowed_fields);

        $this->allowed_fields_label   = dgettext('tuleap-tracker', 'Allowed fields');
        $this->query_label            = dgettext('tuleap-tracker', 'Query');
        $this->query_placeholder      = dgettext('tuleap-tracker', 'Example: (field_1 = \'value\' OR field_2 = \'value\') AND field_3 = \'value\'');
        $this->title                  = dgettext('tuleap-tracker', 'Search');
        $this->btn_report_normal_mode = dgettext('tuleap-tracker', 'Normal mode');
        $this->btn_search             = $GLOBALS['Language']->getText('global', 'btn_search');
        $this->query_tooltip          = sprintf(
            dgettext('tuleap-tracker', 'You can use: %s, parenthesis. Autocomplete is activated with Ctrl + Space.'),
            'AND, OR, WITH PARENT, WITHOUT PARENT, WITH CHILDREN, WITHOUT CHILDREN, BETWEEN(), NOW(), MYSELF(), IN(), NOT IN()'
        );
    }

    private function initAllowedFields(array $allowed_fields)
    {
        $this->allowed_fields = array_values($allowed_fields);
        usort($this->allowed_fields, function ($field_a, $field_b) {
            return strnatcasecmp($field_a->getLabel(), $field_b->getLabel());
        });

        $allowed_fields_names = array_map(
            function (Tracker_FormElement $field) {
                return $field->getName();
            },
            $this->allowed_fields
        );
        usort($allowed_fields_names, 'strnatcasecmp');
        $this->allowed_fields_names_json_encoded = json_encode($allowed_fields_names);
    }
}
