<?php
/**
 * Copyright (c) STMicroelectronics, 2009. All Rights Reserved.
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once 'common/backend/BackendSVN.class.php';
require_once 'LDAP_ProjectManager.class.php';
require_once 'LDAP.class.php';
require_once 'LDAP_UserManager.class.php';

class LDAP_BackendSVN extends BackendSVN {
    private $ldap;
    private $ldapProjectManager = null;
    private $ldapUrl            = null;
    private $ldapUserManager    = null;

    /**
     * Setup backend
     *
     * @param LDAP $ldap The ldap connexion
     */
    public function setUp(LDAP $ldap) {
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
    protected function getProjectSVNApacheConfAuth($row) {
        $ldapPrjMgr = $this->getLDAPProjectManager();
        if ($ldapPrjMgr->hasSVNLDAPAuth($row['group_id'])) {
            $conf = '';
            $conf .= '    AuthType Basic'.PHP_EOL;
            $conf .= '    AuthBasicProvider ldap'.PHP_EOL;
            $conf .= '    AuthzLDAPAuthoritative Off'.PHP_EOL;
            $conf .= '    AuthName "LDAP Subversion Authorization ('.$this->escapeStringForApacheConf($row['group_name']).')"'.PHP_EOL;
            $conf .= '    AuthLDAPUrl "'.$this->getLDAPServersUrl().'"'.PHP_EOL;
            $conf .= '    Require valid-user'.PHP_EOL;
            return $conf;
        } else {
            return parent::getProjectSVNApacheConfAuth($row);
        }
    }

    /**
     * Return a SVNAccessFile group definition based on given userids
     *
     * @param String $groupName
     * @param Array  $userIds
     *
     * @return String
     */
    function getSVNGroupDef($groupName, $userIds) {
        $ldapUserManager = $this->getLDAPUserManager();
        $dar             = $ldapUserManager->getLdapLoginFromUserIds($userIds);
        $members         = $groupName.' = ';
        $first           = true;
        foreach($dar as $row) {
            if ($first) {
                $first = false;
            } else {
                $members .= ', ';
            }
            $members .= strtolower($row['ldap_uid']);
        }
        $members .= "\n";
        return $members;
    }

    /**
     * SVNAccessFile ugroups definitions
     *
     * @see src/common/backend/BackendSVN#getSVNAccessProjectMembers()
     *
     * @param Project $project
     *
     * @return String
     */
    function getSVNAccessProjectMembers(Project $project) {
        $ldapPrjMgr = $this->getLDAPProjectManager();
        if ($ldapPrjMgr->hasSVNLDAPAuth($project->getID())) {
            return $this->getSVNGroupDef('members', $project->getMembersId());
        } else {
            return parent::getSVNAccessProjectMembers($project);
        }
    }

    /**
     * SVNAccessFile ugroups definitions
     *
     * @see src/common/backend/BackendSVN#getSVNAccessUserGroupMembers()
     *
     * @param Project $project
     *
     * @return String
     */
    function getSVNAccessUserGroupMembers(Project $project) {
        $ldapPrjMgr = $this->getLDAPProjectManager();
        if ($ldapPrjMgr->hasSVNLDAPAuth($project->getID())) {
            $conf = "";
            $ugroup_dao = $this->getUGroupDao();
            $dar = $ugroup_dao->searchByGroupId($project->getId());
            foreach ($dar as $row) {
                $ugroup = $this->getUGroupFromRow($row);
                $members = array();
                foreach ($ugroup->getMembers() as $user) {
                    $members[] = $user->getId();
                }
                if ($ugroup->getName() && count($members) > 0) {
                    $conf .= $this->getSVNGroupDef($ugroup->getName(), $members);
                }
            }
            $conf .= "\n";
            return $conf;
        } else {
            return parent::getSVNAccessUserGroupMembers($project);
        }
    }

    /**
     * SVNAccessFile definition for repository root
     * 
     * Block access to non project members if:
     * - project is private,
     * - or SVN is private
     * - or "restricted users" is enabled
     * 
     * @see src/common/backend/BackendSVN#getSVNAccessRootPathDef($project)
     * 
     * @param Project $project
     * 
     * @return String
     */
    function getSVNAccessRootPathDef($project) {
        $ldapPrjMgr = $this->getLDAPProjectManager();
        if ($ldapPrjMgr->hasSVNLDAPAuth($project->getID())) {
            $conf = "[/]\n";
            if (!$project->isPublic() || $project->isSVNPrivate() || $GLOBALS['sys_allow_restricted_users'] == 1) {
                $conf .= "* = \n";
            } else {
                $conf .= "* = r\n";
            }
            $conf .= "@members = rw\n";
            return $conf;
        } else {
            return parent::getSVNAccessRootPathDef($project);
        }
    }

    /**
     * Format LDAP url for apache mod_ldap
     *
     * Combine Codendin ldap parameter 'sys_ldap_server' and 'sys_ldap_dn' to
     * generate an Apache mod_authnz_ldap compatible url
     *
     * @see http://httpd.apache.org/docs/2.2/mod/mod_authnz_ldap.html#authldapurl
     *
     * @return String
     */
    public function getLDAPServersUrl() {
        if ($this->ldapUrl === null) {
            $ldap = $this->getLDAP();
            $serverList = explode(',', $ldap->getLDAPParam('server'));
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
            if ($firstIsLdaps) {
                $this->ldapUrl = 'ldaps://';
            } else {
                $this->ldapUrl = 'ldap://';
            }
            $this->ldapUrl .= implode(' ', $serverList).'/'.$ldap->getLDAPParam('dn');
        }
        return $this->ldapUrl;
    }

    /**
     * Wrapper for LDAP_ProjectManager
     * 
     * @return LDAP_ProjectManager
     */
    protected function getLDAPProjectManager() {
        if ($this->ldapProjectManager === null) {
            $this->ldapProjectManager = new LDAP_ProjectManager();
        }
        return $this->ldapProjectManager;
    }

    /**
     * Wrapper for LDAP
     * 
     * @return LDAP
     */
    protected function getLDAP() {
        return $this->ldap;
    }

    /**
     * Wrapper for LDAP_UserManager
     *
     * @return LDAP_UserManager
     */
    protected function getLDAPUserManager() {
        if ($this->ldapUserManager === null) {
            $this->ldapUserManager = new LDAP_UserManager($this->ldap);
        }
        return $this->ldapUserManager;
    }
}
?>