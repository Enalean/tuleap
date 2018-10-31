<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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

namespace Tuleap\SVN;

use Tuleap\System\ApacheServiceControl;

class SVNAuthenticationCacheInvalidator
{
    /**
     * @var ApacheServiceControl
     */
    private $apache_service_control;
    /**
     * @var \Redis|null
     */
    private $redis_client;

    public function __construct(ApacheServiceControl $apache_service_control, \Redis $redis_client = null)
    {
        $this->apache_service_control = $apache_service_control;
        $this->redis_client           = $redis_client;
    }

    public function invalidateProjectCache(\Project $project)
    {
        if ($this->redis_client !== null) {
            $this->redis_client->del('apache_svn_project_set_' . $project->getID());
            return;
        }

        $this->apache_service_control->reload();
    }
}
