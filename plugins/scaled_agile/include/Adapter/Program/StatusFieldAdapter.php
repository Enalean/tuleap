<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\ScaledAgile\Adapter\Program;

use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldData;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldRetrievalException;
use Tuleap\ScaledAgile\TrackerData;

final class StatusFieldAdapter
{
    /**
     * @var \Tracker_Semantic_StatusFactory
     */
    private $status_factory;

    public function __construct(
        \Tracker_Semantic_StatusFactory $status_factory
    ) {
        $this->status_factory = $status_factory;
    }

    /**
     * @throws FieldRetrievalException
     */
    public function build(TrackerData $replication_tracker_data): FieldData
    {
        $status_field = $this->status_factory->getByTracker($replication_tracker_data->getFullTracker())->getField();
        if (! $status_field) {
            throw new FieldRetrievalException($replication_tracker_data->getTrackerId(), "Status");
        }

        return new FieldData($status_field);
    }
}
