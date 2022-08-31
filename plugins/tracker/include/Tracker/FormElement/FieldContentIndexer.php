<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

namespace Tuleap\Tracker\FormElement;

use Psr\EventDispatcher\EventDispatcherInterface;
use Tracker_FormElement_Field;
use Tuleap\Tracker\Artifact\Artifact;

class FieldContentIndexer
{
    private const INDEX_TYPE_FIELD_CONTENT = 'plugin_artifact_field';

    public function __construct(private EventDispatcherInterface $event_dispatcher)
    {
    }

    public function indexFieldContent(Artifact $artifact, Tracker_FormElement_Field $field, string $value): void
    {
        $this->event_dispatcher->dispatch(
            new \Tuleap\Search\ItemToIndex(
                self::INDEX_TYPE_FIELD_CONTENT,
                $value,
                [
                    'field_id'    => (string) $field->getId(),
                    'artifact_id' => (string) $artifact->getId(),
                    'tracker_id'  => (string) $field->getTrackerId(),
                    'project_id'  => (string) $field->getTracker()->getGroupId(),
                ]
            )
        );
    }
}
