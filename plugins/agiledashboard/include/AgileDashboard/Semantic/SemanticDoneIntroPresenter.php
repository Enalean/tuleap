<?php
/**
 * Copyright Enalean (c) 2017. All rights reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Tracker_FormElement_Field;

class SemanticDoneIntroPresenter
{
    /**
     * @var bool
     */
    public $semantic_status_is_defined;

    /**
     * @var array
     */
    public $selected_values;

    /**
     * @var bool
     */
    public $has_selected_values;

    /**
     * @var string
     */
    public $semantic_status_field_label = '';

    public function __construct(array $selected_values, ?Tracker_FormElement_Field $semantic_status_field = null)
    {
        $this->semantic_status_is_defined  = (bool) ($semantic_status_field !== null);
        $this->selected_values             = $selected_values;
        $this->has_selected_values         = count($selected_values) > 0;

        if ($this->semantic_status_is_defined) {
            $this->semantic_status_field_label = $semantic_status_field->getLabel();
        }
    }
}
