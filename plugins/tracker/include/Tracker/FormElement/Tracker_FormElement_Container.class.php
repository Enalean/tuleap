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


/**
 * Base class for composite formElements. 
 * 
 * A composite is a component which contain other component.
 * See DesignPattern Composite for more details.
 */
abstract class Tracker_FormElement_Container extends Tracker_FormElement {
    /**
     * The formElements of this container
     */
    public $formElements = null;
    
    /**
     * @return array the used formElements contained in this container
     */
    public function getFormElements() {
        if (!is_array($this->formElements)) {
            $aff = Tracker_FormElementFactory::instance();
            $this->formElements = $aff->getUsedFormElementsByParentId($this->id);
        }
        return $this->formElements;
    }
    
    public function getAllFormElements() {
        return Tracker_FormElementFactory::instance()->getAllFormElementsByParentId($this->id);
    }

    public function fetchMailArtifact($recipient, Tracker_Artifact $artifact, $format='text', $ignore_perms=false) {
        return $this->fetchMailRecursiveArtifact($format, 'fetchMailArtifact', array($recipient, $artifact, $format, $ignore_perms));
    }
    
    
    /**
     * Prepare the element to be displayed
     *
     * @return void
     */
    public function prepareForDisplay() {
        $this->has_been_displayed = false;
        foreach($this->getFormElements() as $field) {
            $field->prepareForDisplay();
        }
    }
    
    public function getRankSelectboxDefinition() {
        $def = parent::getRankSelectboxDefinition();
        $def['subitems'] = array(); 
        foreach($this->getFormElements() as $field) {
            $def['subitems'][] = $field->getRankSelectboxDefinition();
        }
        return $def;
    }
    
    /**
     * Fetch the "add criteria" box
     *
     * @param array $used Current used formElements as criteria.
     * @param string $prefix Prefix to add before label in optgroups
     * 
     * @return string
     */
    public function fetchAddCriteria($used, $prefix = '') {
        return $this->fetchOptgroup('fetchAddCriteria', 'add_criteria_container_', $used, $prefix);
    }
    
    /**
     * Fetch the "add column" box in table renderer
     *
     * @param array $used Current used formElements as column.
     * @param string $prefix Prefix to add before label in optgroups
     * 
     * @return string
     */
    public function fetchAddColumn($used, $prefix = '') {
        return $this->fetchOptgroup('fetchAddColumn', 'add_column_container_', $used, $prefix);
    }
    
    /**
     * Fetch the "add tooltip" box in admin
     *
     * @param array $used Current used fields as column.
     * @param string $prefix Prefix to add before label in optgroups
     * 
     * @return string
     */
    public function fetchAddTooltip($used, $prefix = '') {
        return $this->fetchOptgroup('fetchAddTooltip', 'add_tooltip_container_', $used, $prefix);
    }
    
    /**
     * Internal method used to build optgroups
     
     * @see fetchAddCriteria
     * @see fetchAddColumn
     * 
     * @param string $method the method to call recursively on formElements
     * @param string $id_prefix the prefix for the html element id
     * @param array $used Current used formElements as column.
     * @param string $prefix Prefix to add before label in optgroups
     * 
     * @return string
     */
    protected function fetchOptgroup($method, $id_prefix, $used, $prefix) {
        $prefix .= $this->getLabel();
        $html = '<optgroup id="'. $id_prefix . $this->id .'" label="'. $prefix .'">';
        $optgroups = '';
        foreach($this->getFormElements() as $formElement) {
            if ($formElement->userCanRead()) {
                $opt = $formElement->$method($used, $prefix . '::');
                if (strpos($opt, '<optgroup') === 0) {
                    $optgroups .= $opt;
                } else {
                    $html .= $opt;
                }
            }
        }
        $html .= '</optgroup>';
        $html .= $optgroups;
        return $html;
    }
    
    /**
     * Transforms FormElement into a SimpleXMLElement
     * 
     * @param SimpleXMLElement $root        the node to which the FormElement is attached (passed by reference)
     * @param array            &$xmlMapping correspondance between real ids and xml IDs
     * @param int              $index       of the last field in the array
     */
    public function exportToXml(SimpleXMLElement $root, &$xmlMapping, &$index) {
        parent::exportToXML($root, $xmlMapping, $index);
        $subfields = $this->getAllFormElements();
        $child = $root->addChild('formElements');
        foreach($subfields as $subfield) {
            $grandchild = $child->addChild('formElement');
            $index++;
            $subfield->exportToXML($grandchild, $xmlMapping, $index);
        }
    }
    
    /**
     * Verifies the consistency of the imported Tracker
     * 
     * @return true if Tracker is ok 
     */
    public function testImport() {
        if ($this->formElements != null) {
            foreach ($this->formElements as $form) {
                if (!$form->testImport()) {
                    return false;
                }
            }
        }
        return true;
    }
    
    /**
     * Fetch the element for the submit new artifact form
     * @param array $submitted_values the values already submitted
     *
     * @return string html
     */
    public function fetchSubmit($submitted_values = array()) {
        return $this->fetchRecursiveArtifact('fetchSubmit', array($submitted_values));
    }

    /**
     * Fetch the element for the submit masschange form
     * @return <type>
     */
    public function fetchSubmitMasschange($submitted_values = array()) {
        return $this->fetchRecursiveArtifact('fetchSubmitMasschange', array($submitted_values=array()));
    }

