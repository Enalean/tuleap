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
     * @var array
     */
    private $semantic_checked = ['title', 'description', MoveStatusSemanticChecker::STATUS_SEMANTIC_LABEL];
    /**
     * @var MoveStatusSemanticChecker
     */
    private $status_semantic_checker;

    public function __construct(EventManager $event_manager, MoveStatusSemanticChecker $status_semantic_checker)
    {
        $this->event_manager           = $event_manager;
        $this->status_semantic_checker = $status_semantic_checker;
    }

    /**
     * @return bool
     * @throws MoveArtifactSemanticsException
     */
    public function artifactCanBeMoved(Tracker $source_tracker, Tracker $target_tracker)
    {
        if ($this->areTitleSemanticsAligned($source_tracker, $target_tracker) ||
            $this->areDescriptionSemanticsAligned($source_tracker, $target_tracker) ||
            $this->arestatusSemanticsAligned($source_tracker, $target_tracker)
        ) {
            return true;
        }

        $event = new MoveArtifactCheckExternalSemantics($source_tracker, $target_tracker);
        $this->event_manager->processEvent($event);

        $external_semantics_checked = $event->getExternalSemanticsChecked();
        if ($event->wasVisitedByPlugin() && $event->areExternalSemanticAligned()) {
            return true;
        }

        $this->throwException($external_semantics_checked);
    }

    private function areTitleSemanticsAligned(Tracker $source_tracker, Tracker $target_tracker)
    {
        return $source_tracker->hasSemanticsTitle() && $target_tracker->hasSemanticsTitle();
    }

    private function areDescriptionSemanticsAligned(Tracker $source_tracker, Tracker $target_tracker)
    {
        return $source_tracker->hasSemanticsDescription() && $target_tracker->hasSemanticsDescription();
    }

    /**
     * @return bool
     * @throws MoveArtifactSemanticsException
     */
    private function areStatusSemanticsAligned(Tracker $source_tracker, Tracker $target_tracker)
    {
        if (! $this->status_semantic_checker->areBothSemanticsDefined($source_tracker, $target_tracker)) {
            return false;
        }

        if (! $this->status_semantic_checker->doesBothTrackerStatusFieldHaveTheSameType($source_tracker, $target_tracker)) {
            throw new MoveArtifactSemanticsException("Both status fields must have the same type.");
        }

        return true;
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
