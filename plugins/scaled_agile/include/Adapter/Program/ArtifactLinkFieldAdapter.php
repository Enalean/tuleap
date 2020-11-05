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
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\FieldSynchronizationException;
use Tuleap\ScaledAgile\Program\Backlog\ProgramIncrement\Source\Fields\NoArtifactLinkFieldException;
use Tuleap\ScaledAgile\TrackerData;

final class ArtifactLinkFieldAdapter
{
    /**
     * @var \Tracker_FormElementFactory
     */
    private $form_element_factory;

    public function __construct(\Tracker_FormElementFactory $form_element_factory)
    {
        $this->form_element_factory = $form_element_factory;
    }

    /**
     * @throws FieldSynchronizationException
     */
    public function build(TrackerData $replication_tracker_data): FieldData
    {
        $artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($replication_tracker_data->getFullTracker());
        if (count($artifact_link_fields) > 0) {
            return new FieldData($artifact_link_fields[0]);
        }
        throw new NoArtifactLinkFieldException($replication_tracker_data->getTrackerId());
    }
}
