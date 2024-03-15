<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\ProgramManagement\Adapter\Workspace\Tracker\Fields;

use Tuleap\ProgramManagement\Adapter\Workspace\Tracker\RetrieveFullTracker;
use Tuleap\ProgramManagement\Domain\Workspace\Tracker\TrackerIdentifier;

final class FormElementFactoryAdapter implements RetrieveFullArtifactLinkField
{
    public function __construct(
        private RetrieveFullTracker $tracker_retriever,
        private \Tracker_FormElementFactory $form_element_factory,
    ) {
    }

    public function getArtifactLinkField(
        TrackerIdentifier $tracker_identifier,
    ): ?\Tracker_FormElement_Field_ArtifactLink {
        $tracker              = $this->tracker_retriever->getNonNullTracker($tracker_identifier);
        $artifact_link_fields = $this->form_element_factory->getUsedArtifactLinkFields($tracker);
        if (count($artifact_link_fields) > 0) {
            return $artifact_link_fields[0];
        }
        return null;
    }
}
