<?php
/**
 * Copyright Enalean (c) 2012 - Present. All rights reserved.
 * SourceForge: Breaking Down the Barriers to Open Source Development
 * Copyright 1999-2000 (c) The SourceForge Crew
 *
 * Tuleap and Enalean names and logos are registered trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

use Tuleap\Project\Admin\GetProjectHistoryEntryValue;
use Tuleap\Project\Admin\Navigation\HeaderNavigationDisplayer;

function project_admin_header($params, $current_pane_shortname)
{
    global $group_id;

    $params['toptab'] = 'admin';
    $params['group']  = $group_id;
    $title            = $params['title'];

    $navigation_displayer = new HeaderNavigationDisplayer();
    $project              = ProjectManager::instance()->getProject($group_id);
    $navigation_displayer->displayFlamingParrotNavigation($title, $project, $current_pane_shortname);
}

/*

    Standard footer to be used on all /project/admin/* pages

*/

function project_admin_footer($params)
{
    TemplateRendererFactory::build()
        ->getRenderer(ForgeConfig::get('tuleap_dir') . '/src/templates/project')
        ->renderToPage('end-project-admin-content', array());
    site_project_footer($params);
}

/**
 * Builds the group history filter
 *
 * @param String  $event        Events category used to filter results
 * @param String  $subEventsBox Event used to filter results
 * @param String  $value        Value used to filter results
 * @param int $startDate Start date used to filter results
 * @param int $endDate End date used to filter results
 * @param String  $by           User name used to filter results
 *
 * @return String
 */
function build_grouphistory_filter($event = null, $subEventsBox = null, $value = null, $startDate = null, $endDate = null, $by = null)
{
    $data_access = CodendiDataAccess::instance();
    $filter      = '';
    if (!empty($by)) {
        $user_helper = UserHelper::instance();
        $filter     .= $user_helper->getUserFilter($by);
    }
    if (!empty($startDate)) {
        list($timestamp,) = util_date_to_unixtime($startDate);
        $filter .= " AND group_history.date > " . $data_access->escapeInt($timestamp);
    }
    if (!empty($endDate)) {
        list($timestamp,) = util_date_to_unixtime($endDate);
        // Add 23:59:59 to timestamp
        $timestamp = $timestamp + 86399;
        $filter .= " AND group_history.date < " . $data_access->escapeInt($timestamp);
    }
    if (!empty($value)) {
        //all_users need specific treatement
        if (stristr($value, $GLOBALS["Language"]->getText('project_ugroup', 'ugroup_anonymous_users_name_key'))) {
            $value =  'ugroup_anonymous_users_name_key';
        }
        $filter .= " AND group_history.old_value LIKE " . $data_access->quoteLikeValueSurround($value);
    }
    if (!empty($event) && strcmp($event, 'any')) {
        $filter .= " AND ( 0 ";
        if (!empty($subEventsBox)) {
            foreach ($subEventsBox as $key => $value) {
                $filter .= " OR group_history.field_name LIKE " . $data_access->quoteLikeValueSuffix($key);
            }
        } else {
            $subEventsList = get_history_entries();
            foreach ($subEventsList[$event] as $key => $value) {
                $filter .= " OR group_history.field_name LIKE " . $data_access->quoteLikeValueSuffix($value);
            }
        }
        $filter .= " ) ";
    }
    return $filter;
}

/**
 * Returns the events used in project history grouped by category
 *
 * @return Array
 */
function get_history_entries()
{
    $subEvents = array('event_permission' => array('perm_reset_for_field',
                                                   'perm_granted_for_field',
                                                   'perm_reset_for_tracker',
                                                   'perm_granted_for_tracker',
                                                   'perm_reset_for_package',
                                                   'perm_granted_for_package',
                                                   'perm_reset_for_release',
                                                   'perm_granted_for_release',
                                                   'perm_reset_for_wiki',
                                                   'perm_granted_for_wiki',
                                                   'perm_reset_for_wikipage',
                                                   'perm_granted_for_wikipage',
                                                   'perm_reset_for_wikiattachment',
                                                   'perm_granted_for_wikiattachment',
                                                   'perm_reset_for_object',
                                                   'perm_granted_for_object',
                                                   'perm_reset_for_docgroup',
                                                   'perm_granted_for_docgroup'),
                       'event_project' =>    array('rename_done',
                                                   'rename_with_error',
                                                   'add_custom_quota',
                                                   'restore_default_quota',
                                                   'approved',
                                                   'deleted',
                                                   'rename_request',
                                                   'access',
                                                   'group_type',
                                                   'http_domain',
                                                   'unix_box',
                                                   'changed_public_info',
                                                   'changed_trove',
                                                   'membership_request_updated',
                                                   'import',
                                                   'mass_change',
                                                   'status',
                                                   'frs_self_add_monitor_package',
                                                   'frs_add_monitor_package',
                                                   'frs_stop_monitor_package'),
                       'event_ug' =>         array('upd_ug',
                                                   'del_ug',
                                                   'changed_member_perm',
                                                   'ugroup_add_binding',
                                                   'ugroup_remove_binding'),
                       'event_user' =>       array('changed_personal_email_notif',
                                                   'added_user',
                                                   'removed_user'),
                       'event_others' =>     array('changed_bts_form_message',
                                                   'changed_bts_allow_anon',
                                                   'changed_patch_mgr_settings',
                                                   'changed_task_mgr_other_settings',
                                                   'changed_sr_settings'),
                       'choose' =>           array('choose_event'));

    //Plugins related events should be filled using the hook
    $params = array('subEvents' => &$subEvents);
    $em = EventManager::instance();
    $em->processEvent('fill_project_history_sub_events', $params);
    return $subEvents;
}

