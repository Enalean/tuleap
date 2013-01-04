<?php

/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
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
 * class Transition_PostAction_FieldFactory
 * 
 */
class Transition_PostAction_FieldFactory {
    
        /**
     * @var Array of available post actions classes
     */
    protected $post_actions_classes_field = array(
        Transition_PostAction_Field_Date::SHORT_NAME    => 'Transition_PostAction_Field_Date',
        Transition_PostAction_Field_Int::SHORT_NAME     => 'Transition_PostAction_Field_Int',
        Transition_PostAction_Field_Float::SHORT_NAME   => 'Transition_PostAction_Field_Float',
    );

    public function getTypes() {
        return array_keys($this->post_actions_classes_field);
    }
    
    public function addPostAction(Transition $transition, $requested_postaction) {
        if (isset($this->post_actions_classes_field[$requested_postaction])) {
            $this->getDao($requested_postaction)->create($transition->getTransitionId());
        }
    }
    
    /**
     * Get html code to let someone choose a post action for a transition
     *
     * @return string html
     */
    public function fetchPostActions() {
        $html = '';
        $html .= '<p>'.$GLOBALS['Language']->getText('workflow_admin', 'add_new_action');
        $html .= '<select name="add_postaction">';
        $html .= '<option value="" selected>--</option>';
        
        $post_actions_classes = $this->post_actions_classes_field;
        foreach ($post_actions_classes as $shortname => $klass) {
            //Waiting for PHP5.3 and $klass::staticMethod() and Late Static Binding
            eval("\$label = $klass::getLabel();");
            $html .= '<option value="'. $shortname .'">';
            $html .= $label;
            $html .= '</option>';
        }
        
        $html .= '</select></p>';
        return $html;
    }
       
    /**
    * Save a postaction object
    * 
    * @param Transition_PostAction $post_action  the object to save
    * @return void
    */
    public function saveObject(Transition_PostAction $post_action) {
        $short_name = $post_action->getShortName();
        $dao = $this->getDao($short_name);

        $dao->save($post_action->getTransition()->getTransitionId(),
           $post_action->getFieldId(),
           $this->getValue($post_action)
        );
    }
    
    public function loadPostActions(Transition $transition) {
        
        $post_actions = array();
        $post_actions_classes = $this->post_actions_classes_field;

        foreach ($post_actions_classes as $shortname => $klass) {
            foreach($this->loadPostActionRows($transition, $shortname) as $row) {
                $post_actions[] = $this->buildPostAction($transition, $row, $shortname, $klass);
            }
        }
        return $post_actions;
   }
   
    /**
     * Retrieves matching PostAction database records.
     * 
     * @param Transition $transition The Transition to which the PostActions must be associated
     * @param string     $shortname  The PostAction type (short name, not class name)
     * 
     * @return DataAccessResult
     */
    public function loadPostActionRows(Transition $transition, $shortname) {
        $dao = $this->getDao($shortname);
        return $dao->searchByTransitionId($transition->getTransitionId());
    }
    
        
   /**
    * Duplicate postactions of a transition
    *
    * @param int $from_transition_id the id of the template transition
    * @param int $to_transition_id the id of the transition
    * @param Array $postactions 
    * @param Array $field_mapping the field mapping
    * 
    */
    public function duplicate($from_transition_id, $to_transition_id, $postactions, $field_mapping) {
        foreach ($postactions as $postaction) {
            $from_field_id = $postaction->getFieldId();
            
            foreach ($field_mapping as $mapping) {
                if ($mapping['from'] == $from_field_id) {
                    $to_field_id = $mapping['to'];
                    $this->getDao($postaction->getShortname())->duplicate($from_transition_id, $to_transition_id, $from_field_id, $to_field_id);
                }
            }
        }
    }
        
    /**
     * Delete a workflow
     *
     * @param int $workflow_id the id of the workflow
     * 
     */
    public function deleteWorkflow($workflow_id) {
        $post_actions_classes = $this->post_actions_classes_field;
        foreach (array_keys($post_actions_classes) as $shortname) {
            if(! $this->getDao($shortname)->deletePostActionsByWorkflowId($workflow_id)) {
                return false;
            }
        }        
        return true;
    }
    
        
    /**
     * Say if a field is used in its tracker workflow transitions post actions
     *
     * @param Tracker_FormElement_Field $field The field
     *
     * @return bool
     */
    public function isFieldUsedInPostActions(Tracker_FormElement_Field $field) {
        foreach (array_keys($this->post_actions_classes_field) as $shortname) {
            if ($this->getDao($shortname)->countByFieldId($field->getId()) > 0) {
                return true;
            }
        }
        return false;
    }
        
    /**
     * Creates a postaction Object
     * 
     * @param SimpleXMLElement $xml         containing the structure of the imported postaction
     * @param array            &$xmlMapping containig the newly created formElements idexed by their XML IDs
     * @param Transition       $transition     to which the postaction is attached
     * 
     * @return Transition_PostAction The  Transition_PostAction object, or null if error
     */
    public function getInstanceFromXML($xml, &$xmlMapping, Transition $transition) {
        $xml_tag_name          = $xml->getName();
        $post_action_class     = $this->getPostActionClassFromXmlTagName($xml_tag_name);
        $field_id              = $xmlMapping[(string)$xml->field_id['REF']];
        $postaction_attributes = $xml->attributes();
        $value                 = $this->getPostActionValueFromXmlTagName($xml_tag_name, $postaction_attributes);
        
        if ($field_id) {
            return new $post_action_class($transition, 0, $field_id, $value);
        }
    }
    
