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

class JenkinsServerPresenter
{
    /**
     * @var string
     */
    public $jenkins_server_url;

    public readonly string $jenkins_server_id;

    /**
     * @var CSRFSynchronizerToken
     */
    public $csrf_delete;

    /**
     * @var JenkinsServerLogsPresenter[]
     */
    public $logs_presenters;

    public function __construct(JenkinsServer $jenkins_server, array $logs_presenters)
    {
        $this->jenkins_server_id  = $jenkins_server->id->toString();
        $this->jenkins_server_url = $jenkins_server->getServerURL();
        $this->csrf_delete        = new CSRFSynchronizerToken(
            URLBuilder::buildDeleteUrl()
        );

        $this->logs_presenters = $logs_presenters;
    }
}
