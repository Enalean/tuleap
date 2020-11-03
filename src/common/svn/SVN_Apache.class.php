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
 * Manage generation of Apache Subversion configuration for project Authentication
 * and authorization
 * It generates the content of /etc/httpd/conf.d/codendi_svnroot.conf file
 */
abstract class SVN_Apache
{
    /**
     * Return something to be inserted at the top of the svnroot.conf file
     */
    public function getHeaders(): string
    {
        return '';
    }

    /**
     * Return project location configuration
     */
    public function getConf(ApacheConfRepository $repository): string
    {
        $conf = '';
        $conf .= "<Location " . $repository->getURLPath() . ">\n";
        $conf .= "    DAV svn\n";
        $conf .= "    SVNPath " . $repository->getFilesystemPath() . "\n";
        $conf .= "    SVNIndexXSLT \"/svn/repos-web/view/repos.xsl\"\n";
        $conf .= $this->getRepositoryAuthorization($repository);
        $conf .= $this->getProjectAuthentication($repository->getProject());
        $conf .= "</Location>\n\n";

        return $conf;
    }

    /**
     * Returns the Apache authentication directives for given project
     */
    abstract protected function getProjectAuthentication(Project $project): string;

    /**
     * Returns the standard Apache authentication directives (shared by most modules)
     */
    protected function getCommonAuthentication(Project $project): string
    {
        $conf = '';
        $conf .= "    Require valid-user\n";
        $conf .= "    AuthType Basic\n";
        $conf .= "    AuthName \"Subversion Authorization (" . $this->escapeStringForApacheConf($project->getPublicName()) . ")\"\n";
        return $conf;
    }


    protected function getRepositoryAuthorization(ApacheConfRepository $repository): string
    {
        return "    AuthzSVNAccessFile " . $repository->getFilesystemPath() . "/.SVNAccessFile\n";
    }

    /**
     * Replace double quotes by single quotes in project name (conflict with Apache realm name)
     */
    protected function escapeStringForApacheConf(?string $str): string
    {
        if ($str === null) {
            return '';
        }
        return strtr($str, "\"", "'");
    }
}
