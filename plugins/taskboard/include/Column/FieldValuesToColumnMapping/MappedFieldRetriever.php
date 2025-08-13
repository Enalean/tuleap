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

namespace Tuleap\Taskboard\Column\FieldValuesToColumnMapping;

use Tuleap\Option\Option;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

final readonly class MappedFieldRetriever
{
    public function __construct(
        private \Cardwall_FieldProviders_SemanticStatusFieldRetriever $semantic_status_provider,
        private FreestyleMappedFieldRetriever $freestyle_mapped_field_retriever,
    ) {
    }

    /**
     * A mapped field is either:
     * - The field bound to the status semantic
     * - The field chosen by the user to represent columns in the TaskBoard
     *
     * Since this field can be a list field of any type, we cannot name it precisely.
     * @return Option<\Tuleap\Tracker\FormElement\Field\List\SelectboxField>
     */
    public function getField(TaskboardTracker $taskboard_tracker): Option
    {
        return $this->freestyle_mapped_field_retriever->getMappedField($taskboard_tracker)
            ->orElse(fn() => Option::fromNullable(
                $this->semantic_status_provider->getField($taskboard_tracker->getTracker())
            ));
    }
}
