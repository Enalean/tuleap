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

namespace Tuleap\ScaledAgile\Program\Backlog\ProjectIncrement\Source\Fields;

class FieldDescriptionAdapter
{
    /**
     * @var \Tracker_Semantic_DescriptionFactory
     */
    private $description_factory;

    public function __construct(
        \Tracker_Semantic_DescriptionFactory $description_factory
    ) {
        $this->description_factory = $description_factory;
    }

    /**
     * @throws FieldRetrievalException
     */
    public function build(\Tracker $source_tracker): FieldData
    {
        $description_field = $this->description_factory->getByTracker($source_tracker)->getField();
        if (! $description_field) {
            throw new FieldRetrievalException($source_tracker->getId(), "Description");
        }

        return new FieldData($description_field);
    }
}
