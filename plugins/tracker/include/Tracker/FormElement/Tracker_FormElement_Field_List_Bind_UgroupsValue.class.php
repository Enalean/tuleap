<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

require_once('Tracker_FormElement_Field_List_BindValue.class.php');

class Tracker_FormElement_Field_List_Bind_UgroupsValue extends Tracker_FormElement_Field_List_BindValue {

    /**
     * @var UGroup
     */
    protected $ugroup;

    public function __construct($id, UGroup $ugroup, $is_hidden) {
        parent::__construct($id, $is_hidden);
        $this->ugroup    = $ugroup;
    }

    public function getLabel() {
        return $this->ugroup->getTranslatedName();
    }

    public function getUGroupName() {
        return $this->ugroup->getName();
    }

    public function getUgroupId() {
        return $this->ugroup->getId();
    }

    public function __toString() {
        return __CLASS__ .' #'. $this->getId();
    }

    public function getMembersName() {
        return array_map(array($this, 'getUserName'), $this->ugroup->getMembers());
    }

    private function getUserName(User $user) {
        return $user->getUserName();
    }

    public function getSoapValue() {
        return $this->getUGroupName();
    }
}
?>
