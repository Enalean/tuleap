<?php
/**
 * Copyright (c) Xerox Corporation, CodeX Team, 2001-2005. All rights reserved
 *
 * Originally written by Nicolas Terray, 2006
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 */

require_once('common/tracker/ArtifactRulesManager.class.php');
require_once('ArtifactTypeHtml.class.php');
require_once('ArtifactRuleValueJavascript.class.php');
$Language->loadLanguageMsg('tracker/tracker');

/**
* Html view of the manager.
*
* Provide user interface to manage rules.
*/
class ArtifactRulesManagerHtml extends ArtifactRulesManager {

    var $artifact_type;
    var $href;
    
	/**
	 *  ArtifactRulesManagerHtml() - constructor
	 *
	 *  @param $artifact_type object
	 */
	function ArtifactRulesManagerHtml(&$artifact_type_html, $href = '') {
		$this->ArtifactRulesManager();
        $this->artifact_type =& $artifact_type_html;
        $this->href          = $href;
	}
    
    function saveRule($source, $source_value, $target, $target_values) {
        parent::saveRule($this->artifact_type->getId(), $source, $source_value, $target, $target_values);
    }
    
    function displayFieldsAndValuesAsJavascript() {
        echo "\n//------------------------------------------------------\n";
        $art_field_fact =& new ArtifactFieldFactory($this->artifact_type);
        $used_fields = $art_field_fact->getAllUsedFields();
        foreach($used_fields as $field) {
            if ($field->getName() != 'submitted_by') {
                if ($field->isMultiSelectBox() || $field->isSelectBox()) {
                    $values = $field->getFieldPredefinedValues($this->artifact_type->getID());
                    if (db_numrows($values) > 1) {
                        echo "fields['".$field->getID()."'] = new com.xerox.codex.tracker.Field('".$field->getID()."', '".$field->getName()."', '".addslashes($field->getLabel())."');\n";
                        $default_value = $field->getDefaultValue();
                        echo "options['".$field->getID()."'] = {};\n";
                        while ($row = db_fetch_array($values)) {
                            echo "options['". $field->getID() ."']['". $row[0] ."'] = {option:new Option('". addslashes($row[1]) ."', '". $row[0] ."'), selected:". ($row[0]==$default_value?'true':'false') ."};\n";
                        }
                    }
                }
            }
        }
        echo "\n//------------------------------------------------------\n";
    }
    
    function displayRulesAsJavascript() {
        echo "\n//------------------------------------------------------\n";
        $rules = $this->getAllRulesByArtifactTypeWithOrder($this->artifact_type->getId());
        if ($rules && count($rules) > 0) {
            foreach ($rules as $key => $nop) {
                $html =& new ArtifactRuleValueJavascript($rules[$key]);
                echo 'rules_definitions['. $rules[$key]->id .'] = ';
                $html->display();
                echo ";\n";
            }
        }
        echo "\n//------------------------------------------------------\n";
    }
    
    function displayEditForm($params = array()) {
        echo '<noscript class="error">'. $GLOBALS['Language']->getText('tracker_field_dependencies','noscript') .'</noscript>';
        echo '<form action="'. $this->href .'" method="post" id="edit_rule_form"><div id="edit_rule"></div>';
        echo '<input type="hidden" id="save"  name="save" value="no" />';
        echo '<input type="hidden" id="source_field_hidden" name="source_field" value="" />';
        echo '<input type="hidden" id="target_field_hidden" name="target_field" value="" />';
        echo '<input type="hidden" id="value" name="value" value="" />';
        echo '<input type="hidden" id="direction_type" name="direction_type" value="" />';
        echo '</form>';
        echo '<script type="text/javascript">'."\n";
        echo "//<![CDATA[\n";
        
        $this->displayFieldsAndValuesAsJavascript();
        
        $this->displayRulesAsJavascript();
        
        if (isset($params['preselected_source_field'])) {
            echo 'var preselected_source_field = '. $params['preselected_source_field'] .";\n";
        } else {
            echo "var preselected_source_field = '-1';\n";
        }
        if (isset($params['preselected_target_field'])) {
            echo 'var preselected_target_field = '. $params['preselected_target_field'] .";\n";
        } else {
            echo "var preselected_target_field = '-1';\n";
        }
        if (isset($params['preselected_source_value'])) {
            echo 'var preselected_source_value = '. $params['preselected_source_value'] .";\n";
        } else {
            echo "var preselected_source_value = undefined;\n";
        }
        if (isset($params['preselected_target_value'])) {
            echo 'var preselected_target_value = '. $params['preselected_target_value'] .";\n";
        } else {
            echo "var preselected_target_value = undefined;\n";
        }
        echo "var messages = {\n";
            //echo "href:               '". $this->href ."',\n";
            echo "choose_field:       '". addslashes($GLOBALS['Language']->getText('tracker_field_dependencies','choose_field')) ."',\n";
            echo "source:             '". addslashes($GLOBALS['Language']->getText('tracker_field_dependencies','source')) ."',\n";
            echo "target:             '". addslashes($GLOBALS['Language']->getText('tracker_field_dependencies','target')) ."',\n";
            echo "btn_save_rule:      '". addslashes($GLOBALS['Language']->getText('global','btn_submit')) ."',\n";
            echo "btn_reset:          '". addslashes($GLOBALS['Language']->getText('global','btn_reset')) ."'\n";
        echo "};\n";
        echo "Event.observe(window, 'load', buildAdminUI, true);";
        echo "\n//------------------------------------------------------\n";
        echo "\n".'//]]></script>';
    }
    
