<?php
/**
 * Copyright (c) Enalean, 2015 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

class Tracker_FormElement_Field_Selectbox extends Tracker_FormElement_Field_List implements Tracker_FormElement_IComputeValues
{

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','selectbox');
    }

    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','selectbox_description');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-combo-box.png');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/ui-combo-box--plus.png');
    }

    /**
     * Add some additionnal information beside the field in the artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchArtifactAdditionnalInfo($value, $submitted_values = array()) {
        $html = parent::fetchArtifactAdditionnalInfo($value, $submitted_values);
        $values = array();
        if (isset($submitted_values[0][$this->id])) {
            if (!is_array($submitted_values[0][$this->id])) {
                $submitted_values_array[] = $submitted_values[0][$this->id];
                $values = $submitted_values_array;
            }else {
                $values = $submitted_values[0][$this->id];
            }
        } else {
            if (!empty($value)) {
                foreach ($value->getListValues() as $id => $v) {
                    $values[] = $id;
                }
            }
        }

        if ($this->isJavascriptIncludedInValue($submitted_values)) {
            $html .= $this->displayArtifactJavascript($values);
        }

        return $html;
    }

    private function isJavascriptIncludedInValue($submitted_values) {
        return ! is_array($submitted_values)
            || ! isset($submitted_values['render_with_javascript'])
            || $submitted_values['render_with_javascript'] === true;
    }

     /**
     * Add some additionnal information beside the field in the submit new artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchSubmitAdditionnalInfo($submitted_values) {
        $html = parent::fetchSubmitAdditionnalInfo($submitted_values);
        if ($this->isJavascriptIncludedInValue($submitted_values)) {
            $html .= $this->displaySubmitJavascript();
        }
        return $html;
    }

    protected function displayArtifactJavascript($changeset_values) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '<script type="text/javascript">';
        $html .= "codendi.tracker.fields.add('".(int)$this->getID()."', '".$this->getName()."', '". $hp->purify($this->getLabel(), CODENDI_PURIFIER_JS_QUOTE) ."')";
        $default_value = $this->getDefaultValue();
        $values = $this->getBind()->getAllValues();

        $html .= "\n\t.addOption('None'.escapeHTML(), '100', ". (empty($changeset_values)?'true':'false') .")";

        foreach ($values as $id => $value) {
            $html .= "\n\t.addOption('". $hp->purify($value->getLabel(), CODENDI_PURIFIER_JS_QUOTE) ."'.escapeHTML(), '". (int)$id ."', ". (in_array($id, array_values($changeset_values))?'true':'false') .")";
        }
        $html .= ";\n";
        $html .= '</script>';
        return $html;
    }

    protected function displaySubmitJavascript() {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '<script type="text/javascript">';
        $html .= "codendi.tracker.fields.add('".(int)$this->getID()."', '".$this->getName()."', '". $hp->purify($this->getLabel(), CODENDI_PURIFIER_JS_QUOTE) ."')";
        $default_value = $this->getDefaultValue();
        $values = $this->getBind()->getAllValues();
        $html .= "\n\t.addOption('None'.escapeHTML(), '100', ". ($default_value==100?'true':'false') .")";

        foreach ($values as $id => $value) {
            $html .= "\n\t.addOption('". $hp->purify($value->getLabel(), CODENDI_PURIFIER_JS_QUOTE) ."'.escapeHTML(), '". (int)$id ."', ". ($id==$default_value?'true':'false') .")";
        }
        $html .= ";\n";
        $html .= '</script>';
        return $html;
    }

    /**
     * Change the type of the select box
     *
     * @param string $type the new type
     *
     * @return boolean true if the change is allowed and successful
     */
    public function changeType($type) {
        if (in_array($type, array('msb', 'cb'))) {
            //do not change from SB to MSB if the field is used to define the workflow
            $wf = WorkflowFactory::instance();
            return !$wf->isWorkflowField($this);
        } elseif ($type === 'rb') {
            return true;
        }
        return false;
    }

    function displayCheckbox($field_value_from, $field_value_to, $transitions, $box_value)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $check = '';

        if(is_array($transitions) && count($transitions)>0) {
            foreach($transitions as $transition) {
                if($field_value_from===$transition->getFieldValueFrom()&&$field_value_to===$transition->getFieldValueTo()) {
                    $check = 'checked="checked"';
                    break;
                }
             }
          }
          echo '<td class="matrix_cell" style="white-space:nowrap; text-align:center;"><label class="pc_checkbox"><input type="checkbox" name="'.$purifier->purify($box_value).'" '. $check .' />&nbsp;</label>';
          if ($check) {
              echo ' <a href="'.$purifier->purify(TRACKER_BASE_URL.'/?'. http_build_query(array(
                                                        'tracker' => (int)$this->tracker_id,
                                                        'func'    => Workflow::FUNC_ADMIN_TRANSITIONS,
                                                        'edit_transition'  => $transition->getTransitionId()))) .'">[Details]</a>';
          }
          echo '</td>';
    }

    function displayTransitionsMatrix($transitions=null)
    {
        $purifier = Codendi_HTMLPurifier::instance();
       $field=Tracker_FormElementFactory::instance()->getFormElementById($this->id);
       $field_values = array();
       foreach ($field->getBind()->getAllValues() as $id => $v) {
           if (!$v->isHidden()) {
               $field_values[$id] = $v;
           }
       }

       $nb_field_values =count($field_values);
        echo '<table id="tracker_workflow_matrix">';
            echo "<tr class='boxtitle'>\n";
            echo "<td>FROM</td>";
           for ($k=0; $k<$nb_field_values; $k++) {
               echo "<td>TO STATE</td>";
           }
           echo "</tr>";

           echo "<tr class=\"".util_get_alt_row_color(1)."\">\n";
           echo "<td></td>";
           foreach($field_values as $field_value_id=>$field_value) {
               echo '<td class="matrix_cell">'.$purifier->purify($field_value->getLabel())."</td>";
           }
           echo "</tr>";

           $j=0;
           //Display the line corresponding to the initial state
           echo "<tr class=\"".util_get_alt_row_color($j)."\">\n";
           echo "<td>(New Artifact)</td>";
           $field_value_from=null;
           foreach($field_values as $field_value_id_to=>$field_value_to) {
               $field_value_from=null;
               $box_value = '_'.$field_value_id_to;
               $this->displayCheckbox($field_value_from, $field_value_to, $transitions, $box_value);
           }
           echo "</tr>";
           $j++;

           //Display the available transitions
           foreach($field_values as $field_value_id_from=>$field_value_from) {
               echo "<tr class=\"".util_get_alt_row_color($j)."\">\n";
               echo "<td>".$purifier->purify($field_value_from->getLabel())."</td>";
               foreach($field_values as $field_value_id_to=>$field_value_to) {
                   $box_value = $field_value_id_from.'_'.$field_value_id_to;
                   if ($field_value_id_from!=$field_value_id_to) {
                       $this->displayCheckbox($field_value_from, $field_value_to, $transitions, $box_value);
                   }else {
                       echo '<td align="center" class="matrix_cell"><input type="hidden">-&nbsp;</td>';
                   }
               }
               echo "</tr>\n";
               $j++;
           }

            echo '</table>';
    }

    /**
     * @return boolean true if the value corresponds to none
     */
    public function isNone($value) {
        return $value === null ||
               $value === '' ||
               $value === '100' ||
               $value === array() ||
               (is_array($value) && $this->arrayContainsNone($value));
    }

    protected function arrayContainsNone(array $value) {
        return count($value) === 1 && array_pop($value) == '100';
    }

    public function getComputedValue(
        PFUser $user,
        Tracker_Artifact $artifact,
        $timestamp = null
    ) {
        if ($this->userCanRead($user)) {
            return $this->getCurrentValue($artifact);
        }
        return null;
    }

    public function getCachedValue(PFUser $user, Tracker_Artifact $artifact, $timestamp = null) {
        return $this->getComputedValue($user, $artifact, $timestamp);
    }

    private function getCurrentValue(Tracker_Artifact $artifact) {
        $changeset_value = $artifact->getValue($this);

        if ($changeset_value) {
            $values = $this->getBind()->getNumericValues($changeset_value);
            if (! empty($values)) {
                return $values[0];
            }
        }
        return null;
    }

    public function getFieldDataFromRESTValue(array $value, Tracker_Artifact $artifact = null) {
        if (array_key_exists('bind_value_ids', $value) && is_array($value['bind_value_ids'])) {
            if (count($value['bind_value_ids']) > 1) {
                throw new Tracker_FormElement_InvalidFieldValueException('Selectbox fields can only have one value');
            }

            $map = array_map(array($this->getBind(), 'getFieldDataFromRESTValue'), $value['bind_value_ids']);
            return (int) array_shift($map);
        }
        throw new Tracker_FormElement_InvalidFieldValueException('List fields values must be passed as an array of ids (integer) in \'bind_value_ids\''
           .' Expected format for field '.$this->id .' : {"field_id": 1548, "bind_value_ids": [457]}');
    }

    /**
     * Process the SOAP request
     *
     * @param SOAPRequest $request The data coming from the user
     *
     * @return void
     */
    public function processSoap($request){
        if ($request->exist('bind')) {
            $this->getBind()->process($request->get('bind'), $no_redirect = true);
        }
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor) {
        return $visitor->visitSelectbox($this);
    }

    public function getDefaultValue() {
        $default_array = $this->getBind()->getDefaultValues();

        if ($default_array && is_array($default_array) && count($default_array) === 1) {
            $keys = array_keys($default_array);
            return array_shift($keys);
        }

        return Tracker_FormElement_Field_List_Bind::NONE_VALUE;
    }

}
