<?php
/**
 * Copyright (c) Enalean, 2015-2016. All Rights Reserved.
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

class LDAP_SVN_Apache_ModPerl extends SVN_Apache_ModPerl
{
    /**
     * @var LDAP
     */
    private $ldap;

    public function __construct(LDAP $ldap, Parameters $cache_parameters, array $project)
    {
        parent::__construct($cache_parameters, $project);
        $this->ldap = $ldap;
    }

    /**
     * @return String
     */
    public function getProjectAuthentication($row)
    {
        $conf        = parent::getProjectAuthentication($row);
        $server_list = $this->escapeStringForApacheConf($this->ldap->getLDAPParam('server') ?? '');
        $ldap_dn     = $this->escapeStringForApacheConf($this->ldap->getLDAPParam('dn') ?? '');
        $ldap_uid    = $this->escapeStringForApacheConf($this->ldap->getLDAPParam('uid') ?? '');
        $conf       .= '    TuleapLdapServers "' . $server_list . '"' . PHP_EOL;
        $conf       .= '    TuleapLdapDN "' . $ldap_dn . '"' . PHP_EOL;
        $conf       .= '    TuleapLdapUid "' . $ldap_uid . '"' . PHP_EOL;
        if ($this->ldap->getLDAPParam('bind_dn') && $this->ldap->getLDAPParam('bind_passwd')) {
            $ldap_bind_dn     = $this->escapeStringForApacheConf($this->ldap->getLDAPParam('bind_dn') ?? '');
            $ldap_bind_passwd = $this->escapeStringForApacheConf($this->ldap->getLDAPParam('bind_passwd') ?? '');
            $conf            .= '    TuleapLdapBindDN "' . $ldap_bind_dn . '"' . PHP_EOL;
            $conf            .= '    TuleapLdapBindPassword "' . $ldap_bind_passwd . '"' . PHP_EOL;
        }
        return $conf;
    }
}
