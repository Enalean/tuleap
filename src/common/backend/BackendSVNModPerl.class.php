<?php

require_once 'common/backend/BackendSVN.class.php';

/**
 * Backend class to work on subversion repositories
 */
class BackendSVNModPerl extends BackendSVN {

    public function getApacheConfHeaders() {
        $ret  = parent::getApacheConfHeaders();
        $ret .= 'PerlLoadModule Apache::Codendi'."\n\n";
        return $ret;
    }
    
    public function getProjectSVNApacheConfAuth($row) {
        $conf = '';
        $conf .= $this->getProjectSVNApacheConfDefault($row['group_name']);
        $conf .= "    PerlAccessHandler Apache::Authn::Codendi::access_handler\n";
        $conf .= "    PerlAuthenHandler Apache::Authn::Codendi::authen_handler\n";
        $conf .= '    CodendiDSN "DBI:mysql:' . $GLOBALS['sys_dbname'] . ':' . $GLOBALS['sys_dbhost'] . '"' . "\n";
        $conf .= '    CodendiDbUser "' . $GLOBALS['sys_dbauth_user'] . '"' . "\n";
        $conf .= '    CodendiDbPass "' . $GLOBALS['sys_dbauth_passwd'] . '"' . "\n";
        $conf .= '    CodendiGroupId "' . $row['group_id'] . '"' . "\n";
        $conf .= '    CodendiCacheCredsMax 10' . "\n";
        return $conf;
    }
    
}

?>
