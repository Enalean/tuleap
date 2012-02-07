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

require_once('Tracker_FormElement_Field_List_Bind_Static.class.php');
require_once('Tracker_FormElement_Field_List_Bind_StaticValue.class.php');
require_once('Tracker_FormElement_Field_List_Bind_Users.class.php');
require_once('Tracker_FormElement_Field_List_BindDecorator.class.php');

require_once('dao/Tracker_FormElement_Field_List_BindDecoratorDao.class.php');
require_once('dao/Tracker_FormElement_Field_List_Bind_DefaultvalueDao.class.php');
require_once('dao/Tracker_FormElement_Field_List_Bind_StaticDao.class.php');
require_once('dao/Tracker_FormElement_Field_List_Bind_Static_ValueDao.class.php');
require_once('dao/Tracker_FormElement_Field_List_Bind_UsersDao.class.php');

require_once('common/html/HTML_Element_Input_Radio.class.php');

class Tracker_FormElement_Field_List_BindFactory {
    const STATIK  = 'static';
    const USERS   = 'users';
    const COPY_BY_REFERENCE = true;
    
    /**
     * Build a binder associated to a list field.
     * @param Tracker_FormElement_Field $field
     * @param string $type ('ug', 'submit', 'Static')
     */
    public function getBind($field, $type) {
        $default_value = array();
        $dao = new Tracker_FormElement_Field_List_Bind_DefaultvalueDao();
        foreach($dao->searchByFieldId($field->id) as $row) {
            $default_value[$row['value_id']] = true;
        }
        $decorators = array();
        $dao = new Tracker_FormElement_Field_List_BindDecoratorDao();
        foreach($dao->searchByFieldId($field->id) as $row) {
            $decorators[$row['value_id']] = new Tracker_FormElement_Field_List_BindDecorator($row['field_id'],
                                                                                  $row['value_id'],
                                                                                  $row['red'],
                                                                                  $row['green'],
                                                                                  $row['blue']);
        }
        
        $bind = null;
        switch($type) {
            case self::STATIK:
                $dao = new Tracker_FormElement_Field_List_Bind_StaticDao();
                if ($row = $dao->searchByFieldId($field->id)->getRow()) {
                    $values = array();
                    $dao = new Tracker_FormElement_Field_List_Bind_Static_ValueDao();
                    foreach($dao->searchByFieldId($field->id, $row['is_rank_alpha']) as $row_value) {
                        $values[$row_value['id']] = $this->getStaticValueInstance(
                            $row_value['id'],
                            $row_value['label'], 
                            $row_value['description'], 
                            $row_value['rank'], 
                            $row_value['is_hidden']
                        );
                    }
                    $bind = new Tracker_FormElement_Field_List_Bind_Static($field, $row['is_rank_alpha'], $values, $default_value, $decorators);
                }
                break;
            case self::USERS:
                $dao = new Tracker_FormElement_Field_List_Bind_UsersDao();
                if ($row = $dao->searchByFieldId($field->id)->getRow()) {
                    $bind = new Tracker_FormElement_Field_List_Bind_Users($field, $row['value_function'], $default_value, $decorators);
                }
                break;
            default:
                break;
        }
        return $bind;
    }
    
    
    /**
     * Duplicate a field. 
     * @param int $from_field_id
     * @param int $to_field_id
     * @return array the mapping between old values and new ones
     */
    public function duplicate($from_field_id, $to_field_id) {
        
        //duplicate users info, if any
        $dao = new Tracker_FormElement_Field_List_Bind_UsersDao();
        $dao->duplicate($from_field_id, $to_field_id);
        
        //duplicate Static info, if any
        $dao = new Tracker_FormElement_Field_List_Bind_StaticDao();
        $dao->duplicate($from_field_id, $to_field_id);
        
        $value_mapping = array();
        //duplicate Static value, if any
        $dao = new Tracker_FormElement_Field_List_Bind_Static_ValueDao();
        foreach($dao->searchByFieldId($from_field_id, 0) as $row) {
            if ($id = $dao->duplicate($row['id'], $to_field_id)) {
                $value_mapping[$row['id']] = $id;
            }
        }
        
        $dao = new Tracker_FormElement_Field_List_Bind_DefaultvalueDao();
        $dao->duplicate($from_field_id, $to_field_id, $value_mapping);
        
        $dao = new Tracker_FormElement_Field_List_BindDecoratorDao();
        $dao->duplicate($from_field_id, $to_field_id, $value_mapping);
        
        return $value_mapping;
    }
    
    /**
     * @param array the row allowing the construction of a bind
     * @return Field_List_Bind Object
     */
    protected function getInstanceFromRow($row) {
        switch($row['type']) {
            case self::STATIK:
                return new Tracker_FormElement_Field_List_Bind_Static($row['field'],
                                                                      $row['is_rank_alpha'],
                                                                      $row['values'],
                                                                      $row['default_values'],
                                                                      $row['decorators']);
            case self::USERS:
                return new Tracker_FormElement_Field_List_Bind_Users($row['field'],
                                                                      $row['value_function'],
                                                                      $row['default_values'],
                                                                      $row['decorators']);
            default: return null;
        }
    }
    
