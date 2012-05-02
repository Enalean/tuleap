<?php

/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'FormElement/Tracker_FormElement_Usable.class.php';
require_once('FormElement/IHaveAnId.class.php');

interface Tracker_Report_Field extends Tracker_FormElement_IHaveAnId, Tracker_FormElement_Usable {
    
    /**
     * Return a label (e.g. usable both in a form or as a column header)
     */
    function getLabel();
    
    /**
     * Display the field as a criteria
     * @param Tracker_Report_Criteria $criteria
     * @return string
     */
    function fetchCriteria(Tracker_Report_Criteria $criteria);
    
    /**
     * Display the field as a Changeset value.
     * Used in report table
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     * @return string
     */
    function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null);
    
}
?>
