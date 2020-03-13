<?php
/**
 * Copyright (c) Enalean, 2014. All rights reserved
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

namespace Tuleap\ProFTPd\Presenter;

use Tuleap\ProFTPd\Directory\DirectoryPathCollection;
use Tuleap\ProFTPd\Directory\DirectoryItemCollection;
use Project;

class ExplorerPresenter
{

    /**
     * @var DirectoryPathCollection
     */
    public $path_parts;

    /**
     * @var DirectoryItemCollection
     */
    private $directory_items;

    private $group_id;

    public $project_name;

    public function __construct(
        DirectoryPathCollection $path_parts,
        $path,
        DirectoryItemCollection $directory_items,
        Project $project
    ) {
        $this->path_parts      = $path_parts;
        $this->path            = $path;
        $this->directory_items = $directory_items;
        $this->group_id        = $project->getID();
        $this->project_name    = $project->getPublicName();
    }

    public function current_directory()
    {
        $last = $this->path_parts->last();
        if ($last) {
            return $last->path_part_name;
        }
        return '';
    }

    public function current_directory_url()
    {
        return PROFTPD_BASE_URL . '/explorer.php?group_id=' . $this->group_id . '&path=' . $this->getPathUrlParameter();
    }

    public function nav_url()
    {
        return PROFTPD_BASE_URL . '/explorer.php?group_id=' . $this->group_id . '&path=';
    }

    private function getPathUrlParameter()
    {
        if ($this->path_parts->count() > 0) {
            return urlencode($this->path . '/');
        }

        return urlencode($this->path);
    }

    public function parent_directories()
    {
        return $this->path_parts->parent_directory_parts();
    }

    public function is_in_subdirectory()
    {
        return $this->path_parts->count() > 0;
    }

    public function file_column_name()
    {
        return dgettext('tuleap-proftpd', 'File');
    }

    public function size_column_name()
    {
        return dgettext('tuleap-proftpd', 'Size (Bytes)');
    }

    public function date_added_column_name()
    {
        return dgettext('tuleap-proftpd', 'Date Added');
    }

    public function folder_list()
    {
        return $this->directory_items->getFolders();
    }

    public function file_list()
    {
        return $this->directory_items->getFiles();
    }
}
