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

require_once 'common/svn/SVN_Apache.class.php';

class LDAP_SVN_Apache extends SVN_Apache {
    const DEFAULT_ATTRIBUTE = 'uid';

    /**
     * @var LDAP
     */
    private $ldap;
    
    /**
     * @var String
     */
    private $ldapUrl = null;
    
    public function __construct(LDAP $ldap, $projects) {
        parent::__construct($projects);
        $this->ldap = $ldap;
    }
    
    /**
     * Authentification performed by LDAP server
     * 
     * @see src/common/backend/BackendSVN#getProjectSVNApacheConfAuth()
     * @param Array $row DB entry of a given project
     * 
     * @return String
     */
    public function getProjectAuthentication($row) {
        $conf = '';
        $conf .= '    AuthType Basic' . PHP_EOL;
        $conf .= '    AuthBasicProvider ldap' . PHP_EOL;
        $conf .= '    AuthzLDAPAuthoritative Off' . PHP_EOL;
        $conf .= '    AuthName "LDAP Subversion Authorization (' . $this->escapeStringForApacheConf($row['group_name']) . ')"' . PHP_EOL;
        $conf .= '    AuthLDAPUrl "' . $this->getLDAPServersUrl() . '"' . PHP_EOL;
        $conf .= '    Require valid-user' . PHP_EOL;
        if ($this->ldap->getLDAPParam('bind_dn')) {
            $conf .= '    AuthLDAPBindDN "' .$this->ldap->getLDAPParam('bind_dn'). '"' . PHP_EOL;
            $conf .= '    AuthLDAPBindPassword "' .$this->ldap->getLDAPParam('bind_passwd'). '"' . PHP_EOL;
        }
        return $conf;
    }

    /**
     * Format LDAP url for apache mod_ldap
     *
     * Combine ldap parameter 'sys_ldap_server' and 'sys_ldap_dn' to
     * generate an Apache mod_authnz_ldap compatible url
     *
     * @see http://httpd.apache.org/docs/2.2/mod/mod_authnz_ldap.html#authldapurl
     *
     * @return String
     */
    public function getLDAPServersUrl() {
        if ($this->ldapUrl === null) {
            $this->ldapUrl = $this->getHosts().$this->getBaseDn().$this->getAttribute();
        }
        return $this->ldapUrl;
    }

    private function getHosts() {
        $serverList = explode(',', $this->ldap->getLDAPParam('server'));
        $firstIsLdaps = false;
        foreach ($serverList as $k => $server) {
            $server = strtolower(trim($server));
            if ($k == 0 && strpos($server, 'ldaps://') === 0) {
                $firstIsLdaps = true;
            }
            $server = str_replace('ldap://', '', $server);
            $server = str_replace('ldaps://', '', $server);
            $serverList[$k] = $server;
        }
        $prefix = 'ldap://';
        if ($firstIsLdaps) {
            $prefix = 'ldaps://';
        }
        return $prefix.implode(' ', $serverList);
    }

    private function getBaseDn() {
        return '/'.$this->ldap->getLDAPParam('dn');
    }

    private function getAttribute() {
        if ($this->ldap->getLDAPParam('uid') && $this->ldap->getLDAPParam('uid') != self::DEFAULT_ATTRIBUTE) {
            return '?'.$this->ldap->getLDAPParam('uid');
        }
    }

}

?>
