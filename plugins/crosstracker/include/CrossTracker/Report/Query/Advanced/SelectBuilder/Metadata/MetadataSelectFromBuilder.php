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

namespace Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata;

use LogicException;
use Tuleap\CrossTracker\Report\Query\Advanced\AllowedMetadata;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\IProvideParametrizedSelectAndFromSQLFragments;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Description\DescriptionSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\Metadata\Semantic\Title\TitleSelectFromBuilder;
use Tuleap\CrossTracker\Report\Query\Advanced\SelectBuilder\ParametrizedSelectFrom;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final readonly class MetadataSelectFromBuilder
{
    public function __construct(
        private TitleSelectFromBuilder $title_builder,
        private DescriptionSelectFromBuilder $description_builder,
    ) {
    }

    public function getSelectFrom(Metadata $metadata): IProvideParametrizedSelectAndFromSQLFragments
    {
        return match ($metadata->getName()) {
            // Semantics
            AllowedMetadata::TITLE       => $this->title_builder->getSelectFrom(),
            AllowedMetadata::DESCRIPTION => $this->description_builder->getSelectFrom(),
            AllowedMetadata::STATUS,
            AllowedMetadata::ASSIGNED_TO,

            // Always there fields
            AllowedMetadata::SUBMITTED_ON,
            AllowedMetadata::LAST_UPDATE_DATE,
            AllowedMetadata::SUBMITTED_BY,
            AllowedMetadata::LAST_UPDATE_BY,
            AllowedMetadata::ID          => new ParametrizedSelectFrom('', '', []),
            default                      => throw new LogicException("Unknown metadata type: {$metadata->getName()}"),
        };
    }
}
