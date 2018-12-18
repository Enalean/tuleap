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
 * along with Tuleap. If not, see http://www.gnu.org/licenses/.
 *
 *
 */

namespace Tuleap\Docman\rest;

use Docman_ItemFactory;
use PluginManager;
use Project;
use REST_TestDataBuilder;

class DocmanDataBuilder extends REST_TestDataBuilder
{
    const PROJECT_NAME                 = 'DocmanProject';
    const DOCMAN_REGULAR_USER_NAME     = 'docman_regular_user';
    const DOCMAN_REGULAR_USER_PASSWORD = 'welcome0';

    /**
     * @var Docman_ItemFactory
     */
    private $docman_item_factory;

    public function setUp()
    {
        echo 'Setup Docman REST Tests configuration' . PHP_EOL;
        $this->installPlugin();
        $this->addContent();
        $this->generateDocmanRegularUser();
    }

    private function installPlugin()
    {
        $plugin_manager = PluginManager::instance();
        $plugin_manager->installAndActivate('docman');
    }

    private function addItem(Project $project, $docman_root_id, $title, $item_type, $link_url = '', $file_path = '', $wiki_page = '')
    {
        $item = array(
            'parent_id'         => $docman_root_id,
            'group_id'          => $project->getID(),
            'title'             => $title,
            'description'       => '',
            'create_date'       => time(),
            'update_date'       => time(),
            'user_id'           => 102,
            'status'            => 100,
            'obsolescence_date' => 0,
            'rank'              => 1,
            'item_type'         => $item_type,
            'link_url'          => $link_url,
            'wiki_page'         => $wiki_page,
            'file_is_embedded'  => ''
        );

        $item_id = $this->docman_item_factory->create($item, 1);

        switch ($item_type) {
            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                $file_type = 'text/html';
                $this->addItemVersion($item_id, $title, $file_type, $file_path);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                $file_type = 'application/pdf';
                $this->addItemVersion($item_id, $title, $file_type);
                break;
            case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                $this->addLinkVersion($item_id);
                break;
            default:
                $file_type = null;
                break;
        }

        return $item_id;
    }

    private function addItemVersion($item_id, $title, $item_type, $file_path = '')
    {
        $version         = array(
            'item_id'   => $item_id,
            'number'    => 1,
            'user_id'   => 102,
            'label'     => '',
            'changelog' => '',
            'date'      => time(),
            'filename'  => $title,
            'filesize'  => 3,
            'filetype'  => $item_type,
            'path'      => $file_path
        );
        $version_factory = new \Docman_VersionFactory();
        $version_factory->create($version);
    }

    private function addLinkVersion($item_id)
    {
        $docman_factory = new Docman_ItemFactory();
        $docman_link    = $docman_factory->getItemFromDb($item_id);
        $docman_link->setUrl('https://my.example.test');
        $version_link_factory = new \Docman_LinkVersionFactory();
        $version_link_factory->create($docman_link, 'changset1', 'test rest Change', time());
    }


    /**
     * To help understand tests structure, below a representation of folder hierarchy
     *
     *                          folder 1
     *                            +
     *                            |
     *  +---------------+---------+--------+---------------------+---------------------+-------------+-----------+------------+
     *  +               +                  +                     +                     +             +           +            +
     *Item A          Item B             Item C             Folder 2                 Item E        Item F      Item G       Folder 3
     *                                                           +
     *                                                           |
     *                                                           +
     *                                                        Item D
     */
    private function addContent()
    {
        $project = $this->project_manager->getProjectByUnixName(self::PROJECT_NAME);

        $this->docman_item_factory = Docman_ItemFactory::instance($project->getID());
        $docman_root               = $this->docman_item_factory->getRoot($project->getID());

        $folder_id = $this->addItem($project, $docman_root->getId(), 'folder 1', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
        $this->addWritePermissionOnItem($project, $folder_id, \ProjectUGroup::PROJECT_MEMBERS);

        $item_A_id = $this->addItem($project, $folder_id, 'item A', PLUGIN_DOCMAN_ITEM_TYPE_EMPTY);
        $item_B_id = $this->addItem($project, $folder_id, 'item B', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);
        $item_C_id = $this->addItem($project, $folder_id, 'item C', PLUGIN_DOCMAN_ITEM_TYPE_FILE);
        $folder_2_id = $this->addItem($project, $folder_id, 'folder 2', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);
        $folder_3_id = $this->addItem($project, $folder_id, 'folder 3', PLUGIN_DOCMAN_ITEM_TYPE_FOLDER);

        $item_D_id = $this->addItem($project, $folder_2_id, 'item D', PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE);

        $item_E_id = $this->addItem(
            $project,
            $folder_id,
            'item E',
            PLUGIN_DOCMAN_ITEM_TYPE_LINK,
            "https://example.test"
        );

        $item_F_path = dirname(__FILE__) . '/_fixtures/docmanFile/embeddedFile';
        $item_F_id   = $item_B_id = $this->addItem(
            $project,
            $folder_id,
            'item F',
            PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE,
            '',
            $item_F_path
        );
        $item_G_id = $this->addItem($project, $folder_id, 'item G', PLUGIN_DOCMAN_ITEM_TYPE_WIKI, '', '', 'MyWikiPage');

        $this->addReadPermissionOnItem($project, $item_A_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($project, $item_B_id, \ProjectUGroup::PROJECT_ADMIN);
        $this->addReadPermissionOnItem($project, $item_C_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($project, $folder_2_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($project, $item_D_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($project, $item_E_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($project, $item_F_id, \ProjectUGroup::PROJECT_MEMBERS);
        $this->addReadPermissionOnItem($project, $folder_3_id, \ProjectUGroup::PROJECT_ADMIN);
        $this->addReadPermissionOnItem($project, $item_G_id, \ProjectUGroup::PROJECT_MEMBERS);
    }

    private function addReadPermissionOnItem(Project $project, $object_id, $ugroup_name)
    {
        permission_add_ugroup(
            $project->getID(),
            'PLUGIN_DOCMAN_READ',
            $object_id,
            $ugroup_name,
            true
        );
    }

    private function addWritePermissionOnItem(Project $project, $object_id, $ugroup_name)
    {
        permission_add_ugroup(
            $project->getID(),
            'PLUGIN_DOCMAN_WRITE',
            $object_id,
            $ugroup_name,
            true
        );
    }

    private function generateDocmanRegularUser()
    {
        $docman_user = $this->user_manager->getUserByUserName(self::DOCMAN_REGULAR_USER_NAME);
        $docman_user->setPassword(self::DOCMAN_REGULAR_USER_PASSWORD);
        $this->user_manager->updateDb($docman_user);
    }
}
