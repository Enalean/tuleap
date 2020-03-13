<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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

/**
 * Manage values in changeset for integer fields
 */
class Tracker_Artifact_ChangesetValue_Integer extends Tracker_Artifact_ChangesetValue_Numeric
{

    /**
     * @return mixed
     */
    public function accept(Tracker_Artifact_ChangesetValueVisitor $visitor)
    {
        return $visitor->visitInteger($this);
    }

    /**
     * Returns the integer value
     *
     * @return int the integer value
     */
    public function getInteger()
    {
        if ($this->numeric !== null) {
            $this->numeric = (int) $this->numeric;
        }
        return $this->numeric;
    }

    /**
     * Returns the integer value
     *
     * @return int the integer value
     */
    public function getNumeric()
    {
        return $this->getInteger();
    }

    /**
     * Returns the value of this changeset value (integer)
     *
     * @return int The value of this artifact changeset value
     */
    public function getValue()
    {
         return $this->getInteger();
    }

    public function getRESTValue(PFUser $user)
    {
        return $this->getRESTRepresentation($this->getInteger());
    }

    public function getFullRESTValue(PFUser $user)
    {
        return $this->getFullRESTRepresentation($this->getInteger());
    }
}
