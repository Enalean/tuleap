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

namespace Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata;

use LogicException;
use Tracker;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Date\DateFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\AlwaysThereField\Users\UsersFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\AssignedTo\AssignedToFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Description\DescriptionFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Status\StatusFromWhereBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\QueryBuilder\Metadata\Semantic\Title\TitleFromWhereBuilder;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Comparison;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;
use Tuleap\Tracker\Report\Query\IProvideParametrizedFromAndWhereSQLFragments;

final readonly class MetadataFromWhereBuilder
{
    private const SUBMITTED_ON_ALIAS     = 'tracker_artifact.submitted_on';
    private const LAST_UPDATE_DATE_ALIAS = 'last_changeset.submitted_on';
    private const SUBMITTED_BY_ALIAS     = 'tracker_artifact.submitted_by';
    private const LAST_UPDATE_BY_ALIAS   = 'last_changeset.submitted_by';

    public function __construct(
        private TitleFromWhereBuilder $title_builder,
        private DescriptionFromWhereBuilder $description_builder,
        private StatusFromWhereBuilder $status_builder,
        private AssignedToFromWhereBuilder $assigned_to_builder,
        private DateFromWhereBuilder $date_builder,
        private UsersFromWhereBuilder $users_builder,
    ) {
    }

    /**
     * @param Tracker[] $trackers
     */
    public function getFromWhere(
        Metadata $metadata,
        Comparison $comparison,
        array $trackers,
    ): IProvideParametrizedFromAndWhereSQLFragments {
        $parameters = new MetadataValueWrapperParameters($comparison, $trackers, '');
        return match ($metadata->getName()) {
            // Semantics
            AllowedMetadata::TITLE            => $this->title_builder->getFromWhere($parameters),
            AllowedMetadata::DESCRIPTION      => $this->description_builder->getFromWhere($parameters),
            AllowedMetadata::STATUS           => $this->status_builder->getFromWhere($parameters),
            AllowedMetadata::ASSIGNED_TO      => $this->assigned_to_builder->getFromWhere($parameters),

            // Always there fields
            AllowedMetadata::SUBMITTED_ON     => $this->date_builder->getFromWhere(new MetadataValueWrapperParameters($comparison, $trackers, self::SUBMITTED_ON_ALIAS)),
            AllowedMetadata::LAST_UPDATE_DATE => $this->date_builder->getFromWhere(new MetadataValueWrapperParameters($comparison, $trackers, self::LAST_UPDATE_DATE_ALIAS)),
            AllowedMetadata::SUBMITTED_BY     => $this->users_builder->getFromWhere(new MetadataValueWrapperParameters($comparison, $trackers, self::SUBMITTED_BY_ALIAS)),
            AllowedMetadata::LAST_UPDATE_BY   => $this->users_builder->getFromWhere(new MetadataValueWrapperParameters($comparison, $trackers, self::LAST_UPDATE_BY_ALIAS)),
            default                           => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }
}
