<?php
/**
 * Copyright (c) Ericsson AB, 2018. All Rights Reserved.
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

namespace Tuleap\Tracker\Notifications;

use Cocur\Slugify\Slugify;

/**
 * This class is responsible for formatting the sender name field of email notifications
 * It currently uses a syntax like this:
 * %realname through Tuleap
 * where %realname would be replaced by the value represented by 'realname' in $kvargs
 * unknown formatting will be slugified and then ignored
 * */
class ConfigNotificationEmailCustomSenderFormatter
{
    const SLUGIFY_ARGS = [
        'separator' => ' ',
        'lowercase' => false
    ];

    /**
     * @var array
     * */
    private $kvargs;

    /**
     * @param $kvargs assoc array of fields exposed for formatting and their values
     * */
    public function __construct(array $kvargs)
    {
        $this->kvargs = $kvargs;
    }

    /**
     * Replaces all occurrences of %field_name with the value of kvargs['field_name'] in $str
     * @param string $str
     * @return the input string, with all formatting using %field_name replaced by values in kvargs
     * */
    public function formatString($str)
    {
        $result = $str;
        $slugify = new Slugify(self::SLUGIFY_ARGS);
        foreach ($this->kvargs as $k => $v) {
            $result = str_replace("%$k", $slugify->slugify($v), $result);
        }
        return $slugify->slugify($result);
    }
}
