<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\PullRequest;

use GitRepository;
use CSRFSynchronizerToken;

class AdditionalActionsPresenter
{

    /** @var GitRepository */
    private $repository;

    /** @var string */
    public $csrf_input;

    /** @var array */
    public $branches;


    public function __construct(GitRepository $repository, CSRFSynchronizerToken $csrf, array $branches)
    {
        $this->repository = $repository;
        $this->csrf_input = $csrf->fetchHTMLInput();
        $this->branches   = $branches;
    }

    public function getTemplateName()
    {
        return 'additional-actions';
    }

    public function form_action()
    {
        return '/plugins/pullrequest/?action=generatePullRequest&group_id=' . $this->repository->getProjectId() . '&repository_id=' . $this->repository->getId();
    }

    public function new_pull_request_button_title()
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_button_title');
    }

    public function new_pull_request_modal_title()
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_modal_title');
    }

    public function new_pull_request_modal_from()
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_modal_from');
    }

    public function new_pull_request_modal_to()
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_modal_to');
    }

    public function new_pull_request_modal_choose_branch_from()
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_modal_choose_branch_from');
    }

    public function new_pull_request_modal_choose_branch_to()
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_modal_choose_branch_to');
    }

    public function new_pull_request_modal_close()
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_modal_close');
    }

    public function new_pull_request_modal_submit()
    {
        return $GLOBALS['Language']->getText('plugin_pullrequest', 'new_pull_request_modal_submit');
    }
}
