<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\Docman\REST\v1;

use Docman_EmbeddedFile;
use Docman_Empty;
use Docman_File;
use Docman_Folder;
use Docman_Item;
use Docman_Link;
use Docman_Wiki;
use Tuleap\Docman\Item\ItemVisitor;

class AfterItemCreationVisitor implements ItemVisitor
{
    /**
     * @var \EventManager
     */
    private $event_manager;
    /**
     * @var \PermissionsManager
     */
    private $permission_manager;

    public function __construct(\PermissionsManager $permission_manager, \EventManager $event_manager)
    {
        $this->permission_manager = $permission_manager;
        $this->event_manager      = $event_manager;
    }

    public function visitFolder(Docman_Folder $item, array $params = [])
    {
        throw new CannotCreateThisItemTypeException();
    }

    public function visitWiki(Docman_Wiki $item, array $params = [])
    {
        $this->inheritPermissionsFromParent($item);
        $params['wiki_page'] = $item->getPagename();
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_PHPWIKI_PAGE, $params);
        $this->triggerPostCreationEvents($params);
    }

    public function visitLink(Docman_Link $item, array $params = [])
    {
        throw new CannotCreateThisItemTypeException();
    }

    public function visitFile(Docman_File $item, array $params = [])
    {
        throw new CannotCreateThisItemTypeException();
    }

    public function visitEmbeddedFile(Docman_EmbeddedFile $item, array $params = [])
    {
        throw new CannotCreateThisItemTypeException();
    }

    public function visitEmpty(Docman_Empty $item, array $params = [])
    {
        $this->inheritPermissionsFromParent($item);
        $this->event_manager->processEvent(PLUGIN_DOCMAN_EVENT_NEW_EMPTY, $params);
        $this->triggerPostCreationEvents($params);
    }

    public function visitItem(Docman_Item $item, array $params = [])
    {
        throw new CannotCreateThisItemTypeException();
    }

    private function triggerPostCreationEvents($params)
    {
        $this->event_manager->processEvent('plugin_docman_event_add', $params);
        $this->event_manager->processEvent('send_notifications', []);
    }

    private function inheritPermissionsFromParent(Docman_Item $item)
    {
        $this->permission_manager->clonePermissions(
            $item->getParentId(),
            $item->getId(),
            ['PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE']
        );
    }
}
