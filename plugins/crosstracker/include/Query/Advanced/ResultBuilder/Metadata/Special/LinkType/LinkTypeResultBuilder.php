<?php
/**
 * Copyright (c) Enalean, 2025 - Present. All Rights Reserved.
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

namespace Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Special\LinkType;

use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\TextResultRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;

/**
 * @psalm-immutable
 */
final readonly class LinkTypeResultBuilder
{
    public function getResult(array $select_results): SelectedValuesCollection
    {
        $values = [];


        foreach ($select_results as $result) {
            $id = $result['id'];

            $values[$id] = new SelectedValue('@link_type', new TextResultRepresentation($result['@link_type'] ?? ''));
        }

        return new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@link_type', CrossTrackerSelectedType::LINK_TYPE),
            $values,
        );
    }
}
