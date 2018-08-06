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
use Tuleap\Tracker\Action\Move\FeedbackFieldCollector;
use Tuleap\Tracker\Events\MoveArtifactGetExternalSemanticCheckers;
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
    public function artifactCanBeMoved(
        Tracker $source_tracker,
        Tracker $target_tracker,
        FeedbackFieldCollector $feedback_field_collector
    ) {
        $event = new MoveArtifactGetExternalSemanticCheckers();
        $this->event_manager->processEvent($event);

        $semantics_are_aligned = false;

        $all_semantic_checkers = array_merge($this->semantic_checkers, $event->getExternalSemanticsCheckers());
        foreach ($all_semantic_checkers as $semantic_checker) {
            $semantic_name            = $semantic_checker->getSemanticName();
            $source_semantic_field    = $semantic_checker->getSourceSemanticField($source_tracker);
            $this->semantic_checked[] = $semantic_name;

            if ($semantic_checker->areSemanticsAligned($source_tracker, $target_tracker)) {
                $semantics_are_aligned = true;

                $feedback_field_collector->addFieldInFullyMigrated($source_semantic_field);
            }

            if ($source_semantic_field &&
                ! $semantic_checker->areBothSemanticsDefined($source_tracker, $target_tracker)
            ) {
                $feedback_field_collector->addFieldInNotMigrated($source_semantic_field);
            }

            if ($semantic_checker->areBothSemanticsDefined($source_tracker, $target_tracker) &&
                ! $semantic_checker->doesBothSemanticFieldHaveTheSameType($source_tracker, $target_tracker)
            ) {
                $feedback_field_collector->addFieldInNotMigrated($source_semantic_field);
            }
        }

        if ($semantics_are_aligned) {
            return true;
        }

        throw new MoveArtifactSemanticsException(
            "Both trackers must have at least one of the following semantic defined: " . implode(', ', $this->semantic_checked)
        );
    }
}
