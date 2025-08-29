<?php
/**
 * Copyright (c) Enalean 2023 - Present. All Rights Reserved.
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

/**
 * @psalm-immutable
 */
final class PossibleFieldsPresenter
{
    private function __construct(public int $id, public string $label, public bool $is_selected)
    {
    }

    /**
     * @param \Tuleap\Tracker\FormElement\Field\TrackerField[] $tracker_filed_list
     * @return self[]
     */
    public static function buildFromTrackerFieldList(array $tracker_filed_list, \AgileDashBoard_Semantic_InitialEffort $initial_effort): array
    {
        $fields_presenter = [];
        foreach ($tracker_filed_list as $field) {
            $is_selected        = $initial_effort->getFieldId() === $field->getId();
            $fields_presenter[] = new self($field->getId(), $field->getLabel(), $is_selected);
        }

        return $fields_presenter;
    }
}
