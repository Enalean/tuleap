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

namespace Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Metadata;

use LogicException;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\Field\Text\TextFromOrderBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\OrderByBuilderParameters;
use Tuleap\CrossTracker\Report\Query\Advanced\OrderByBuilder\ParametrizedFromOrder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\OrderByDirection;
use Tuleap\Tracker\Semantic\Description\GetDescriptionSemantic;
use Tuleap\Tracker\Semantic\Title\GetTitleSemantic;

final readonly class MetadataFromOrderBuilder
{
    public function __construct(
        private GetTitleSemantic $title_semantic_retriever,
        private GetDescriptionSemantic $description_semantic_retriever,
        private TextFromOrderBuilder $text_builder,
    ) {
    }

    public function getFromOrder(Metadata $metadata, OrderByBuilderParameters $parameters): ParametrizedFromOrder
    {
        $order = match ($parameters->direction) {
            OrderByDirection::ASCENDING  => ' ASC',
            OrderByDirection::DESCENDING => ' DESC',
        };

        return match ($metadata->getName()) {
            AllowedMetadata::TITLE            => $this->text_builder->getFromOrder($this->getTitleFieldIds($parameters->trackers), $order),
            AllowedMetadata::DESCRIPTION      => $this->text_builder->getFromOrder($this->getDescriptionFieldIds($parameters->trackers), $order),
            AllowedMetadata::STATUS,
            AllowedMetadata::ASSIGNED_TO,

            AllowedMetadata::SUBMITTED_BY,
            AllowedMetadata::LAST_UPDATE_BY   => new ParametrizedFromOrder('', [], ''),
            AllowedMetadata::SUBMITTED_ON     => new ParametrizedFromOrder('', [], 'artifact.submitted_on' . $order),
            AllowedMetadata::LAST_UPDATE_DATE => new ParametrizedFromOrder('', [], 'changeset.submitted_on' . $order),
            AllowedMetadata::ID               => new ParametrizedFromOrder('', [], 'artifact.id' . $order),
            default                           => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }

    /**
     * @param Tracker[] $trackers
     * @return list<int>
     */
    private function getTitleFieldIds(array $trackers): array
    {
        $field_ids = [];
        foreach ($trackers as $tracker) {
            $semantic_title = $this->title_semantic_retriever->getByTracker($tracker);
            if ($semantic_title->getField() !== null) {
                $field_ids[] = $semantic_title->getFieldId();
            }
        }

        return $field_ids;
    }

    /**
     * @param Tracker[] $trackers
     * @return list<int>
     */
    private function getDescriptionFieldIds(array $trackers): array
    {
        $field_ids = [];
        foreach ($trackers as $tracker) {
            $semantic_description = $this->description_semantic_retriever->getByTracker($tracker);
            if ($semantic_description->getField() !== null) {
                $field_ids[] = $semantic_description->getFieldId();
            }
        }
        return $field_ids;
    }
}
