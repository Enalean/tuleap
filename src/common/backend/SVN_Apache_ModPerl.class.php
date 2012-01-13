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

require_once 'SVN_Apache.class.php';

class SVN_Apache_ModPerl extends SVN_Apache {

    protected function getApacheConfHeaders() {
        $ret  = parent::getApacheConfHeaders();
        $ret .= 'PerlLoadModule Apache::Codendi'."\n\n";
        return $ret;
    }
    
    protected function getProjectSVNApacheConfAuth($row) {
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
