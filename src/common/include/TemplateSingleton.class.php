<?php
//
// Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
//
// 
//


/**
 *  TemplateSingleton object for Project Templates
 */
class TemplateSingleton {
  
  // simply containing the 
  var $data_array = array();

  var $PROJECT = 1;
  var $TEMPLATE = 2;
  var $TEST_PROJECT = 3;
  
  function TemplateSingleton() {
    $this->update();
  }
  
  function &instance() {
    static $template_instance;
    if (isset($GLOBALS['Language'])) {
      $GLOBALS['Language']->loadLanguageMsg('include/include');
    }
    if (!$template_instance) {
      $template_instance = new TemplateSingleton();
    }
    return $template_instance;
  }
  
  function getLabel($proj_type) {
    return $GLOBALS['Language']->getText('include_common_template',$this->data_array[$proj_type]);
  }

  function update() {
    $db_res=db_query("SELECT * FROM group_type");
    $this->data_array=array();
    $rows=db_numrows($db_res);
    for ($i=0; $i<$rows; $i++) {
      $this->data_array[db_result($db_res,$i,'type_id')] = db_result($db_res,$i,'name');
    }
  }

  function isTemplate($id) {
    return ($id == $this->TEMPLATE);
  }

  function isProject($id) {
    return ($id == $this->PROJECT);
  }

  function isTestProject($id) {
    return ($id == $this->TEST_PROJECT);
  }

  function showTypeBox($name='group_type',$checked_val='xzxz') {
    $localizedTypes = array();
    foreach (array_keys($this->data_array) as $type_id) {
      $localizedTypes[] = $this->getLabel($type_id);
    }
    return html_build_select_box_from_arrays (array_keys($this->data_array),$localizedTypes,$name,$checked_val,false);
  }

  function getTemplates() {
    $db_templates = db_query("SELECT group_id,group_name,unix_group_name,short_description,register_time FROM groups WHERE type='2' and status IN ('A','s')");
    return $db_templates;
  }
}


?>