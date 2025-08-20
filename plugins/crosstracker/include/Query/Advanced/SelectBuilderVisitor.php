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

use PFUser;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Field\FieldSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\MetadataSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\SelectBuilderVisitorParameters;
use Tuleap\Option\Option;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Selectable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SelectableVisitor;
use Tuleap\Tracker\Tracker;

/**
 * @template-implements SelectableVisitor<SelectBuilderVisitorParameters, IProvideParametrizedSelectAndFromAndWhereSQLFragments>
 */
final readonly class SelectBuilderVisitor implements SelectableVisitor
{
    public function __construct(
        private FieldSelectFromBuilder $field_select_from_builder,
        private MetadataSelectFromBuilder $metadata_select_from_builder,
    ) {
    }

    /**
     * @param Selectable[] $selects
     * @param Tracker[] $trackers
     * @param Option<int> $artifact_id_for_links
     * @return list<IProvideParametrizedSelectAndFromAndWhereSQLFragments>
     */
    public function buildSelectFrom(
        array $selects,
        array $trackers,
        PFUser $user,
        Option $artifact_id_for_links,
        array $artifact_ids,
    ): array {
        $selects   = array_unique($selects, SORT_REGULAR);
        $fragments = [];
        foreach ($selects as $select) {
            $fragments[] = $select->acceptSelectableVisitor($this, new SelectBuilderVisitorParameters($trackers, $user, $artifact_id_for_links, $artifact_ids));
        }

        return $fragments;
    }

    public function visitField(Field $field, $parameters)
    {
        return $this->field_select_from_builder->getSelectFrom(
            $field,
            $parameters->user,
            $parameters->trackers,
        );
    }

    public function visitMetaData(Metadata $metadata, $parameters)
    {
        return $this->metadata_select_from_builder->getSelectFrom($metadata, $parameters->target_artifact_id_for_reverse_links, $parameters->artifact_ids);
    }
}
