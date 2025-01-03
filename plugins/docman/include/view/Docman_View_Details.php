<?php
/**
 * Copyright (c) Enalean, 2017 - Present. All Rights Reserved.
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

class Docman_View_Details extends Docman_View_Display
{
    /* protected */ public function _getTitle($params)
    {
        $hp = Codendi_HTMLPurifier::instance();
        return sprintf(dgettext('tuleap-docman', 'Details of %1$s'), $hp->purify($params['item']->getTitle(), CODENDI_PURIFIER_CONVERT_HTML));
    }

    protected function displayTitle(array $params): void
    {
        if ($this->isOldUiAllowed($params['user'], $params['item'])) {
            parent::displayTitle($params);
        }
    }

    protected function displayOldBreadcrumbs(array $params): void
    {
        if ($this->isOldUiAllowed($params['user'], $params['item'])) {
            parent::displayOldBreadcrumbs($params);
        }
    }

    protected function displayMode(array $params): void
    {
        if ($this->isOldUiAllowed($params['user'], $params['item'])) {
            parent::displayMode($params);
        }
    }

    protected function getBreadcrumbs(array $params, Project $project, \Tuleap\Docman\ServiceDocman $service): array
    {
        if ($this->isOldUiAllowed($params['user'], $params['item'])) {
            return parent::getBreadcrumbs($params, $project, $service);
        }

        $documents_item = [
            'title' => dgettext('tuleap-docman', 'Documents'),
            'url'   => $service->getUrl(),
        ];
        if ($this->_controller->userCanAdmin()) {
            $documents_item['sub_items'] = [[
                'title' => dgettext('tuleap-docman', 'Administration'),
                'url' => '/plugins/docman/?' . http_build_query(['group_id' => $project->getGroupId(), 'action' => 'admin']),
            ],
            ];
        }

        $hierarchy = [];
        if ($params['item']->getParentId()) {
            $hierarchy[] = [
                'title' => $params['item']->getTitle(),
                'url'   => $service->getUrl() . 'preview/' . $params['item']->getId(),
            ];

            $parent = $this->_getItemFactory()->getItemFromDb($params['item']->getParentId());
            while ($parent && $parent->getParentId() !== 0) {
                $hierarchy[] = [
                    'title' => $parent->getTitle(),
                    'url'   => $service->getUrl() . 'folder/' . $parent->getId(),
                ];
                $parent      = $this->_getItemFactory()->getItemFromDb($parent->getParentId());
            }
        }

        return [
            $documents_item,
            ...array_reverse($hierarchy),
        ];
    }

    private function isOldUiAllowed(PFUser $user, Docman_Item $item): bool
    {
        $project = ProjectManager::instance()->getProject($item->getGroupId());

        return \Tuleap\Document\Tree\SwitchToOldUi::isAllowed($user, $project);
    }

    public function _content($params, $view = null, $section = null)
    {
        $url = $params['default_url'];

        $token = isset($params['token']) ? $params['token'] : null;

        $user_can_manage = $this->_controller->userCanManage($params['item']->getId());
        $user_can_write  = $user_can_manage || $this->_controller->userCanWrite($params['item']->getId());
        $user_can_read   = $user_can_write || $this->_controller->userCanRead($params['item']->getId());

        $user_can_read_obsolete = false;
        if ($params['item']->isObsolete()) {
            // Restrict access to non docman admin.
            if (! $this->_controller->userCanAdmin()) {
                $user_can_manage = false;
                $user_can_write  = false;
                // Save read value to let user (according to their rights) to see
                // the properties.
                $user_can_read_obsolete = $user_can_read;
                $user_can_read          = false;
            }
        }

        $is_old_ui_allowed = $this->isOldUiAllowed($params['user'], $params['item']);

        $item_factory = $this->_getItemFactory();
        $details      = new Docman_View_ItemDetails($params['item'], $url);
        $sections     = [];
        if ($user_can_write) {
            $actions = null;
            if ($view && $section == 'actions') {
                $actions = $view;
            } elseif ($is_old_ui_allowed) {
                $actions = new Docman_View_ItemDetailsSectionActions($params['item'], $params['default_url'], $item_factory->isMoveable($params['item']), ! $item_factory->isRoot($params['item']), $this->_controller);
            }
            if ($actions) {
                $sections['actions'] = true;
                $details->addSection($actions);
            }
        }
        if ($user_can_manage && $is_old_ui_allowed) {
            $sections['permissions'] = true;
            $permissions             = new Docman_View_ItemDetailsSectionPermissions($params['item'], $params['default_url']);
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

        if ($user_can_read && ! ($params['item'] instanceof Docman_Empty)) {
            if ($view && $section == 'approval') {
                $approval = $view;
            } else {
                $approval = new Docman_View_ItemDetailsSectionApproval($params['item'], $params['default_url'], $params['theme_path'], $this->_controller->notificationsManager);
            }
            $sections['approval'] = true;
            $details->addSection($approval);
        }

        if ($user_can_read) {
            $sections['references'] = true;
            $details->addSection(new Docman_View_ItemDetailsSectionReferences($params['item'], $params['default_url']));
        }

        if ($user_can_read && $params['item'] instanceof \Docman_Folder) {
            $sections['statistics'] = true;
            $details->addSection(new Docman_View_ItemDetailsSectionStatistics($params['item'], $params['default_url'], $this->_controller, $token));
        }

        if ($section && isset($sections[$section])) {
            $details->setCurrentSection($section);
        } elseif (isset($params['section']) && isset($sections[$params['section']])) {
            $details->setCurrentSection($params['section']);
        } elseif ($this->_controller->request->get('action') == 'permissions' && isset($sections['permissions'])) {
            $details->setCurrentSection('permissions');
        }
        $details->display();
    }
}