    function displayRules($params = array()) {
        $this->_header();
        echo '<div>'. $GLOBALS['Language']->getText('tracker_field_dependencies','inline_help') .'</div>';
        echo '<br />';
        $this->displayEditForm($params);
        echo '<br />';
        $this->_footer();
    }
    
    function saveFromRequest(&$request) {
        switch ($request->get('direction_type')) {
            case 'source': // 1 source -> n targets
                $this->deleteRuleValueBySource($this->artifact_type->getId(), $request->get('source_field'), $request->get('value'), $request->get('target_field'));
                //get target values
                $art_field_fact =& new ArtifactFieldFactory($this->artifact_type);
                $target_field   =& $art_field_fact->getFieldFromId($request->get('target_field'));
                $target_values  = $target_field->getFieldPredefinedValues($this->artifact_type->getID());
                while ($row = db_fetch_array($target_values)) {
                    if ($request->exist('target_'. $request->get('source_field') .'_'. $request->get('target_field') .'_'. $row[0] .'_chk')) {
                        $this->saveRuleValue($this->artifact_type->getId(), $request->get('source_field'), $request->get('value'), $request->get('target_field'), $row[0]);
                    }
                }
                $GLOBALS['Response']->addFeedback('info',  '<span class="feedback_field_dependencies">'. $GLOBALS['Language']->getText('tracker_field_dependencies','saved') .'</span>');
                $this->displayRules(array(
                    'preselected_source_field' => $request->get('source_field'),
                    'preselected_target_field' => $request->get('target_field'),
                    'preselected_source_value' => $request->get('value'),
                ));
                break;
            case 'target': // n sources -> 1 target
                $this->deleteRuleValueByTarget($this->artifact_type->getId(), $request->get('source_field'), $request->get('target_field'), $request->get('value'));
                //get source values
                $art_field_fact =& new ArtifactFieldFactory($this->artifact_type);
                $source_field   =& $art_field_fact->getFieldFromId($request->get('source_field'));
                $source_values  = $source_field->getFieldPredefinedValues($this->artifact_type->getID());
                while ($row = db_fetch_array($source_values)) {
                    if ($request->exist('source_'. $request->get('source_field') .'_'. $request->get('target_field') .'_'. $row[0] .'_chk')) {
                        $this->saveRuleValue($this->artifact_type->getId(), $request->get('source_field'), $row[0], $request->get('target_field'), $request->get('value'));
                    }
                }
                $GLOBALS['Response']->addFeedback('info',  '<span class="feedback_field_dependencies">'. $GLOBALS['Language']->getText('tracker_field_dependencies','saved') .'</span>');
                $this->displayRules(array(
                    'preselected_source_field' => $request->get('source_field'),
                    'preselected_target_field' => $request->get('target_field'),
                    'preselected_target_value' => $request->get('value')
                ));
                break;
            default:
                $this->badRequest();
                break;
        }
    }
    
    function badRequest() {
        header("HTTP/1.1 400 Bad Request");
        $GLOBALS['Response']->addFeedback('info', 'Bad Request');
        $this->_header();
        echo "The server is unable to process your request.";
        $this->_footer();
        exit();
    }
    function _header() {
        $params = array();
        $params['title']   = $this->artifact_type->getName() .' '. $GLOBALS['Language']->getText('tracker_include_type','mng_field_dependencies');
        $params['help']    = 'TrackerAdministration.html#TrackerFieldDependenciesManagement';
		$this->artifact_type->adminHeader($params);
        $this->artifact_type->displayAdminTitle($GLOBALS['Language']->getText('tracker_include_type','mng_field_dependencies_title'));
    }
    
    function _footer() {
        $this->artifact_type->footer(array());
    }
}

?>
