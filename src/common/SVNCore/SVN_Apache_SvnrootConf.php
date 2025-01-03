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

use Tuleap\SVNCore\ApacheConfRepository;

/**
 * Manage generation of Apache svnroot.conf file with all project subversion
 * configuration
 */
class SVN_Apache_SvnrootConf
{
    public const CONFIG_SVN_LOG_PATH = 'svn_log_path';

    /**
     * @param ApacheConfRepository[] $repositories
     */
    public function __construct(private SVN_Apache $svn_apache_conf_auth, private array $repositories)
    {
    }

    /**
     * Generate the SVN apache authentication configuration for each project
     */
    public function getFullConf(): string
    {
        $conf = '';
        foreach ($this->repositories as $repository) {
            $conf .= $this->svn_apache_conf_auth->getConf($repository);
        }

        return $this->getApacheConfHeaders() . $conf;
    }

    private function getApacheConfHeaders(): string
    {
        $log_file_path = ForgeConfig::get(self::CONFIG_SVN_LOG_PATH);
        $headers       = '';
        $headers      .= '# ' . ForgeConfig::get(\Tuleap\Config\ConfigurationVariables::NAME) . " SVN repositories\n";
        $headers      .= '# Generated at ' . date('c') . "\n";
        $headers      .= "# Custom log file for SVN queries\n";
        $headers      .= 'CustomLog ' . $log_file_path . ' "%h %l %u %t %U %>s \"%{SVN-ACTION}e\"" env=SVN-ACTION' . "\n\n";
        return $headers;
    }
}
