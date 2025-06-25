<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

namespace Tuleap\Tracker\Semantic;

final class CollectionOfSemanticsUsingAParticularTrackerField
{
    private \Tuleap\Tracker\Tracker $tracker;
    /**
     * @var \Tuleap\Tracker\Semantic\TrackerSemantic[]
     */
    private array $semantics = [];

    public function __construct(
        \Tracker_FormElement_Field $field,
        array $semantics,
    ) {
        $this->tracker   = $field->getTracker();
        $this->semantics = $semantics;
    }

    public function areThereSemanticsUsingField(): bool
    {
        return count($this->semantics) > 0;
    }

    public function getUsages(): string
    {
        if (! $this->areThereSemanticsUsingField()) {
            return '';
        }

        $base_text = dgettext('tuleap-tracker', 'Impossible to delete this field (used by: %s)');

        return sprintf($base_text, $this->getSemanticsNames());
    }

    private function getSemanticsNames(): string
    {
        $names = [];

        foreach ($this->semantics as $semantic) {
            $semantic_label   = $semantic->getLabel();
            $semantic_tracker = $semantic->getTracker();

            if ($semantic_tracker->getId() !== $this->tracker->getId()) {
                $names[] = $this->getSemanticNameWithTrackerNameAndProjectNameIfNeeded($semantic_tracker, $semantic_label);
                continue;
            }

            $names[] =  sprintf(
                dgettext(
                    'tuleap-tracker',
                    'semantic %s'
                ),
                $semantic_label
            );
        }

        return join(', ', $names);
    }

    private function getSemanticNameWithTrackerNameAndProjectNameIfNeeded(\Tuleap\Tracker\Tracker $semantic_tracker, string $semantic_label): string
    {
        if ($this->tracker->getProject()->getID() !== $semantic_tracker->getProject()->getID()) {
            return sprintf(
                dgettext('tuleap-tracker', 'semantic %s of tracker %s in project %s'),
                $semantic_label,
                $semantic_tracker->getName(),
                $semantic_tracker->getProject()->getPublicName()
            );
        }

        return sprintf(
            dgettext('tuleap-tracker', 'semantic %s of tracker %s'),
            $semantic_label,
            $semantic_tracker->getName()
        );
    }
}
