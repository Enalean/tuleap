<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\REST\v1;

use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;

/**
 * @psalm-immutable
 */
class ArtifactPatchDryRunResponseRepresentation
{
    /**
     * @var ArtifactPatchDryRunFieldsResponseRepresentation {@type ArtifactPatchDryRunFieldsResponseRepresentation}
     */
    public $fields;

    private function __construct(ArtifactPatchDryRunFieldsResponseRepresentation $fields_representation)
    {
        $this->fields = $fields_representation;
    }

    public static function fromFeedbackCollector(FeedbackFieldCollectorInterface $feedback_field_collector): self
    {
        return new self(ArtifactPatchDryRunFieldsResponseRepresentation::fromFeedbackCollector($feedback_field_collector));
    }

    public static function fromDuckTypedMovedCollection(\Tuleap\Tracker\Action\DuckTypedMoveFieldCollection $field_collection): self
    {
        return new self(ArtifactPatchDryRunFieldsResponseRepresentation::fromDuckTypedFieldCollector($field_collection));
    }
}
