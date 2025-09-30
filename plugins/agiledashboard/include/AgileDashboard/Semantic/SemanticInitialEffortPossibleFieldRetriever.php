<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard\Semantic;

use Tracker_FormElementFactory;
use Tuleap\Tracker\Tracker;

class SemanticInitialEffortPossibleFieldRetriever
{
    public function __construct(private Tracker_FormElementFactory $form_element_factory)
    {
    }

    public function getPossibleFieldsForInitialEffort(Tracker $tracker, int $selected_field_id): array
    {
        $numeric_fields = $this->form_element_factory->getUsedPotentiallyContainingNumericValueFields(
            $tracker
        );

        $possible_numeric_fields = [];

        foreach ($numeric_fields as $numeric_field) {
            $selected = false;
            if ($numeric_field->getId() == $selected_field_id) {
                $selected = true;
            }
            if ($numeric_field->getName() !== Tracker::REMAINING_EFFORT_FIELD_NAME || $selected) {
                $possible_numeric_fields[] = $numeric_field;
            }
        }

        return $possible_numeric_fields;
    }
}
