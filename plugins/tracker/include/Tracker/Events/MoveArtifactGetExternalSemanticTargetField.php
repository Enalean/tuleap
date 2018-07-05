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
use Tracker_FormElement_Field;
use Tuleap\Event\Dispatchable;

class MoveArtifactGetExternalSemanticTargetField implements Dispatchable
{
    const NAME = "moveArtifactGetExternalSemanticTargetField";

    /**
     * @var Tracker
     */
    private $source_tracker;

    /**
     * @var Tracker
     */
    private $target_tracker;

    /**
     * @var null|Tracker_FormElement_Field
     */
    private $field = null;

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
     * @return null|Tracker_FormElement_Field
     */
    public function getField()
    {
        return $this->field;
    }

    public function setField(Tracker_FormElement_Field $field = null)
    {
        $this->field = $field;
    }
}
