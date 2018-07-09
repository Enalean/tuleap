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

use Tracker;

abstract class MoveSemanticChecker
{
    /**
     * @return string
     */
    abstract public function getSemanticName();

    /**
     * @return bool
     */
    abstract public function areBothSemanticsDefined(Tracker $source_tracker, Tracker $target_tracker);

    /**
     * @return bool
     */
    public function doesBothSemanticFieldHaveTheSameType(Tracker $source_tracker, Tracker $target_tracker)
    {
        return true;
    }

    /**
     * @return bool
     */
    public function areSemanticsAligned(Tracker $source_tracker, Tracker $target_tracker)
    {
        return $this->areBothSemanticsDefined($source_tracker, $target_tracker) &&
            $this->doesBothSemanticFieldHaveTheSameType($source_tracker, $target_tracker);
    }
}
