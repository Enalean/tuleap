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

class AdditionalInfoPresenter
{

    /** @var GitRepository */
    private $repository;

    /** @var int */
    private $nb_pull_requests;


    public function __construct(GitRepository $repository, $nb_pull_requests)
    {
        $this->repository       = $repository;
        $this->nb_pull_requests = $nb_pull_requests;
    }

    public function getTemplateName()
    {
        return 'additional-info';
    }

    public function action_url()
    {
        return '/plugins/git/?action=pull-requests&repo_id=' . $this->repository->getId() . '&group_id=' . $this->repository->getProjectId();
    }

    public function nb_pull_request_badge()
    {
        if ($this->nb_pull_requests <= 1) {
            return $GLOBALS['Language']->getText('plugin_pullrequest', 'nb_pull_request_badge', array($this->nb_pull_requests));
        }

        return $GLOBALS['Language']->getText('plugin_pullrequest', 'nb_pull_request_badge_plural', array($this->nb_pull_requests));
    }

    public function is_there_at_least_one_pull_request()
    {
        return $this->nb_pull_requests > 0;
    }
}
