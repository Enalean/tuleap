<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

namespace Tuleap\HudsonGit\Git\Administration;

use CSRFSynchronizerToken;
use GitPresenters_AdminPresenter;

class AdministrationPresenter extends GitPresenters_AdminPresenter
{
    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_token;

    /**
     * @var JenkinsServerPresenter[]
     */
    public $jenkins_server_presenters;

    public function __construct(
        $project_id,
        bool $are_mirrors_defined,
        array $external_pane_presenters,
        array $jenkins_server_presenters,
        CSRFSynchronizerToken $csrf_token
    ) {
        parent::__construct($project_id, $are_mirrors_defined, $external_pane_presenters);

        $this->csrf_token = $csrf_token;
        $this->jenkins_server_presenters = $jenkins_server_presenters;
    }
}
