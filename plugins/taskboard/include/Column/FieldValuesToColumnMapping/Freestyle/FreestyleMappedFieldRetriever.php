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

use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Tracker\TaskboardTracker;
use Tuleap\Tracker\FormElement\Field\ListFields\RetrieveUsedListField;

final readonly class FreestyleMappedFieldRetriever
{
    public function __construct(
        private SearchMappedField $search_mapped_field,
        private RetrieveUsedListField $form_element_factory,
    ) {
    }

    public function getMappedField(TaskboardTracker $taskboard_tracker): ?Tracker_FormElement_Field_Selectbox
    {
        return $this->search_mapped_field->searchMappedField($taskboard_tracker)
            ->mapOr(function (int $field_id) use ($taskboard_tracker): ?\Tracker_FormElement_Field_Selectbox {
                $field = $this->form_element_factory->getUsedListFieldById($taskboard_tracker->getTracker(), $field_id);
                if ($field instanceof \Tracker_FormElement_Field_Selectbox) {
                    return $field;
                }
                return null;
            }, null);
    }
}
