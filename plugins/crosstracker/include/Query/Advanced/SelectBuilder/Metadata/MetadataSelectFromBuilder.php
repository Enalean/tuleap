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

namespace Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata;

use LogicException;
use Tuleap\CrossTracker\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromAndWhereSQLFragments;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\AssignedTo\AssignedToSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Description\DescriptionSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Status\StatusSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Semantic\Title\TitleSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\LinkType\BuildLinkTypeSelectFrom;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\PrettyTitle\PrettyTitleSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\Metadata\Special\ProjectName\ProjectNameSelectFromBuilder;
use Tuleap\CrossTracker\Query\Advanced\SelectBuilder\ParametrizedSelectFromAndWhere;
use Tuleap\Option\Option;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use function Psl\Type\string;

final readonly class MetadataSelectFromBuilder
{
    public function __construct(
        private TitleSelectFromBuilder $title_builder,
        private DescriptionSelectFromBuilder $description_builder,
        private StatusSelectFromBuilder $status_builder,
        private AssignedToSelectFromBuilder $assigned_to_builder,
        private ProjectNameSelectFromBuilder $project_name_builder,
        private PrettyTitleSelectFromBuilder $pretty_title_builder,
        private BuildLinkTypeSelectFrom $link_type_builder,
    ) {
    }

    /**
     * @param Option<int> $target_artifact_id_for_reverse_links
     */
    public function getSelectFrom(Metadata $metadata, Option $target_artifact_id_for_reverse_links, array $artifact_ids): IProvideParametrizedSelectAndFromAndWhereSQLFragments
    {
        return match ($metadata->getName()) {
            // Semantics
            AllowedMetadata::TITLE            => $this->title_builder->getSelectFrom(),
            AllowedMetadata::DESCRIPTION      => $this->description_builder->getSelectFrom(),
            AllowedMetadata::STATUS           => $this->status_builder->getSelectFrom(),
            AllowedMetadata::ASSIGNED_TO      => $this->assigned_to_builder->getSelectFrom(),

            // Always there fields
            AllowedMetadata::SUBMITTED_ON     => new ParametrizedSelectFromAndWhere("artifact.submitted_on AS '@submitted_on'", '', [],
                Option::nothing(string()),
                []),
            AllowedMetadata::LAST_UPDATE_DATE => new ParametrizedSelectFromAndWhere("changeset.submitted_on AS '@last_update_date'", '', [],
                Option::nothing(string()),
                []),
            AllowedMetadata::SUBMITTED_BY     => new ParametrizedSelectFromAndWhere("artifact.submitted_by AS '@submitted_by'", '', [],
                Option::nothing(string()),
                []),
            AllowedMetadata::LAST_UPDATE_BY   => new ParametrizedSelectFromAndWhere("changeset.submitted_by AS '@last_update_by'", '', [],
                Option::nothing(string()),
                []),
            AllowedMetadata::ID               => new ParametrizedSelectFromAndWhere("artifact.id AS '@id'", '', [],
                Option::nothing(string()),
                []),

            // Custom fields
            AllowedMetadata::PROJECT_NAME     => $this->project_name_builder->getSelectFrom(),
            AllowedMetadata::TRACKER_NAME     => new ParametrizedSelectFromAndWhere("tracker.name AS '@tracker.name', tracker.color AS '@tracker.color'", '', [],
                Option::nothing(string()),
                []),
            AllowedMetadata::PRETTY_TITLE     => $this->pretty_title_builder->getSelectFrom(),
            AllowedMetadata::LINK_TYPE        => $this->link_type_builder->getSelectFrom($target_artifact_id_for_reverse_links, $artifact_ids),
            default                           => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }
}
