<?php
/**
 * Copyright (c) Enalean, 2015 - 2016. All rights reserved
 *
 * This file is a part of Tuleap.
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
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>
 */

use Tuleap\Git\Gitolite\VersionDetector;

class Git_Gitolite_GitoliteRCReader
{

    public const OLD_GITOLITE_RC_PATH = "/usr/com/gitolite/.gitolite.rc";
    public const NEW_GITOLITE_RC_PATH = "/var/lib/gitolite/.gitolite.rc";

    public function __construct(VersionDetector $version_detector)
    {
        $this->version_detector = $version_detector;
    }

    private function getGitoliteRCPath()
    {
        if (! file_exists(self::OLD_GITOLITE_RC_PATH)) {
            return self::NEW_GITOLITE_RC_PATH;
        }

        return self::OLD_GITOLITE_RC_PATH;
    }

    private function extractHostnameFromRCFile()
    {
        $file_path    = $this->getGitoliteRCPath();
        $file_content = file_get_contents($file_path);
        $match        = [];

        $hostname_found = preg_match('/^\s*HOSTNAME\s*=>\s*\"(.+)\".*/m', $file_content, $match);

        if (! $hostname_found) {
            return;
        }

        return $match[1];
    }

    public function getHostname()
    {
        if (! $this->version_detector->isGitolite3()) {
            return;
        }

        return $this->extractHostnameFromRCFile();
    }
}
