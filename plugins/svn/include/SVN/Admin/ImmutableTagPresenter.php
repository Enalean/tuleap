<?php
/**
 * Copyright Enalean (c) 2016 - Present. All rights reserved.
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
    public static $SO_MUCH_FOLDERS = [];

    public const MAX_NUMBER_OF_FOLDERS = 10000;

    public readonly int $max_number_of_folders;

    public $project_id;
    public $path;
    public $immutable_tags_path;
    public $preview;
    public $exceeds_max_number_of_folders;
    public $tree;
    public $existing_tree;
    public $loading;
    public $save;
    public $repository_id;
    public $immutable_tags_whitelist;
    public $repository_name;
    public $repository_full_name;
    public $title;
    public $sections;

    public function __construct(
        Repository $repository,
        ImmutableTag $immutable_tags,
        array $existing_tree,
        $title,
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
            $existing_tree                       = [];
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

        $this->max_number_of_folders = self::MAX_NUMBER_OF_FOLDERS;

        $this->title = $title;

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
