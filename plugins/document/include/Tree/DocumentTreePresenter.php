<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Document\Tree;

use CSRFSynchronizerToken;

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
     * @var bool
     */
    public $user_can_delete_item;
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
    public $embedded_are_allowed;
    /**
     * @var bool
     */
    public $is_item_status_metadata_used;
    /**
     * @var bool
     */
    public $is_obsolescence_date_metadata_used;
    /**
     * @var string
     */
    public $csrf_token_name;
    /**
     * @var string
     */
    public $csrf_token;

    public function __construct(
        \Project $project,
        \PFUser $user,
        bool $embedded_are_allowed,
        bool $is_item_status_metadata_used,
        bool $is_obsolescence_date_metadata_used,
        bool $only_siteadmin_can_delete_option,
        CSRFSynchronizerToken $csrf
    ) {
        $this->project_id                         = $project->getID();
        $this->project_name                       = $project->getUnixNameLowerCase();
        $this->user_is_admin                      = $user->isAdmin($project->getID());
        $this->user_can_create_wiki               = $project->usesWiki();
        $this->user_can_delete_item               = ! $only_siteadmin_can_delete_option || $user->isSuperUser();
        $this->max_size_upload                    = \ForgeConfig::get(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
        $this->max_files_dragndrop                = \ForgeConfig::get(PLUGIN_DOCMAN_MAX_NB_FILE_UPLOADS_SETTING);
        $this->embedded_are_allowed               = $embedded_are_allowed;
        $this->is_item_status_metadata_used       = $is_item_status_metadata_used;
        $this->is_obsolescence_date_metadata_used = $is_obsolescence_date_metadata_used;
        $this->csrf_token_name                    = $csrf->getTokenName();
        $this->csrf_token                         = $csrf->getToken();
    }
}
