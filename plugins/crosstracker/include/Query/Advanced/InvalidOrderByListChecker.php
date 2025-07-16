<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced;

use LogicException;
use Tracker_FormElement_Field_List;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Semantic\Contributor\RetrieveContributorField;
use Tuleap\Tracker\Semantic\Status\RetrieveSemanticStatusField;
use Tuleap\Tracker\Tracker;

final readonly class InvalidOrderByListChecker
{
    public function __construct(
        private RetrieveSemanticStatusField $status_field_retriever,
        private RetrieveContributorField $contributor_field_retriever,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function metadataListIsSortable(Metadata $metadata, array $trackers): bool
    {
        foreach ($trackers as $tracker) {
            $field = $this->getFieldFromMetadata($metadata, $tracker);
            if ($field !== null && $field->isMultiple()) {
                return false;
            }
        }

        return true;
    }

    private function getFieldFromMetadata(Metadata $metadata, Tracker $tracker): ?Tracker_FormElement_Field_List
    {
        return match ($metadata->getName()) {
            AllowedMetadata::ASSIGNED_TO => $this->contributor_field_retriever->getContributorField($tracker),
            AllowedMetadata::STATUS      => $this->status_field_retriever->fromTracker($tracker),
            default                      => throw new LogicException('Should not be called'),
        };
    }
}