/**
 * Display the retrieved reult set
 *
 * @param int $group_id Id of the project
 * @param Array   $res      Contains the retrieved results
 * @param bool $export Switch CSV export mode or HTML display
 * @param int $i   Line number indicator
 *
 * @return string html
 */
function displayProjectHistoryResults($group_id, $res, $export = false, &$i = 1)
{
    global $Language;
    $html = '';

    $hp = Codendi_HTMLPurifier::instance();

    while ($row = $res['history']->getRow()) {
        $field = $row['field_name'];

        // see if there are any arguments after the message key
        // format is "msg_key ## arg1||arg2||...
        // If msg_key cannot be found in the localized message
        // catalog then display the msg has is because this is very
        // likely a legacy message (pre-localization version)
        $arr_args = '';
        if (strpos($field, " %% ") !== false) {
            list($msg_key, $args) = explode(" %% ", $field);
            if ($args) {
                $arr_args = explode('||', $args);
            }
        } else {
            $msg_key = $field;
            $arr_args = "";
        }
        $msg = $Language->getText('project_admin_utils', $msg_key, $arr_args);
        if (!(strpos($msg, "*** Unkown msg") === false)) {
            $msg = $field;
        }

        if (!$export) {
            $html .= '<TR class="' . html_get_alt_row_color($i++) . '"><TD>' . $hp->purify($msg, CODENDI_PURIFIER_BASIC, $group_id) . '</TD><TD>';
        }
        $val = $row['old_value'];
        //Translate dynamic ugroup name for permission entries
        if (strstr($msg_key, "perm_granted_for_") || strstr($msg_key, "perm_reset_for_") || strstr($msg_key, "membership_request_updated")) {
            $ugroupList = explode(",", $val);
            $val = '';
            foreach ($ugroupList as $ugroup) {
                if ($val === '') {
                    $val .= ', ';
                }
                $val .= util_translate_name_ugroup($ugroup);
            }
        } elseif ($msg_key == "group_type") {
            $template = TemplateSingleton::instance();
            $val = $template->getLabel($val);
        }
        $event = new GetProjectHistoryEntryValue($row, $val);
        EventManager::instance()->processEvent($event);
        $val = $event->getValue();

        if ($export) {
            $documents_body = array ('event' => $hp->purify($msg, CODENDI_PURIFIER_BASIC, $group_id),
                                     'val'   => $hp->purify($val),
                                     'date'  => format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['date']),
                                     'by'    => UserHelper::instance()->getDisplayNameFromUserName($row['user_name']));
            require_once __DIR__ . '/../export/project_export_utils.php';
            $html .= build_csv_record(array('event', 'val', 'date', 'by'), $documents_body) . "\n";
        } else {
            $html .= $hp->purify($val, CODENDI_PURIFIER_BASIC);
            $user = UserManager::instance()->getUserByUserName($row['user_name']);
            $html .= '</TD><TD>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $row['date']) .
            '</TD><TD>' . UserHelper::instance()->getLinkOnUser($user) . '</TD></TR>';
        }
    }

    return $html;
}

/**
 * Nicely html-formatted output of this group's audit trail
 *
 * @param int $group_id Id of the project
 * @param int $offset Offset used for pagination
 * @param int $limit Number of events by page
 * @param String  $event        Events category used to filter results
 * @param array|null  $subEventsBox Event used to filter results
 * @param String  $value        Value used to filter results
 * @param int $startDate Start date used to filter results
 * @param int $endDate End date used to filter results
 * @param String  $by           User name used to filter results
 *
 * @return void
 */
