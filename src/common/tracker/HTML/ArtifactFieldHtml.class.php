<?php
/**
 * Copyright (c) Enalean, 2011 - 2018. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * Parts of code come from bug_util.php (written by Laurent Julliard)
 * Written for Codendi by Stephane Bouhet
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


class ArtifactFieldHtml extends ArtifactField
{
    /**
     * If set to false, no JS will be generated in Read Only mode.
     *
     * @var bool
     */
    protected $isJavascriptEnabled = true;

    /**
     *  Copy constructor
     *
     */
    public function __construct($art_field)
    {
        $this->field_id = $art_field->field_id;
        $this->field_name = $art_field->field_name;
        $this->data_type = $art_field->data_type;
        $this->display_type = $art_field->display_type;
        $this->display_size = $art_field->display_size;
        $this->label = $art_field->label;
        $this->description = $art_field->description;
        $this->scope = $art_field->scope;
        $this->required = $art_field->required;
        $this->empty_ok = $art_field->empty_ok;
        $this->keep_history = $art_field->keep_history;
        $this->special = $art_field->special;
        $this->value_function = $art_field->value_function;
        $this->use_it = $art_field->use_it;
        $this->place = $art_field->place;
    }

    /**
     * Allow to disable JS generation in Read Only mode.
     *
     * Usefull in HTML Mail context when we need (read) HTML but no Javascript
     */
    public function disableJavascript()
    {
        $this->isJavascriptEnabled = false;
    }

    /**
     *
     *  Returns the label display for this field (HTML code)
     *
     *  @param break: force a break line after the label
     *  @param ascii: display in ascii mode
     *
     *    @return    string
     */
    public function labelDisplay($break = false, $ascii = false, $tooltip = false)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $output = SimpleSanitizer::unsanitize($this->getLabel()) . ': ';
        if (!$ascii) {
            $output =  $hp->purify($output, CODENDI_PURIFIER_CONVERT_HTML);
            if ($tooltip) {
                $output = '<a class="artifact_field_tooltip" href="#" title="' . $hp->purify(SimpleSanitizer::unsanitize($this->description), CODENDI_PURIFIER_CONVERT_HTML) . '">' . $output . '</a>';
            }
            $output = '<B>' . $output . '</B>';
        }
        if ($break) {
            $output .= ($ascii ? "\n" : '<BR>');
        } else {
            $output .= ($ascii ? ' ' : '&nbsp;');
        }
        return $output;
    }

    /**
     *
     *  Returns a multiplt select box populated with field values for this project
     *  if box_name is given then impose this name in the select box
     *  of the  HTML form otherwise use the field_name)
     *
     *  @param box_name: the selectbox name
     *  @param group_artifact_id: the artifact type id
     *  @param checked,show_none,text_none,show_any,text_any,show_value: values used by html_build_select_box function
     *  @param display: define whether the MB will be displayed or not: In case of RO, it will not be displayed
     *    @return    string
     */
    public function multipleFieldBox($box_name, $group_artifact_id, $checked = false, $show_none = false, $text_none = 0, $show_any = false, $text_any = 0, $show_unchanged = false, $text_unchanged = 0, $show_value = false, $display = true)
    {
        global $Language;
         $hp = Codendi_HTMLPurifier::instance();
        if (!$text_none) {
            $text_none = $Language->getText('global', 'none');
        }
        if (!$text_any) {
            $text_any = $Language->getText('global', 'any');
        }
        if (!$text_unchanged) {
            $text_unchanged = $Language->getText('global', 'unchanged');
        }

        if (!$group_artifact_id) {
            return $Language->getText('tracker_include_field', 'error_no_atid');
        } else {
            $result = $this->getFieldPredefinedValues($group_artifact_id, $checked, false, true, false, true);
            $array_values = array();
            // $array_values is used to write javascript field dependencies
            // getFieldPredefinedValues doesn't always return the none value and the any value for the binded fields
            // so we add them everytime by precaution.
            if ($show_any) {
                $array_values[] = array(0, $text_any);
            }
            if ($show_none) {
                $array_values[] = array(100, $text_none);
            }
            while ($row = db_fetch_array($result)) {
                $array_values[]  = $row;
            }
            if (db_numrows($result) > 0) {
                db_reset_result($result);
            }

            if ($box_name == '') {
                $box_name = $this->field_name . '[]';
            }
            $output = '';
            if ($display) {
                $output  .= html_build_multiple_select_box($result, $box_name, $checked, ($this->getDisplaySize() != "" ? $this->getDisplaySize() : "6"), $show_none, $text_none, $show_any, $text_any, $show_unchanged, $text_unchanged, $show_value);
            }
            if ($this->isJavascriptEnabled) {
                $output .= '<script type="text/javascript">';
                $output .= "\ncodendi.trackerv3.fields.add('" . (int) $this->getID() . "', '" . $hp->purify($this->getName(), CODENDI_PURIFIER_JS_QUOTE) . "', '" . $hp->purify(SimpleSanitizer::unsanitize($this->getLabel()), CODENDI_PURIFIER_JS_QUOTE) . "')";
                $output .= $this->_getValuesAsJavascript($array_values, $checked);
                $output .= ";\n";
                $output .= "</script>";
            }
            return $output;
        }
    }

    public function _isValueDefaultValue($value, $default_value)
    {
        return (is_array($default_value) && in_array($value, $default_value)) || $value == $default_value;
    }
    /**
     * _getValuesAsJavascript
     *
     * MV: I added the latest param in order to manage mass change in the safest way.
     * I didn't know why the $row[0] value is casted as Int so in order to avoid clashes
     * the value is not casted ONLY when it match $text_unchanged. We must pass the param
     * as it may change.
     *
     * @param $values
     * @param $default_value
     * @param $text_unchanged
     */
    public function _getValuesAsJavascript($values, $default_value, $text_unchanged = false)
    {
        global $Language;
        $hp = Codendi_HTMLPurifier::instance();
            $output  = "";
            $isDefaultValuePresent = false;
        foreach ($values as $row) {
            if ($row['0'] === $text_unchanged) {
                $output .= "\n\t.addOption('" .  $hp->purify(SimpleSanitizer::unsanitize($row['1']), CODENDI_PURIFIER_JS_QUOTE) . "'.escapeHTML(), '" . $row['0'] . "', " . ($this->_isValueDefaultValue($row['0'], $default_value) ? 'true' : 'false') . ")";
            } else {
                $output .= "\n\t.addOption('" .  $hp->purify(SimpleSanitizer::unsanitize($row['1']), CODENDI_PURIFIER_JS_QUOTE) . "'.escapeHTML(), '" . (int) $row['0'] . "', " . ($this->_isValueDefaultValue($row['0'], $default_value) ? 'true' : 'false') . ")";
            }
            if ($row['0'] == $default_value) {
                $isDefaultValuePresent = true;
            }
        }
        if (!$isDefaultValuePresent && !is_array($default_value)) {
            // for single select box, if the default value is not present,
            // we add the javascript for this "missing value" (the corresponding html code will be added by html_build_select_box_from_arrays)
            $output .= "\n\t.addOption('" . $hp->purify($Language->getText('tracker_include_field', 'unknown_value'), CODENDI_PURIFIER_JS_QUOTE) . "', '" . (int) $default_value . "', true)\n";
        }
            return $output;
    }
    /**
     *
     *  Returns a select box populated with field values for this project
     *  if box_name is given then impose this name in the select box
     *  of the  HTML form otherwise use the field_name)
     *
     *  @param box_name: the selectbox name
     *  @param group_artifact_id: the artifact type id
     *  @param checked,show_none,text_none,show_any,text_any: values used by html_build_select_box function
     *  @param display: to manage the display of the selectBox: In case of RO it is not dispalyed
     *
     *    @return    string
     */
    public function fieldBox($box_name, $group_artifact_id, $checked = false, $show_none = false, $text_none = 0, $show_any = false, $text_any = 0, $show_unchanged = false, $text_unchanged = 0, $display = true)
    {
        global $Language;
         $hp = Codendi_HTMLPurifier::instance();
        if (!$text_none) {
            $text_none = $Language->getText('global', 'none');
        }
        if (!$text_any) {
            $text_any = $Language->getText('global', 'any');
        }
        if (!$text_unchanged) {
            $text_unchanged = $Language->getText('global', 'unchanged');
        }

        if (!$group_artifact_id) {
            return $Language->getText('tracker_include_field', 'error_no_atid');
        } else {
            $result = $this->getFieldPredefinedValues($group_artifact_id, $checked, false, true, false, true);
                $array_values = array();
            // $array_values is used to write javascript field dependencies
            // getFieldPredefinedValues doesn't always return the none value and the any value for the binded fields
            // so we add them everytime by precaution.
            if ($show_any) {
                $array_values[] = array(0, $text_any);
            }
            if ($show_none) {
                $array_values[] = array(100, $text_none);
            }
            if ($show_unchanged) {
                $array_values[] = array($text_unchanged, $text_unchanged);
            }
            while ($row = db_fetch_array($result)) {
                $array_values[]  = $row;
            }
            if (db_numrows($result) > 0) {
                db_reset_result($result);
            }

            if ($box_name == '') {
                $box_name = $this->field_name;
            }
            $output = '';
            if ($display) {
                $output  .= html_build_select_box(
                    $result,
                    $box_name,
                    $checked,
                    $show_none,
                    $text_none,
                    $show_any,
                    $text_any,
                    $show_unchanged,
                    $text_unchanged,
                    CODENDI_PURIFIER_CONVERT_HTML,
                    false
                );
            }
            if ($this->isJavascriptEnabled) {
                $output .= '<script type="text/javascript">';
                $output .= "\ncodendi.trackerv3.fields.add('" . (int) $this->getID() . "', '" . $hp->purify($this->getName(), CODENDI_PURIFIER_JS_QUOTE) . "', '" . $hp->purify(SimpleSanitizer::unsanitize($this->getLabel()), CODENDI_PURIFIER_JS_QUOTE) . "')";
                $output .= $this->_getValuesAsJavascript($array_values, $checked, $text_unchanged);
                $output .= ';';
                $output .= "\n</script>";
            }
              return $output;
        }
    }

    /**
     *
     *  Returns a multi date field
     *
     *  @param date_begin: start date
     *  @param date_end: end date
     *  @param size: the field size
     *  @param maxlength: the max field size
     *  @param ro: if true, the field is read-only
     *
     *    @return    string
     */
    public function multipleFieldDate($date_begin = '', $date_end = '', $size = 10, $maxlength = 10, $ro = false)
    {
        global $Language;

        // CAUTION!!!! The Javascript below assumes that the date always appear
        // in a field called 'artifact_form'
        $hp = Codendi_HTMLPurifier::instance();
        if ($ro) {
            if ($date_begin || $date_end) {
                $html = $Language->getText('tracker_include_field', 'start') . "&nbsp;$date_begin<br>" . $Language->getText('tracker_include_field', 'end') . "&nbsp;$date_end";
            } else {
                $html = $Language->getText('tracker_include_field', 'any_time');
            }
        } else {
            if (!$size || !$maxlength) {
                list($size, $maxlength) = $this->getGlobalDisplaySize();
            }

              $html = $Language->getText('tracker_include_field', 'start');
              $html .= $GLOBALS['HTML']->getDatePicker("field_" . $this->field_id, $this->getName(), $date_begin, $size, $maxlength);
              $html .= "<br />";
              $html .= $Language->getText('tracker_include_field', 'end');
              $html .= $GLOBALS['HTML']->getDatePicker("field_" . $this->field_id . "_end", $this->getName() . "_end", $date_end, $size, $maxlength);
        }

            return($html);
    }

    /**
     *
     *  Returns a date operator field
     *
     *  @param value: initial value
     *  @param ro: if true, the field is read-only
     *
     *    @return    string
     */
    public function fieldDateOperator($value = '', $ro = false)
    {
        global $Language;
         $hp = Codendi_HTMLPurifier::instance();
        if ($ro) {
            $html = htmlspecialchars($value);
        } else {
            $html = '<SELECT name="' . $hp->purify($this->field_name, CODENDI_PURIFIER_CONVERT_HTML) . '_op">' .
            '<OPTION VALUE=">"' . (($value == '>') ? 'SELECTED' : '') . '>&gt;</OPTION>' .
            '<OPTION VALUE="="' . (($value == '=') ? 'SELECTED' : '') . '>=</OPTION>' .
            '<OPTION VALUE="<"' . (($value == '<') ? 'SELECTED' : '') . '>&lt;</OPTION>' .
            '</SELECT>';
        }
        return($html);
    }

    /**
     *
     *  Returns a date field
     *
     *  @param value: initial value
     *  @param size: the field size
     *  @param maxlength: the max field size
     *  @param ro: if true, the field is read-only
     *
     *    @return    string
     */
    public function fieldDate($value = '', $ro = false, $size = '10', $maxlength = '10', $form_name = 'artifact_form', $today = false)
    {
        global $Language;
         $hp = Codendi_HTMLPurifier::instance();
        if ($ro) {
            $html = $value;
        } else {
            $html = $GLOBALS['HTML']->getDatePicker("field_" . $this->field_id, $this->field_name, $value, $size, $maxlength);
        }
        return($html);
    }

    /**
     *
     *  Returns a text field
     *
     *  @param value: initial value
     *  @param size: the field size
     *  @param maxlength: the max field size
     *
     *    @return    string
     */
    public function fieldText($value = '', $size = 0, $maxlength = 0)
    {
        $hp = Codendi_HTMLPurifier::instance();
        if (!$size || !$maxlength) {
            list($size, $maxlength) = $this->getGlobalDisplaySize();
        }

        $maxlengtharg = ' maxlength="' . (int) $maxlength . '"';
        if ($maxlength == "") {
            $maxlengtharg = "";
        }

        $sizearg = ' size="' . (int) $size . '"';
        if ($size == "") {
            $sizearg = "";
        }

        $html = '<input type="text"'
            . ' name="' . $hp->purify($this->field_name, CODENDI_PURIFIER_CONVERT_HTML) . '"'
            . ' value="' . $hp->purify(util_unconvert_htmlspecialchars($value), CODENDI_PURIFIER_CONVERT_HTML) . '"'
            . $sizearg
            . $maxlengtharg
            . '>';

        return($html);
    }

    /**
     *
     *  Returns a text area field
     *
     *  @param value: initial value
     *  @param cols: number of columns
     *  @param rows: number of rows
     *
     *    @return    string
     */
    public function fieldTextarea($value = '', $cols = 0, $rows = 0)
    {
        $hp = Codendi_HTMLPurifier::instance();
        if (!$cols || !$rows) {
            list($cols, $rows) = $this->getGlobalDisplaySize();
        }

        $html = '<TEXTAREA NAME="' . $hp->purify($this->field_name, CODENDI_PURIFIER_CONVERT_HTML) .
        '" id="tracker_' . $hp->purify($this->field_name, CODENDI_PURIFIER_CONVERT_HTML)  . '" ROWS="' . (int) $rows . '" COLS="' . (int) $cols . '" WRAP="SOFT">' . $hp->purify(util_unconvert_htmlspecialchars($value), CODENDI_PURIFIER_CONVERT_HTML) . '</TEXTAREA>';

        return($html);
    }

    /**
     *
     * Display a artifact field either as a read-only value or as a read-write
     * making modification possible
     *
     * @param group_artifact_id : the group artifact id (artifact type id)
     * @param value: the current value stored in this field (for select boxes type of field
     *         it is the value_id actually. It can also be an array with mutliple values.
     * @param break: true if a break line is to be inserted between the field label
     *        and the field value
     * @param label: if true display the field label.
     * @param ro: true if only the field value is to be displayed. Otherwise
     *        display an HTML select box, text field or text area to modify the value
     * @param ascii: if true do not use any HTML decoration just plain text (if true
     *        then read-only (ro) flag is forced to true as well)
     * @param show_none: show the None entry in the select box if true (value_id 100)
     * @param text_none: text associated with the none value_id to display in the select box
     * @param show_any: show the Any entry in the select box if true (value_id 0)
     * @param text_any: text associated with the any value_id  tp display in the select box
     * @param htmlEmail: Specific display for HTML email
     *
     *    @return    string
     */
    public function display(
        $group_artifact_id,
        $value = 'xyxy',
        $break = false,
        $label = true,
        $ro = false,
        $ascii = false,
        $show_none = false,
        $text_none = 0,
        $show_any = false,
        $text_any = 0,
        $show_unchanged = false,
        $text_unchanged = 0,
        $htmlEmail = true,
        $project_id = 0
    ) {
        global $Language;
        $hp = Codendi_HTMLPurifier::instance();
        //Use url parameters to populate fields
        if (!$ro) {
            $request = HTTPRequest::instance();
            if ($request->get('func') == 'add' && $request->exist($this->field_name)) {
                $value = htmlentities($request->get($this->field_name), ENT_QUOTES, 'UTF-8');
            }
        }

        if (!$text_none) {
            $text_none = $Language->getText('global', 'none');
        }
        if (!$text_any) {
            $text_any = $Language->getText('global', 'any');
        }
        if (!$text_unchanged) {
            $text_unchanged = $Language->getText('global', 'unchanged');
        }

        $output = "";

        if ($label) {
            $output = $this->labelDisplay($break, $ascii, !$ro);
        }
        // display depends upon display type of this field
        switch ($this->getDisplayType()) {
            case 'SB':
                if ($ro) {
                      // if multiple selected values return a list of <br> separated values
                      $arr = ( is_array($value) ? $value : array($value));
                    for ($i = 0; $i < count($arr); $i++) {
                        if ($arr[$i] == 0) {
                            $arr[$i] = $text_any;
                        } elseif ($arr[$i] == 100) {
                            $arr[$i] = $text_none;
                        } else {
                            $arr[$i] = SimpleSanitizer::unsanitize($this->getValue($group_artifact_id, $arr[$i]));
                            if (!$ascii) {
                                $arr[$i] =  $hp->purify($arr[$i], CODENDI_PURIFIER_BASIC_NOBR, $project_id);
                            }
                        }
                    }
                    if ($ascii) {
                        $output .= join(', ', $arr);
                    } else {
                        $output .= join('<br>', $arr);
                        if ($htmlEmail) {
                            //The span is used to pass values that would be processed in JS as dependency sources' values
                            $output .= '<span id="' . $this->field_name . '" style="display: none;">' . $value . '</span>';
                        }
                        $output .= $this->fieldBox(
                            '',
                            $group_artifact_id,
                            $value,
                            $show_none,
                            $text_none,
                            $show_any,
                            $text_any,
                            $show_unchanged,
                            $text_unchanged,
                            false
                        );
                    }
                } else {
                    // Only show the 'None" label if empty value is allowed or
                    // if value is already none (it can happen if the field was not used in
                    // the artifact submission form)
                    if ($this->isEmptyOk() || $value == 100) {
                        $show_none = true;
                        $text_none = $Language->getText('global', 'none');
                    }

                    if (is_array($value)) {
                        $output .= $this->multipleFieldBox(
                            '',
                            $group_artifact_id,
                            $value,
                            $show_none,
                            $text_none,
                            $show_any,
                            $text_any,
                            $show_unchanged,
                            $text_unchanged
                        );
                    } else {
                        $output .= $this->fieldBox(
                            '',
                            $group_artifact_id,
                            $value,
                            $show_none,
                            $text_none,
                            $show_any,
                            $text_any,
                            $show_unchanged,
                            $text_unchanged
                        );
                    }
                }
                break;

            case 'MB':
                $arr = ( is_array($value) ? $value : array($value));
                $valueArray = $arr;
                if ($ro) {
                      // if multiple selected values return a list of , separated values
                    for ($i = 0; $i < count($arr); $i++) {
                        if ($arr[$i] == 0) {
                            $arr[$i] = $text_any;
                        } elseif ($arr[$i] == 100) {
                            $arr[$i] = $text_none;
                        } else {
                                    $arr[$i] = SimpleSanitizer::unsanitize($this->getValue($group_artifact_id, $arr[$i]));
                            if (!$ascii) {
                                $arr[$i] =  $hp->purify($arr[$i], CODENDI_PURIFIER_BASIC_NOBR, $project_id);
                            }
                        }
                    }
                      $output .= join(",", $arr);
                    if (!$ascii) {
                        if ($htmlEmail) {
                            //The span is used to pass values id that would be processed in JS as dependency sources' values
                            $output .= '<span id="' . $this->field_name . '" style="display: none;">' . implode(',', $valueArray) . '</span>';
                        }
                        $output .= $this->multipleFieldBox(
                            '',
                            $group_artifact_id,
                            $value,
                            $show_none,
                            $text_none,
                            $show_any,
                            $text_any,
                            $show_unchanged,
                            $text_unchanged,
                            false,
                            false
                        );
                    }
                } else {
                    // Only show the 'None" label if empty value is allowed or
                    // if value is already none (it can happen if the field was not used in
                    // the artifact submission form)
                    if ($this->isEmptyOk() || (implode(",", $arr) == "100")) {
                        $show_none = true;
                        $text_none = $Language->getText('global', 'none');
                    }

                    //if (is_array($value))
                    $output .= $this->multipleFieldBox(
                        '',
                        $group_artifact_id,
                        $value,
                        $show_none,
                        $text_none,
                        $show_any,
                        $text_any,
                        $show_unchanged,
                        $text_unchanged
                    );
                   // else
                 //    $output .= $this->fieldBox('',$group_artifact_id, $value,
                 //                   $show_none,$text_none,$show_any,
                 //                   $text_any);
                }
                break;

            case 'DF':
                if ($value == $Language->getText('global', 'unchanged')) {
                         //$value = 'Unchanged (e.g. '.format_date("Y-m-j",time()).')';
                         $value = $Language->getText('global', 'unchanged');
                         $output .= $this->fieldDate($value, false, (strlen($value) + 1), (strlen($value) + 1), 'masschange_form', true);
                } else {
                      // Default value
                    if ($value == "") {
                        $value = time();
                    }
                    if ($ascii) {
                // most date fields (except open_date) are real dates (without time), so do not use $sys_datefmt
                // any more which can include an hour:min (than set on 00:00 for most dates). Especially in mail_follow_ups
                // after changing an Artifact
                        $output .= ( ($value == 0) ? '' : format_date("Y-m-j", $value));
                    } elseif ($ro) {
                        $output .= format_date($GLOBALS['Language']->getText('system', 'datefmt'), $value);
                    } else {
                        $output .= $this->fieldDate((($value == 0) ? '' : format_date("Y-m-j", $value, '')));
                    }
                }
                break;

            case 'TF':
                if ($this->getDataType() == $this->DATATYPE_FLOAT) {
                    if ($value == $Language->getText('global', 'unchanged')) {
                           //$value = 'Unchanged (e.g. '.number_format($value,2).')';
                           $output .= $this->fieldText($value, (strlen($value) + 1), (strlen($value) + 1));
                           break;
                    } else {
                           $value = number_format($value, 2, '.', '');
                    }
                }
                if ($ascii) {
                    $output .= util_unconvert_htmlspecialchars($value);
                } else {
                    $output .= ($ro ? $value : $this->fieldText($value));
                }
                break;

            case 'TA':
                if ($ascii) {
                    $output .= util_unconvert_htmlspecialchars($value);
                } else {
                    $output .= ($ro ? nl2br($value) : $this->fieldTextarea($value));
                }
                break;

            default:
                $output .= $Language->getText('tracker_include_field', 'unknown_display_type', $this->getName());
        }

        return($output);
    }
}
