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

use Tuleap\SVN\Repository\Repository;
use Project;
use CSRFSynchronizerToken;

class HooksConfigurationPresenter extends BaseAdminPresenter
{

    public $project_id;
    public $repo_id;
    public $csrf_input;
    public $title;
    public $repository_name;
    public $repository_full_name;

    public $hooks_config_subtitle;
    public $comment;
    public $pre_commit_must_contain_reference;
    public $allow_commit_message_changes;
    public $label_pre_commit_must_contain_reference;
    public $label_allow_commit_message_changes;
    public $submit;
    public $repository_id;
    public $sections;

    public function __construct(
        Repository $repository,
        Project $project,
        CSRFSynchronizerToken $token,
        $title,
        $pre_commit_must_contain_reference,
        $allow_commit_message_changes
    ) {
        parent::__construct();

        $this->project_id                 = $project->getID();
        $this->repository_id              = $repository->getId();
        $this->csrf_input                 = $token->fetchHTMLInput();
        $this->title                      = $title;
        $this->repository_name            = $repository->getName();
        $this->repository_full_name       = $repository->getFullName();
        $this->commit_rule_active         = true;
        $this->pre_commit_must_contain_reference = $pre_commit_must_contain_reference;
        $this->allow_commit_message_changes      = $allow_commit_message_changes;

        $this->hooks_config_subtitle                   = $GLOBALS['Language']->getText('plugin_svn_admin_hooks', 'subtitle');
        $this->comment                                 = $GLOBALS['Language']->getText('plugin_svn_admin_hooks', 'comment');
        $this->label_pre_commit_must_contain_reference = $GLOBALS['Language']->getText('plugin_svn_admin_hooks', 'label_pre_commit_must_contain_reference');
        $this->label_allow_commit_message_changes      = $GLOBALS['Language']->getText('plugin_svn_admin_hooks', 'label_allow_commit_message_changes');
        $this->submit                                  = $GLOBALS['Language']->getText('plugin_svn_admin_hooks', 'submit');

        $this->sections = new SectionsPresenter($repository);
    }
}
