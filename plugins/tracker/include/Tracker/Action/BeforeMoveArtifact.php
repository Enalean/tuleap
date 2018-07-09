<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Action;

use EventManager;
use Tracker;
use Tuleap\Tracker\Events\MoveArtifactCheckExternalSemantics;
use Tuleap\Tracker\Exception\MoveArtifactSemanticsException;

class BeforeMoveArtifact
{
    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var MoveSemanticChecker[]
     */
    private $semantic_checkers;

    /**
     * @var array
     */
    private $semantic_checked = [];

    public function __construct(
        EventManager $event_manager,
        MoveSemanticChecker ...$semantic_checkers
    ) {
        $this->event_manager     = $event_manager;
        $this->semantic_checkers = $semantic_checkers;
    }

    /**
     * @return bool
     * @throws MoveArtifactSemanticsException
     */
    public function artifactCanBeMoved(Tracker $source_tracker, Tracker $target_tracker)
    {
        foreach ($this->semantic_checkers as $semantic_checker) {
            $semantic_name            = $semantic_checker->getSemanticName();
            $this->semantic_checked[] = $semantic_name;

            if ($semantic_checker->areSemanticsAligned($source_tracker, $target_tracker)) {
                return true;
            }

            if (! $semantic_checker->areBothSemanticsDefined($source_tracker, $target_tracker)) {
                continue;
            }

            if (! $semantic_checker->doesBothSemanticFieldHaveTheSameType($source_tracker, $target_tracker)) {
                throw new MoveArtifactSemanticsException("Both $semantic_name fields must have the same type.");
            }
        }

        $event = new MoveArtifactCheckExternalSemantics($source_tracker, $target_tracker);
        $this->event_manager->processEvent($event);

        $external_semantics_checked = $event->getExternalSemanticsChecked();
        if ($event->wasVisitedByPlugin() && $event->areExternalSemanticAligned()) {
            return true;
        }

        $this->throwException($external_semantics_checked);
    }

    /**
     * @throws MoveArtifactSemanticsException
     */
    private function throwException(array $external_semantics_checked)
    {
        $all_checked_semantics = array_merge($this->semantic_checked, $external_semantics_checked);
        throw new MoveArtifactSemanticsException(
            "Both trackers must have at least one of the following semantic defined: " . implode(', ', $all_checked_semantics)
        );
    }
}
