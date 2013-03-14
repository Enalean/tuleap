<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
   /**
     * Get available values of this field for SOAP usage
     *
     * @return mixed The values or null if there are no specific available values
     */
    public function getSoapAvailableValues() {
        $soap_values = array();
        $tracker = $this->field->getTracker();
        foreach($this->value_function as $function) {
            if ($function == 'ugroup_2') {
                
                /*switch ($function) {
                   case 'group_members':
                        $ugroup_id = $GLOBALS['UGROUP_PROJECT_MEMBERS'];
                        $ugroup_res = ugroup_db_get_ugroup($id);
                        $ugroup_name = util_translate_name_ugroup(db_result($ugroup_res, 0, 'name'));
                        $ugroup_values = ugroup_db_get_dynamic_members($GLOBALS['UGROUP_PROJECT_MEMBERS'], $tracker->id, $tracker->group_id, true);
                        break;
                    case 'group_admins':
                        $ugroup_id = $GLOBALS['UGROUP_PROJECT_ADMIN'];
                        $ugroup_res = ugroup_db_get_ugroup($id);
                        $ugroup_name = util_translate_name_ugroup(db_result($ugroup_res, 0, 'name'));
                        break;
                }*/
            $soap_values[] = array(
                        'bind_value_id'    => $ugroup_id,
                        'bind_value_label' => $function,
                    );
            }
        }
        
        return $soap_values;
    }
    
    /**
     * Get available values of this field for SOAP usage
     * Fields like int, float, date, string don't have available values
     *
     * @return mixed The values or null if there are no specific available values
     */
    /*public function getSoapAvailableValues() {
        $soap_values = array();
        
        foreach($this->getAllValues() as $value) {
            $soap_values[] = $this->getSoapBindValue($value);
        }
        return $soap_values;
    }*/

    private function getSoapBindValue($value) {
        return array(
            'bind_value_id'    => $value->getId(),
            'bind_value_label' => $value->getSoapValue()
        );
    }
?>
