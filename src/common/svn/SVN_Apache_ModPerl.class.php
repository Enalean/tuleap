<?php
/**
 * Copyright (c) Enalean, 2012-2016. All Rights Reserved.
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

require_once 'SVN_Apache.class.php';

class SVN_Apache_ModPerl extends SVN_Apache
{
    /**
     * @var Parameters
     */
    private $cache_parameters;

    public function __construct(Parameters $cache_parameters, array $project)
    {
        parent::__construct($project);
        $this->cache_parameters = $cache_parameters;
    }

    public function getHeaders()
    {
        $ret = 'PerlLoadModule Apache::Tuleap' . PHP_EOL;
        return $ret;
    }

    protected function getProjectAuthentication($row)
    {
        $tuleap_dsn          = $this->escapeStringForApacheConf(
            'DBI:mysql:' . ForgeConfig::get('sys_dbname') . ':' . ForgeConfig::get('sys_dbhost')
        );
        $maximum_credentials = $this->escapeStringForApacheConf($this->cache_parameters->getMaximumCredentials());
        $lifetime            = $this->escapeStringForApacheConf($this->cache_parameters->getLifetime());

        $conf  = '';
        $conf .= $this->getCommonAuthentication($row['group_name']);
        $conf .= "    PerlAccessHandler Apache::Authn::Tuleap::access_handler\n";
        $conf .= "    PerlAuthenHandler Apache::Authn::Tuleap::authen_handler\n";
        $conf .= '    TuleapDSN "' . $tuleap_dsn . '"' . "\n";
        $conf .= '    TuleapDbUser "' . $this->escapeStringForApacheConf(ForgeConfig::get('sys_dbauth_user')) . '"' . "\n";
        $conf .= '    TuleapDbPass "' . $this->escapeStringForApacheConf(ForgeConfig::get('sys_dbauth_passwd')) . '"' . "\n";
        $conf .= '    TuleapGroupId "' . $this->escapeStringForApacheConf($row['group_id']) . '"' . "\n";
        $conf .= '    TuleapCacheCredsMax ' . $maximum_credentials . "\n";
        $conf .= '    TuleapCacheLifetime ' . $lifetime . "\n";

        $conf .= $this->addRedisBlock();

        return $conf;
    }

    private function addRedisBlock()
    {
        $conf = '';
        $redis_server = trim(ForgeConfig::get('redis_server'));
        if ($redis_server) {
            $redis_server .= ':' . trim(ForgeConfig::get('redis_port'));
            $conf .= '    TuleapRedisServer "' . $this->escapeStringForApacheConf($redis_server) . '"' . "\n";
        }
        $redis_password = trim(ForgeConfig::get('redis_password'));
        if ($redis_password) {
            $conf .= '    TuleapRedisPassword "' . $this->escapeStringForApacheConf($redis_password) . '"' . "\n";
        }
        return $conf;
    }
}
