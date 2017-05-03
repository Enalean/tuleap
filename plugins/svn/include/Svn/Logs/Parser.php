<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Tuleap\Svn\Logs;

use \DateTime;

class Parser
{
    const BASE_URL = '/svnplugin/';

    public function parse($file)
    {
        $log_cache = new LogCache();

        $parse_regexp = '/^
            (?P<ip>[\d\.]+)
            \s
            -
            \s
            (?P<username>.+)
            \s
            \[(?P<date>.+)\]
            \s
            (?P<url>.+)
            \s
            (?P<code>\d\d\d)
            \s
            \"(?P<svncommand>.*)\"$/x';

        $file_handler = fopen($file, 'r');
        while (($line = fgets($file_handler)) !== false) {
            trim($line);
            $matches = array();
            if (preg_match($parse_regexp, $line, $matches) === 1) {
                list($project_name, $repo_name) = $this->parseURL($matches['url']);
                $log_cache->add(
                    $project_name,
                    $repo_name,
                    $matches['username'],
                    $this->getActionType($matches['svncommand']),
                    new DateTime($matches['date'])
                );
            }
        }
        fclose($file_handler);

        return $log_cache;
    }

    private function parseURL($url)
    {
        if (strpos($url, self::BASE_URL) === 0) {
            $url_without_base = substr($url, strlen(self::BASE_URL));
            $url_parts = explode('/', $url_without_base);
            return array($url_parts[0], $url_parts[1]);
        }
    }

    private function getActionType($svn_command)
    {
        return strpos($svn_command, 'commit') === 0 ? LogCache::WRITE : LogCache::READ;
    }
}