    /**
     * Return the PostAction short name, given an XML tag name.
     * 
     * @param string $xml_tag_name
     * 
     * @return string
     */
    private function getShortNameFromXmlTagName($xml_tag_name) {
        return str_replace('postaction_', '', $xml_tag_name);
    }
    
    /**
     * Return the PostAction class, given an XML tag name.
     * 
     * @param string $xml_tag_name
     * 
     * @return string
     * 
     * @throws Transition_PostAction_NotFoundException
     */
    private function getPostActionClassFromXmlTagName($xml_tag_name) {
        $short_name = $this->getShortNameFromXmlTagName($xml_tag_name);
        
        if (! key_exists($short_name, $this->post_actions_classes_field)) {
            throw new Transition_PostAction_NotFoundException($short_name);
        }
        
        return $this->post_actions_classes_field[$short_name];
    }
    
    /**
     * Extract the PostAction value from the attributes,
     * deducing the PostAction type from the XML tag name.
     * 
     * @param string $xml_tag_name
     * @param array $postaction_attributes
     * 
     * @return mixed
     * 
     * @throws Transition_PostAction_NotFoundException 
     */
    private function getPostActionValueFromXmlTagName($xml_tag_name, $postaction_attributes) {
        switch($xml_tag_name) {
            case 'postaction_field_date':  return (int) $postaction_attributes['valuetype'];
            case 'postaction_field_int':   return (int) $postaction_attributes['value'];
            case 'postaction_field_float': return (float) $postaction_attributes['value'];
            default: throw new Transition_PostAction_NotFoundException($xml_tag_name);
        }
    }
    
   /**   
    * Reconstitute a PostAction from database
    * 
    * @param Transition $transition The transition to which this PostAction is associated
    * @param mixed      $row        The raw data (array-like)
    * @param string     $shortname  The PostAction short name
    * @param string     $klass      The PostAction class name
    * 
    * @return Transition_PostAction
    */
    private function buildPostAction(Transition $transition, $row, $shortname, $klass) {
        $id    = (int)$row['id'];
        $field = $this->getFieldFromRow($row);
        $value = $this->getValueFromRow($row, $shortname);

        return new $klass($transition, $id, $field, $value);
    }
        
    /**
     * Returns the corresponding DAO given a post action short name.
     *
     * @param string $post_action_short_name
     * @return Transition_PostAction_FieldDao
     * @throws Transition_PostAction_NotFoundException
     */
    public function getDao($post_action_short_name) {
        switch ($post_action_short_name) {
            case Transition_PostAction_Field_Date::SHORT_NAME:  
                return new Transition_PostAction_Field_DateDao();
            case Transition_PostAction_Field_Int::SHORT_NAME:   
                return new Transition_PostAction_Field_IntDao();
            case Transition_PostAction_Field_Float::SHORT_NAME: 
                return new Transition_PostAction_Field_FloatDao();
            default:
                throw new Transition_PostAction_NotFoundException();
        }
    }
    
    
    
    /**
     * Retrieves the field from the given PostAction database row.
     * 
     * @param array $row
     * 
     * @return Tracker_FormElement_Field
     */
    private function getFieldFromRow($row) {
        return $this->getFormElementFactory()->getFormElementById((int)$row['field_id']);
    }
    
        
    /**
     * Wrapper for Tracker_FormElementFactory
     *
     * @return Tracker_FormElementFactory
     */
    public function getFormElementFactory() {
        return Tracker_FormElementFactory::instance();
    }
    
   /**
    * Retrieves the value (or value type) from the given PostAction database row.
    * 
    * @param array $row
    * @param string $shortname
    * 
    * @return mixed
    * 
    * @throws Transition_PostAction_NotFoundException 
    */
    private function getValueFromRow($row, $shortname) {
        switch ($shortname) {
            case Transition_PostAction_Field_Date::SHORT_NAME:
                return (int) $row['value_type'];
            case Transition_PostAction_Field_Int::SHORT_NAME:
                return (int) $row['value'];
            case Transition_PostAction_Field_Float::SHORT_NAME:
                return (float) $row['value'];            
            default:
                throw new Transition_PostAction_NotFoundException($shortname);
        }
    }
   
   /**
    * XXX: PostAction value / value type should be an object representing
    * the PostAction configuration, allowing DAOs to share the same API.
    */
    private function getValue(Transition_PostAction $post_action) {
        $short_name = $post_action->getShortName();
        
        switch($short_name) {
            case Transition_PostAction_Field_Date::SHORT_NAME:
                return $post_action->getValueType();
            case Transition_PostAction_Field_Int::SHORT_NAME:
            case Transition_PostAction_Field_Float::SHORT_NAME:
                return $post_action->getValue();
            default:
                throw new Transition_PostAction_NotFoundException($short_name);
        }
    }
}

?>
