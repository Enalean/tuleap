<?php

class LDAP_SVN_Apache extends SVN_Apache_Configuration {
    /**
     * @var LDAP_ProjectManager
     */
    private $ldapProjectManager;
    
    /**
     * @var LDAP
     */
    private $ldap;
    
    /**
     * @var String
     */
    private $ldapUrl = null;
    
    public function __construct(LDAP $ldap, LDAP_ProjectManager $ldapProjectManager, $projects) {
        parent::__construct($projects);
        $this->ldap               = $ldap;
        $this->ldapProjectManager = $ldapProjectManager;
    }
    
    /**
     * Authentification performed by LDAP server
     * 
     * @see src/common/backend/BackendSVN#getProjectSVNApacheConfAuth()
     * @param Array $row DB entry of a given project
     * 
     * @return String
     */
    public function getProjectSVNApacheConfAuth($row) {
        if ($this->ldapProjectManager->hasSVNLDAPAuth($row['group_id'])) {
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
            if ($firstIsLdaps) {
                $this->ldapUrl = 'ldaps://';
            } else {
                $this->ldapUrl = 'ldap://';
            }
            $this->ldapUrl .= implode(' ', $serverList).'/'.$this->ldap->getLDAPParam('dn');
        }
        return $this->ldapUrl;
    }
}

?>
