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

use Tuleap\Tracker\Action\DuckTypedMoveFieldCollection;
use Tuleap\Tracker\Action\Move\FeedbackFieldCollectorInterface;
use Tuleap\Tracker\REST\MinimalFieldRepresentation;

/**
 * @psalm-immutable
 */
class ArtifactPatchDryRunFieldsResponseRepresentation
{
    /**
     * @var array {@type MinimalFieldRepresentation}
     */
    public $fields_migrated = [];

    /**
     * @var array {@type MinimalFieldRepresentation}
     */
    public $fields_not_migrated = [];

    /**
     * @var array {@type MinimalFieldRepresentation}
     */
    public $fields_partially_migrated = [];

    private function __construct(array $fields_not_migrated, array $fields_migrated, array $fields_partially_migrated)
    {
        $this->fields_not_migrated       = $fields_not_migrated;
        $this->fields_migrated           = $fields_migrated;
        $this->fields_partially_migrated = $fields_partially_migrated;
    }

    public static function fromFeedbackCollector(FeedbackFieldCollectorInterface $feedback_field_collector): self
    {
        $fields_not_migrated = [];
        foreach ($feedback_field_collector->getFieldsNotMigrated() as $field) {
            $fields_not_migrated[] = new MinimalFieldRepresentation($field);
        }

        $fields_migrated = [];
        foreach ($feedback_field_collector->getFieldsFullyMigrated() as $field) {
            $fields_migrated[] = new MinimalFieldRepresentation($field);
        }

        $fields_partially_migrated = [];
        foreach ($feedback_field_collector->getFieldsPartiallyMigrated() as $field) {
            $fields_partially_migrated[] = new MinimalFieldRepresentation($field);
        }

        return new self($fields_not_migrated, $fields_migrated, $fields_partially_migrated);
    }

    public static function fromDuckTypedFieldCollector(DuckTypedMoveFieldCollection $feedback_field_collector): self
    {
        $fields_not_migrated = [];
        foreach ($feedback_field_collector->not_migrateable_field_list as $field) {
            $fields_not_migrated[] = new MinimalFieldRepresentation($field);
        }

        $fields_migrated = [];
        foreach ($feedback_field_collector->migrateable_field_list as $field) {
            $fields_migrated[] = new MinimalFieldRepresentation($field);
        }

        $fields_partially_migrated = [];

        return new self($fields_not_migrated, $fields_migrated, $fields_partially_migrated);
    }
}
