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

namespace Tuleap\Velocity\Semantic;

use Tracker;

class BacklogRequiredTracker
{
    /** @var Tracker */
    private $tracker;
    /** @var bool */
    private $is_done_semantic_missing;
    /** @var bool */
    private $is_initial_effort_semantic_missing;

    /**
     * @param bool $is_done_semantic_missing
     * @param bool $is_initial_effort_semantic_missing
     */
    public function __construct(Tracker $tracker, $is_done_semantic_missing, $is_initial_effort_semantic_missing)
    {
        $this->tracker                            = $tracker;
        $this->is_done_semantic_missing           = $is_done_semantic_missing;
        $this->is_initial_effort_semantic_missing = $is_initial_effort_semantic_missing;
    }

    /**
     * @return Tracker
     */
    public function getTracker()
    {
        return $this->tracker;
    }

    /**
     * @return bool
     */
    public function isDoneSemanticMissing()
    {
        return $this->is_done_semantic_missing;
    }

    /**
     * @return bool
     */
    public function isInitialEffortSemanticMissing()
    {
        return $this->is_initial_effort_semantic_missing;
    }

    public function isWellConfigured()
    {
        return ! $this->isDoneSemanticMissing() && ! $this->isInitialEffortSemanticMissing();
    }
}
