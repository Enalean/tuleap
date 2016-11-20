<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2012 – 2016. All Rights Reserved.
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

require_once 'pre.php';
require_once 'adminPresenter.class.php';
require_once 'common/dao/SystemEventsFollowersDao.class.php';
require_once 'common/include/Toggler.class.php';

session_require(array('group'=>'1', 'admin_flags'=>'A'));

$token  = new CSRFSynchronizerToken('/admin/system_events/');
$se     = SystemEventManager::instance();

$request_queue = $request->get('queue');

$id_to_replay = $request->get('replay');
if ($id_to_replay) {
    $token->check();
    $se->replay($id_to_replay);
    $GLOBALS['Response']->redirect('/admin/system_events/?queue='.$request_queue);
}

$purifier = Codendi_HTMLPurifier::instance();

$available_queues = array(
    SystemEventQueue::NAME => new SystemEventQueue()
);
EventManager::instance()->processEvent(
    Event::SYSTEM_EVENT_GET_CUSTOM_QUEUES,
    array('queues' => &$available_queues)
);

$selected_queue_name = SystemEventQueue::NAME;
if (isset($available_queues[$request_queue])) {
    $selected_queue_name = $request_queue;
}

$offset        = $request->get('offset') && !$request->exist('filter') ? (int)$request->get('offset') : 0;
$limit         = 25;
$full          = true;
$filter_status = $request->get('filter_status');
if (!$filter_status) {
    $filter_status = array(
        SystemEvent::STATUS_NEW,
        SystemEvent::STATUS_RUNNING,
        SystemEvent::STATUS_DONE,
        SystemEvent::STATUS_WARNING,
        SystemEvent::STATUS_ERROR,
    );
}
$filter_type     = $request->get('filter_type');
$filter_type_any = '0';

if (! $filter_type || (count($filter_type) === 1 && $filter_type[0] === $filter_type_any)) {
    $filter_type = array();
}


$all_types = $se->getTypesForQueue($selected_queue_name);
uksort($all_types, 'strnatcasecmp');

$dao = new SystemEventDao();
if ($filter_type) {
    $filter_type = array_intersect($filter_type, $all_types);
}

if (! $filter_type) {
    $filter_type = $all_types;
}

$matching_events = $dao->searchLastEvents($offset, $limit, $filter_status, $filter_type)
    ->instanciateWith(array($se, 'getInstanceFromRow'));
$num_total_rows = $dao->foundRows();

$events = array();
foreach ($matching_events as $event) {
    $events[] = new Tuleap\SystemEvent\SystemEventPresenter($event);
}

$default_params = array(
    'filter_status' => $filter_status,
    'filter_type'   => $filter_type,
    'queue'         => $selected_queue_name
);
$pagination = new Tuleap\Layout\PaginationPresenter(
    $limit,
    $offset,
    count($events),
    $num_total_rows,
    '/admin/system_events/',
    $default_params
);

$search = new Tuleap\SystemEvent\SystemEventSearchPresenter(
    $available_queues,
    $selected_queue_name,
    $filter_status,
    $all_types,
    $filter_type
);

$title = $Language->getText('admin_system_events', 'title');

$GLOBALS['HTML']->includeFooterJavascriptFile('/scripts/admin/system-events.js');
$renderer = new \Tuleap\Admin\AdminPageRenderer();
$renderer->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') .'/src/templates/admin/system_events/',
    'admin-system-events',
    new SystemEvents_adminPresenter(
        $title,
        $token,
        $events,
        $selected_queue_name,
        $search,
        $pagination
    )
);
