<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

use Tracker_FormElement_Field_List;
use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Field\RetrieveFieldById;
use Tuleap\Tracker\Tracker;

final readonly class SemanticStatusFieldRetriever implements RetrieveSemanticStatusField
{
    public function __construct(
        private SearchStatusField $search_status_field,
        private RetrieveFieldById $retrieve_field_by_id,
    ) {
    }

    public function fromTracker(Tracker $tracker): ?Tracker_FormElement_Field_List
    {
        return $this->search_status_field->searchFieldByTrackerId($tracker->getId())
            ->andThen(fn(int $field_id) => Option::fromNullable($this->retrieve_field_by_id->getFieldById($field_id)))
            ->unwrapOr(null);
    }
}
