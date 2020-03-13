<?php
/**
 * Copyright (c) Enalean, 2015. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class SVN_ImmutableTagsPresenter
{
    // Should be a const, waiting for PHP 5.6+
    public static $SO_MUCH_FOLDERS = array();

    public const MAX_NUMBER_OF_FOLDERS = 10000;

    /** @var Project */
    private $project;

    /** @var string */
    public $immutable_tags_whitelist;

    /** @var string */
    public $immutable_tags_path;

    /** @var array */
    public $existing_tree;

    /** @var bool */
    public $exceeds_max_number_of_folders;

    public function __construct(
        Project $project,
        $immutable_tags_whitelist,
        $immutable_tags_path,
        array $existing_tree
    ) {
        $this->project                  = $project;
        $this->immutable_tags_whitelist = $immutable_tags_whitelist;
        $this->immutable_tags_path      = $immutable_tags_path;

        $existing_tree = array_filter(
            $existing_tree,
            static function ($path) {
                return substr($path, -1) === '/';
            }
        );
        if ($existing_tree === self::$SO_MUCH_FOLDERS || count($existing_tree) > self::MAX_NUMBER_OF_FOLDERS) {
            $this->exceeds_max_number_of_folders = true;
            $existing_tree = array();
        } else {
            $this->exceeds_max_number_of_folders = false;
            array_walk(
                $existing_tree,
                static function (&$path) {
                    if ($path !== '/') {
                        $path = '/' . $path;
                    }
                }
            );
            usort($existing_tree, 'strnatcasecmp');
        }

        $this->existing_tree = json_encode($existing_tree);
    }

    public function svn_allow_tag_immutable_title()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'title');
    }

    public function svn_allow_tag_immutable_comment()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'configuration_description');
    }

    public function immutable_tag_configuration()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'configuration');
    }

    public function tree()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'tree');
    }

    public function tree_empty_state()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'tree_empty_state');
    }

    public function preview()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'preview');
    }

    public function preview_description()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'preview_description');
    }

    public function my_tag()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'my-tag');
    }

    public function some_path()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'some/path');
    }

    public function select_tag()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'select_tag');
    }

    public function loading()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'loading');
    }

    public function svn_status_style()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'svn_status_style');
    }

    public function impacted_svn()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'impacted_svn');
    }

    public function impacted_svn_empty_state()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'impacted_svn_empty_state');
    }

    public function whitelist()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'whitelist');
    }

    public function path()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'path');
    }

    public function sooo_fat()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'sooo_fat', self::MAX_NUMBER_OF_FOLDERS);
    }

    public function save()
    {
        return $GLOBALS['Language']->getText('svn_admin_immutable_tags', 'save');
    }

    public function project_id()
    {
        return $this->project->getID();
    }
}