function show_grouphistory($group_id, $offset, $limit, $event = null, $subEventsBox = null, $value = null, $startDate = null, $endDate = null, $by = null)
{
    /*
     show the group_history rows that are relevant to
     this group_id
     */
    global $Language;

    $dao            = new ProjectHistoryDao(CodendiDataAccess::instance());
    $history_filter = build_grouphistory_filter($event, $subEventsBox, $value, $startDate, $endDate, $by);
    $history_rows   = $dao->groupGetHistory($offset, $limit, $group_id, $history_filter);

    if (isset($subEventsBox)) {
        $subEventsString = implode(",", array_keys($subEventsBox));
        $forwardSubEvents = '&event=' . $event . '&subEventsBox=' . $subEventsString;
    } else {
        $forwardSubEvents = '&event=' . $event;
    }

    $renderer = TemplateRendererFactory::build()->getRenderer(ForgeConfig::get('codendi_dir') . '/src/templates/project/');

    //Event select Box
    $events = array(
        'any'              => $GLOBALS["Language"]->getText('global', 'any'),
        'event_permission' => $GLOBALS["Language"]->getText("project_admin_utils", "event_permission"),
        'event_project'    => $GLOBALS["Language"]->getText("project_admin_utils", "event_project"),
        'event_user'       => $GLOBALS["Language"]->getText("project_admin_utils", "event_user"),
        'event_ug'         => $GLOBALS["Language"]->getText("project_admin_utils", "event_ug"),
        'event_others'     => $GLOBALS["Language"]->getText("project_admin_utils", "event_others")
    );

    $select = new HTML_Element_Selectbox('', 'events_box', '');
    $select->setId('events_box');
    $select->addMultipleOptions($events, $event);

    $title_arr   = array();
    $title_arr[] = $Language->getText('project_admin_utils', 'event');
    $title_arr[] = $Language->getText('project_admin_utils', 'val');
    $title_arr[] = $Language->getText('project_admin_utils', 'date');
    $title_arr[] = $Language->getText('global', 'by');

    $index = 1;

    $presenter = new ProjectHistoryPresenter(
        $group_id,
        $select->renderValue(),
        $value,
        $startDate,
        $endDate,
        $by,
        $history_rows,
        $title_arr,
        $index,
        $offset,
        $limit,
        $forwardSubEvents
    );
    echo $renderer->renderToString('project_history', $presenter);

    $history_entries   = get_history_entries();
    $translated_events = [];
    foreach ($history_entries as $sub_event_category => $sub_events) {
        $translated_sub_events = [];
        foreach ($sub_events as $sub_event) {
            $translated_sub_events[$sub_event] = $GLOBALS['Language']->getText('project_admin_utils', $sub_event);
        }
        $translated_events[$sub_event_category] = $translated_sub_events;
    }

    if (isset($subEventsString)) {
        $selectedSubEvents = explode(",", $subEventsString);
        foreach ($selectedSubEvents as $element) {
            $subEventsBox[] = $element;
        }
    }

    $translated_selected_sub_events = [];
    if ($subEventsBox !== null) {
        foreach (array_keys($subEventsBox) as $sub_event) {
            if (is_string($sub_event)) {
                $translated_selected_sub_events[$sub_event] = $GLOBALS['Language']->getText('project_admin_utils', $sub_event);
            }
        }
    }

    $js = "new UserAutoCompleter('by', '" . util_get_dir_image_theme() . "', true);
           new ProjectHistory(" . json_encode($translated_events) . ", " . json_encode($translated_selected_sub_events) . ");";

    $GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/codendi/ProjectHistory.js');
    $GLOBALS['Response']->includeFooterJavascriptSnippet($js);
}

/**
 * Export project history to a csv file
 *
 * @param int $group_id Id of the project
 * @param String  $event        Events category used to filter results
 * @param String  $subEventsBox Event used to filter results
 * @param String  $value        Value used to filter results
 * @param int $startDate Start date used to filter results
 * @param int $endDate End date used to filter results
 * @param String  $by           User name used to filter results
 *
 * @return void
 */
function export_grouphistory($group_id, $event = null, $subEventsBox = null, $value = null, $startDate = null, $endDate = null, $by = null)
{
    global $Language;

    header('Content-Type: text/csv');
    header('Content-Disposition: filename=project_history.csv');

    $eol = "\n";

    $col_list = array('event', 'val', 'date', 'by');
    $documents_title = array ('event' => $Language->getText('project_admin_utils', 'event'),
                              'val'   => $Language->getText('project_admin_utils', 'val'),
                              'date'  => $Language->getText('project_admin_utils', 'date'),
                              'by'    => $Language->getText('global', 'by'));
    echo build_csv_header($col_list, $documents_title) . $eol;

    $dao = new ProjectHistoryDao(CodendiDataAccess::instance());
    $history_filter = build_grouphistory_filter($event, $subEventsBox, $value, $startDate, $endDate, $by);
    $res = $dao->groupGetHistory(0, 0, $group_id, $history_filter);

    if ($res['numrows'] > 0) {
        echo displayProjectHistoryResults($group_id, $res, true);
    }
    echo build_csv_header($col_list, array()) . $eol;
}
