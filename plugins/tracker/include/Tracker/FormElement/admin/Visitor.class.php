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

class Tracker_FormElement_Admin_Visitor {
    /**
     * @var Tracker_FormElement_Admin
     */
    private $adminElement = null;
    
    /**
     * @var Tracker_FormElement 
     */
    private $element = null;
    
    private $allUsedElements = array();
    
    public function __construct($allUsedElements) {
        $this->allUsedElements = $allUsedElements;
    }
    
    /**
     *
     * @param Tracker_FormElement $element 
     */
    public function visit(Tracker_FormElement $element) {
        $this->element = $element;
        
        if ($element instanceof Tracker_FormElement_Field_MultiSelectbox) {
            $this->visitMultiSelectbox($element);
        } elseif ($element instanceof Tracker_FormElement_Field_Selectbox) {
            $this->visitSelectbox($element);
        } elseif ($element instanceof Tracker_FormElement_Field_SubmittedBy) {
            $this->visitSubmittedBy($element);
        } elseif ($element instanceof Tracker_FormElement_Field_List) {
            $this->visitList($element);
        } elseif ($element instanceof Tracker_FormElement_Field_ArtifactId) {
            $this->visitArtifactId($element);
        } elseif ($element instanceof Tracker_FormElement_Field_CrossReferences) {
            $this->visitCrossReferences($element);
        } elseif ($element instanceof Tracker_FormElement_Field_LastUpdateDate) {
            $this->visitLastUpdateDate($element);
        } elseif ($element instanceof Tracker_FormElement_Field_PermissionsOnArtifact) {
            $this->visitPermissionsOnArtifact($element);
        } elseif ($element instanceof Tracker_FormElement_Field_SubmittedOn) {
            $this->visitSubmittedOn($element);
        } elseif ($element instanceof Tracker_FormElement_Field) {
            $this->visitField($element);
        } elseif ($element instanceof Tracker_FormElement_Container) {
            $this->visitContainer($element);
        } elseif ($element instanceof Tracker_FormElement_StaticField_LineBreak) {
            $this->visitLineBreak($element);
        } elseif ($element instanceof Tracker_FormElement_StaticField_Separator) {
            $this->visitSeparator($element);
        } elseif ($element instanceof Tracker_FormElement_StaticField) {
            $this->visitStaticField($element);
        } else {
            throw new Exception("Cannot visit unknown type");
        }
    }

    private function visitField(Tracker_FormElement_Field $element) {
        include_once 'Admin_Field.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field($element, $this->allUsedElements);
    }
    
    private function visitArtifactId(Tracker_FormElement_Field_ArtifactId $element) {
        include_once 'Admin_Field_ArtifactId.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_ArtifactId($element, $this->allUsedElements);
    }
    
    private function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $element) {
        include_once 'Admin_Field_CrossReferences.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_CrossReferences($element, $this->allUsedElements);
    }
    
    private function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $element) {
        include_once 'Admin_Field_LastUpdateDate.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_LastUpdateDate($element, $this->allUsedElements);
    }
    
    private function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $element) {
        include_once 'Admin_Field_PermissionsOnArtifact.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_PermissionsOnArtifact($element, $this->allUsedElements);
    }
    
    private function visitList(Tracker_FormElement_Field_List $element) {
        include_once 'Admin_Field_List.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_List($element, $this->allUsedElements);
    }
    
    private function visitSelectbox(Tracker_FormElement_Field_Selectbox $element) {
        include_once 'Admin_Field_Selectbox.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_Selectbox($element, $this->allUsedElements);
    }

    private function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $element) {
        include_once 'Admin_Field_SubmittedBy.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_SubmittedBy($element, $this->allUsedElements);
    }
    
    private function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $element) {
        include_once 'Admin_Field_SubmittedOn.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_SubmittedOn($element, $this->allUsedElements);
    }
    
    private function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $element) {
        include_once 'Admin_Field_MultiSelectbox.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Field_MultiSelectbox($element, $this->allUsedElements);
    }
    
    private function visitContainer(Tracker_FormElement_Container $element) {
        include_once 'Admin_Container.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_Container($element, $this->allUsedElements);
    }
    
    private function visitStaticField(Tracker_FormElement_StaticField $element) {
        include_once 'Admin_StaticField.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_StaticField($element, $this->allUsedElements);
    }
    
    private function visitLineBreak(Tracker_FormElement_StaticField_LineBreak $element) {
        include_once 'Admin_StaticField_LineBreak.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_StaticField_LineBreak($element, $this->allUsedElements);
    }
    
    private function visitSeparator(Tracker_FormElement_StaticField_Separator $element) {
        include_once 'Admin_StaticField_Separator.class.php';
        $this->adminElement = new Tracker_FormElement_Admin_StaticField_Separator($element, $this->allUsedElements);
    }
    
    public function getAdmin() {
        return $this->adminElement;
    }
    
    public function getAdminForm($submit_name) {
        if ($submit_name == 'update-formElement') {
            if ($this->element->isModifiable()) {
                $html = $this->adminElement->fetchAdminForUpdate();
            } else {
                $html = $this->adminElement->fetchAdminForShared();
            }
        } else if ($submit_name == 'docreate-formElement') {
            $html = $this->adminElement->fetchAdminForCreate();
        }
        return $html;
    }
    
}

?>
