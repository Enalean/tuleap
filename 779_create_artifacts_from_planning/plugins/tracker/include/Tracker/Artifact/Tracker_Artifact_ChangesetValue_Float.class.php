<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once('Tracker_Artifact_ChangesetValue_Numeric.class.php');

/**
 * Manage values in changeset for float fields
 */
class Tracker_Artifact_ChangesetValue_Float extends Tracker_Artifact_ChangesetValue_Numeric {
    
    /**
     * Get the float value
     *
     * @return float the float value
     */
    public function getFloat() {
        if ($this->numeric !== null) {
            $this->numeric = (float)$this->numeric;
        }
        return $this->numeric;
    }
    
    /**
     * Get the float value
     *
     * @return float the float value
     */
    public function getNumeric() {
        return $this->getFloat();
    }
    
    /**
     * Get the string value for this float
     *
     * @return string The value of this artifact changeset value
     */
    public function getValue() {
        if ($this->getFloat() !== null) {
            return number_format($this->getFloat(), Tracker_FormElement_Field_Float::FLOAT_DECIMALS, '.', '');
        } else {
            return '';
        }
    }
    
    /**
     * Get the SOAP value
     *
     * @return string The value of this artifact changeset value for SOAP
     */
    public function getSoapValue() {
        return (string)$this->getFloat();
    }

}

?>