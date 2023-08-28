<?php
/**
 * Copyright (c) Enalean, 2013-Present. All Rights Reserved.
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

$datetime_fmt = 'Y-m-d H:i:s';
$datetime_msg = 'yyyy-mm-dd hh:mm:ss';

require_once __DIR__ . '/../../include/utils.php';

/**
 * This function does not do any sort of HTML escaping but is never expected to be used
 * in a context where the content type is something else than text/csv. To avoid
 * false-positives it is considered HTML is escaped.
 *
 * @psalm-taint-escape html
 * @psalm-taint-escape has_quotes
 */
function tocsv($string, $csv_separator)
{
    // Escape the double quote character by doubling it
    $string = str_replace('"', '""', $string);

    //Surround with double quotes if there is a comma;
    // a space or the user separator in the string
    if (
        strpos($string, ' ') !== false || strpos($string, ',') !== false || strpos($string, $csv_separator) !== false ||
        strpos($string, '"') !== false || strpos($string, "\n") !== false || strpos($string, "\t") !== false ||
        strpos($string, "\r") !== false || strpos($string, "\0") !== false || strpos($string, "\x0B") !== false
    ) {
        return "\"$string\"";
    } else {
        return $string;
    }
}

/**
 * Get the CSV separator defined in the Account Maintenance preferences
 *
 * @return string the CSV separator defined by the user or "," by default if the user didn't defined it
 */
function get_csv_separator()
{
    if ($u_separator = user_get_preference("user_csv_separator")) {
    } else {
        $u_separator = PFUser::DEFAULT_CSV_SEPARATOR;
    }
    $separator = '';
    switch ($u_separator) {
        case 'semicolon':
            $separator = ";";
            break;
        case 'tab':
            $separator = "\t";
            break;
        default:
            $separator = ',';
            break;
    }
    return $separator;
}

function build_csv_header($col_list, $lbl_list)
{
    $line      = '';
    $separator = get_csv_separator();
    foreach ($col_list as $col) {
        if (isset($lbl_list[$col])) {
            $line .= tocsv($lbl_list[$col], $separator) . $separator;
        }
    }
    $line = substr($line, 0, -1);
    return $line;
}

function build_csv_record($col_list, $record)
{
    $line      = '';
    $separator = get_csv_separator();
    foreach ($col_list as $col) {
        $line .= tocsv($record[$col], $separator) . $separator;
    }
    $line = substr($line, 0, -1);
    return $line;
}

function display_exported_fields($col_list, $lbl_list, $dsc_list, $sample_val, $mand_list = false)
{
    global $Language;

    $title_arr   = [];
    $title_arr[] = $Language->getText('project_export_utils', 'label');
    $title_arr[] = $Language->getText('project_export_utils', 'sample_val');
    $title_arr[] = $Language->getText('project_admin_editugroup', 'desc');

    $purifier = Codendi_HTMLPurifier::instance();

    echo html_build_list_table_top($title_arr);
    $cnt = 0;
    foreach ($col_list as $col) {
        $star = (($mand_list && isset($mand_list[$col]) && $mand_list[$col]) ? ' <span class="highlight"><big>*</big></b></span>' : '');
        echo '<tr class="' . util_get_alt_row_color($cnt++) . '">' .
        '<td><b>' . $lbl_list[$col] . '</b>' . $star .
        '</td><td>' . nl2br($purifier->purify($sample_val[$col])) . '</td><td>' . $purifier->purify($dsc_list[$col]) . '</td></tr>';
    }

    echo '</table>';
}

function pick_a_record_at_random($result, $numrows, $col_list)
{
    /* return a record from a result set at random using the column
         list passed as an argument */

    $record = [];

    // If there is an item  available pick one at random
    // and display Sample values.
    if ($result && $numrows > 0) {
        $pickone = ($numrows <= 1 ? 0 : random_int(0, $numrows - 1));
    }

    // Build the array with the record picked at random
    $record = [];
    foreach ($col_list as $col) {
        $record[$col] = db_result($result, $pickone, $col);
    }

    return $record;
}

function prepare_textarea($textarea)
{
    // Turn all HTML entities in ASCII and remove all \r characters
    // because even MS Office apps don't like it in text cells (Excel)
    return( str_replace(chr(13), "", util_unconvert_htmlspecialchars($textarea)) );
}

