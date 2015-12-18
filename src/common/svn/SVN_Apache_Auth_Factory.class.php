<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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

require_once 'SVN_Apache_ModPerl.class.php';
require_once 'SVN_Apache_ModMysql.class.php';

/**
 * Manage load of the right SVN_Apache authentication module for given project
 */
class SVN_Apache_Auth_Factory {
    
    /**
     * @param array $projectInfo The project data db row
     * 
     * @return SVN_Apache
     */
    public function get($projectInfo) {
        $requested_authentication_method = ForgeConfig::get(SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_KEY);
        $svnApacheAuth                   = null;
        $params = array(
            'svn_apache_auth' => &$svnApacheAuth,
            'svn_conf_auth'   => $requested_authentication_method,
            'project_info'    => $projectInfo,
        );
        $this->getEventManager()->processEvent(Event::SVN_APACHE_AUTH, $params);
        if (!$svnApacheAuth) {
            switch ($requested_authentication_method) {
                case SVN_Apache_SvnrootConf::CONFIG_SVN_AUTH_PERL:
                    $svnApacheAuth = new SVN_Apache_ModPerl($projectInfo);
                    break;
                default:
                    $svnApacheAuth = new SVN_Apache_ModMysql($projectInfo);
            }
        }
        return $svnApacheAuth;
    }
    
    /**
     * Wrapper for EventManager
     * 
     * @return EventManager
     */
    protected function getEventManager() {
        return EventManager::instance();
    }
    
}

?>
