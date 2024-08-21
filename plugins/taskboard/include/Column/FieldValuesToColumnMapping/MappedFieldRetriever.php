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

use Tracker_FormElement_Field_Selectbox;
use Tuleap\Taskboard\Column\FieldValuesToColumnMapping\Freestyle\FreestyleMappedFieldRetriever;
use Tuleap\Taskboard\Tracker\TaskboardTracker;

class MappedFieldRetriever
{
    public function __construct(
        private \Cardwall_FieldProviders_SemanticStatusFieldRetriever $semantic_status_provider,
        private FreestyleMappedFieldRetriever $freestyle_mapping_factory,
    ) {
    }

    /**
     * A mapped field is either:
     * - The field bound to the status semantic
     * - The field chosen by the user to represent columns in the TaskBoard
     *
     * Since this field can be a list field of any type, we cannot name it precisely.
     */
    public function getField(TaskboardTracker $taskboard_tracker): ?Tracker_FormElement_Field_Selectbox
    {
        $mapped_field = $this->freestyle_mapping_factory->getMappedField($taskboard_tracker);
        if ($mapped_field) {
            return $mapped_field;
        }
        return $this->semantic_status_provider->getField($taskboard_tracker->getTracker());
    }
}
