<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\JSONHeader;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\FormElement\View\Admin\Field\Computed;

/**
 * Can visit a FormElement and provides the corresponding administration element
 */
class Tracker_FormElement_View_Admin_Visitor implements Tracker_FormElement_Visitor, Tracker_FormElement_FieldVisitor
{
    public const SUBMIT_UPDATE = 'update-formElement';
    public const SUBMIT_CREATE = 'docreate-formElement';

    /**
     * @var Tracker_FormElement_View_Admin
     */
    protected $adminElement = null;

    /**
     * @var Tracker_FormElement
     */
    protected $element = null;

    protected $allUsedElements = array();

    /**
     * @param Array $allUsedElements
     */
    public function __construct($allUsedElements)
    {
        $this->allUsedElements = $allUsedElements;
    }

    /**
     * Inspect the element
     *
     * @param Tracker_FormElement $element
     */
    public function visit(/*Tracker_FormElement*/ $element)
    {
        $this->element = $element;

        if ($element instanceof Tracker_FormElement_Container) {
            $this->visitContainer($element);
        } elseif ($element instanceof Tracker_FormElement_StaticField_LineBreak) {
            $this->visitLineBreak($element);
        } elseif ($element instanceof Tracker_FormElement_StaticField_Separator) {
            $this->visitSeparator($element);
        } elseif ($element instanceof Tracker_FormElement_StaticField) {
            $this->visitStaticField($element);
        } elseif ($element instanceof Tracker_FormElement_Shared) {
            $this->visitShared($element);
        } else {
            throw new Exception("Cannot visit unknown type");
        }
    }

    public function visitArtifactLink(Tracker_FormElement_Field_ArtifactLink $field)
    {
        $this->visitField($field);
    }

    public function visitDate(Tracker_FormElement_Field_Date $field)
    {
        $this->visitField($field);
    }

    public function visitFile(Tracker_FormElement_Field_File $field)
    {
        $this->visitField($field);
    }

    public function visitFloat(Tracker_FormElement_Field_Float $field)
    {
        $this->visitField($field);
    }

    public function visitInteger(Tracker_FormElement_Field_Integer $field)
    {
        $this->visitField($field);
    }

    public function visitOpenList(Tracker_FormElement_Field_OpenList $field)
    {
        $this->visitList($field);
    }

    public function visitString(Tracker_FormElement_Field_String $field)
    {
        $this->visitField($field);
    }

    public function visitText(Tracker_FormElement_Field_Text $field)
    {
        $this->visitField($field);
    }

    public function visitComputed(Tracker_FormElement_Field_Computed $element)
    {
        $this->element      = $element;
        $this->adminElement = new Computed($element, $this->allUsedElements);
    }

    private function visitField(Tracker_FormElement_Field $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field($element, $this->allUsedElements);
    }

    public function visitArtifactId(Tracker_FormElement_Field_ArtifactId $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_ArtifactId($element, $this->allUsedElements);
    }

    public function visitPerTrackerArtifactId(Tracker_FormElement_Field_PerTrackerArtifactId $element)
    {
        $this->visitArtifactId($element);
    }

    public function visitCrossReferences(Tracker_FormElement_Field_CrossReferences $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_CrossReferences($element, $this->allUsedElements);
    }

    public function visitBurndown(Tracker_FormElement_Field_Burndown $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_Burndown($element, $this->allUsedElements);
    }
    public function visitLastUpdateDate(Tracker_FormElement_Field_LastUpdateDate $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_LastUpdateDate($element, $this->allUsedElements);
    }

    public function visitPermissionsOnArtifact(Tracker_FormElement_Field_PermissionsOnArtifact $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_PermissionsOnArtifact($element, $this->allUsedElements);
    }

    private function visitList(Tracker_FormElement_Field_List $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_List($element, $this->allUsedElements);
    }

    public function visitSelectbox(Tracker_FormElement_Field_Selectbox $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_Selectbox($element, $this->allUsedElements);
    }

    public function visitSubmittedBy(Tracker_FormElement_Field_SubmittedBy $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_SubmittedBy($element, $this->allUsedElements);
    }

    public function visitLastModifiedBy(Tracker_FormElement_Field_LastModifiedBy $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_LastModifiedBy($element, $this->allUsedElements);
    }

    public function visitSubmittedOn(Tracker_FormElement_Field_SubmittedOn $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_SubmittedOn($element, $this->allUsedElements);
    }

    public function visitMultiSelectbox(Tracker_FormElement_Field_MultiSelectbox $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_MultiSelectbox($element, $this->allUsedElements);
    }

    public function visitCheckbox(Tracker_FormElement_Field_Checkbox $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_Checkbox($element, $this->allUsedElements);
    }

    public function visitRadiobutton(Tracker_FormElement_Field_Radiobutton $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Field_Radiobutton($element, $this->allUsedElements);
    }

    private function visitContainer(Tracker_FormElement_Container $element)
    {
        $this->adminElement = new Tracker_FormElement_View_Admin_Container($element, $this->allUsedElements);
    }

    private function visitStaticField(Tracker_FormElement_StaticField $element)
    {
        $this->adminElement = new Tracker_FormElement_View_Admin_StaticField($element, $this->allUsedElements);
    }

    private function visitLineBreak(Tracker_FormElement_StaticField_LineBreak $element)
    {
        $this->adminElement = new Tracker_FormElement_View_Admin_StaticField_LineBreak($element, $this->allUsedElements);
    }

    private function visitSeparator(Tracker_FormElement_StaticField_Separator $element)
    {
        $this->adminElement = new Tracker_FormElement_View_Admin_StaticField_Separator($element, $this->allUsedElements);
    }

    private function visitShared(Tracker_FormElement_Shared $element)
    {
        $this->adminElement = new Tracker_FormElement_View_Admin_Shared($element, $this->allUsedElements);
    }

    public function visitPriority(Tracker_FormElement_Field_Priority $element)
    {
        $this->element      = $element;
        $this->adminElement = new Tracker_FormElement_View_Admin_Priority($element, $this->allUsedElements);
    }

    public function visitExternalField(TrackerFormElementExternalField $element)
    {
        $this->element      = $element;
        $this->adminElement = $element->getFormAdminVisitor($element, $this->allUsedElements);
    }

    /**
     * Return the AdminEdition element corresponding to the visited element
     *
     * Mostly used for tests.
     *
     * @return Tracker_FormElement_View_Admin
     */
    public function getAdmin()
    {
        return $this->adminElement;
    }

    protected function displayForm(TrackerManager $tracker_manager, HTTPRequest $request, $url, $title, $formContent)
    {
        $form  = '<form name="form1" method="POST" action="' . $url . '">';
        $form .= $formContent;
        $form .= '</form>';

        if ($request->isAjax()) {
            $this->displayAjax($title, $form);
        } else {
            $this->displayFullPage($tracker_manager, $title, $form);
        }
    }

    protected function displayAjax($title, $form)
    {
        header(JSONHeader::getHeaderForPrototypeJS(['dialog-title' => $title]));
        echo $form;
    }

    protected function displayFullPage(TrackerManager $tracker_manager, $title, $form)
    {
        $this->element->getTracker()->displayAdminFormElementsHeader($tracker_manager, $title);
        $purifier = Codendi_HTMLPurifier::instance();
        echo '<h2 class="almost-tlp-title">' . $purifier->purify($title) . '</h2>';
        echo $form;
        $this->element->getTracker()->displayFooter($tracker_manager);
    }
}
