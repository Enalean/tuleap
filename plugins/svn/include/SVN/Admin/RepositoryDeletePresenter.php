<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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

use Project;
use Tuleap\SVN\Repository\Repository;
use CSRFSynchronizerToken;

class RepositoryDeletePresenter extends BaseAdminPresenter
{
    public $project_id;
    public $repository_name;
    public $repository_id;
    public $is_created;
    public $title;
    public $cannot_delete;
    public $subtitle;
    public $comment;
    public $button;
    public $token;

    public $modal_content;
    public $modal_button_delete;
    public $modal_button_cancel;
    public $modal_title;

    public $sections;
    public $comment_undone;
    public $repository_full_name;

    public function __construct(
        Repository $repository,
        Project $project,
        $title,
        CSRFSynchronizerToken $token
    ) {
        parent::__construct();

        $this->project_id               = $project->getID();
        $this->repository_id            = $repository->getId();
        $this->repository_name          = $repository->getName();
        $this->repository_full_name     = $repository->getFullName();
        $this->is_created               = $repository->isRepositoryCreated();
        $this->title                    = $title;
        $this->repository_delete_active = true;
        $this->token                    = $token->fetchHTMLInput();

        $this->cannot_delete            = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'cannot_delete');
        $this->subtitle                 = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'subtitle');
        $this->comment                  = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'comment');
        $this->comment_undone           = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'comment_undone');
        $this->button                   = $GLOBALS['Language']->getText('plugin_svn_admin_repository_delete', 'button_delete');

        $this->modal_title         = $GLOBALS['Language']->getText('plugin_svn_admin_modal_repository_delete', 'title');
        $this->modal_content       = $GLOBALS['Language']->getText('plugin_svn_admin_modal_repository_delete', 'content');
        $this->modal_button_delete = $GLOBALS['Language']->getText('plugin_svn_admin_modal_repository_delete', 'button_delete');
        $this->modal_button_cancel = $GLOBALS['Language']->getText('global', 'btn_cancel');

        $this->sections = new SectionsPresenter($repository);
    }
}
