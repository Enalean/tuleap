<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2012 – Present. All Rights Reserved.
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

use Tuleap\SystemEvent\GetSystemEventQueuesEvent;

require_once __DIR__ . '/../../include/pre.php';
require_once __DIR__ . '/adminPresenter.class.php';

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$token = new CSRFSynchronizerToken('/admin/system_events/');
$se    = SystemEventManager::instance();

$request_queue = $request->get('queue');

$purifier = Codendi_HTMLPurifier::instance();

$event = new GetSystemEventQueuesEvent(
    [
        SystemEventQueue::NAME => new SystemEventQueue(),
    ]
);
EventManager::instance()->processEvent(
    $event
);

$available_queues = $event->getAvailableQueues();

$selected_queue_name = SystemEventQueue::NAME;
if (isset($available_queues[$request_queue])) {
    $selected_queue_name = $request_queue;
}

$offset          = $request->get('offset') && ! $request->exist('filter') ? (int) $request->get('offset') : 0;
$limit           = 25;
$full            = true;
$selected_status = $request->get('filter_status');
$all_status      = [
    SystemEvent::STATUS_NEW,
    SystemEvent::STATUS_RUNNING,
    SystemEvent::STATUS_DONE,
    SystemEvent::STATUS_WARNING,
    SystemEvent::STATUS_ERROR,
];
$filter_status   = $all_status;
if (is_array($selected_status)) {
    if (in_array("0", $selected_status) || $selected_status === $all_status) {
        $selected_status = [];
    } else {
        $filter_status = array_intersect($selected_status, $filter_status);
    }
} else {
    $selected_status = [];
}

$filter_type     = $request->get('filter_type');
$filter_type_any = '0';

if (! $filter_type || (count($filter_type) === 1 && $filter_type[0] === $filter_type_any)) {
    $filter_type = [];
}

$all_types_by_queue = [];
foreach ($available_queues as $name => $queue) {
    $types = $se->getTypesForQueue($name);
    uksort($types, 'strnatcasecmp');
    $all_types_by_queue[$name] = $types;
}

$dao = new SystemEventDao();
if ($filter_type) {
    $filter_type = array_intersect($filter_type, $all_types_by_queue[$selected_queue_name]);
}

if (! $filter_type) {
    $filter_type = $all_types_by_queue[$selected_queue_name];
}

$matching_events = $dao->searchLastEvents($offset, $limit, $filter_status, $filter_type)
    ->instanciateWith([$se, 'getInstanceFromRow']);
$num_total_rows  = $dao->foundRows();

$events = [];
foreach ($matching_events as $event) {
    $events[] = new Tuleap\SystemEvent\SystemEventPresenter($event);
}

$default_params = [
    'filter_status' => $filter_status,
    'filter_type'   => $filter_type,
    'queue'         => $selected_queue_name,
];
$pagination     = new Tuleap\Layout\PaginationPresenter(
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
    $selected_status,
    $all_types_by_queue,
    $filter_type
);

$id_to_replay = $request->get('replay');
if ($id_to_replay) {
    $token->check();
    $se->replay($id_to_replay);
    $GLOBALS['Response']->redirect('/admin/system_events/?' . http_build_query(['offset' => $offset] + $default_params));
}

$title = $Language->getText('admin_system_events', 'title');

$include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($include_assets, 'site-admin-system-events.js'));

$renderer = new \Tuleap\Admin\AdminPageRenderer();
$renderer->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/system_events/',
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
