<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Originally by to the SourceForge Team,1999-2000
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

//require_once('common/tracker/ArtifactFactory.class.php');

// HTTP GET arguments
//
// $group_id = The group ID
// $atid = The group artifact ID (artifact type id)
// $set = <custom|my|open> : different types of display
// $advsrch = <0|1> : advanced search or simple simple
// $msort = <0|1> : multi column sort activated
// $report_id = the report ID
// <field_name>[] = <default value> : list of each field and its default values associed
// $chunksz = default 50 : number of artifact displayed in the page
// $morder = comma separated list of sort criteria followed by < for DESC and > for ASC order
// $order = last sort criteria selected in the UI
// $offset = the first element of the query result to display (used for the sql limit)
// $pv = printable version (=1)
//
//  make sure this person has permission to view artifacts
if (! $ath->userCanView()) {
    exit_permission_denied();
}

// Check if this tracker is valid (not deleted)
if (! $ath->isValid()) {
    exit_error($Language->getText('global', 'error'), $Language->getText('tracker_add', 'invalid'));
}

//  If the report type is not defined then get it from the user preferences.
//  If it is set then update the user preference.  Also initialize the
//  artifact report structures.
if (user_isloggedin()) {
    if (! $request->exist('report_id')) {
        $report_id = user_get_preference('artifact_browse_report' . $atid);
        if ($report_id == "") {
            // Default value
            $arf       = new ArtifactReportFactory();
            $report_id = $arf->getDefaultReport($atid);
            if ($report_id == null) {
                $report_id = 100;
            }
        }
    } else {
        $report_id = $request->get('report_id');
        if ($report_id != user_get_preference('artifact_browse_report' . $atid)) {
            user_set_preference('artifact_browse_report' . $atid, $report_id);
            user_del_preference('artifact_browse_order' . $atid);
            user_del_preference('artifact_brow_cust' . $atid);
            $GLOBALS['Response']->redirect('?atid=' . $atid . '&group_id=' . $group_id);
        }
    }
} else {
    if (! $request->exist('report_id')) {
            $arf       = new ArtifactReportFactory();
            $report_id = $arf->getDefaultReport($atid);
    } else {
            $report_id = $request->get('report_id');
    }
}

// Number of artifacts displayed on screen in one chunk.
// Default 50
$chunksz = (int) $request->get('chunksz');
if (! $chunksz) {
    $chunksz = 50;
}

// Make sure offset values, search and multisort flags are defined
// and have a correct value
$offset = $request->get('offset');
if (! $offset || $offset < 0) {
    $offset = 0;
}
$advsrch = $request->get('advsrch');
if ($advsrch != 1) {
    $advsrch = 0;
}
$msort = $request->get('msort');
if ($msort != 1) {
    $msort = 0;
}
$pv = $request->get('pv');
if (! $pv) {
    $pv = 0;
}

/* ==================================================
  If the report type is not defined then get it from the user preferences.
  If it is set then update the user preference.  Also initialize the
  tracker report structures.
  ================================================== */
if (user_isloggedin()) {
    if (! isset($report_id)) {
        $report_id = user_get_preference('artifact_browse_report' . $atid);
    } else {
        if ($report_id != user_get_preference('artifact_browse_report' . $atid)) {
            user_set_preference('artifact_browse_report' . $atid, $report_id);
        }
    }
}

// If still not defined then force it to system 'Default' report
if (! isset($report_id) || ! $report_id) {
    $report_id = 100;
}


// Create factories
$report_fact = new ArtifactReportFactory();

// Retrieve HTTP GET variables and store them in $prefs array
$prefs = $art_field_fact->extractFieldList(false);

// Create the HTML report object
$art_report_html = $report_fact->getArtifactReportHtml($report_id, $atid);
// {{{ (SR #832) If it does not exist, use default report instead.
if (! $art_report_html) {
    $report_id = 100;
    if (user_isloggedin()) {
        user_set_preference('artifact_browse_report' . $atid, $report_id);
    }
    $art_report_html = $report_fact->getArtifactReportHtml($report_id, $atid);
}
// }}}

/* ==================================================
   Make sure all URL arguments are captured as array. For simple
   search they'll be arrays with only one element at index 0 (this
   will avoid to deal with scalar in simple search and array in
   advanced which would greatly complexifies the code)
 ================================================== */