    /**
     * Creates a Field_List_Bind Object
     * 
     * @param SimpleXMLElement          $xml         containing the structure of the imported bind
     * @param Tracker_FormElement_Field $field       to which the bind is attached
     * @param array                     &$xmlMapping where the newly created formElements indexed by their XML IDs are stored
     * 
     * @return Tooltip Object 
     */
    public function getInstanceFromXML($xml, $field, &$xmlMapping) {
        $row = array('type' => (string)$xml['type'],
                     'field' => $field,
                     'default_values' => null,
                     'decorators' => null);
        if (isset($xml->decorators)) {
            $row['decorators'] = array();
            foreach ($xml->decorators->decorator as $deco) {
                $ID = (string)$deco['REF'];
                $row['decorators'][$ID] = $this->getDecoratorInstance(
                       $field, $ID, (int)$deco['r'], (int)$deco['g'], (int)$deco['b']);
            }
        }
        switch((string)$xml['type']) {
            case self::STATIK:
                $row['is_rank_alpha'] = (int)$xml['is_rank_alpha'];
                $values = array();
                if ($xml->items->item) {
                    $i = 0;
                    foreach ($xml->items->item as $item) {
                        $ID = (string)$item['ID'];
                        $description = '';
                        if (isset($item->description)) {
                            $description = (string)$item->description;
                        }
                        $is_hidden = isset($item['is_hidden']) && (int)$item['is_hidden'] ? 1 : 0;
                        $values[$ID] = $this->getStaticValueInstance($ID, (string)$item['label'], $description, $i++, $is_hidden);
                        
                        $xmlMapping[$ID] = $values[$ID];
                        
                    }
                }
                $row['values'] = $values;
                
                break;
            case self::USERS:
                $values = array();
                if ($xml->items->item) {
                    foreach ($xml->items->item as $item) {
                        $values[] = (string)$item['label'];
                    }
                }
                $row['value_function'] = implode(',', $values);
                break;
            default: return null;
        }
        if (isset($xml->default_values)) {
            $row['default_values'] = array();
            foreach ($xml->default_values->value as $default_value) {
                $ID = (string)$default_value['REF'];
                if (isset($xmlMapping[$ID])) {
                    $row['default_values'][$ID] = $xmlMapping[$ID];
                }
            }
        }
        return $this->getInstanceFromRow($row);
    }
    
    /**
     * Buil an instance of static value
     *
     * @return Tracker_FormElement_Field_List_Bind_StaticValue
     */
    function getStaticValueInstance($id, $label, $description, $rank, $is_hidden) {
        return new Tracker_FormElement_Field_List_Bind_StaticValue($id, $label, $description, $rank, $is_hidden);
    }
    
    /**
     * Buil an instance of decorator
     *
     * @return Tracker_FormElement_Field_List_BindDecorator
     */
    function getDecoratorInstance($field, $id, $r, $g, $b) {
        return new Tracker_FormElement_Field_List_BindDecorator($field, $id, $r, $g, $b);
    }
    
    /**
     * @return string html
     */
    public function fetchCreateABind($field) {
        $html = '';
        $html .= '<h3>'.$GLOBALS['Language']->getText('plugin_tracker_formelement_admin','values').'</h3>';
        $html .= '<dl id="tracker-bind-factory">';
        
        $html .= '<dt class="tracker-bind-type">';
        $h = new HTML_Element_Input_Radio( $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','choose_values'), 'formElement_data[bind-type]', self::STATIK, 'checked');
        $h->addParam('autocomplete', 'off');
        $html .= $h->render();
        $html .= '</dt>';
        
        $html .= '<dd class="tracker-bind-def">';
        $html .= Tracker_FormElement_Field_List_Bind_Static::fetchAdminCreateForm($field);
        $html .= '</dd>';
        
        $html .= '<dt class="tracker-bind-type">';
        $h = new HTML_Element_Input_Radio( $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','bind_to_users'), 'formElement_data[bind-type]', self::USERS, '');
        $h->addParam('autocomplete', 'off');
        $html .= $h->render();
        $html .= '</dt>';
        
        $html .= '<dd class="tracker-bind-def">';
        $html .= Tracker_FormElement_Field_List_Bind_Users::fetchAdminCreateForm($field);
        $html .= '</dd>';
        
        $html .= '</dl>';
        return $html;
    }
    
    /**
     * Create a bind for the field
     *
     * @param Field $field     the field
     * @param string $type     the type of bind. If empty, STATIK
     * @param array $bind_data the data used to create the bind
     *
     * @return Bind null if error
     */
    public function createBind($field, $type, $bind_data) {
        $bind = null;
        switch ($type) {
            case '': //default is static
            case self::STATIK:
                $dao = new Tracker_FormElement_Field_List_Bind_StaticDao();
                if ($dao->save($field->getId(), 0)) {
                    $bind = new Tracker_FormElement_Field_List_Bind_Static($field, 0, array(), array(), array());
                    $bind->process($bind_data, 'no redirect');
                }
                break;
            case self::USERS:
                $dao = new Tracker_FormElement_Field_List_Bind_UsersDao();
                if ($dao->save($field->getId(), array())) {
                    $bind = new Tracker_FormElement_Field_List_Bind_Users($field, '', array(), array());
                    $bind->process($bind_data, 'no redirect');
                }
                break;
            default:
                break;
        }
        return $bind;
    }
    
    public function getType($bind) {
        return is_a($bind, 'Tracker_FormElement_Field_List_Bind_Static') ? self::STATIK :
                (is_a($bind, 'Tracker_FormElement_Field_List_Bind_Users') ? self::USERS : '');
    }
}
?>
