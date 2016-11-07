<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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
class SVN_Apache_Auth_Factory {

    /**
     * @var SVN_TokenUsageManager
     */
    private $token_manager;

    /**
     * @var EventManager
     */
    private $event_manager;

    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var Parameters
     */
    private $cache_parameters;

    public function __construct(
        ProjectManager $project_manager,
        EventManager $event_manager,
        SVN_TokenUsageManager $token_manager,
        Parameters $cache_parameters
    ) {
        $this->project_manager  = $project_manager;
        $this->event_manager    = $event_manager;
        $this->token_manager    = $token_manager;
        $this->cache_parameters = $cache_parameters;
    }

    /**
     * @param array $projectInfo The project data db row
     *
     * @return SVN_Apache
     */
    public function get(array $projectInfo, $requested_authentication_method) {
        $project                   = $this->project_manager->getProjectFromDbRow($projectInfo);
        $project_authorizes_tokens = $this->token_manager->isProjectAuthorizingTokens($project);

        $svn_apache_auth = $this->getModFromPlugins(
            $projectInfo,
            $requested_authentication_method,
            $project_authorizes_tokens
        );

        if (! $svn_apache_auth) {

            if ($project_authorizes_tokens) {
                $svn_apache_auth = new SVN_Apache_ModPerl($this->cache_parameters, $projectInfo);
            } else {
                $svn_apache_auth = $this->getModFromLocalIncFile($projectInfo, $requested_authentication_method);
            }

        }

        return $svn_apache_auth;
    }

    private function getModFromLocalIncFile(array $projectInfo, $requested_authentication_method) {
        switch ($requested_authentication_method) {
            case SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL:
                $svnApacheAuth = new SVN_Apache_ModPerl($this->cache_parameters, $projectInfo);
                break;
            default:
                $svnApacheAuth = new SVN_Apache_ModMysql($projectInfo);
        }

        return $svnApacheAuth;
    }

    private function getModFromPlugins(array $project_info, $requested_authentication_method, $project_authorizes_tokens) {
        $svn_apache_auth = null;

        $params = array(
            'svn_apache_auth'           => &$svn_apache_auth,
            'svn_conf_auth'             => $requested_authentication_method,
            'project_authorizes_tokens' => $project_authorizes_tokens,
            'cache_parameters'          => $this->cache_parameters,
            'project_info'              => $project_info
        );

        $this->event_manager->processEvent(Event::SVN_APACHE_AUTH, $params);

        return $svn_apache_auth;
    }
}