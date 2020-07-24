<?php
/**
 * Copyright (c) Enalean, 2017 - 2018. All Rights Reserved.
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

namespace Tuleap\SVN\Logs;

use DateTime;

class Parser
{
    public const BASE_URL = '/svnplugin/';
    public const CORE_URL = '/svnroot/';

    public function parse($file)
    {
        $log_cache = new LogCache();

        $parse_regexp = '/^
            (?P<ip>[\d\.]+)
            \s
            -
            \s
            (?P<username>.+?)
            \s
            \[(?P<date>.+?)\]
            \s
            (?P<url>.+)
            \s
            (?P<code>\d\d\d)
            \s
            \"(?P<svncommand>.*)\"$/x';

        $file_handler = fopen($file, 'r');
        while (($line = fgets($file_handler)) !== false) {
            trim($line);
            $matches = [];
            if (preg_match($parse_regexp, $line, $matches) === 1) {
                $this->parsePluginURL($log_cache, $matches);
                $this->parseCoreURL($log_cache, $matches);
            }
        }
        fclose($file_handler);

        return $log_cache;
    }

    private function parsePluginURL(LogCache $log_cache, array $matches)
    {
        if (strpos($matches['url'], self::BASE_URL) === 0) {
            $url_without_base = substr($matches['url'], strlen(self::BASE_URL));
            $url_parts = explode('/', $url_without_base);

            $log_cache->add(
                $url_parts[0],
                $url_parts[1],
                $matches['username'],
                $this->getActionType($matches['svncommand']),
                new DateTime($matches['date'])
            );
        }
    }

    private function parseCoreURL(LogCache $log_cache, array $matches)
    {
        if (strpos($matches['url'], self::CORE_URL) === 0) {
            $url_without_base = substr($matches['url'], strlen(self::CORE_URL));
            $url_parts = explode('/', $url_without_base);

            $log_cache->addCore(
                $url_parts[0],
                $matches['username'],
                $this->getActionType($matches['svncommand']),
                new DateTime($matches['date'])
            );
        }
    }

    private function getActionType($svn_command)
    {
        return strpos($svn_command, 'commit') === 0 ? LogCache::WRITE : LogCache::READ;
    }
}
