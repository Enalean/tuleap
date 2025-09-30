<?php
/**
 * Copyright (c) Enalean, 2025 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic\Description;

use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Field\RetrieveFieldById;
use Tuleap\Tracker\Tracker;

final readonly class SemanticDescriptionFieldRetriever implements RetrieveSemanticDescriptionField
{
    public function __construct(
        private SearchDescriptionField $search_description_field,
        private RetrieveFieldById $retrieve_field_by_id,
    ) {
    }

    #[\Override]
    public function fromTracker(Tracker $tracker): ?\Tuleap\Tracker\FormElement\Field\Text\TextField
    {
        return $this->search_description_field->searchByTrackerId($tracker->getId())
            ->andThen(function (int $field_id) {
                return Option::fromNullable(
                    $this->retrieve_field_by_id->getFieldById($field_id)
                );
            })->unwrapOr(null);
    }
}
