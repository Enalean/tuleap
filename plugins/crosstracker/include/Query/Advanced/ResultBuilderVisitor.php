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
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Field\FieldResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\MetadataResultBuilder;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\ResultBuilderVisitorParameters;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\Option\Option;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\LinkDirection;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Field;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Selectable;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\SelectableVisitor;
use Tuleap\Tracker\Tracker;

/**
 * @template-implements SelectableVisitor<ResultBuilderVisitorParameters, SelectedValuesCollection>
 */
final readonly class ResultBuilderVisitor implements SelectableVisitor
{
    public function __construct(
        private FieldResultBuilder $field_result_builder,
        private MetadataResultBuilder $metadata_result_builder,
    ) {
    }

    /**
     * @param Selectable[] $selects
     * @param Tracker[] $trackers
     * @param Option<LinkDirection> $direction
     * @return SelectedValuesCollection[]
     */
    public function buildResult(
        array $selects,
        array $trackers,
        PFUser $user,
        array $select_results,
        Option $direction,
    ): array {
        return array_map(
            fn(Selectable $select) => $select->acceptSelectableVisitor($this, new ResultBuilderVisitorParameters($trackers, $user, $select_results, $direction)),
            array_unique($selects, SORT_REGULAR)
        );
    }

    #[\Override]
    public function visitField(Field $field, $parameters)
    {
        return $this->field_result_builder->getResult(
            $field,
            $parameters->user,
            $parameters->trackers,
            $parameters->select_results,
        );
    }

    #[\Override]
    public function visitMetaData(Metadata $metadata, $parameters)
    {
        return $this->metadata_result_builder->getResult(
            $metadata,
            $parameters->select_results,
            $parameters->user,
            $parameters->direction,
        );
    }
}
