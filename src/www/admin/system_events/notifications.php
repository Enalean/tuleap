<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

$request = HTTPRequest::instance();
$request->checkUserIsSuperUser();

$dao = new SystemEventsFollowersDao(CodendiDataAccess::instance());

$token = new CSRFSynchronizerToken('/admin/system_events/notifications.php');
$title = $Language->getText('admin_system_events', 'title');

$new_followers = $request->get('new_followers');
if ($new_followers) {
    $token->check();
    if (isset($new_followers['emails']) && $new_followers['emails']) {
        if (isset($new_followers['types']) && is_array($new_followers['types']) && count($new_followers['types'])) {
            if ($dao->create($new_followers['emails'], implode(',', $new_followers['types']))) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $GLOBALS['Language']->getText('admin_system_events', 'save_success')
                );
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('admin_system_events', 'save_error')
                );
            }
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('admin_system_events', 'at_least_one_status')
            );
        }
    } else {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('admin_system_events', 'people_not_empty')
        );
    }
    $GLOBALS['Response']->redirect('/admin/system_events/notifications.php');
}

$notification_to_delete = $request->get('delete');
if ($notification_to_delete) {
    $token->check();
    if ($dao->delete($notification_to_delete)) {
        $GLOBALS['Response']->addFeedback(
            Feedback::INFO,
            $GLOBALS['Language']->getText('admin_system_events', 'delete_success')
        );
    } else {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('admin_system_events', 'delete_error')
        );
    }
    $GLOBALS['Response']->redirect('/admin/system_events/notifications.php');
}

$notification_to_update = $request->get('followers');
if ($notification_to_update) {
    $token->check();
    $id   = key($notification_to_update);
    $info = current($notification_to_update);
    if (isset($info['emails']) && $info['emails']) {
        if (isset($info['types']) && is_array($info['types']) && count($info['types'])) {
            if ($dao->save($id, $info['emails'], implode(',', $info['types']))) {
                $GLOBALS['Response']->addFeedback(
                    Feedback::INFO,
                    $GLOBALS['Language']->getText('admin_system_events', 'save_success')
                );
            } else {
                $GLOBALS['Response']->addFeedback(
                    Feedback::ERROR,
                    $GLOBALS['Language']->getText('admin_system_events', 'save_error')
                );
            }
        } else {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $GLOBALS['Language']->getText('admin_system_events', 'at_least_one_status')
            );
        }
    } else {
        $GLOBALS['Response']->addFeedback(
            Feedback::ERROR,
            $GLOBALS['Language']->getText('admin_system_events', 'people_not_empty')
        );
    }
    $GLOBALS['Response']->redirect('/admin/system_events/notifications.php');
}

$notifications = [];
foreach ($dao->searchAll() as $row) {
    $status_selected = explode(',', $row['types']);
    $checked         = false;
    $status          = [
        [
            'label'            => ucfirst(strtolower(SystemEvent::STATUS_NEW)),
            'value'            => SystemEvent::STATUS_NEW,
            'checked'          => in_array(SystemEvent::STATUS_NEW, $status_selected),
            'is_first_checked' => in_array(SystemEvent::STATUS_NEW, $status_selected) && ! $checked ? $checked = true : false,
        ],
        [
            'label'            => ucfirst(strtolower(SystemEvent::STATUS_RUNNING)),
            'value'            => SystemEvent::STATUS_RUNNING,
            'checked'          => in_array(SystemEvent::STATUS_RUNNING, $status_selected),
            'is_first_checked' => in_array(SystemEvent::STATUS_RUNNING, $status_selected) && ! $checked ? $checked = true : false,
        ],
        [
            'label'            => ucfirst(strtolower(SystemEvent::STATUS_DONE)),
            'value'            => SystemEvent::STATUS_DONE,
            'checked'          => in_array(SystemEvent::STATUS_DONE, $status_selected),
            'is_first_checked' => in_array(SystemEvent::STATUS_DONE, $status_selected) && ! $checked ? $checked = true : false,
        ],
        [
            'label'            => ucfirst(strtolower(SystemEvent::STATUS_WARNING)),
            'value'            => SystemEvent::STATUS_WARNING,
            'checked'          => in_array(SystemEvent::STATUS_WARNING, $status_selected),
            'is_first_checked' => in_array(SystemEvent::STATUS_WARNING, $status_selected) && ! $checked ? $checked = true : false,
        ],
        [
            'label'            => ucfirst(strtolower(SystemEvent::STATUS_ERROR)),
            'value'            => SystemEvent::STATUS_ERROR,
            'checked'          => in_array(SystemEvent::STATUS_ERROR, $status_selected),
            'is_first_checked' => in_array(SystemEvent::STATUS_ERROR, $status_selected) && ! $checked ? $checked = true : false,
        ],
    ];

    $notifications[] = [
        'id'     => $row['id'],
        'emails' => $row['emails'],
        'status' => $status,
    ];
}

$include_assets = new \Tuleap\Layout\IncludeAssets(__DIR__ . '/../../../scripts/site-admin/frontend-assets', '/assets/core/site-admin');
$GLOBALS['HTML']->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($include_assets, 'site-admin-system-events-notifications.js'));

$renderer = new \Tuleap\Admin\AdminPageRenderer();
$renderer->renderANoFramedPresenter(
    $title,
    ForgeConfig::get('codendi_dir') . '/src/templates/admin/system_events/',
    'notifications',
    new \Tuleap\SystemEvent\NotificationsPresenter(
        $title,
        $notifications,
        $token
    )
);
