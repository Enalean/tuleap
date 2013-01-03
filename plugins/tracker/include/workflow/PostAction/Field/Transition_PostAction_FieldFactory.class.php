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
class Transition_PostAction_FieldFactory extends Transition_PostActionFactory {
    
    /**
     * Returns the corresponding DAO given a post action short name.
     *
     * @param string $post_action_short_name
     * 
     * @return Transition_PostAction_FieldDao
     */
    protected function getDao($post_action_short_name) {
        switch ($post_action_short_name) {
            case 'field_date':  return new Transition_PostAction_Field_DateDao();
            case 'field_int':   return new Transition_PostAction_Field_IntDao();
            case 'field_float': return new Transition_PostAction_Field_FloatDao();
            default:            throw new Transition_PostAction_NotFoundException();
        }
    }
    
    public function loadPostActions(Transition $transition) {
        $post_actions_classes = $this->post_actions_classes_field;
        
        foreach ($post_actions_classes as $shortname => $klass) {
            foreach($this->loadPostActionRows($transition, $shortname) as $row) {
                $post_actions[] = $this->buildPostAction($transition, $row, $shortname, $klass);
            }
        }
        return $post_actions;
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
        $id    = $this->getIdFromRow($row);
        $field = $this->getFieldFromRow($row);
        $value = $this->getValueFromRow($row, $shortname);

        return new $klass($transition, $id, $field, $value);
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
            case 'field_date':    return (int) $row['value_type'];
            case 'field_int':     return (int) $row['value'];
            case 'field_float':   return (float) $row['value'];            
            default: throw new Transition_PostAction_NotFoundException($shortname);
        }
    }
   
   /**
    * XXX: PostAction value / value type should be an object representing
    * the PostAction configuration, allowing DAOs to share the same API.
    */
    private function getValue(Transition_PostAction $post_action) {
        $short_name = $post_action->getShortName();
        
        switch($short_name) {
            case 'field_date':  return $post_action->getValueType();
            case 'field_int':
            case 'field_float': return $post_action->getValue();
            default: throw new Transition_PostAction_NotFoundException($short_name);
        }
    }
}

?>
