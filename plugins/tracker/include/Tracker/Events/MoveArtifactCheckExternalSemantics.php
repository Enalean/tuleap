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

namespace Tuleap\Tracker\Events;

use Tracker;
use Tuleap\Event\Dispatchable;

class MoveArtifactCheckExternalSemantics implements Dispatchable
{
    const NAME = "moveArtifactCheckExternalSemantics";

    /**
     * @var Tracker
     */
    private $source_tracker;

    /**
     * @var Tracker
     */
    private $target_tracker;

    /**
     * @var bool
     */
    private $are_external_semantic_aligned = false;

    /**
     * @var array
     */
    private $external_semantics_checked = [];

    /**
     * @var bool
     */
    private $visited_by_plugin = false;

    public function __construct(Tracker $source_tracker, Tracker $target_tracker)
    {
        $this->source_tracker = $source_tracker;
        $this->target_tracker = $target_tracker;
    }

    /**
     * @return Tracker
     */
    public function getSourceTracker()
    {
        return $this->source_tracker;
    }

    /**
     * @return Tracker
     */
    public function getTargetTracker()
    {
        return $this->target_tracker;
    }

    /**
     * @return bool
     */
    public function areExternalSemanticAligned()
    {
        return $this->are_external_semantic_aligned;
    }

    public function setExternalSemanticAligned()
    {
        $this->are_external_semantic_aligned = true;
    }

    /**
     * @return bool
     */
    public function wasVisitedByPlugin()
    {
        return $this->visited_by_plugin;
    }

    public function setVisitedByPlugin()
    {
        $this->visited_by_plugin = true;
    }

    /**
     * @return array
     */
    public function getExternalSemanticsChecked()
    {
        return $this->external_semantics_checked;
    }

    /**
     * @param string $external_semantics_checked
     */
    public function setExternalSemanticsChecked($external_semantics_checked)
    {
        $this->external_semantics_checked[] = $external_semantics_checked;
    }
}
