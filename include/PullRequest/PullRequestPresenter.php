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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\PullRequest;

class PullRequestPresenter
{
    /** @var int */
    public $nb_pull_requests;

    /** @var int */
    public $repository_id;

    /** @var int */
    public $user_id;

    /** @var string */
    public $language;


    public function __construct($repository_id, $user_id, $language, $nb_pull_requests)
    {
        $this->repository_id         = $repository_id;
        $this->user_id               = $user_id;
        $this->language              = $language;
        $this->nb_pull_requests      = $nb_pull_requests;
    }

    public function getTemplateName()
    {
        return 'index';
    }

    public function nb_pull_request_badge()
    {
        if ($this->nb_pull_requests <= 1) {
            return $GLOBALS['Language']->getText('plugin_pullrequest', 'nb_pull_request_badge', array($this->nb_pull_requests));
        }

        return $GLOBALS['Language']->getText('plugin_pullrequest', 'nb_pull_request_badge_plural', array($this->nb_pull_requests));
    }
}
