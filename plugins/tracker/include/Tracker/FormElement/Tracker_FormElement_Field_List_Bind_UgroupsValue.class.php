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

require_once('Tracker_FormElement_Field_List_BindValue.class.php');

class Tracker_FormElement_Field_List_Bind_UgroupsValue extends Tracker_FormElement_Field_List_BindValue {

    /**
     * @var UGroup
     */
    protected $ugroup;

    public function __construct($id, UGroup $ugroup) {
        parent::__construct($id);
        $this->ugroup = $ugroup;
    }

    public function getLabel() {
        return $this->ugroup->getTranslatedName();
    }

    public function __toString() {
        return __CLASS__ .' #'. $this->getId();
    }

    public function getMembers() {
        return $this->ugroup->getMembers();
    }
}
?>
