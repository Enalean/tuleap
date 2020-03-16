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

class Search_SearchForumResultPresenter
{
    public function __construct(array $result)
    {
        $this->message_id      = $result['msg_id'];
        $this->message_subject = $result['subject'];
        $this->message_author  = $result['user_name'];
        $this->message_date    = $result['date'];
    }

    public function message_subject()
    {
        return $this->message_subject;
    }

    public function message_uri()
    {
        return "/forum/message.php?msg_id=" . $this->message_id;
    }

    public function message_author()
    {
        return $this->message_author;
    }

    public function message_date()
    {
        return format_date($GLOBALS['Language']->getText('system', 'datefmt'), $this->message_date);
    }
}
