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

/**
 * Manage generation of Apache Subversion configuration for project Authentication
 * and authorization
 * It generates the content of /etc/httpd/conf.d/codendi_svnroot.conf file
 */
abstract class SVN_Apache
{
    private $project = array();

    /**
     * Takes a project DB row
     *
     * @param Array $project
     */
    public function __construct($project)
    {
        $this->project = $project;
    }

    /**
     * Return something to be inserted at the top of the svnroot.conf file
     *
     * @return String
     */
    public function getHeaders()
    {
        return '';
    }

    /**
     * Return project location configuration
     *
     * @return String
     */
    public function getConf($public_path, $system_path)
    {
        $conf = '';
        $conf .= "<Location $public_path>\n";
        $conf .= "    DAV svn\n";
        $conf .= "    SVNPath $system_path\n";
        $conf .= "    SVNIndexXSLT \"/svn/repos-web/view/repos.xsl\"\n";
        $conf .= $this->getRepositoryAuthorization($system_path);
        $conf .= $this->getProjectAuthentication($this->project);
        $conf .= "</Location>\n\n";

        return $conf;
    }

    /**
     * Returns the Apache authentication directives for given project
     *
     * @param Array $row Project DB row
     *
     * @return String
     */
    abstract protected function getProjectAuthentication($row);

    /**
     * Returns the standard Apache authentication directives (shared by most modules)
     *
     * @param String $projectName
     *
     * @return String
     */
    protected function getCommonAuthentication($projectName)
    {
        $conf = '';
        $conf .= "    Require valid-user\n";
        $conf .= "    AuthType Basic\n";
        $conf .= "    AuthName \"Subversion Authorization (" . $this->escapeStringForApacheConf($projectName) . ")\"\n";
        return $conf;
    }


    protected function getRepositoryAuthorization($svn_dir)
    {
        $conf = "    AuthzSVNAccessFile " . $svn_dir . "/.SVNAccessFile\n";
        return $conf;
    }

    /**
     * Replace double quotes by single quotes in project name (conflict with Apache realm name)
     *
     * @param String $str
     *
     * @return String
     */
    protected function escapeStringForApacheConf($str)
    {
        return strtr($str, "\"", "'");
    }
}
