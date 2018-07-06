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

class MoveSemanticChecker
{
    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var array
     */
    private $semantic_checked = ['title', 'description'];

    public function __construct(EventManager $event_manager)
    {
        $this->event_manager = $event_manager;
    }

    /**
     * @return bool
     * @throws MoveArtifactSemanticsException
     */
    public function checkSemanticsAreAligned(Tracker $source_tracker, Tracker $target_tracker)
    {
        if (($source_tracker->hasSemanticsTitle() && $target_tracker->hasSemanticsTitle()) ||
            ($source_tracker->hasSemanticsDescription() && $target_tracker->hasSemanticsDescription())
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
