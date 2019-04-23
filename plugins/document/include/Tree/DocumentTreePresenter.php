<?php
/**
 * Copyright (c) Enalean, 2018-2019. All Rights Reserved.
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

declare(strict_types = 1);

namespace Tuleap\Document\Tree;

class DocumentTreePresenter
{
    /**
     * @var int
     */
    public $project_id;
    /**
     * @var string
     */
    public $project_name;
    /**
     * @var bool
     */
    public $user_is_admin;
    /**
     * @var bool
     */
    public $user_can_create_wiki;
    /**
     * @var int
     */
    public $max_size_upload;
    /**
     * @var int
     */
    public $max_files_dragndrop;
    /**
     * @var bool
     */
    public $is_under_construction;
    /**
     * @var bool
     */
    public $embedded_are_allowed;
    /**
     * @var bool
     */
    public $is_item_status_metadata_used;

    public function __construct(
        \Project $project,
        \PFUser $user,
        bool $is_under_construction,
        bool $embedded_are_allowed,
        bool $is_item_status_metadata_used
    ) {
        $this->project_id                   = $project->getID();
        $this->project_name                 = $project->getUnixNameLowerCase();
        $this->user_is_admin                = $user->isAdmin($project->getID());
        $this->user_can_create_wiki         = $project->usesWiki();
        $this->max_size_upload              = \ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
        $this->max_files_dragndrop          = \ForgeConfig::get(PLUGIN_DOCMAN_MAX_NB_FILE_UPLOADS_SETTING);
        $this->is_under_construction        = $is_under_construction;
        $this->embedded_are_allowed         = $embedded_are_allowed;
        $this->is_item_status_metadata_used = $is_item_status_metadata_used;
    }
}
