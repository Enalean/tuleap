<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle;

use Tuleap\Option\Option;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveUsedListField;

final readonly class FreestyleMappedFieldRetriever
{
    public function __construct(
        private SearchMappedField $search_mapped_field,
        private RetrieveUsedListField $form_element_factory,
    ) {
    }

    /**
     * @return Option<\Tuleap\Tracker\FormElement\Field\List\SelectboxField>
     */
    public function getMappedField(TaskboardTracker $taskboard_tracker): Option
    {
        return $this->search_mapped_field->searchMappedField($taskboard_tracker)
            ->andThen(function (int $field_id) use ($taskboard_tracker) {
                $field = $this->form_element_factory->getUsedListFieldById($taskboard_tracker->getTracker(), $field_id);
                if ($field instanceof \Tuleap\Tracker\FormElement\Field\List\SelectboxField) {
                    return Option::fromValue($field);
                }
                return Option::nothing(\Tuleap\Tracker\FormElement\Field\List\SelectboxField::class);
            });
    }
}
