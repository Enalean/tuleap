<?php
/*
 * Copyright (c) Enalean, 2011. All Rights Reserved.
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

require_once('Field/Transition_PostAction_Field_Date.class.php');
require_once('Field/dao/Transition_PostAction_Field_DateDao.class.php');

/**
 * class Transition_PostActionFactory
 * 
 */
class Transition_PostActionFactory {
    
    /**
     * @var Array of available post actions classes
     */
    protected $post_actions_classes = array(
        'field_date' => 'Transition_PostAction_Field_Date',
    );
    
    /**
     * Get html code to let someone choose a post action for a transition
     *
     * @return string html
     */
    public function fetchPostActions() {
        $html = '';
        $html .= '<p>Add a new action: ';
        $html .= '<select name="add_postaction">';
        $html .= '<option value="" selected>--</option>';
        
        foreach ($this->post_actions_classes as $shortname => $klass) {
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
     * Create a new post action for the transition
     *
     * @param Transition $transition           On wich transition we should add the post action
     * @param string     $requested_postaction The type of post action
     *
     * @return void
     */
    public function addPostAction(Transition $transition, $requested_postaction) {
        if (isset($this->post_actions_classes[$requested_postaction])) {
            $this->getDao()->create($transition->getTransitionId());
        }
    }
    
    protected function getDao() {
        return new Transition_PostAction_Field_DateDao();
    }
    
    public function loadPostActions($transition) {
        $post_actions = array();
        foreach ($this->getDao()->searchByTransitionId($transition->getTransitionId()) as $row) {
            $post_actions[] = new Transition_PostAction_Field_Date($transition, (int)$row['id'], (int)$row['field_id'], (int)$row['value_type']);
        }
        $transition->setPostActions($post_actions);
    }
}
?>
