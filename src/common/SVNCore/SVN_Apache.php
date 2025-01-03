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
 * Manage generation of Apache Subversion configuration for project Authentication
 * and authorization
 * It generates the content of /etc/httpd/conf.d/codendi_svnroot.conf file
 */
class SVN_Apache
{
    /**
     * Return project location configuration
     */
    public function getConf(ApacheConfRepository $repository): string
    {
        $conf  = '';
        $conf .= '<Location ' . $repository->getURLPath() . ">\n";
        $conf .= "    DAV svn\n";
        $conf .= '    SVNPath ' . $repository->getFilesystemPath() . "\n";
        $conf .= '    AuthzSVNAccessFile ' . $repository->getFilesystemPath() . "/.SVNAccessFile\n";
        // The authentication is managed by nginx but we need to "register" the current user so it can be validated
        // against the SVNAccessFile
        $conf .= "    Require valid-user\n";
        $conf .= "    AuthType Basic\n";
        $conf .= "    AuthBasicProvider anon\n";
        $conf .= "    AuthName SVN\n";
        $conf .= "    Anonymous '*'\n";
        $conf .= "</Location>\n\n";

        return $conf;
    }
}
