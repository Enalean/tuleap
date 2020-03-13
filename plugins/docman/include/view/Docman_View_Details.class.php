<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

use Tuleap\Docman\Notifications\CollectionOfUgroupMonitoredItemsBuilder;

require_once('Docman_View_Display.class.php');

require_once('Docman_View_ItemDetails.class.php');
require_once('Docman_View_ItemDetailsSectionProperties.class.php');
require_once('Docman_View_ItemDetailsSectionStatistics.class.php');
require_once('Docman_View_ItemDetailsSectionEditProperties.class.php');
require_once('Docman_View_ItemDetailsSectionPermissions.class.php');
require_once('Docman_View_ItemDetailsSectionNotifications.class.php');
require_once('Docman_View_ItemDetailsSectionHistory.class.php');
require_once('Docman_View_ItemDetailsSectionReferences.class.php');
require_once('Docman_View_ItemDetailsSectionActions.class.php');
require_once('Docman_View_ItemDetailsSectionApproval.class.php');

require_once(dirname(__FILE__) . '/../Docman_LockFactory.class.php');

class Docman_View_Details extends Docman_View_Display
{

    /* protected */ public function _getTitle($params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return sprintf(dgettext('tuleap-docman', 'Details of %1$s'), $hp->purify($params['item']->getTitle(), CODENDI_PURIFIER_CONVERT_HTML));
    }

    public function _content($params, $view = null, $section = null)
    {
        $url = $params['default_url'];

        $token = isset($params['token']) ? $params['token'] : null;

        $user_can_manage = $this->_controller->userCanManage($params['item']->getId());
        $user_can_write = $user_can_manage || $this->_controller->userCanWrite($params['item']->getId());
        $user_can_read  = $user_can_write || $this->_controller->userCanRead($params['item']->getId());

        $user_can_read_obsolete = false;
        if ($params['item']->isObsolete()) {
            // Restrict access to non docman admin.
            if (!$this->_controller->userCanAdmin()) {
                $user_can_manage = false;
                $user_can_write  = false;
                // Save read value to let user (according to their rights) to see
                // the properties.
                $user_can_read_obsolete = $user_can_read;
                $user_can_read   = false;
            }
        }

        $item_factory = $this->_getItemFactory($params);
        $details      = new Docman_View_ItemDetails($params['item'], $url);
        $sections     = array();
        if ($user_can_read || $user_can_read_obsolete) {
            if ($view && $section == 'properties') {
                $props = $view;
            } else {
                $props = new Docman_View_ItemDetailsSectionProperties($params['item'], $params['default_url'], $params['theme_path'], $user_can_write, $token);
            }
            $sections['properties'] = true;
            $details->addSection($props);
        }
        if ($user_can_write) {
            if ($view && $section == 'actions') {
                $actions = $view;
            } else {
                $actions = new Docman_View_ItemDetailsSectionActions($params['item'], $params['default_url'], $item_factory->isMoveable($params['item']), !$item_factory->isRoot($params['item']), $this->_controller, $token);
            }
            $sections['actions'] = true;
            $details->addSection($actions);
        }
        if ($user_can_manage) {
            $sections['permissions'] = true;
            $permissions = new Docman_View_ItemDetailsSectionPermissions($params['item'], $params['default_url']);
            $details->addSection($permissions);
        }

        if ($user_can_read) {
            $notifications_manager = $this->_controller->notificationsManager;

            $sections['notifications'] = true;
            $details->addSection(
                new Docman_View_ItemDetailsSectionNotifications(
                    $params['item'],
                    $params['default_url'],
                    $notifications_manager,
                    $token,
                    new CollectionOfUgroupMonitoredItemsBuilder($notifications_manager)
                )
            );
        }

        if ($user_can_read && !is_a($params['item'], 'Docman_Empty')) {
            if ($view && $section == 'approval') {
                $approval = $view;
            } else {
                $approval = new Docman_View_ItemDetailsSectionApproval($params['item'], $params['default_url'], $params['theme_path'], $this->_controller->notificationsManager);
            }
            $sections['approval'] = true;
            $details->addSection($approval);
        }

        if ($user_can_read) {
            $sections['history'] = true;
            $logger = $this->_controller->getLogger();
            $details->addSection(new Docman_View_ItemDetailsSectionHistory($params['item'], $params['default_url'], $user_can_manage, $logger));
        }

        if ($user_can_read) {
            $sections['references'] = true;
            $details->addSection(new Docman_View_ItemDetailsSectionReferences($params['item'], $params['default_url']));
        }

        if ($user_can_read && is_a($params['item'], 'Docman_Folder')) {
            $sections['statistics'] = true;
            $details->addSection(new Docman_View_ItemDetailsSectionStatistics($params['item'], $params['default_url'], $this->_controller, $token));
        }

        if ($section && isset($sections[$section])) {
            $details->setCurrentSection($section);
        } elseif (isset($params['section']) &&  isset($sections[$params['section']])) {
            $details->setCurrentSection($params['section']);
        } elseif ($this->_controller->request->get('action') == 'permissions' &&  isset($sections['permissions'])) {
            $details->setCurrentSection('permissions');
        }
        $details->display();
    }
}
