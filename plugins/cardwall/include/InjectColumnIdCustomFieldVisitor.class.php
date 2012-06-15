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
require_once 'InjectColumnIdVisitor.class.php';

/**
 * Foreach artifact in a TreeNode, inject the id of the field used for the column
 *
 * There we use only one custom field instead of status semantic
 */
class Cardwall_InjectColumnIdCustomFieldVisitor extends Cardwall_InjectColumnIdVisitor {

    /**
     * @var Tracker_FormElement_Field_Selectbox
     */
    private $field;

    public function __construct(Tracker_FormElement_Field_Selectbox $field = null) {
        $this->field = $field;
    }

    /**
     * @return Tracker_FormElement_Field_Selectbox
     */
    protected function getField(Tracker_Artifact $artifact) {
        return $this->field;
    }
}
?>
