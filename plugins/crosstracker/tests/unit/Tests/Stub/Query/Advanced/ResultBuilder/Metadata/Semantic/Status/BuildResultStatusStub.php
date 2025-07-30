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


namespace Tuleap\CrossTracker\Tests\Stub\Query\Advanced\ResultBuilder\Metadata\Semantic\Status;

use PFUser;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Metadata\Semantic\Status\BuildResultStatus;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\Representations\StaticListValueRepresentation;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValue;
use Tuleap\CrossTracker\Query\Advanced\ResultBuilder\SelectedValuesCollection;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedRepresentation;
use Tuleap\CrossTracker\REST\v1\Representation\CrossTrackerSelectedType;

final class BuildResultStatusStub implements BuildResultStatus
{
    private int $call_count;

    public function __construct(private readonly SelectedValuesCollection $selected_values)
    {
        $this->call_count = 0;
    }

    public static function withDefaultValues(): self
    {
        return new self(new SelectedValuesCollection(
            new CrossTrackerSelectedRepresentation('@status', CrossTrackerSelectedType::TYPE_STATIC_LIST),
            [
                21 => new SelectedValue('@status', new StaticListRepresentation([
                    new StaticListValueRepresentation('Closed', 'fiesta-red'),
                    new StaticListValueRepresentation('Also open', null),
                ])),
            ]
        ));
    }

    #[\Override]
    public function getResult(array $select_results, PFUser $user): SelectedValuesCollection
    {
        $this->call_count++;
        return $this->selected_values;
    }

    public function getCallCount(): int
    {
        return $this->call_count;
    }
}
