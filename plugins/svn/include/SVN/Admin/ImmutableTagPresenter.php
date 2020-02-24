<?php
/**
 * Copyright Enalean (c) 2016 - 2018. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\SVN\Admin;

use Tuleap\SVN\Repository\Repository;

class ImmutableTagPresenter extends BaseAdminPresenter
{
    // Should be a const, waiting for PHP 5.6+
    public static $SO_MUCH_FOLDERS = array();

    public const MAX_NUMBER_OF_FOLDERS = 10000;

    public $svn_allow_tag_immutable_title;
    public $svn_allow_tag_immutable_comment;
    public $project_id;
    public $immutable_tag_configuration;
    public $path;
    public $immutable_tags_path;
    public $preview;
    public $exceeds_max_number_of_folders;
    public $sooo_fat;
    public $preview_description;
    public $tree;
    public $existing_tree;
    public $loading;
    public $impacted_svn;
    public $select_tag;
    public $my_tag;
    public $save;
    public $tree_empty_state;
    public $some_path;
    public $svn_status_style;
    public $repository_id;
    public $whitelist;
    public $immutable_tags_whitelist;
    public $repository_name;
    public $repository_full_name;
    public $title;
    public $impacted_svn_empty_state;
    public $sections;

    public function __construct(
        Repository $repository,
        ImmutableTag $immutable_tags,
        array $existing_tree,
        $title
    ) {
        parent::__construct();

        $this->repository_id            = $repository->getId();
        $this->repository_name          = $repository->getName();
        $this->repository_full_name     = $repository->getFullName();
        $this->project_id               = $repository->getProject()->getID();
        $this->immutable_tags_path      = $immutable_tags->getPathsAsString();
        $this->immutable_tags_whitelist = $immutable_tags->getWhitelistAsString();
        $this->immutable_tag_url_active = true;

        $existing_tree = array_filter(
            $existing_tree,
            function (string $path) {
                return $this->keepOnlyDirectories($path);
            }
        );
        if ($existing_tree === self::$SO_MUCH_FOLDERS || count($existing_tree) > self::MAX_NUMBER_OF_FOLDERS) {
            $this->exceeds_max_number_of_folders = true;
            $existing_tree                       = array();
        } else {
            $this->exceeds_max_number_of_folders = false;
            array_walk(
                $existing_tree,
                function (string &$path) {
                    $this->addSlasheAsPrefix($path);
                }
            );
            usort($existing_tree, 'strnatcasecmp');
        }

        $this->existing_tree = json_encode($existing_tree);

        $this->title                           = $title;
        $this->whitelist                       = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'whitelist');
        $this->svn_allow_tag_immutable_comment = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'configuration_description');
        $this->immutable_tag_configuration     = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'configuration');
        $this->tree                            = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'tree');
        $this->tree_empty_state                = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'tree_empty_state');
        $this->preview                         = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'preview');
        $this->preview_description             = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'preview_description');
        $this->my_tag                          = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'my-tag');
        $this->some_path                       = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'some/path');
        $this->select_tag                      = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'select_tag');
        $this->loading                         = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'loading');
        $this->svn_status_style                = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'svn_status_style');
        $this->impacted_svn                    = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'impacted_svn');
        $this->impacted_svn_empty_state        = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'impacted_svn_empty_state');
        $this->path                            = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'path');
        $this->whitelist                       = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'whitelist');
        $this->sooo_fat                        = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'sooo_fat', self::MAX_NUMBER_OF_FOLDERS);
        $this->save                            = $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'save');

        $this->sections = new SectionsPresenter($repository);
    }

    private function keepOnlyDirectories($path)
    {
        return substr($path, -1) === '/';
    }

    private function addSlasheAsPrefix(&$path)
    {
        if ($path !== '/') {
            $path = '/' . $path;
        }
    }
}
