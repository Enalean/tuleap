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
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\TitleFieldHasIncorrectTypeException;
use Tuleap\ScaledAgile\TrackerData;

final class TitleFieldAdapter
{
    /**
     * @var \Tracker_Semantic_TitleFactory
     */
    private $title_factory;

    public function __construct(
        \Tracker_Semantic_TitleFactory $title_factory
    ) {
        $this->title_factory        = $title_factory;
    }
    /**
     * @throws FieldRetrievalException
     * @throws TitleFieldHasIncorrectTypeException
     */
    public function build(TrackerData $replication_tracker_data): FieldData
    {
        $title_field = $this->title_factory->getByTracker($replication_tracker_data->getFullTracker())->getField();
        if (! $title_field) {
            throw new FieldRetrievalException($replication_tracker_data->getTrackerId(), "Title");
        }

        if (! $title_field instanceof \Tracker_FormElement_Field_String) {
            throw new TitleFieldHasIncorrectTypeException((int) $replication_tracker_data->getTrackerId(), (int) $title_field->getId());
        }
        return new FieldData($title_field);
    }
}
