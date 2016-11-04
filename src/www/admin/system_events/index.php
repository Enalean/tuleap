<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */
require_once 'pre.php';
require_once 'adminPresenter.class.php';
require_once 'common/dao/SystemEventsFollowersDao.class.php';
require_once 'common/include/Toggler.class.php';

session_require(array('group'=>'1', 'admin_flags'=>'A'));

$token  = new CSRFSynchronizerToken('/admin/system_events/');
$se     = SystemEventManager::instance();
$sefdao = new SystemEventsFollowersDao(CodendiDataAccess::instance());

$request_queue = $request->get('queue');

if ($new_followers = $request->get('new_followers')) {
    if (isset($new_followers['emails']) && $new_followers['emails']) {
        if (count($new_followers['types'])) {
            $sefdao->create($new_followers['emails'], implode(',', $new_followers['types']));
            $GLOBALS['Response']->redirect('/admin/system_events/?queue='.$request_queue);
        }
    }
}
if ($request->get('delete')) {
    $token->check();
    $sefdao->delete($request->get('delete'));
    $GLOBALS['Response']->redirect('/admin/system_events/?queue='.$request_queue);
}
if ($request->get('cancel')) {
    $GLOBALS['Response']->redirect('/admin/system_events/?queue='.$request_queue);
}
if ($request->get('save') && ($followers = $request->get('followers'))) {
    $token->check();
    list($id, $info) = each($followers);
    $sefdao->save($id, $info['emails'], implode(',', $info['types']));
    $GLOBALS['Response']->redirect('/admin/system_events/?queue='.$request_queue);
}
$id_to_replay = $request->get('replay');
if ($id_to_replay) {
    $token->check();
    $se->replay($id_to_replay);
    $GLOBALS['Response']->redirect('/admin/system_events/?queue='.$request_queue);
}

$hp           = Codendi_HTMLPurifier::instance();
$template_dir = ForgeConfig::get('codendi_dir') .'/src/templates/admin/system_events/';
$renderer     = TemplateRendererFactory::build()->getRenderer($template_dir);

$title = $Language->getText('admin_system_events', 'title');
$HTML->header(array('title' => $title, 'main_classes' => array('tlp-framed-vertically')));

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

$queue_links = array();
foreach ($available_queues as $queue) {
    $href = '?';
    if ($queue->getName() !== SystemEventQueue::NAME) {
        $href .= 'queue=' . $queue->getName();
    }

    $queue_links[] = array(
        'href'   => $href,
        'label'  => $queue->getLabel(),
        'active' => $selected_queue_name === $queue->getName()
    );
}

$offset        = $request->get('offset') && !$request->exist('filter') ? (int)$request->get('offset') : 0;
$limit         = 50;
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


$all_status = array(
    array(
        'label'   => SystemEvent::STATUS_NEW,
        'checked' => in_array(SystemEvent::STATUS_NEW, $filter_status)
    ),
    array(
        'label'   => SystemEvent::STATUS_RUNNING,
        'checked' => in_array(SystemEvent::STATUS_RUNNING, $filter_status)
    ),
    array(
        'label'   => SystemEvent::STATUS_DONE,
        'checked' => in_array(SystemEvent::STATUS_DONE, $filter_status)
    ),
    array(
        'label'   => SystemEvent::STATUS_WARNING,
        'checked' => in_array(SystemEvent::STATUS_WARNING, $filter_status)
    ),
    array(
        'label'   => SystemEvent::STATUS_ERROR,
        'checked' => in_array(SystemEvent::STATUS_ERROR, $filter_status)
    )
);


$types = $se->getTypesForQueue($selected_queue_name);
uksort($types, 'strnatcasecmp');
foreach(array_chunk($types, ceil(count($types) / 3)) as $col) {
    foreach ($col as $type) {
        $typesArray[] = array('value' => $type, 'text' => $type);
    }
}

$selectbox = html_build_multiple_select_box_from_array(
    $typesArray,
    "filter_type[]",
    array_values($filter_type),
    10,
    false,
    '',
    true,
    '',
    false,
    '',
    false
);

$events = $se->fetchLastEventsStatus($offset, $limit, $full, $filter_status, $filter_type, $token, $selected_queue_name);

$system_event_followers = array();
$dar = $sefdao->searchAll();

foreach ($dar as $row) {
    $types_selected = explode(',', $row['types']);
    $types = array(
        array(
            'label'   => SystemEvent::STATUS_NEW,
            'selected' => in_array(SystemEvent::STATUS_NEW, $types_selected)
        ),
        array(
            'label'   => SystemEvent::STATUS_RUNNING,
            'selected' => in_array(SystemEvent::STATUS_RUNNING, $types_selected)
        ),
        array(
            'label'   => SystemEvent::STATUS_DONE,
            'selected' => in_array(SystemEvent::STATUS_DONE, $types_selected)
        ),
        array(
            'label'   => SystemEvent::STATUS_WARNING,
            'selected' => in_array(SystemEvent::STATUS_WARNING, $types_selected)
        ),
        array(
            'label'   => SystemEvent::STATUS_ERROR,
            'selected' => in_array(SystemEvent::STATUS_ERROR, $types_selected)
        )
    );

    $system_event_followers[] = array_merge(
        $row,
        array(
            'edit'           => ($request->get('edit') == $row['id']),
            'email'          => $hp->purify($row['emails'], CODENDI_PURIFIER_CONVERT_HTML),
            'types-selected' => $types
        )
    );
}

$status_new_followers = array(
    array(
        'label' => SystemEvent::STATUS_NEW
    ),
    array(
        'label' => SystemEvent::STATUS_DONE
    ),
    array(
        'label' => SystemEvent::STATUS_WARNING
    ),
    array(
        'label' => SystemEvent::STATUS_ERROR
    )
);

$renderer->renderToPage(
    'admin-system-events',
    new SystemEvents_adminPresenter(
        $hp,
        $queue_links,
        $token,
        $all_status,
        $selectbox,
        $events,
        $system_event_followers,
        $request->get('edit'),
        $status_new_followers,
        $selected_queue_name
    )
);

$HTML->footer(array());
