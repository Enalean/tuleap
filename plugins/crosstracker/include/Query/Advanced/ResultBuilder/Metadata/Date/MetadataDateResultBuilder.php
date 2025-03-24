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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Date;

use DateTimeImmutable;
use DateTimeZone;
use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\DateResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;
use Tuleap\TimezoneRetriever;
use Tuleap\Tracker\Report\Query\Advanced\Grammar\Metadata;

final class MetadataDateResultBuilder
{
    public function getResult(Metadata $metadata, array $select_results, PFUser $user): SelectedValuesCollection
    {
        $values = [];
        $alias  = $metadata->getName();

        foreach ($select_results as $result) {
            $id = (int) $result['id'];
            if (isset($values[$id])) {
                continue;
            }

            $value = $result[$alias];
            if (is_int($value)) {
                $value = (new DateTimeImmutable("@$value"))
                    ->setTimezone(new DateTimeZone(TimezoneRetriever::getUserTimezone($user)))
                    ->format(DateTimeImmutable::ATOM);
            }
            $values[$id] = new SelectedValue($metadata->getName(), new DateResultRepresentation($value, true));
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation($metadata->getName(), CrossTrackerSelectedType::TYPE_DATE),
            $values,
        );
    }
}
