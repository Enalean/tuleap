<?php
/**
 * Copyright (c) Enalean, 2013. All Rights Reserved.
 * Copyright (c) STMicroelectronics, 2004-2011. All rights reserved
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

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/project_admin_utils.php';
require_once __DIR__ . '/../export/project_export_utils.php';
$GLOBALS['HTML']->includeCalendarScripts();


$request = HTTPRequest::instance();

// Check if group_id is valid
$vGroupId = new Valid_GroupId();
$vGroupId->required();
if ($request->valid($vGroupId)) {
    $group_id = $request->get('group_id');
} else {
    exit_no_group();
}

$eventsList = array('any', 'event_permission', 'event_project', 'event_ug', 'event_user', 'event_others');
$validEvents = new Valid_WhiteList('events_box', $eventsList);
$event = $request->getValidated('events_box', $validEvents, null);
if (!$event) {
    //Check event value within pagination process
    $validPaginationEvents = new Valid_WhiteList('event', $eventsList);
    $event = $request->getValidated('event', $validPaginationEvents, null);
}
$validSubEvents = new Valid_String('sub_events_box');
if ($request->validArray($validSubEvents)) {
    $subEvents      = array();
    $subEventsArray = $request->get('sub_events_box');
    foreach ($subEventsArray as $element) {
        $subEvents[$element] = true;
    }
} elseif ($request->exist('subEventsBox')) {
    $validPaginationSubEvents = new Valid_String('subEventsBox');
    if ($request->valid($validPaginationSubEvents)) {
        $subEvents       = array();
        $subEventsString = $request->get('subEventsBox');
        $subEventsBox    = explode(",", $subEventsString);
        foreach ($subEventsBox as $element) {
            $subEvents[$element] = true;
        }
    }
} else {
    $subEvents = null;
}

$validValue = new Valid_String('value');
if ($request->valid($validValue)) {
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
    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_utils', 'verify_start_date'));
    $startDate = null;
}

$vEndDate = new Valid('end');
$vEndDate->addRule(new Rule_Date());
$vEndDate->required();
$endDate = $request->get('end');
if ($request->valid($vEndDate)) {
    $endDate = $request->get('end');
} elseif (!empty($endDate)) {
    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_utils', 'verify_end_date'));
    $endDate = null;
}

if ($startDate && $endDate && (strtotime($startDate) > strtotime($endDate))) {
    $GLOBALS['Response']->addFeedback('error', $Language->getText('project_admin_utils', 'verify_dates'));
    $startDate = null;
    $endDate = null;
}

$validBy = new Valid_String('by');
if ($request->valid($validBy)) {
    $by = $request->get('by');
} else {
    $by = null;
}

$offset = $request->getValidated('offset', 'uint', 0);
if (!$offset || $offset < 0) {
    $offset = 0;
}
$limit  = 50;
