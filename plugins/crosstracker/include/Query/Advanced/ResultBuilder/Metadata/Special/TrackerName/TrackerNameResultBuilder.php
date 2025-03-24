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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\TrackerName;

use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\TrackerRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;

final class TrackerNameResultBuilder
{
    public function getResult(array $select_results): SelectedValuesCollection
    {
        $values = [];

        foreach ($select_results as $result) {
            $id = $result['id'];
            if (isset($values[$id])) {
                continue;
            }

            $name        = $result['@tracker.name'];
            $color       = $result['@tracker.color'];
            $values[$id] = new SelectedValue('@tracker.name', new TrackerRepresentation($name, $color));
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@tracker.name', CrossTrackerSelectedType::TYPE_TRACKER),
            $values,
        );
    }
}
