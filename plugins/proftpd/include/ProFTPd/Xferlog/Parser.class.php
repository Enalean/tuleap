<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 */

namespace Tuleap\ProFTPd\Xferlog;

class Parser
{

    /**
     *
     * @param string $line
     *
     * @return Entry
     */
    public function extract($line)
    {
        $pattern = '/^
            (?P<current_time>.*)
            \s
            (?P<transfer_time>[^ ]+)
            \s
            (?P<remote_host>[^ ]+)
            \s
            (?P<file_size>[^ ]+)
            \s
            (?P<filename>[^ ]+)
            \s
            (?P<transfer_type>[^ ]+)
            \s
            (?P<special_action_flag>[^ ]+)
            \s
            (?P<direction>[^ ]+)
            \s
            (?P<access_mode>[^ ]+)
            \s
            (?P<username>[^ ]+)
            \s
            (?P<service_name>[^ ]+)
            \s
            (?P<authentication_method>[^ ]+)
            \s
            (?P<authenticated_user_id>[^ ]+)
            \s
            (?P<completion_status>.)
            $/x';
        if (! preg_match($pattern, $line, $matches)) {
            throw new InvalidEntryException($line);
        }

        return new Entry(
            strtotime($matches['current_time']),
            $matches['transfer_time'],
            $matches['remote_host'],
            $matches['file_size'],
            $matches['filename'],
            $matches['transfer_type'],
            $matches['special_action_flag'],
            $matches['direction'],
            $matches['access_mode'],
            $matches['username'],
            $matches['service_name'],
            $matches['authentication_method'],
            $matches['authenticated_user_id'],
            $matches['completion_status']
        );
    }
}
