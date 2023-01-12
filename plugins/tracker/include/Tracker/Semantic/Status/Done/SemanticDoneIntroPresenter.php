<?php
/**
 * Copyright Enalean (c) 2017 - Present. All rights reserved.
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

namespace Tuleap\Tracker\Semantic\Status\Done;

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

    /**
     * @var SemanticDoneUsedExternalService[]
     */
    public array $external_services;
    public bool $has_external_service_used;

    public function __construct(
        array $selected_values,
        ?Tracker_FormElement_Field $semantic_status_field,
        array $external_services_descriptions,
    ) {
        $this->semantic_status_is_defined = ($semantic_status_field !== null);
        $this->selected_values            = $selected_values;
        $this->has_selected_values        = count($selected_values) > 0;

        if ($semantic_status_field !== null) {
            $this->semantic_status_field_label = $semantic_status_field->getLabel();
        }

        $this->has_external_service_used = count($external_services_descriptions) > 0;
        $this->external_services         = $external_services_descriptions;
    }
}
