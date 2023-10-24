<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All rights reserved
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Originally written by Nicolas Terray, 2006
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

/**
* Html view of the manager.
*
* Provide user interface to manage rules.
*/
class ArtifactRulesManagerHtml extends ArtifactRulesManager // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public $artifact_type;
    public $href;

    /**
     *  ArtifactRulesManagerHtml() - constructor
     *
     *  @param $artifact_type object
     */
    public function __construct(&$artifact_type_html, $href = '')
    {
        parent::__construct();
        $this->artifact_type = $artifact_type_html;
        $this->href          = $href;
    }

    public function saveRule($source, $source_value, $target, $target_values)
    {
        parent::saveRule($this->artifact_type->getId(), $source, $source_value, $target, $target_values);
    }

    public function displayFieldsAndValuesAsJavascript()
    {
        $hp = Codendi_HTMLPurifier::instance();
        echo "\n//------------------------------------------------------\n";
        $art_field_fact = new ArtifactFieldFactory($this->artifact_type);
        $used_fields    = $art_field_fact->getAllUsedFields();
        foreach ($used_fields as $field) {
            if ($field->getName() != 'submitted_by') {
                if ($field->isMultiSelectBox() || $field->isSelectBox()) {
                    $values = $field->getFieldPredefinedValues($this->artifact_type->getID());
                    if (db_numrows($values) >= 1) {
                        echo "codendi.trackerv3.fields.add('" . (int) $field->getID() . "', '" . $field->getName() . "', '" . $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_JS_QUOTE) . "')";
                        $default_value = $field->getDefaultValue();
                        while ($row = db_fetch_array($values)) {
                            echo "\n\t.addOption('" .  $hp->purify(SimpleSanitizer::unsanitize($row[1]), CODENDI_PURIFIER_JS_QUOTE)  . "'.escapeHTML(), '" . (int) $row[0] . "', " . ($row[0] == $default_value ? 'true' : 'false') . ")";
                        }
                        echo ";\n";
                    }
                }
            }
        }
        echo "\n//------------------------------------------------------\n";
    }

    public function displayRulesAsJavascript()
    {
        echo "\n//------------------------------------------------------\n";
        $rules = $this->getAllRulesByArtifactTypeWithOrder($this->artifact_type->getId());
        if ($rules && count($rules) > 0) {
            foreach ($rules as $key => $nop) {
                $html = new ArtifactRuleValueJavascript($rules[$key]);
                echo 'codendi.trackerv3.rules_definitions.push(';
                $html->display();
                echo ");\n";
            }
        }
        echo "\n//------------------------------------------------------\n";
    }

    public function getAllSourceFields($target_id)
    {
        $sources        = [];
        $art_field_fact = new ArtifactFieldFactory($this->artifact_type);
        $used_fields    = $art_field_fact->getAllUsedFields();
        foreach ($used_fields as $field) {
            if ($field->getName() != 'submitted_by') {
                if ($field->isMultiSelectBox() || $field->isSelectBox()) {
                    if (! $target_id || ! $this->fieldIsAForbiddenSource($this->artifact_type->getId(), $field->getId(), $target_id)) {
                        $sources[$field->getId()] = $field;
                    }
                }
            }
        }
        return $sources;
    }

    public function getAllTargetFields($source_id)
    {
        $targets        = [];
        $art_field_fact = new ArtifactFieldFactory($this->artifact_type);
        $used_fields    = $art_field_fact->getAllUsedFields();
        foreach ($used_fields as $field) {
            if ($field->getName() != 'submitted_by') {
                if ($field->isMultiSelectBox() || $field->isSelectBox()) {
                    if (! $source_id || ! $this->fieldIsAForbiddenTarget($this->artifact_type->getId(), $field->getId(), $source_id)) {
                        $targets[$field->getId()] = $field;
                    }
                }
            }
        }
        return $targets;
    }

    public function displayEditForm($source_field = false, $target_field = false, $source_value = false, $target_value = false)
    {
        $hp = Codendi_HTMLPurifier::instance();
        echo '<noscript class="error">' . $GLOBALS['Language']->getText('tracker_field_dependencies', 'noscript') . '</noscript>';
        echo '<form action="' . $this->href . '" method="post" id="edit_rule_form"><div id="edit_rule">';
        echo '<table border=0><thead><tr><td>';
        echo $GLOBALS['Language']->getText('tracker_field_dependencies', 'source');

        $onchange = '$(\'source_field_hidden\').value = $(\'source_field\').value;' .
                    '$(\'target_field_hidden\').value = $(\'target_field\').value;' .
                    'Form.Element.disable(\'source_field\');' .
                    'Form.Element.disable(\'target_field\');' .
                    'this.up(\'table\').down(\'tbody\').update(\'<tr><td align=\\\'center\\\' colspan=\\\'2\\\'>' .
                    addslashes(str_replace('"', "'", $GLOBALS['HTML']->getImage('ic/spinner.gif'))) .
                    '</td></tr>\');' .
                    'this.form.submit();';

        echo PHP_EOL . '<select id="source_field" name="source_field" onchange="' . $onchange . '">' . PHP_EOL;
        echo '<option value="-1">' . $GLOBALS['Language']->getText('tracker_field_dependencies', 'choose_field') . '</option>';
        $sources = $this->getAllSourceFields($target_field);
        foreach ($sources as $id => $field) {
            $highlight = $this->fieldHasTarget($this->artifact_type->getId(), $field->getId()) ? ' class="boxhighlight" ' : ' ';
            $selected  = $field->getId() == $source_field ? ' selected="selected" ' : ' ';
            echo '<option value="' . $id . '" ' . $highlight . $selected . '>';
            echo $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML);
            echo '</option>';
        }
        echo '</select>';
        echo '</td><td>';
        echo $GLOBALS['Language']->getText('tracker_field_dependencies', 'target');
        echo '<select id="target_field" name="target_field" onchange="' . $onchange . '">';
        echo '<option value="-1">' . $GLOBALS['Language']->getText('tracker_field_dependencies', 'choose_field') . '</option>';
        $targets = $this->getAllTargetFields($source_field);
        foreach ($targets as $id => $field) {
            $highlight = $this->fieldHasSource($this->artifact_type->getId(), $field->getId()) ? ' class="boxhighlight" ' : ' ';
            $selected  = $field->getId() == $target_field ? ' selected="selected" ' : ' ';
            echo '<option value="' . $id . '" ' . $highlight . $selected . '>';
            echo $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML);
            echo '</option>';
        }
        echo '</select>';
        //Preload spinner
        echo $GLOBALS['HTML']->getImage('ic/spinner.gif', ['style' => 'display:none']);
        echo '</td></tr></thead>';
        echo '<tbody><tr style="vertical-align:top;" class="boxitemalt"><td>';
        if ($source_field && $target_field && isset($sources[$source_field]) && isset($targets[$target_field])) {
            //Source values
            echo '<table width="100%" cellpadding="0" cellspacing="0">';
            $values = $sources[$source_field]->getFieldPredefinedValues($this->artifact_type->getID());
            if (db_numrows($values) >= 1) {
                while ($row = db_fetch_array($values)) {
                    echo '<tr id="source_' . $source_field . '_' . $target_field . '_' . (int) $row[0] . '">';
                    echo '<td style="width: 1%;">';
                    echo '<input type="checkbox"
                                 id="source_' . $source_field . '_' . $target_field . '_' . (int) $row[0] . '_chk"
                                 name="source_' . $source_field . '_' . $target_field . '_' . (int) $row[0] . '_chk"
                                 style="visibility: hidden;"
                                 onclick="admin_checked(this.id)" />';
                    echo '</td><td style="cursor: pointer;" onclick="return admin_selectSourceEvent(this)"><span> </span><label style="cursor: pointer;">';
                    $v = $hp->purify(SimpleSanitizer::unsanitize($row[1]), CODENDI_PURIFIER_CONVERT_HTML);
                    if ($this->valueHasTarget($this->artifact_type->getId(), $source_field, $row[0], $target_field)) {
                        echo '<strong>' . $v . '</strong>';
                    } else {
                        echo $v;
                    }
                    echo ' </label></td>';
                    echo '<td style="text-align: right;"><div id="source_' . $source_field . '_' . $target_field . '_' . (int) $row[0] . '_arrow" style="visibility: hidden;">&rarr;</div></td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
            echo '</td><td>';
            //Target values
            echo '<table width="100%" cellpadding="0" cellspacing="0">';
            $values = $targets[$target_field]->getFieldPredefinedValues($this->artifact_type->getID());
            if (db_numrows($values) >= 1) {
                while ($row = db_fetch_array($values)) {
                    echo '<tr id="target_' . $source_field . '_' . $target_field . '_' . (int) $row[0] . '">';
                    echo '<td style="text-align: right; width: 1%"><div id="target_' . $source_field . '_' . $target_field . '_' . (int) $row[0] . '_arrow" style="visibility: hidden;">&rarr;</div></td>';
                    echo '<td style="width: 1%;">';
                    echo '<input type="checkbox"
                                 id="target_' . $source_field . '_' . $target_field . '_' . (int) $row[0] . '_chk"
                                 name="target_' . $source_field . '_' . $target_field . '_' . (int) $row[0] . '_chk"
                                 style="visibility: hidden;"
                                 onclick="admin_checked(this.id)" />';
                    echo '</td><td style="cursor: pointer;" onclick="return admin_selectTargetEvent(this)"><span> </span><label style="cursor: pointer;">';
                    $v = $hp->purify(SimpleSanitizer::unsanitize($row[1]), CODENDI_PURIFIER_CONVERT_HTML);
                    if ($this->valueHasSource($this->artifact_type->getId(), $target_field, $row[0], $source_field)) {
                        echo '<strong>' . $v . '</strong>';
                    } else {
                        echo $v;
                    }
                    echo ' </label></td>';
                    echo '</tr>';
                }
            }
            echo '</table>';
        } else {
            echo '</td><td>';
        }
        echo '</td></tr>';
        echo '<tr id="save_panel">';
        echo '<td colspan="2">';
        echo '<input type="submit" class="btn btn-primary" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '" id="save_btn"/> ';
        echo '<button class="btn" id="reset_btn">' . $GLOBALS['Language']->getText('global', 'btn_reset') . '</button>';
        echo '</td>';
        echo '</tr>';
        echo '</tbody>';
        echo '</table>';
        echo '</div>';

        echo '<input type="hidden" id="save"  name="save" value="no" />';
        echo '<input type="hidden" id="source_field_hidden" name="source_field" value="" />';
        echo '<input type="hidden" id="target_field_hidden" name="target_field" value="" />';
        echo '<input type="hidden" id="value" name="value" value="" />';
        echo '<input type="hidden" id="direction_type" name="direction_type" value="" />';
        echo '</form>';
        echo '<script type="text/javascript">' . "\n";
        echo "//<![CDATA[\n";

        $this->displayFieldsAndValuesAsJavascript();

        $this->displayRulesAsJavascript();

        echo "var messages = {\n";
            echo "btn_save_rule:      '" . addslashes($GLOBALS['Language']->getText('global', 'btn_submit')) . "',\n";
            echo "btn_reset:          '" . addslashes($GLOBALS['Language']->getText('global', 'btn_reset')) . "'\n";
        echo "};\n";
        echo "document.observe('dom:loaded', buildAdminUI);";
        echo "\n//------------------------------------------------------\n";
        echo "\n" . '//]]></script>';
    }

    public function displayRules($source_field = false, $target_field = false, $source_value = false, $target_value = false)
    {
        $this->_header();
        echo '<div>' . $GLOBALS['Language']->getText('tracker_field_dependencies', 'inline_help') . '</div>';
        echo '<br />';
        $this->displayEditForm($source_field, $target_field, $source_value, $target_value);
        echo '<br />';
        $this->_footer();
    }

    public function saveFromRequest(&$request)
    {
        //TODO: Valid the request
        switch ($request->get('direction_type')) {
            case 'source': // 1 source -> n targets
                $this->deleteRuleValueBySource($this->artifact_type->getId(), $request->get('source_field'), $request->get('value'), $request->get('target_field'));
                //get target values
                $art_field_fact = new ArtifactFieldFactory($this->artifact_type);
                $target_field   = $art_field_fact->getFieldFromId($request->get('target_field'));
                $target_values  = $target_field->getFieldPredefinedValues($this->artifact_type->getID());
                while ($row = db_fetch_array($target_values)) {
                    if ($request->exist('target_' . $request->get('source_field') . '_' . $request->get('target_field') . '_' . $row[0] . '_chk')) {
                        $this->saveRuleValue($this->artifact_type->getId(), $request->get('source_field'), $request->get('value'), $request->get('target_field'), $row[0]);
                    }
                }
                $GLOBALS['Response']->addFeedback('info', '<span class="feedback_field_dependencies">' . $GLOBALS['Language']->getText('tracker_field_dependencies', 'saved') . '</span>', CODENDI_PURIFIER_DISABLED);
                $this->displayRules($request->get('source_field'), $request->get('target_field'), $request->get('value'), false);
                break;
            case 'target': // n sources -> 1 target
                $this->deleteRuleValueByTarget($this->artifact_type->getId(), $request->get('source_field'), $request->get('target_field'), $request->get('value'));
                //get source values
                $art_field_fact = new ArtifactFieldFactory($this->artifact_type);
                $source_field   = $art_field_fact->getFieldFromId($request->get('source_field'));
                $source_values  = $source_field->getFieldPredefinedValues($this->artifact_type->getID());
                while ($row = db_fetch_array($source_values)) {
                    if ($request->exist('source_' . $request->get('source_field') . '_' . $request->get('target_field') . '_' . $row[0] . '_chk')) {
                        $this->saveRuleValue($this->artifact_type->getId(), $request->get('source_field'), $row[0], $request->get('target_field'), $request->get('value'));
                    }
                }
                $GLOBALS['Response']->addFeedback('info', '<span class="feedback_field_dependencies">' . $GLOBALS['Language']->getText('tracker_field_dependencies', 'saved') . '</span>', CODENDI_PURIFIER_DISABLED);
                $this->displayRules($request->get('source_field'), $request->get('target_field'), false, $request->get('value'));
                break;
            default:
                $this->badRequest();
                break;
        }
    }

    public function badRequest()
    {
        header("HTTP/1.1 400 Bad Request");
        $GLOBALS['Response']->addFeedback('info', 'Bad Request');
        $this->_header();
        echo "The server is unable to process your request.";
        $this->_footer();
        exit();
    }

    public function _header() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $params          = [];
        $params['title'] = $this->artifact_type->getName() . ' ' . $GLOBALS['Language']->getText('tracker_include_type', 'mng_field_dependencies');
        $this->artifact_type->adminHeader($params);
        $this->artifact_type->displayAdminTitle($GLOBALS['Language']->getText('tracker_include_type', 'mng_field_dependencies_title'));
    }

    public function _footer() // phpcs:ignore PSR2.Methods.MethodDeclaration.Underscore
    {
        $this->artifact_type->footer([]);
    }
}
