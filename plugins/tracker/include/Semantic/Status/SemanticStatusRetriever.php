<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Status;

use Tracker_FormElement_Field_List_Bind;
use Tuleap\Tracker\Tracker;

final readonly class SemanticStatusRetriever implements RetrieveSemanticStatus
{
    public function __construct(
        private RetrieveSemanticStatusField $status_field_retriever,
        private SearchStatusOpenValues $search_open_values,
    ) {
    }

    #[\Override]
    public function fromTracker(Tracker $tracker): TrackerSemanticStatus
    {
        $field = $this->status_field_retriever->fromTracker($tracker);
        if ($field === null) {
            return new TrackerSemanticStatus($tracker, $field, []);
        }

        $open_values = $this->search_open_values->searchOpenValuesByFieldId($field->getId());
        if ($open_values === []) {
            $open_values[] = Tracker_FormElement_Field_List_Bind::NONE_VALUE;
        }

        return new TrackerSemanticStatus($tracker, $field, $open_values);
    }
}