/**
 * Prepare the column values in the artifact record
 *
 * @param ArtifactType (tracker) $at the tracker the artifact to prepare blelong to
 * @param ArtifactField[] $fields the fields of the artifact to export
 * @param int $group_artifact_id the tracker ID
 * @param array $record array 'field_name' => 'field_value'
 * @param string $export type of export ('csv' or 'database' : for date format, csv will take user preference, wheareas for database the format will be mysql format.)
 */
function prepare_artifact_record($at, $fields, $group_artifact_id, &$record, $export)
{
    global $datetime_fmt,$sys_lf,$Language;
    /* $record:
       Input: a row from the artifact table (passed by reference.
       Output: the same row with values transformed for export
    */

    $line = '';
    foreach ($fields as $field) {
        if ($field->isSelectBox() || $field->isMultiSelectBox()) {
            $values = [];
            if ($field->isStandardField()) {
                $values[] = $record[$field->getName()];
            } else {
                $values = $field->getValues($record['artifact_id']);
            }
            $label_values              = $field->getLabelValues($group_artifact_id, $values);
            $record[$field->getName()] = SimpleSanitizer::unsanitize(join(",", $label_values));
        } elseif ($field->isTextArea() || ($field->isTextField() && $field->getDataType() == $field->DATATYPE_TEXT)) {
            // all text fields converted from HTML to ASCII
            $record[$field->getName()] = prepare_textarea($record[$field->getName()]);
        } elseif ($field->isDateField()) {
            // replace the date fields (unix time) with human readable dates that
            // is also accepted as a valid format in future import
            if ($record[$field->getName()] == '') {
          // if date undefined then set datetime to 0. Ideally should
          // NULL as well but if we pass NULL it is interpreted as a string
          // later in the process
                $record[$field->getName()] = '0';
            } else {
                if ($export == 'database') {
                    $record[$field->getName()] = format_date($datetime_fmt, $record[$field->getName()]);
                } else {
                    $record[$field->getName()] = format_date(util_get_user_preferences_export_datefmt(), $record[$field->getName()]);
                }
            }
        } elseif ($field->isFloat()) {
            $record[$field->getName()] = number_format($record[$field->getName()], 2);
        }
    }

    // Follow ups
    $ah                   = new ArtifactHtml($at, $record['artifact_id']);
    $sys_lf_sav           = $sys_lf;
    $sys_lf               = "\n";
    $record['follow_ups'] = $ah->showFollowUpComments($at->Group->getID(), true, Artifact::OUTPUT_EXPORT);
    $sys_lf               = $sys_lf_sav;

    // Dependencies
    $result    = $ah->getDependencies();
    $rows      = db_numrows($result);
    $dependent = '';
    for ($i = 0; $i < $rows; $i++) {
        $dependent_on_artifact_id = db_result($result, $i, 'is_dependent_on_artifact_id');
        $dependent               .= $dependent_on_artifact_id . ",";
    }
    $record['is_dependent_on'] = (($dependent !== '') ? substr($dependent, 0, strlen($dependent) - 1) : $Language->getText('global', 'none'));

    //CC
    $cc_list = $ah->getCCList();
    $rows    = db_numrows($cc_list);
    $cc      = [];
    for ($i = 0; $i < $rows; $i++) {
        $cc_email = db_result($cc_list, $i, 'email');
        $cc[]     = $cc_email;
    }
    $record['cc'] = implode(',', $cc);
}

    /**
         *  Prepare the column values in the access logs  record
         *  @param: group_id: group id
         *  @param: record: a row from the access logs table (passed by  reference).
         *
         *  @return: the same row with values transformed for database export
         */

function prepare_access_logs_record($group_id, &$record)
{
    if (isset($record['time'])) {
        $time                 = $record['time'];
        $record['time']       = format_date('Y-m-d', $time);
        $record['local_time'] = date("H:i", $time);
    }
    $um   = UserManager::instance();
    $user = $um->getUserByUserName($record['user_name']);
    if ($user) {
        $record['user'] = $user->getRealName() . "(" . $user->getUserName() . ")";
    } else {
        $record['user'] = 'N/A';
    }
    //for svn access logs
    if (isset($record['day'])) {
        $day           = $record['day'];
        $record['day'] = substr($day, 0, 4) . "-" . substr($day, 4, 2) . "-" . substr($day, 6, 2);
    }
}
