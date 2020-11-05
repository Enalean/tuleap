<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\SVN\ApacheConfRepository;

/**
 * Manage generation of Apache svnroot.conf file with all project subversion
 * configuration
 */
class SVN_Apache_SvnrootConf
{
    public const CONFIG_SVN_LOG_PATH = 'svn_log_path';

    /**
     * @var ApacheConfRepository[]
     */
    private $repositories;

    /**
     * @var SVN_Apache_Auth_Factory
     */
    private $authFactory;

    private $apacheConfHeaders = [];

    /**
     * @param ApacheConfRepository[] $repositories
     */
    public function __construct(SVN_Apache_Auth_Factory $authFactory, array $repositories)
    {
        $this->authFactory  = $authFactory;
        $this->repositories = $repositories;
    }

    /**
     * Generate the SVN apache authentication configuration for each project
     */
    public function getFullConf(): string
    {
        $conf = '';
        foreach ($this->repositories as $repository) {
            $auth = $this->authFactory->get($repository->getProject());
            $this->collectApacheConfHeaders($auth);
            $conf .= $auth->getConf($repository);
        }

        return $this->getApacheConfHeaders() . $conf;
    }

    private function collectApacheConfHeaders(SVN_Apache $auth)
    {
        $headers = $auth->getHeaders();
        $key     = md5($headers);
        $this->apacheConfHeaders[$key] = $headers;
    }

    private function getApacheConfHeaders()
    {
        $log_file_path = ForgeConfig::get(self::CONFIG_SVN_LOG_PATH);
        $headers  = '';
        $headers .= "# " . ForgeConfig::get('sys_name') . " SVN repositories\n";
        $headers .= '# Generated at ' . date('c') . "\n";
        $headers .= "# Custom log file for SVN queries\n";
        $headers .= 'CustomLog ' . $log_file_path . ' "%h %l %u %t %U %>s \"%{SVN-ACTION}e\"" env=SVN-ACTION' . "\n\n";
        $headers .= implode(PHP_EOL, $this->apacheConfHeaders);
        return $headers;
    }
}
