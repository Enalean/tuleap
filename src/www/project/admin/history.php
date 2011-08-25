<?php
//
// SourceForge: Breaking Down the Barriers to Open Source Development
// Copyright 1999-2000 (c) The SourceForge Crew
// http://sourceforge.net
//
// 

require_once('pre.php');    
require_once('www/project/admin/project_admin_utils.php');
require_once('www/project/export/project_export_utils.php');
$GLOBALS['HTML']->includeCalendarScripts();

session_require(array('group'=>$group_id,'admin_flags'=>'A'));

$request = HTTPRequest::instance();

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$validEvents = new Valid_WhiteList('events_box' ,array('Permissions',
                                                   'Project',
                                                   'Users',
                                                   'User Group',
                                                   'Others'));
$event = $request->getValidated('events_box', $validEvents, null);
if(!$event) {
    $event = $request->get('event');
}

$validSubEvents = new Valid_String('sub_events_box');
if($request->validArray($validSubEvents)) {
    $subEventsArray = $request->get('sub_events_box');
    foreach ($subEventsArray as $element) {
        $subEvents[$element] = true;
    }
} elseif ( $subEventsArray = $request->get('subEventsBox')) {
    $subEventsBox = explode(",", $subEventsArray);
    foreach ($subEventsBox as $element) {
        $subEvents[$element] = true;
    }
} else {
    $subEvents = null;
}

$validValue = new Valid_String('value');
if($request->valid($validValue)) {
    $value = $request->get('value');
} else {
    $value = null;
}

$vStartDate = new Valid('start');
$vStartDate->addRule(new Rule_Date());
$vStartDate->required();
$startDate = $request->get('start');
if ($request->valid($vStartDate)) {
    $startDate = $request->get('start');
} elseif (!empty($startDate)) {
    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_utils','verify_start_date'));
    $startDate = null;
}

$vEndDate = new Valid('end');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('end');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('end');
} elseif (!empty($endDate)) {
    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_utils','verify_end_date'));
    $endDate = null;
}

if ($startDate && $endDate && (strtotime($startDate) >= strtotime($endDate))) {
    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_utils','verify_dates'));
    $startDate = null;
    $endDate = null;
}

$validBy = new Valid_String('by');
if($request->valid($validBy)) {
    $by = $request->get('by');
} else {
    $by = null;
}

$offset = $request->getValidated('offset', 'uint', 0);
if ( !$offset || $offset < 0 ) {
    $offset = 0;
}
$limit  = 50;

if ($request->exist('export')) {
    export_grouphistory($group_id, $event, $subEvents, $value, $startDate, $endDate, $by);
    exit;
}

project_admin_header(array('title'=>$Language->getText('project_admin_history','proj_history'),'group'=>$group_id));

echo $Language->getText('project_admin_history','proj_change_log_msg');

$all_sub_events = $request->get('all_sub_events');

//for pagination
echo show_grouphistory($group_id, $offset, $limit, $event, $subEvents, $value, $startDate, $endDate, $by, $all_sub_events);

project_admin_footer(array());
?>
