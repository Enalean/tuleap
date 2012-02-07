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

require_once 'Admin.class.php';
require_once 'Admin_StaticField_LineBreak.class.php';
require_once 'Admin_StaticField_Separator.class.php';
require_once 'Admin_Field_LastUpdateDate.class.php';
require_once 'Admin_Field_PermissionsOnArtifact.class.php';
require_once 'Admin_Field_SubmittedBy.class.php';
require_once 'Admin_Field_SubmittedOn.class.php';


class Tracker_FormElement_AdminFactory {

    public function getElement(Tracker_FormElement $element) {
        switch(get_class($element)) {
            case 'Tracker_FormElement_Field_SubmittedBy':
                $adminElement = new Tracker_FormElement_Admin_Field_SubmittedBy($element);
                break;
            
            case 'Tracker_FormElement_Field_SubmittedOn':
                $adminElement = new Tracker_FormElement_Admin_Field_SubmittedOn($element);
                break;
            
            case 'Tracker_FormElement_Field_PermissionsOnArtifact':
                $adminElement = new Tracker_FormElement_Admin_Field_PermissionsOnArtifact($element);
                break;
            
            case 'Tracker_FormElement_Field_LastUpdateDate':
                $adminElement = new Tracker_FormElement_Admin_Field_LastUpdateDate($element);
                break;
            
            case 'Tracker_FormElement_StaticField_LineBreak':
                $adminElement = new Tracker_FormElement_Admin_StaticField_LineBreak($element);
                break;
            
            case 'Tracker_FormElement_StaticField_Separator':
                $adminElement = new Tracker_FormElement_Admin_StaticField_Separator($element);
                break;
            
            default:
                $adminElement = new Tracker_FormElement_Admin($element);
        }
        return $adminElement;
    }

}

?>