    /**
     * Fetch the element for the update artifact form
     *
     * @param Tracker_Artifact $artifact
     *
     * @return string html
     */
    public function fetchArtifact(Tracker_Artifact $artifact, $submitted_values = array()) {
        return $this->fetchRecursiveArtifact('fetchArtifact', array($artifact, $submitted_values));
    }

    /**
     * Fetch the element for the update artifact form
     *
     * @param Tracker_Artifact $artifact
     *
     * @return string html
     */
    public function fetchArtifactReadOnly(Tracker_Artifact $artifact) {
        return $this->fetchRecursiveArtifact('fetchArtifactReadOnly', array($artifact));
    }
    
    protected function fetchRecursiveArtifact($method, $params = array()) {
        $html = '';
        $content = $this->getContainerContent($method, $params);
        
        if (count($content)) {
            $html .= $this->fetchArtifactPrefix();
            $html .= $this->fetchArtifactContent($content);
            $html .= $this->fetchArtifactSuffix();
        }
        $this->has_been_displayed = true;
        return $html;
    }
    
    protected function fetchRecursiveArtifactReadOnly($method, $params = array()) {
        $html = '';
        $content = $this->getContainerContent($method, $params);

        if (count($content)) {
            $html .= $this->fetchArtifactReadOnlyPrefix();
            $html .= $this->fetchArtifactReadOnlyContent($content);
            $html .= $this->fetchArtifactReadOnlySuffix();
        }
        $this->has_been_displayed = true;
        return $html;
    }

    protected function fetchMailRecursiveArtifact($format, $method, $params = array()) {
        $output = '';
        $content = $this->getContainerContent($method, $params);
        
        if (count($content)) {
            $output .= $this->fetchMailArtifactPrefix($format);
            $output .= $this->fetchMailArtifactContent($format, $content);
            $output .= $this->fetchMailArtifactSuffix($format);
        }
        $this->has_been_displayed = true;
        return $output;
    }
    
    protected function getContainerContent($method, $params) {
        $content = array();
        foreach($this->getFormElements() as $formElement) {
            if ($c = call_user_func_array(array($formElement, $method), $params)) {
                $content[] = $c;
            }
        }
        return $content;
    }
    
    protected $has_been_displayed = false;
    public function hasBeenDisplayed() {
        return $this->has_been_displayed;
    }
    
    /**
     * Continue the initialisation from an xml (FormElementFactory is not smart enough to do all stuff.
     * Polymorphism rulez!!!
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported Tracker_FormElement
     * @param array            &$xmlMapping where the newly created formElements indexed by their XML IDs are stored (and values)
     *
     * @return void
     */
    public function continueGetInstanceFromXML($xml, &$xmlMapping) {
        parent::continueGetInstanceFromXML($xml, $xmlMapping);
        // add children
        if ($xml->formElements) {
            foreach ($xml->formElements->formElement as $elem) {
                $this->formElements[] = $this->getFormElementFactory()->getInstanceFromXML($this->getTracker(), $elem, $xmlMapping);
            }
        }
    }
    
    /**
     * Callback called after factory::saveObject. Use this to do post-save actions
     *
     * @param Tracker $tracker The tracker
     *
     * @return void
     */
    public function afterSaveObject(Tracker $tracker) {
        //save sub elements
        foreach ($this->getFormElements() as $elem){
            $this->getFormElementFactory()->saveObject($tracker, $elem, $this->getId());
        }
    }
    
    /**
     * Get the FormElement factory
     *
     * @return Tracker_FormElementFactory
     */
    public function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }
    
    /**
     * Say if the field is updateable
     *
     * @return bool
     */
    public function isUpdateable() {
        return false;
    }
    
    /**
     * Say if the field is submitable
     *
     * @return bool
     */
    public function isSubmitable() {
        return false;
    }
    
    /**
     * Is the form element can be removed from usage?
     * This method is to prevent tracker inconsistency
     *
     * @return string
     */
    public function getCannotRemoveMessage() {
        $message = '';

        if (! $this->canBeRemovedFromUsage()) {
            $message = $GLOBALS['Language']->getText(
                'plugin_tracker_common_fieldset_factory',
                'delete_only_empty_fieldset'
            );
        }
        
        return $message;
    }

    /**
     *
     * @return boolean
     */
    public function canBeRemovedFromUsage() {
        $container_sub_elements = count($this->getFormElements());

        if ($container_sub_elements > 0) {
            return false;
        }

        return true;
    }


    /** 
     * return true if user has Read or Update permission on this field
     * 
     * @param User $user The user. if not given or null take the current user
     *
     * @return bool
     */ 
    public function userCanRead(User $user = null) {
        return true;
    }
    
    protected abstract function fetchArtifactPrefix();
    protected abstract function fetchArtifactSuffix();
    protected abstract function fetchArtifactReadOnlyPrefix();
    protected abstract function fetchArtifactReadOnlySuffix();
    protected abstract function fetchMailArtifactPrefix($format);
    protected abstract function fetchMailArtifactSuffix($format);

    
    protected function fetchMailArtifactContent($format, array $content) {
        if ($format == 'text') {
            return implode(PHP_EOL, $content);
        } else {
            return $this->fetchArtifactContent($content);
        }
    }
    
    protected function fetchArtifactContent(array $content) {
        return implode('', $content);
    }

    protected function fetchArtifactReadOnlyContent(array $content) {
        return $this->fetchArtifactContent($content);
    }
}
?>