<?php
/**
 * Copyright (c) Enalean, 2012 - Present. All Rights Reserved.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

use Tuleap\SvnCore\Cache\Parameters;

/**
 * Manage load of the right SVN_Apache authentication module for given project
 */
class SVN_Apache_Auth_Factory
{
    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var Parameters
     */
    private $cache_parameters;

    public function __construct(
        EventManager $event_manager,
        Parameters $cache_parameters
    ) {
        $this->event_manager    = $event_manager;
        $this->cache_parameters = $cache_parameters;
    }

    public function get(\Project $project): SVN_Apache
    {
        $svn_apache_auth = $this->getModFromPlugins($project);

        if (! $svn_apache_auth) {
            $svn_apache_auth = new SVN_Apache_ModPerl($this->cache_parameters);
        }

        return $svn_apache_auth;
    }

    private function getModFromPlugins(\Project $project): ?SVN_Apache
    {
        $svn_apache_auth = null;

        $params = [
            'svn_apache_auth'           => &$svn_apache_auth,
            'cache_parameters'          => $this->cache_parameters,
            'project'                   => $project,
        ];

        $this->event_manager->processEvent(Event::SVN_APACHE_AUTH, $params);

        return $svn_apache_auth;
    }
}
