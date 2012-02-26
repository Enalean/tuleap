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

require_once 'SVN_Apache_Auth_Factory.class.php';

/**
 * Manage generation of Apache svnroot.conf file with all project subversion
 * configuration
 */
class SVN_Apache_SvnrootConf {
    const CONFIG_SVN_AUTH_KEY   = 'sys_auth_svn_mod';
    const CONFIG_SVN_AUTH_MYSQL = 'modmysql';
    const CONFIG_SVN_AUTH_PERL  = 'modperl';
    
    /**
     * @var Array
     */
    private $projects;
    
    /**
     * @var SVN_Apache_Auth_Factory
     */
    private $authFactory;
    
    private $apacheConfHeaders = array();
    
    function __construct(SVN_Apache_Auth_Factory $authFactory, $projects) {
        $this->authFactory = $authFactory;
        $this->projects    = $projects;
    }
    
    /**
     * Generate the SVN apache authentication configuration for each project
     * 
     * @return String
     */
    public function getFullConf() {
        $conf = '';
        foreach ($this->projects as $row) {
            $auth = $this->authFactory->get($row);
            $this->collectApacheConfHeaders($auth);
            $conf .= $auth->getConf();
        }
        return $this->getApacheConfHeaders().$conf;
    }
    
    private function collectApacheConfHeaders(SVN_Apache $auth) {
        $headers = $auth->getHeaders();
        $key     = md5($headers);
        $this->apacheConfHeaders[$key] = $headers;
    }
    
    private function getApacheConfHeaders() {
        $headers  = '';
        $headers .= "# " . $GLOBALS['sys_name'] . " SVN repositories\n";
        $headers .= "# Custom log file for SVN queries\n";
        $headers .= 'CustomLog logs/svn_log "%h %l %u %t %U %>s \"%{SVN-ACTION}e\"" env=SVN-ACTION' . "\n\n";
        $headers .= implode(PHP_EOL, $this->apacheConfHeaders);
        return $headers;
    }
    
    /**
     * Return the right Authentication module for SVN/apache
     * 
     * @param Array $projects
     * 
     * @return SVN_Apache_ModMysql
     */
    protected function getApacheAuthMod($projects) {
        if ($this->getConfig(self::CONFIG_SVN_AUTH_KEY) == self::CONFIG_SVN_AUTH_PERL) {
            return new SVN_Apache_ModPerl($projects);
        }
        return new SVN_Apache_ModMysql($projects);
    }
}

?>