$all_prefs = [];
foreach ($prefs as $field => $value_id) {
    $field_object = $art_field_fact->getFieldFromName($field);
    if (! is_array($value_id)) {
        unset($prefs[$field]);
        $all_prefs[$field][] = ($field_object && $field_object->isDateField()) ? $value_id : htmlspecialchars($value_id);
        //echo '<br> DBG Setting $prefs['.$field.'] [] = '.$value_id;
    } else {
        $all_prefs[$field] = $value_id;
        //echo '<br> DBG $prefs['.$field.'] = ('.implode(',',$value_id).')';
    }

    if (($field_object) && ($field_object->isDateField())) {
        if ($advsrch) {
            $field_end = $field . '_end';
            if (! is_array($request->get($field_end))) {
                $all_prefs[$field_end] = [$request->get($field_end)];
            } else {
                $all_prefs[$field_end] = $request->get($field_end);
            }
            //echo 'DBG Setting $prefs['.$field.'_end]= '.$prefs[$field.'_end'].'<br>';
        } else {
            $field_op = $field . '_op';
            if (! $request->get($field_op)) {
                $all_prefs[$field_op] = ['>'];
            } else {
                $all_prefs[$field_op] = [$request->get($field_op)];
            }
            //echo 'DBG Setting $prefs['.$field.'_op]= '.$prefs[$field.'_op'].'<br>';
        }
    }
}
$prefs = $all_prefs;
/* ==================================================
   Memorize order by field as a user preference if explicitly specified.

   $morder = comma separated list of sort criteria followed by - for
     DESC and + for ASC order
   $order = last sort criteria selected in the UI
   $msort = 1 if multicolumn sort activated.
  ================================================== */
//echo "<br>DBG \$morder at top: [$morder ]";
//   if morder not defined then reuse the one in preferences
$morder = '';
if (user_isloggedin()) {
    if (! $request->exist('morder')) {
        $morder = user_get_preference('artifact_browse_order' . $atid);
    } else {
        $morder = $request->get('morder');
    }
}

if ($request->exist('order')) {
    $order = $request->get('order');
    if ($order != '') {
        // Add the criteria to the list of existing ones
        $morder = $art_report_html->addSortCriteria($morder, $order, $msort);
    } else {
        // reset list of sort criteria
        $morder = '';
    }
}

if (isset($morder)) {
    if (user_isloggedin()) {
        if ($morder != user_get_preference('artifact_browse_order' . $atid)) {
            user_set_preference('artifact_browse_order' . $atid, $morder);
        }
    }
} else {
    $morder = '';
}

//echo "<BR> DBG Order by = $morder";



/* ==================================================
  Now see what type of artifact set is requested (set is one of none,
  'my', 'open', 'custom').
    - if no set is passed in, see if a preference was set ('custom' set).
    - if no preference and logged in then use 'all' set (see all artifacts)
    - if no preference and not logged in the use 'open' set
     (Prefs is a string of the form  &field1[]=value_id1&field2[]=value_id2&.... )
  ================================================== */
if (! $request->exist('set')) {
    if (user_isloggedin()) {
        $custom_pref = user_get_preference('artifact_brow_cust' . $atid);

        if ($custom_pref) {
            $pref_arr = explode('&', substr($custom_pref, 1));
            foreach ($pref_arr as $expr) {
          // Extract left and right parts of the assignment
          // and remove the '[]' array symbol from the left part
                list($field,$value_id) = explode('=', $expr);
                $field                 = str_replace('[]', '', $field);
                if ($field == 'advsrch') {
                    $advsrch = ($value_id ? 1 : 0);
                } elseif ($field == 'msort') {
                    $msort = ($value_id ? 1 : 0);
                } elseif ($field == 'chunksz') {
                    $chunksz = $value_id;
                } elseif ($field == 'report_id') {
                    $report_id = $value_id;
                } else {
                    $prefs[$field][] = urldecode($value_id);
                }

          //echo '<br>DBG restoring prefs : $prefs['.$field.'] []='.$value_id;
            }
            $set = 'custom';
        } else {
            $set = 'all';
        }
    } else {
        $set = 'open';
    }
} else {
    $validSet = new Valid_WhiteList('set', ['my', 'open', 'custom', 'all']);
    $set      = $request->getValidated('set', $validSet, '');
}

