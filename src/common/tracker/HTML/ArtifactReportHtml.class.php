<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

class ArtifactReportHtml extends ArtifactReport //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public $fields_per_line;

        /**
         *
         *
         *      @param  report_id
         *  @param  atid: the artifact type id
         *
         *      @return bool success.
         */
    public function __construct($report_id, $atid)
    {
        // echo 'ArtifactReportHtml('.$report_id.','.$atid.')';
            return parent::__construct($report_id, $atid);
    }

        /**
         *      Return the HTML table which displays the priority colors and export button
         *
         *      @param msg: the label of te table
         *
         *      @return string
         */
    public function showPriorityColorsKey($msg, $aids, $masschange, $pv)
    {
        $hp = Codendi_HTMLPurifier::instance();
        global $Language,$group_id;
            $html_result = "";

        if (! $masschange) {
            $html_result .= '<table width="100%"><tr><td align="left" width="50%">';
        }

            $html_result .= '<P class="small"><B>' . ($msg ? $msg : $Language->getText('tracker_include_report', 'prio_colors')) . '</B><BR><TABLE BORDER=0><TR>';

        for ($i = 1; $i < 10; $i++) {
                $html_result .=  '<TD class="' . get_priority_color($i) . '">' . $i . '</TD>';
        }
            $html_result .=  '</TR></TABLE>';

        if ((! $masschange) && ($pv == 0)) {
            $html_result .= '</td><td align="right" width="50%">';
            $html_result .= '
                          <FORM ACTION="" METHOD="POST" NAME="artifact_export_form">
                          <INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . (int) $this->group_artifact_id . '">
                          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . (int) $group_id . '">
			  <INPUT TYPE="HIDDEN" NAME="func" VALUE="export">
                          <INPUT TYPE="HIDDEN" NAME="export_aids" VALUE="' . $hp->purify(implode(",", $aids), CODENDI_PURIFIER_CONVERT_HTML) . '">
                          <input type="checkbox" name="only_displayed_fields" /> <small>' . $Language->getText('tracker_include_report', 'export_only_report_fields') . '</small><br />
                          <FONT SIZE="-1"><INPUT TYPE="SUBMIT" VALUE="' . $Language->getText('tracker_include_report', 'btn_export') . '"></FONT><br />
                          <input type="hidden" name="report_id" value="' . (int) $this->getReportId() . '" />
                          </FORM>';

            $html_result .=  '</td></tr></table>';
        }

            return $html_result;
    }

        /**
         *  Check is a sort criteria is already in the list of comma
         *  separated criterias. If so invert the sort order, if not then
         *  simply add it
         *
         *      @param criteria_list: the criteria list
         *  @param order: the order chosen by the UI
         *  @param msort: if multi sort is activate
         *
         *      @return string
         */
    public function addSortCriteria($criteria_list, $order, $msort)
    {
        //echo "<br>DBG \$criteria_list=$criteria_list,\$order=$order";
        $found = false;
        if ($criteria_list) {
                    $arr = explode(',', $criteria_list);
                    $i   = 0;
            foreach ($arr as $attr) {
                preg_match("/\s*([^<>]*)([<>]*)/", $attr, $match);
                list(,$mattr,$mdir) = $match;
            //echo "<br>DBG \$mattr=$mattr,\$mdir=$mdir";
                if ($mattr == $order) {
                    if (($mdir == '>') || (! isset($mdir))) {
                        $arr[$i] = $order . '<';
                    } else {
                        $arr[$i] = $order . '>';
                    }
                                    $found = true;
                }
                        $i++;
            }
        }

        if (! $found) {
            if (! $msort) {
                unset($arr);
            }
            if (($order == 'severity') || ($order == 'hours')) {
            // severity, effort and dates sorted in descending order by default
                $arr[] = $order . '<';
            } else {
                $arr[] = $order . '>';
            }
        }

        //echo "<br>DBG \$arr[]=".join(',',$arr);

        return(join(',', $arr));
    }

        /**
         * Transform criteria list to readable text statement
         * $url must not contain the morder parameter
         *
         *      @param criteria_list: the criteria list
         *  @param url: HTTP Get variables to add
         *
         *      @return string
         */
    public function criteriaListToText($criteria_list, $url)
    {
        global $art_field_fact;
        $arr_text = [];
        if ($criteria_list) {
            $arr    = explode(',', $criteria_list);
            $morder = '';
            foreach ($arr as $crit) {
                $morder .= ($morder ? "," . $crit : $crit);
                $attr    = str_replace('>', '', $crit);
                $attr    = str_replace('<', '', $attr);

                $field = $art_field_fact->getFieldFromName($attr);
                if ($field && $field->isUsed()) {
                    $label      = $field->getLabel();
                    $arr_text[] = '<a href="' . $url . '&morder=' . urlencode($morder) . '#results">' .
                     $label . '</a><img src="' . util_get_dir_image_theme() .
                     ((substr($crit, -1) == '<') ? 'dn' : 'up') .
                     '_arrow.png" border="0">';
                }
            }
        }

        return join(' > ', $arr_text);
    }

        /**
         *  Display the HTML code to display the query fields
         *
         *  @param prefs: array of parameters used for the current query
         *  @param advsrch,pv: HTTP get variables
         *
         *      @return string
         *
         */
    public function displayQueryFields($prefs, $advsrch, $pv)
    {
        global $ath,$Language;
        $hp          = Codendi_HTMLPurifier::instance();
        $html_select = '';
        // Loop through the list of used fields to define label and fields/boxes
        // used as search criteria
        $query_fields = $this->getQueryFields();

        //the width has been removed to correct the display of the Query Form (related to the fix of STTab theme)
        $html_select .= "<table>";
        $labels       = '';
        $boxes        = '';
        // Number of search criteria (boxes) displayed in one row
        $this->fields_per_line = 5;

        $ib       = 0;
        $is       = 0;
        $load_cal = false;

        foreach ($query_fields as $field) {
            $field_html = new ArtifactFieldHtml($field);

            //echo $field->getName()."-".$field->display_type."-".$field->data_type."-".$field->dump()."<br>";

            // beginning of a new row
            if ($ib % $this->fields_per_line == 0) {
                $align   = "left";
                $labels .= "\n" . '<TR align="' . $align . '" valign="top">';
                $boxes  .= "\n" . '<TR align="' . $align . '" valign="top">';
            }

            // Need to build help button argument.
            // Concatenate 3 args in one string
            $group_id  = $ath->Group->getID();
            $help_args = $group_id . '|' . $this->group_artifact_id . '|' . $field->getName();
            $labels   .= '<td class="small"><b>' . $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML) . '&nbsp;' .
                '</b></td>';

            $boxes .= '<TD><FONT SIZE="-1">';

            if ($field->isSelectBox()) {
                    // Check for advanced search if you have the field in the $prefs (HTTP parameters)
                if ($advsrch) {
                    if (isset($prefs[$field->getName()]) && $prefs[$field->getName()]) {
                        if (is_array($prefs[$field->getName()])) {
                              $values = $prefs[$field->getName()];
                        } else {
                              $values[] = $prefs[$field->getName()];
                        }
                    } else {
                        $values[] = 0;
                    }
                } else {
                    if (isset($prefs[$field->getName()][0])) {
                        $values = $prefs[$field->getName()][0];
                    } else {
                        $values = "";
                    }
                }

                    $boxes .=
                    $field_html->display(
                        $this->group_artifact_id,
                        $values,
                        false,
                        false,
                        ($pv != 0 ? true : false),
                        false,
                        true,
                        $Language->getText('global', 'none'),
                        true,
                        $Language->getText('global', 'any')
                    );
            } elseif ($field->isMultiSelectBox()) {
                $boxes .=
                    $field_html->display(
                        $this->group_artifact_id,
                        $prefs[$field->getName()],
                        false,
                        false,
                        ($pv != 0 ? true : false),
                        false,
                        true,
                        $Language->getText('global', 'none'),
                        true,
                        $Language->getText('global', 'any')
                    );
            } elseif ($field->isDateField()) {
                $load_cal = true; // We need to load the Javascript Calendar
                if ($advsrch) {
                    $date_begin = isset($prefs[$field->getName()][0])        ? $prefs[$field->getName()][0]        : '';
                    $date_end   = isset($prefs[$field->getName() . '_end'][0]) ? $prefs[$field->getName() . '_end'][0] : '';
                    $boxes     .= $field_html->multipleFieldDate($date_begin, $date_end, 0, 0, $pv);
                } else {
                    $val_op = isset($prefs[$field->getName() . '_op'][0]) ? $prefs[$field->getName() . '_op'][0] : '';
                    $val    = isset($prefs[$field->getName()][0])       ? $prefs[$field->getName()][0]       : '';
                    $boxes .= $field_html->fieldDateOperator($val_op, $pv) . $field_html->fieldDate($val, $pv);
                }
            } elseif (
                $field->isTextField() ||
                       $field->isTextArea()
            ) {
                $val    = isset($prefs[$field->getName()][0]) ? $prefs[$field->getName()][0] : "";
                $boxes .=
                    ($pv != 0 ? $val : $field_html->fieldText(stripslashes($val), 15, 80));
            }
            $boxes .= "</TD>\n";

            $ib++;

            // end of this row
            if ($ib % $this->fields_per_line == 0) {
                $html_select .= $labels . '</TR>' . $boxes . '</TR>';
                $labels       = $boxes = '';
            }
        }

        // Make sure the last few cells are in the table
        if ($labels) {
            $html_select .= $labels . '</TR>' . $boxes . '</TR>';
        }

        $html_select .= "</table>";

        return $html_select;
    }

    public function getFieldsSelectbox($name, $label, $used, $ath)
    {
        $html_result  = '';
        $html_result .= '<select name="' . $name . '" onchange="this.form.submit();">';
        $html_result .= '<option selected="selected" value="">' . $label . '</option>';
        $afsf         = new ArtifactFieldSetFactory($ath);
        foreach ($afsf->getAllFieldSets() as $fieldset) {
            $html_result .= '<optgroup label="' . $fieldset->getLabel() . '">';
            foreach ($fieldset->getArtifactFields() as $field) {
                if ($field->isUsed()) {
                    $highlight = '';
                    if ($field->getName() != 'comment_type_id') {
                        if (isset($used[$field->getName()])) {
                            $highlight = 'boxhighlight';
                        }
                        $html_result .= '<option value="' . $field->getName() . '" class="' . $highlight . '">';
                        $html_result .= $field->getLabel();
                        $html_result .= '</option>';
                    }
                }
            }
            $html_result .= '</optgroup>';
        }
        $html_result .= '</select>';
        return $html_result;
    }

        /**
         *  Return the HTML code to display the results of the query fields
         *
         *  @param group_id: the group id
         *  @param prefs: array of parameters used for the current query
         *  @param total_rows: number of rows of the result
         *  @param url: HTTP Get variables to add
         *  @param nolink: link to detailartifact
         *  @param offset,chunksz,morder,advsrch,offset,chunksz: HTTP get variables
         *
         *      @return string
         *
         */
    public function showResult($group_id, $prefs, $offset, $total_rows, $url, $nolink, $chunksz, $morder, $advsrch, $aids, $masschange, $pv)
    {
        global $Language,$ath;
        $hp          = Codendi_HTMLPurifier::instance();
        $html_result = "";

        // Build the list of links to use for column headings
        // Used to trigger sort on that column
        $result_fields = $this->getResultFields();

        $links_arr = [];
        $title_arr = [];
        $width_arr = [];
        $id_arr    = [];

        if (count($result_fields) == 0) {
            return;
        }

        foreach ($result_fields as $field) {
            if ($pv != 0) {
                $links_arr[] = $url . '&pv=' . (int) $pv . '&order=' . urlencode($field->getName()) . '#results';
            } else {
                $links_arr[] = $url . '&order=' . urlencode($field->getName()) . '#results';
            }
            $title_arr[]                  =  $hp->purify(SimpleSanitizer::unsanitize($field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML);
            $width_arr[$field->getName()] = $field->getColWidth();
            $id_arr[]                     = $hp->purify($field->getName(), CODENDI_PURIFIER_CONVERT_HTML);
        }

        $query  = $this->createQueryReport($prefs, $morder, $advsrch, $offset, $chunksz, $aids);
        $result = $this->getResultQueryReport($query);
        $rows   = count($result);

       /*
          Show extra rows for <-- Prev / Next -->
        */
        $nav_bar  = '<table width= "100%"><tr>';
        $nav_bar .= '<td width="40%" align ="left">';

        // If all artifacts on screen so no prev/begin pointer at all
        if ($total_rows > $chunksz) {
            if ($offset > 0) {
                $nav_bar .=
                '<A HREF="' . $url . '&offset=0#results" class="small"><B>&lt;&lt; ' . $Language->getText('global', 'begin') . '</B></A>' .
                '&nbsp;&nbsp;&nbsp;' .
                '<A HREF="' . $url . '&offset=' . ($offset - $chunksz) .
                '#results" class="small"><B>&lt; ' . $Language->getText('global', 'prev') . ' ' . (int) $chunksz . '</B></A></td>';
            } else {
                $nav_bar .=
                    '<span class="disable">&lt;&lt; ' . $Language->getText('global', 'begin') . '&nbsp;&nbsp;&lt; ' . $Language->getText('global', 'prev') . ' ' . (int) $chunksz . '</span>';
            }
        }

        $nav_bar .= '</td>';

        $offset_last = min($offset + $chunksz - 1, $total_rows - 1);

        // display 'Items x - y'  only in normal and printer-version modes
        if ($pv != 2) {
            $nav_bar .= '<td width= "20% " align = "center" class="small">' . $Language->getText('tracker_include_report', 'items') . ' ' . ($offset + 1) . ' - ' .
                ($offset_last + 1) . "</td>\n";
        }

        $nav_bar .= '<td width="40%" align ="right">';

        // If all artifacts on screen, no next/end pointer at all
        if ($total_rows > $chunksz) {
            if (($offset + $chunksz) < $total_rows) {
                $offset_end = ($total_rows - ($total_rows % $chunksz));
                if ($offset_end == $total_rows) {
                    $offset_end -= $chunksz;
                }

                $nav_bar .=
                    '<A HREF="' . $url . '&offset=' . ($offset + $chunksz) .
                    '#results" class="small"><B>' . $Language->getText('global', 'next') . ' ' . (int) $chunksz . ' &gt;</B></A>' .
                    '&nbsp;&nbsp;&nbsp;' .
                    '<A HREF="' . $url . '&offset=' . ($offset_end) .
                    '#results" class="small"><B>' . $Language->getText('global', 'end') . ' &gt;&gt;</B></A></td>';
            } else {
                $nav_bar .=
                    '<span class="disable">' . $Language->getText('global', 'next') . ' ' . (int) $chunksz .
                    ' &gt;&nbsp;&nbsp;' . $Language->getText('global', 'end') . ' &gt;&gt;</span>';
            }
        }
        $nav_bar .= '</td>';
        $nav_bar .= "</tr></table>\n";

        $html_result .= $nav_bar;

        if ($masschange) {
                   $html_result .= '</form><FORM NAME="artifact_list" action="" METHOD="POST">';
                //TODO: put width here
                   $html_result .= html_build_list_table_top($title_arr, $links_arr, true);
        } else {
            $html_result .= '<table width="100%" cellpadding="2" cellspacing="1" border="0">';
            $html_result .= '<thead>';
            $html_result .= '<tr class="boxtable">';
            while (
                ($title = current($title_arr)) &&
                  ($link = current($links_arr)) &&
                  ($id = current($id_arr)) &&
                  ($width = current($width_arr))
            ) {
                if ($width) {
                    $width = 'style="width:' . $width . '%"';
                } else {
                    $width = '';
                }
                $html_result .= '<th class="boxtitle" ' . $width . '><a href="' . $link . '" id="' . $id . '">' . $title . '</a></th>';
                next($title_arr);
                next($links_arr);
                next($id_arr);
                next($width_arr);
            }
            $html_result .= '</tr>';
            $html_result .= '</thead>';
        }
        $html_result .= '<tbody>';
        for ($i = 0; $i < $rows; $i++) {
            $html_result .= '<TR class="' . get_priority_color($result[$i]['severity_id']) . '">' . "\n";

            if ($masschange) {
                    $html_result .= '<TD align="center"><INPUT TYPE="checkbox" name="mass_change_ids[]" value="' . $result[$i]['artifact_id'] . '"></td>';
            }

            foreach ($result_fields as $key => $field) {
                //echo "$key=".$result[$i][$key]."<br>";

                $value = $result[$i][$key];
                $width = ' class="small"';

                if ($field->isDateField()) {
                    if ($value) {
                        if ($field->getName() == 'last_update_date') {
                             $html_result .= "<TD $width>" . format_date("Y-m-d H:i", $value) . '</TD>' . "\n";
                        } else {
                            $html_result .= "<TD $width>" . format_date("Y-m-d", $value) . '</TD>' . "\n";
                        }
                    } else {
                        $html_result .= '<TD align="center">-</TD>';
                    }
                } elseif ($field->getName() == 'artifact_id') {
                    if ($nolink) {
                        $html_result .= "<TD $width>" .  $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . "</TD>\n";
                    } else {
                        $target       = ($pv == 0 ? "" : " target=blank");
                        $html_result .= "<TD $width>" . '<A HREF="/tracker/?func=detail&aid=' .
                        urlencode($value) . '&atid=' . (int) $this->group_artifact_id . '&group_id=' . (int) $group_id . '"' . $target . '>' .
                        $value . '</A></TD>' . "\n";
                    }
                } elseif ($field->isUsername()) {
                    if ($nolink) {
                        $html_result .= "<TD $width>" . util_multi_user_nolink($value) . "</TD>\n";
                    } else {
                        $html_result .= "<TD $width>" . util_multi_user_link($value) . "</TD>\n";
                    }
                } elseif ($field->isFloat()) {
                         $html_result .= "<TD $width>" . number_format($value, 2) . '&nbsp;</TD>' . "\n";
                } elseif ($field->isTextArea()) {
                                   $unsane       = util_unconvert_htmlspecialchars($value);
                                   $text         = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $hp->purify($unsane, CODENDI_PURIFIER_BASIC, $group_id));
                                   $text         = str_replace('  ', '&nbsp; ', $text);
                                   $text         = str_replace('  ', '&nbsp; ', $text);
                                   $html_result .= '<TD ' . $width . ' style="font-family:monospace; font-size:10pt;">' . $text . '&nbsp;</TD>';
                } elseif ($field->getName() == 'status_id') {
                    $html_result .= "<TD $width>";
                    $html_result .= '<div id="status_id_' . $i . '">';
                    $html_result .= $hp->purify($value, CODENDI_PURIFIER_BASIC, $group_id);
                    $html_result .= '</div>';
                    if ($field->userCanUpdate($group_id, $ath->getId())) {
                        $field_values = $field->getFieldPredefinedValues($ath->getId(), false, false, true, false);
                        $array_values = [];
                        while ($row = db_fetch_array($field_values)) {
                            $array_values[] = "[" . $row['value_id'] . ", '" . addslashes($row['value']) . "']";
                        }
                    }
                    $html_result .= "</TD>\n";
                } else {
                    $html_result .= "<TD $width>" .  $hp->purify(util_unconvert_htmlspecialchars($value), CODENDI_PURIFIER_BASIC, $group_id)  . '&nbsp;</TD>' . "\n";
                }
            } // while
            $html_result .= "</tr>\n";
        }

        $html_result .= '</tbody></table>';

        if ($masschange) {
            $html_result .= '<script language="JavaScript">';
            $html_result .= "
       <!--
              function checkAll(val) {
                  $$('input[name=\"mass_change_ids[]\"]').each(function (element) {
                      element.checked = val;
                  });
              }
       //-->
       </script>";

            $html_result .= '<INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . (int) $this->group_artifact_id . '">
                          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . (int) $group_id . '">
                          <INPUT TYPE="HIDDEN" NAME="report_id" VALUE="' . (int) $this->report_id . '">
                          <INPUT TYPE="HIDDEN" NAME="advsrch" VALUE="' . (int) $advsrch . '">
                          <INPUT TYPE="HIDDEN" NAME="func" VALUE="masschange_detail">';
        // get the query
            foreach ($prefs as $field => $value) {
                if (is_array($value)) {
                    foreach ($value as $val) {
                        $html_result .= '<INPUT TYPE="HIDDEN" NAME="' . $hp->purify($field, CODENDI_PURIFIER_CONVERT_HTML) . '[]" VALUE="' . $hp->purify($val, CODENDI_PURIFIER_CONVERT_HTML) . '">';
                    }
                } else {
                    $html_result .= '<INPUT TYPE="HIDDEN" NAME="' . $hp->purify($field, CODENDI_PURIFIER_CONVERT_HTML) . '" VALUE="' . $hp->purify($value, CODENDI_PURIFIER_CONVERT_HTML) . '">';
                }
            }
        // stuff related to mass-change (buttons, check_all_items link, clear_all_items link) should be hidden in printer version
        // as well as table-only view. keep only 'select' column checkboxes
            if ($pv == 0) {
                if ($total_rows > $chunksz) {
                           $html_result .=
                           '<a href="javascript:checkAll(1)">' . $Language->getText('tracker_include_report', 'check_items') . ' ' . ($offset + 1) . '-' . ($offset_last + 1) . '</a>' .
                           ' - <a href="javascript:checkAll(0)">' . $Language->getText('tracker_include_report', 'clear_items') . ' ' . ($offset + 1) . '-' . ($offset_last + 1) . '</a><p>';

                    $html_result .= '<table width= "100%"><tr><td width="50%" align ="center" class="small">';
                    $html_result .= '<INPUT TYPE="SUBMIT" name="submit_btn" VALUE="' . $Language->getText('tracker_masschange_detail', 'selected_items') . '(' . ($offset + 1) . '-' . ($offset_last + 1) . ')">';
                    $html_result .= '</td><td width="50%" align ="center" class="small">';
                    $html_result .= '<INPUT TYPE="SUBMIT" name="submit_btn" VALUE="' . $Language->getText('tracker_include_report', 'mass_change_all', (int) $total_rows) . '">';
                } else {
                    $html_result .=
                           '<a href="javascript:checkAll(1)">' . $Language->getText('tracker_include_report', 'check_all_items') . '</a>' .
                           ' - <a href="javascript:checkAll(0)">' . $Language->getText('tracker_include_report', 'clear_all_items') . ' </a><p>';

                    $html_result .= '<table width= "100%"><tr><td width="60%" align ="center" class="small">';
                    $html_result .= '<INPUT TYPE="SUBMIT" name="submit_btn" VALUE="' . $Language->getText('tracker_masschange_detail', 'selected_items', [1, (int) $total_rows]) . '">';
                }
            }
            $html_result .= '</td></tr></table>';
        } else {
            $html_result .= $nav_bar;
        }

        return $html_result;
    }

        /**
         *  Display the report
         *
         *  @param prefs: array of parameters used for the current query
         *  @param group_id,report_id,set,advsrch,msort,morder,order,pref_stg,offset,chunksz,pv: HTTP get variables
         *
         *      @return string
         *
         */
    public function displayReport($prefs, $group_id, $report_id, $set, $advsrch, $msort, $morder, $order, $pref_stg, $offset, $chunksz, $pv, $masschange = false)
    {
        $hp = Codendi_HTMLPurifier::instance();
        global $ath,$art_field_fact,$Language;

        $html_result = '';

            // Display browse informations if any
        if ($ath->getBrowseInstructions() && $pv == 0) {
                $html_result .=  $hp->purify($ath->getBrowseInstructions(), CODENDI_PURIFIER_FULL);
        }

        echo "<p><div class='alert alert-danger'> " . $Language->getText('tracker_index', 'feature_is_deprecated')  .  "</div></p>";


        $html_result .= '
                          <FORM ACTION="" METHOD="GET" CLASS="form-inline" NAME="artifact_form">
                          <INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . (int) $this->group_artifact_id . '">
                          <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . (int) $group_id . '">';
        if ($masschange) {
            $html_result .= '<INPUT TYPE="HIDDEN" NAME="func" VALUE="masschange">';
        } else {
            $html_result .= '<INPUT TYPE="HIDDEN" NAME="func" VALUE="browse">';
        }

            $html_result .= '
                          <INPUT TYPE="HIDDEN" NAME="set" VALUE="custom">
                          <INPUT TYPE="HIDDEN" NAME="advsrch" VALUE="' . $hp->purify($advsrch, CODENDI_PURIFIER_CONVERT_HTML) . '">
                          <INPUT TYPE="HIDDEN" NAME="msort" VALUE="' . $hp->purify($msort, CODENDI_PURIFIER_CONVERT_HTML) . '">
                          <BR>
                          <TABLE BORDER="0" CELLPADDING="0" CELLSPACING="5">
                          <TR><TD colspan="' . (int) $this->fields_per_line . '" nowrap>';

            //Show the list of available artifact reports
        if ($pv == 0) {
            $res_report       = $this->getReports($this->group_artifact_id, UserManager::instance()->getCurrentUser()->getId());
            $box_name         = 'report_id" onChange="document.artifact_form.go_report.click()';
                $html_result .= '<b>' . $Language->getText('tracker_include_report', 'using_report');
                $html_result .= html_build_select_box($res_report, $box_name, $report_id, false, '', false, '', false, '', CODENDI_PURIFIER_CONVERT_HTML);
                $html_result .= ' <input class="btn" VALUE="' . $Language->getText('tracker_include_report', 'btn_go') . '" NAME="go_report" type="submit">' . '</b>';
        }

            // Start building the URL that we use to for hyperlink in the form
            $url = "/tracker/?atid=" . (int) $this->group_artifact_id . "&group_id=" . (int) $group_id . "&set=" .  $hp->purify($set, CODENDI_PURIFIER_CONVERT_HTML)  . "&msort=" .  $hp->purify($msort, CODENDI_PURIFIER_CONVERT_HTML);
        if ($masschange) {
            $url .= '&func=masschange';
        }

        if ($set == 'custom') {
             $url .= $pref_stg;
        } else {
             $url .= '&advsrch=' . $hp->purify($advsrch, CODENDI_PURIFIER_CONVERT_HTML);
        }

            $url_nomorder = $url;
        if ($pv != 0) {
            $url_nomorder .= "&pv=" . (int) $pv;
        }
            $url .= "&morder=" .  $hp->purify($morder, CODENDI_PURIFIER_CONVERT_HTML);

            $params = ['url' => &$url];
            $em     = EventManager::instance();
            $em->processEvent('tracker_urlparam_processing', $params);
            $url_nomorder = $url;

            // Build the URL for alternate Search
        if ($advsrch) {
            $url_alternate_search = str_replace('advsrch=1', 'advsrch=0', $url);
            $text                 = $Language->getText('tracker_include_report', 'simple_search');
        } else {
            $url_alternate_search = str_replace('advsrch=0', 'advsrch=1', $url);
            $text                 = $Language->getText('tracker_include_report', 'adv_search');
        }

        if ($pv == 0) {
             $html_result .= '<small>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;(' . $Language->getText('tracker_include_report', 'or_use') . ' <a href="' .
                 $url_alternate_search . '">' . $hp->purify($text, CODENDI_PURIFIER_CONVERT_HTML) . '</a>)</small></h3><p>';
        }

            $current_user                = UserManager::instance()->getCurrentUser();
            $user_dont_want_to_see_query = $current_user->getPreference('tracker_' . (int) $this->group_artifact_id . '_hide_section_query');
            $html_result                .= '</TABLE>';
            // Display query fields
        if ($pv != 2) {
            $html_result .= '<A name="query"></A>';
            $html_result .= '<fieldset class="tracker-search"><legend>';
            $onclick      = '';
            $onclick     .= "if ($('artifacts_query').empty()) { return true }";
            if (! $current_user->isAnonymous()) {
                $onclick .= "else { new Ajax.Request(this.href); }";
            }
            $onclick     .= "if ($('artifacts_query').visible()) { this.firstChild.src.replace(/minus.png/, 'plus.png'); } else {this.firstChild.src.replace(/plus.png/, 'minus.png');}";
            $onclick     .= "new Effect.toggle($('artifacts_query'), 'slide', {duration:0.1});";
            $onclick     .= "return false;";
            $html_result .= '<a href="' . $url . '&amp;func=toggle_section&amp;section=query" onclick="' . $onclick . '">';
            if ($user_dont_want_to_see_query) {
                $image = 'ic/toggle_plus.png';
            } else {
                $image = 'ic/toggle_minus.png';
            }
            $html_result .= $GLOBALS['HTML']->getimage($image);
            $html_result .= '</a>';
            $html_result .= $Language->getText('tracker_include_report', 'query') . '</legend>';
            $html_result .= '<div id="artifacts_query" style="padding-left:16px;">';
            if (! $user_dont_want_to_see_query) {
                $html_result .= $this->displayQueryFields($prefs, $advsrch, $pv);
                $html_result .= '<div style="text-align:left"><br><input class="btn" type="submit" value="' . $Language->getText('global', 'btn_submit') . '" /></div>';
            }
            $html_result .= '</div></fieldset><br>';
        }

            // Finally display the result table
            $user_dont_want_to_see_results = $current_user->getPreference('tracker_' . (int) $this->group_artifact_id . '_hide_section_results');
            $totalrows                     = $this->selectReportItems($prefs, $morder, $advsrch, $aids); // Filter according to permissions

        if ($totalrows > 0) {
            // Build the sorting header messages
            if ($pv != 2) {
                if ($morder) {
                        $order_statement = $Language->getText('tracker_include_report', 'sorted_by') .
                            ' : ' . $this->criteriaListToText($morder, $url_nomorder);
                } else {
                        $order_statement = '';
                }

                $html_result .= '<A name="results"></A>';
                $html_result .= '<fieldset class="tracker-search"><legend>';
                if ($pv == 0) {
                    $onclick  = '';
                    $onclick .= "if ($('artifacts_result').empty()) { return true }";
                    if (! $current_user->isAnonymous()) {
                        $onclick .= "else { new Ajax.Request(this.href); }";
                    }
                    $onclick     .= "if ($('artifacts_result').visible()) { this.firstChild.src.replace(/minus.png/, 'plus.png'); } else {this.firstChild.src.replace(/plus.png/, 'minus.png');}";
                    $onclick     .= "new Effect.toggle($('artifacts_result'), 'slide', {duration:0.1});";
                    $onclick     .= "return false;";
                    $html_result .= '<a href="' . $url . '&amp;func=toggle_section&amp;section=results" onclick="' . $onclick . '">';
                    if ($user_dont_want_to_see_results) {
                        $image = 'ic/toggle_plus.png';
                    } else {
                        $image = 'ic/toggle_minus.png';
                    }
                    $html_result .= $GLOBALS['HTML']->getimage($image);
                    $html_result .= '</a>';
                }
                $html_result .= (int) $totalrows . ' ' . $Language->getText('tracker_include_report', 'matching') . ' ' . $order_statement . '</legend>';
                $html_result .= '<div id="artifacts_result" style="padding-left:16px;">';
            }
            if ($pv != 2 && ! $user_dont_want_to_see_results) {
                if ($pv == 0) {
                        $html_result .= '<p> ' . $Language->getText('global', 'btn_browse') .
                            ' <input TYPE="text" class="input-mini" name="chunksz" size="3" MAXLENGTH="5" ' .
                            'VALUE="' . (int) $chunksz . '">&nbsp;' . $hp->purify($ath->getItemName(), CODENDI_PURIFIER_CONVERT_HTML) . $Language->getText('tracker_include_report', 'at_once');
                        $html_result .= '<P>' . $Language->getText('tracker_include_report', 'sort_results') . ' ';
                        $field        = $art_field_fact->getFieldFromName('severity');
                    if ($field && $field->isUsed()) {
                        $html_result .= $Language->getText('global', 'or') . ' <A HREF="' . $url . '&order=severity#results"><b>' . $hp->purify($Language->getText('tracker_include_report', 'sort_sev', $field->getLabel()), CODENDI_PURIFIER_CONVERT_HTML) . '</b></A> ';
                    }
                        $html_result .= $Language->getText('global', 'or') . ' <A HREF="' . $url . '&order=#results"><b>' . $Language->getText('tracker_include_report', 'reset_sort') . '</b></a>. ';
                }

                if ($msort) {
                        $url_alternate_sort = str_replace('msort=1', 'msort=0', $url) .
                            '&order=#results';
                        $text               = $Language->getText('global', 'deactivate');
                } else {
                        $url_alternate_sort = str_replace('msort=0', 'msort=1', $url) .
                    '&order=#results';
                        $text               = $Language->getText('global', 'activate');
                }

                if ($pv == 0) {
                        $html_result .= $Language->getText('tracker_include_report', 'multicolumn_sort', [$url_alternate_sort, $text]) . '&nbsp;&nbsp;&nbsp;&nbsp;' .
                            '(<a href="' . $url . '&pv=1"> <img src="' . util_get_image_theme("ic/printer.png") . '" border="0">' .
                            '&nbsp;' . $Language->getText('global', 'printer_version') . '</a>)' . "\n";
                }
            }

            if ($pv != 0) {
                $chunksz = 100000;
            }
            if ($pv == 2 || ! $user_dont_want_to_see_results) {
                $html_result .= $this->showResult($group_id, $prefs, $offset, $totalrows, $url, ($pv == 1 ? true : false), $chunksz, $morder, $advsrch, $aids, $masschange, $pv);
            }
            $html_result .= '</form>';
            if ($pv != 2 && ! $user_dont_want_to_see_results) {
                // priority colors are not displayed in table-only view
                $html_result .= $this->showPriorityColorsKey($Language->getText('tracker_include_report', 'sev_colors'), $aids, $masschange, $pv);
            }
        } else {
            $html_result .= '</form>';
            $html_result .= '<h2>' . $Language->getText('tracker_include_report', 'no_match') . '</h2>';
            $html_result .= db_error();
        }
            $html_result .= '</div></fieldset>';
            echo $html_result;

            $em = EventManager::instance();
            $em->processEvent('tracker_after_report', ['group_id' => $group_id, 'atid' => (int) $this->group_artifact_id, 'url' => $url]);
    }

        /**
         * Return a label for the scope code
         *
         * param scope: the scope code
         *
         * @return string
         */
    public function getScopeLabel($scope)
    {
        global $Language;

        switch ($scope) {
            case 'P':
                return $Language->getText('global', 'Project');
            case 'I':
                return $Language->getText('global', 'Personal');
            case 'S':
                return $Language->getText('global', 'System');
        }
    }

    /**
     * Return a link for the setting default report
     *
     * param default_val: the default report  value
     * @return string
     */

    public function getDefaultLink($default_val, $scope, $report_id)
    {
        $g        = $GLOBALS['ath']->getGroup();
        $group_id = $g->getID();
        $atid     = $GLOBALS['ath']->getID();
        if (($scope != 'S') && ($scope != 'I')) {
            switch ($default_val) {
                case 0:
                    return '<a href="/tracker/admin/?func=report&group_id=' . $group_id . '&atid=' . $atid . '&update_default=' . $report_id . '">' . $GLOBALS['Language']->getText('tracker_include_report', 'set_default') . '</a>';
                case 1:
                    return '<b>' . $GLOBALS['Language']->getText('tracker_include_report', 'is_default') . '</b>';
                default:
                    return '<a href="/tracker/admin/?func=report&group_id=' . $group_id . '&atid=' . $atid . '&update_default=' . $report_id . '">' . $GLOBALS['Language']->getText('tracker_include_report', 'set_default') . '</a>';
            }
        } else {
            return '<b>-</b>';
        }
    }

        /**
         * Display the report list
         *
         * param : $reports      the list the reports within an artifact to display
         *
         * @return void
         */
    public function showAvailableReports($reports)
    {
        $hp = Codendi_HTMLPurifier::instance();
            global $ath,$Language;

            $g        = $ath->getGroup();
            $group_id = $g->getID();
            $atid     = $ath->getID();

            $ath->adminHeader(['title' => $Language->getText('tracker_include_report', 'report_mgmt'),
            ]);
            $trackerName = $ath->getName();

            echo '<H2>' . $Language->getText('tracker_import_admin', 'tracker') . ' \'<a href="/tracker/admin/?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '">';
            echo $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML);
            echo '</a>\'' . $Language->getText('tracker_include_report', 'report_admin') . '</H2>';

        if ($reports) {
       // Loop through the list of all artifact report
            $title_arr   = [];
            $title_arr[] = $Language->getText('tracker_include_report', 'id');
            $title_arr[] = $Language->getText('tracker_include_report', 'report_name');
            $title_arr[] = $Language->getText('tracker_include_artifact', 'desc');
            $title_arr[] = $Language->getText('tracker_include_report', 'scope');
            if ($ath->userIsAdmin()) {
                    $title_arr[] = $Language->getText('tracker_include_report', 'default');
            }
               $title_arr[] = $Language->getText('tracker_include_canned', 'delete');

               echo '<p>' . $Language->getText('tracker_include_report', 'mod');
               echo html_build_list_table_top($title_arr);
               $i = 0;
            while ($arr = db_fetch_array($reports)) {
                  echo '<TR class="' . util_get_alt_row_color($i) . '"><TD>';

                if ($arr['scope'] == 'S' || (! $ath->userIsAdmin() && ($arr['scope'] == 'P'))) {
                    echo (int) $arr['report_id'];
                } else {
                            echo '<A HREF="/tracker/admin/?func=report&group_id=' . (int) $group_id .
                        '&show_report=1&report_id=' . (int) $arr['report_id'] . '&group_id=' . (int) $group_id . '&atid=' . (int) $ath->getID() . '">' .
                         $hp->purify($arr['report_id'], CODENDI_PURIFIER_CONVERT_HTML) . '</A>';
                }

                    echo "</td><td>" . $hp->purify($arr['name'], CODENDI_PURIFIER_CONVERT_HTML) . '</td>' .
                  "<td>" . $hp->purify($arr['description'], CODENDI_PURIFIER_BASIC, $group_id) . '</td>' .
                  '<td align="center">' . $hp->purify($this->getScopeLabel($arr['scope']), CODENDI_PURIFIER_CONVERT_HTML) . '</td>';

                  $name = $arr['name'];

                if ($ath->userIsAdmin()) {
                    echo "\n<td align=\"center\">" . $this->getDefaultLink($arr['is_default'], $arr['scope'], $arr['report_id']) . '</td>';
                }
                    echo "\n<td align=\"center\">";
                if ($arr['scope'] == 'S' || (! $ath->userIsAdmin() && ($arr['scope'] == 'P'))) {
                      echo '-';
                } else {
                    echo '<A HREF="/tracker/admin/?func=report&group_id=' . (int) $group_id .
                        '&atid=' . (int) $atid . '&delete_report=1&report_id=' . (int) $arr['report_id'] .
                        '" onClick="return confirm(\'' . $Language->getText('tracker_include_report', 'delete_report', $hp->purify(addslashes($name), CODENDI_PURIFIER_CONVERT_HTML)) . '\');">' .
                            '<img src="' . util_get_image_theme("ic/trash.png") . '" border="0"></A>';
                }

                    echo '</td></tr>';
                    $i++;
            }
                    echo '</TABLE>';
        } else {
            echo '<p><h3>' . $Language->getText('tracker_include_report', 'no_rep_def') . '</h3>';
        }

        echo '<P> ' . $Language->getText('tracker_include_report', 'create_report', ['/tracker/admin/?func=report&group_id=' . (int) $group_id . '&atid=' . (int) $atid . '&new_report=1']);
    }

        /**
         *  Display the report form
         *
         *  @return void
         */
    public function createReportForm()
    {
        $hp = Codendi_HTMLPurifier::instance();
            global $ath,$Language;

            $g        = $ath->getGroup();
            $group_id = $g->getID();
            $atid     = $ath->getID();

            $ath->adminHeader(['title' => $Language->getText('tracker_include_report', 'create_rep'),
            ]);

        echo '<H2>' . $Language->getText('tracker_import_admin', 'tracker') . ' \'<a href="/tracker/admin/?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '">' . $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) . '</a>\'  - ' . $Language->getText('tracker_include_report', 'create_rep') . ' </H2>';

        // display the table of all fields that can be included in the report
        $title_arr   = [];
        $title_arr[] = $Language->getText('tracker_include_report', 'field_label');
        $title_arr[] = $Language->getText('tracker_include_artifact', 'desc');
        $title_arr[] = $Language->getText('tracker_include_report', 'search_crit');
        $title_arr[] = $Language->getText('tracker_include_report', 'rank_search');
        $title_arr[] = $Language->getText('tracker_include_report', 'rep_col');
        $title_arr[] = $Language->getText('tracker_include_report', 'rank_repo');
        $title_arr[] = $Language->getText('tracker_include_report', 'col_width');

        echo '
                <FORM ACTION="/tracker/admin/" METHOD="POST">
                   <INPUT TYPE="HIDDEN" NAME="func" VALUE="report">
                   <INPUT TYPE="HIDDEN" NAME="create_report" VALUE="y">
                   <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . (int) $group_id . '">
                   <INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . (int) $atid . '">
                   <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">
                   <B>' . $Language->getText('tracker_include_artifact', 'name') . ':</B>
                   <INPUT TYPE="TEXT" NAME="rep_name" VALUE="" CLASS="textfield_small" MAXLENGTH="80">
                   &nbsp;&nbsp;&nbsp;&nbsp;<B>' . $Language->getText('tracker_include_report', 'scope') . ': </B>';

        if ($ath->userIsAdmin()) {
                    echo '<SELECT ID="rep_scope" NAME="rep_scope" onchange="if (document.getElementById(\'rep_scope\').value == \'P\') {document.getElementById(\'rep_default\').disabled=false} else { document.getElementById(\'rep_default\').disabled=true;document.getElementById(\'rep_default\').checked=false }">
                                        <OPTION VALUE="I">' . $Language->getText('global', 'Personal') . '</OPTION>
                                        <OPTION VALUE="P">' . $Language->getText('global', 'Project') . '</OPTION>
                                        </SELECT>';
                    echo '&nbsp;&nbsp;&nbsp;&nbsp;<B>' . $Language->getText('tracker_include_report', 'default') . ':</B>' . '<INPUT TYPE="CHECKBOX" ID="rep_default" NAME="rep_default" DISABLED>';
        } else {
                    echo $Language->getText('global', 'Personal') . ' <INPUT TYPE="HIDDEN" NAME="rep_scope" VALUE="I">';
                    echo '&nbsp;&nbsp;&nbsp;&nbsp;<B>' . $Language->getText('tracker_include_report', 'default') . ':</B>' . '<INPUT TYPE="CHECKBOX" ID="rep_default" NAME="rep_default" DISABLED>';
        }
        echo ' <P>
                    <B>' . $Language->getText('tracker_include_artifact', 'desc') . ': </B>
                     <INPUT TYPE="TEXT" NAME="rep_desc" VALUE="" SIZE="50" MAXLENGTH="120">
                          <P>';

        echo html_build_list_table_top($title_arr);
        $i   = 0;
        $aff = new ArtifactFieldFactory($ath);

        $art_fieldset_fact    = new ArtifactFieldSetFactory($ath);
              $used_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();

              // fetch list of used fieldsets for this artifact
        foreach ($used_fieldsets as $fieldset_id => $fieldset) {
                $used_fields = $fieldset->getAllUsedFields();
                echo '<TR class="fieldset_separator">';
                echo '<TD colspan="7">' . $fieldset->getLabel() . '</TD>';
                echo '</TR>';
            foreach ($used_fields as $field) {
             // Do not show fields not used by the project
                if (! $field->isUsed()) {
                    continue;
                }

             // Do not show some special fields any way
                if ($field->isSpecial()) {
                    if (
                        ($field->getName() == 'group_id') ||
                             ($field->getName() == 'comment_type_id')
                    ) {
                        continue;
                    }
                }

                    //Do not show unreadable fields
                if (! $ath->userIsAdmin() && ! $field->userCanRead($group_id, $this->group_artifact_id)) {
                    continue;
                }

                    $cb_search   = 'CBSRCH_' . $field->getName();
                    $cb_report   = 'CBREP_' . $field->getName();
                    $tf_search   = 'TFSRCH_' . $field->getName();
                    $tf_report   = 'TFREP_' . $field->getName();
                    $tf_colwidth = 'TFCW_' . $field->getName();
                    echo '<TR class="' . util_get_alt_row_color($i) . '">';

                    echo "\n<td>" . $field->label . '</td>' .
                 "\n<td>" . $field->description . '</td>' .
                 "\n<td align=\"center\">" . '<input type="checkbox" name="' . $cb_search . '" value="1"></td>' .
                 "\n<td align=\"center\">" . '<input type="text" name="' . $tf_search . '" value="" size="5" maxlen="5"></td>' .
                 "\n<td align=\"center\">" . '<input type="checkbox" name="' . $cb_report . '" value="1"></td>' .
                 "\n<td align=\"center\">" . '<input type="text" name="' . $tf_report . '" value="" size="5" maxlen="5"></td>' .
                 "\n<td align=\"center\">" . '<input type="text" name="' . $tf_colwidth . '" value="" size="5" maxlen="5"></td>' .
                 '</tr>';
                    $i++;
            }
        }
        echo '</TABLE>' .
            '<P><CENTER><INPUT TYPE="SUBMIT" VALUE="' . $Language->getText('global', 'btn_submit') . '"></CENTER>' .
            '</FORM>';
    }

        /**
         *  Display detail report form
         *
         *  @return void
         */
    public function showReportForm()
    {
        $hp = Codendi_HTMLPurifier::instance();
            global $ath, $Language;

            $g        = $ath->getGroup();
            $group_id = $g->getID();
            $atid     = $ath->getID();

            $ath->adminHeader(['title' => $Language->getText('tracker_include_report', 'modify_report'),
            ]);

        echo '<H2>' . $Language->getText('tracker_import_admin', 'tracker') . ' \'<a href="/tracker/admin/?group_id=' . (int) $group_id . '&atid=' . (int) $atid . '">' . $hp->purify(SimpleSanitizer::unsanitize($ath->getName()), CODENDI_PURIFIER_CONVERT_HTML) . '</a>\' -  ' . $Language->getText('tracker_include_report', 'modify_report') . ' \'' . $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML) . '\'</H2>';

        // display the table of all fields that can be included in the report
        // along with their current state in this report
        $title_arr   = [];
        $title_arr[] = $Language->getText('tracker_include_report', 'field_label');
        $title_arr[] = $Language->getText('tracker_include_artifact', 'desc');
        $title_arr[] = $Language->getText('tracker_include_report', 'search_crit');
        $title_arr[] = $Language->getText('tracker_include_report', 'rank_search');
        $title_arr[] = $Language->getText('tracker_include_report', 'rep_col');
        $title_arr[] = $Language->getText('tracker_include_report', 'rank_repo');
        $title_arr[] = $Language->getText('tracker_include_report', 'col_width');

        echo '<FORM ACTION="/tracker/admin/" METHOD="POST">
                   <INPUT TYPE="HIDDEN" NAME="func" VALUE="report">
                   <INPUT TYPE="HIDDEN" NAME="update_report" VALUE="y">
                   <INPUT TYPE="HIDDEN" NAME="atid" VALUE="' . (int) $atid . '">
                   <INPUT TYPE="HIDDEN" NAME="group_id" VALUE="' . (int) $group_id . '">
                   <INPUT TYPE="HIDDEN" NAME="report_id" VALUE="' . (int) $this->report_id . '">
                   <INPUT TYPE="HIDDEN" NAME="post_changes" VALUE="1">
                   <B>' . $Language->getText('tracker_include_artifact', 'name') . ': </B>
                   <INPUT TYPE="TEXT" NAME="rep_name" VALUE="' . $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML) . '" CLASS="textfield_small" MAXLENGTH="80">
                         &nbsp;&nbsp;&nbsp;&nbsp;<B>' . $Language->getText('tracker_include_report', 'scope') . ': </B>';
        $scope = $this->scope;
        if ($ath->userIsAdmin()) {
                    echo '<SELECT ID="rep_scope" NAME="rep_scope" onchange="if (document.getElementById(\'rep_scope\').value == \'P\') {document.getElementById(\'rep_default\').disabled=false} else { document.getElementById(\'rep_default\').disabled=true;document.getElementById(\'rep_default\').checked=false }" >
                                        <OPTION VALUE="I"' . ($scope == 'I' ? 'SELECTED' : '') . '>' . $Language->getText('global', 'Personal') . '</OPTION>
                                        <OPTION VALUE="P"' . ($scope == 'P' ? 'SELECTED' : '') . '>' . $Language->getText('global', 'Project') . '</OPTION>
                                        </SELECT>';
                    echo '&nbsp;&nbsp;&nbsp;&nbsp;<B>' . $Language->getText('tracker_include_report', 'default') . ':</B>' . '<INPUT TYPE="CHECKBOX" ID="rep_default" NAME="rep_default" ' . ($this->is_default == 1 ? 'CHECKED' : '') . ' ' . ($this->scope != 'P' ? 'DISABLED' : '') . '>';
        } else {
                    echo ($scope == 'P' ? $Language->getText('global', 'Project') : $Language->getText('global', 'Personal')) .
                        '<INPUT TYPE="HIDDEN" NAME="rep_scope" VALUE="' . $hp->purify($scope, CODENDI_PURIFIER_CONVERT_HTML) . '">';
                    echo '&nbsp;&nbsp;&nbsp;&nbsp;<B>' . $Language->getText('tracker_include_report', 'default') . ':</B>' . '<INPUT TYPE="CHECKBOX" ID="rep_default" NAME="rep_default" ' . ($this->is_default == 1 ? 'CHECKED' : '') . ' DISABLED >';
        }
        echo '
                    <P>
                    <B>' . $Language->getText('tracker_include_artifact', 'desc') . ':</B>
                    <INPUT TYPE="TEXT" NAME="rep_desc" VALUE="' . $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) . '" SIZE="50" MAXLENGTH="120">
                          <P>';

        echo html_build_list_table_top($title_arr);

        // Write all the fields, grouped by fieldsetset and ordered by rank.

        $i   = 0;
        $aff = new ArtifactFieldFactory($ath);

        $art_fieldset_fact    = new ArtifactFieldSetFactory($ath);
              $used_fieldsets = $art_fieldset_fact->getAllFieldSetsContainingUsedFields();

              // fetch list of used fieldsets for this artifact
        foreach ($used_fieldsets as $fieldset_id => $fieldset) {
                $used_fields = $fieldset->getAllUsedFields();
                echo '<TR class="fieldset_separator">';
                echo '<TD colspan="7">' . $fieldset->getLabel() . '</TD>';
                echo '</TR>';
            foreach ($used_fields as $field) {
             // Do not show fields not used by the project
                if (! $field->isUsed()) {
                    continue;
                }

             // Do not show some special fields any way
                if ($field->isSpecial()) {
                    if (
                        ($field->getName() == 'group_id') ||
                             ($field->getName() == 'comment_type_id')
                    ) {
                        continue;
                    }
                }

                    //Do not show unreadable fields
                if (! $ath->userIsAdmin() && ! $field->userCanRead($group_id, $this->group_artifact_id)) {
                    continue;
                }
                    $cb_search   = 'CBSRCH_' . $field->getName();
                    $cb_report   = 'CBREP_' . $field->getName();
                    $tf_search   = 'TFSRCH_' . $field->getName();
                    $tf_report   = 'TFREP_' . $field->getName();
                    $tf_colwidth = 'TFCW_' . $field->getName();

                    $rep_field = null;
                if (isset($this->fields[$field->getName()])) {
                        $rep_field = $this->fields[$field->getName()];
                }
                if (! $rep_field) {
                    $rep_field = new ArtifactReportField();
                }

                    $cb_search_chk   = ($rep_field->isShowOnQuery() ? 'CHECKED' : '');
                    $cb_report_chk   = ($rep_field->isShowOnResult() ? 'CHECKED' : '');
                    $tf_search_val   = $rep_field->getPlaceQuery();
                    $tf_report_val   = $rep_field->getPlaceResult();
                    $tf_colwidth_val = $rep_field->getColWidth();

                    echo '<TR class="' . util_get_alt_row_color($i) . '">';

                    echo "\n<td>" . $field->getLabel() . '</td>' .
                 "\n<td>" . $field->getDescription() . '</td>' .
                 "\n<td align=\"center\">" . '<input type="checkbox" name="' . $cb_search . '" value="1" ' . $cb_search_chk . ' ></td>' .
                 "\n<td align=\"center\">" . '<input type="text" name="' . $tf_search . '" value="' . $tf_search_val . '" size="5" maxlen="5"></td>' .
                 "\n<td align=\"center\">" . '<input type="checkbox" name="' . $cb_report . '" value="1" ' . $cb_report_chk . ' ></td>' .
                 "\n<td align=\"center\">" . '<input type="text" name="' . $tf_report . '" value="' . $tf_report_val . '" size="5" maxlen="5"></td>' .
                 "\n<td align=\"center\">" . '<input type="text" name="' . $tf_colwidth . '" value="' . $tf_colwidth_val . '" size="5" maxlen="5"></td>' .
                 '</TR>';
                    $i++;
            }
        }

        echo '</TABLE>' .
            '<P><CENTER><INPUT TYPE="SUBMIT" VALUE="' . $Language->getText('global', 'btn_submit') . '"></CENTER>' .
            '</FORM>';
    }
}