if ($set == 'my') {
    /*
      My artifacts - backwards compat can be removed 9/10
    */
    $prefs['status_id'][] = 1; // Open status
    // Check if the current user is in the assigned_to list
    $field_object       = $art_field_fact->getFieldFromName('assigned_to');
    $field_object_multi = $art_field_fact->getFieldFromName('multi_assigned_to');
    $user_id            = UserManager::instance()->getCurrentUser()->getId();
    if (($field_object) && ($field_object->checkValueInPredefinedValues($atid, $user_id))) {
        $prefs['assigned_to'][] = $user_id;
    } elseif (($field_object_multi) && ($field_object_multi->checkValueInPredefinedValues($atid, $user_id))) {
        $prefs['multi_assigned_to'][] = $user_id;
    } else {
      // Any value
        $prefs['assigned_to'][]       = 0;
        $prefs['multi_assigned_to'][] = 0;
    }
} elseif ($set == 'custom') {
    // Get the list of artifact fields used in the form (they are in the URL - GET method)
    // and then build the preferences array accordingly
    // Exclude the group_id parameter
    $pref_stg = "";
    foreach ($prefs as $field => $arr_val) {
        while ($value_id = current($arr_val)) {
            next($arr_val);
            if (! is_array($value_id)) {
                // Don't add [] for date operator (not really a field)
                if (substr($field, 0 - strlen('_op')) == '_op') {
                    $pref_stg .= '&' . $field . '=' . urlencode($value_id);
                } else {
                    $pref_stg .= '&' . $field . '[]=' . urlencode($value_id);
                }
            } else {
                $pref_stg .= '&' . $field . '[]=' . $value_id;
            }
        }

        // build part of the HTML title of this page for more friendly bookmarking
        // Do not add the criteria in the header if value is "Any"
        if ($value_id != 0 && is_object($field)) {
            $hdr .= $Language->getText('global', 'by') . $field->getLabel() . ': ' .
            $field->getValue($group_id, $value_id);
        }
    }
    $pref_stg .= '&advsrch=' . ($advsrch ? 1 : 0);
    $pref_stg .= '&msort=' . ($msort ? 1 : 0);
    $pref_stg .= '&chunksz=' . (int) $chunksz;
    $pref_stg .= '&report_id=' . (int) $report_id;

    if ($pref_stg != user_get_preference('artifact_brow_cust' . $atid)) {
        //echo "<br> DBG setting pref = $pref_stg";
        user_set_preference('artifact_brow_cust' . $atid, $pref_stg);
    }
} elseif ($set == 'all') {
    // Any value for very field
    $prefs['status_id'][]         = 0;
    $prefs['assigned_to'][]       = 0;
    $prefs['multi_assigned_to'][] = 0;
} else {
    // Open artifacts - backwards compat can be removed 9/10
    $prefs['status_id'][] = 1;
    // Any value for assigned to
    $prefs['assigned_to'][]       = 0;
    $prefs['multi_assigned_to'][] = 0;
}


/* ==================================================
   At this point make sure that all paramaters are defined
   as well as all the arguments that serves as selection criteria
   If not defined then defaults to ANY (0)
  ================================================== */
$_title     = $group->getPublicName() . ': \'' . $ath->getName() . '\' ';
$masschange = isset($masschange) && $masschange;
if ($pv != 2) {
    if ($masschange) {
        $_title .=  $Language->getText('tracker_masschange', 'mass_change_report');
    } else {
        $_title .= $Language->getText('tracker_browse', 'search_report');
    }
}

$params = ['title' => $_title,
    'titlevals' => [$ath->getName()],
    'pagename' => 'tracker_browse',
    'atid' => $ath->getID(),
    'pv' => $pv,
];


// Display the menus
$ath->header($params);
echo '<div id="tracker_toolbar_clear"></div>';

$em          = EventManager::instance();
$pref_params = ['group_id'   => $group_id,
    'atid'       => $atid,
    'report_id'  => $report_id,
    'prefs'      => $prefs,
    'morder'     => $morder,
    'chunksz'    => $chunksz,
    'advsrch'    => $advsrch,
    'msort'      => $msort,
    'offset'     => $offset,
    'set'        => $set,
];
$em->processEvent('tracker_user_pref', $pref_params);

// Display the artifact items according to all the parameters
$art_report_html->displayReport($prefs, $group_id, $report_id, $set, $advsrch, $msort, $morder, (isset($order) ? $order : false), isset($pref_stg) ? $pref_stg : "", $offset, $chunksz, $pv, $masschange);
$ath->footer($params);
