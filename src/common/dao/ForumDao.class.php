<?php
/**
 * Copyright (c) Xerox Corporation, CodeXTeam, 2001-2009. All rights reserved
 *
 * This file is a part of CodeX.
 *
 * CodeX is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * CodeX is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class ForumDao extends DataAccessObject
{
    public function __construct($da = null)
    {
        parent::__construct($da);
        $this->table_name = 'forum_group_list';
    }

    public function searchByGroupForumId($forum_id)
    {
        $forum_id = $this->da->quoteSmart($forum_id);
        $sql = "SELECT group_id 
                FROM $this->table_name
                WHERE group_forum_id=$forum_id";
        return $this->retrieve($sql);
    }

    public function getMessageProjectIdAndForumId($message_id)
    {
        $message_id = $this->da->escapeInt($message_id);

        $sql = "SELECT forum_group_list.group_id, forum.group_forum_id
                FROM forum
                  JOIN forum_group_list ON (forum.group_forum_id = forum_group_list.group_forum_id)
                WHERE forum.msg_id = $message_id";

        return $this->retrieveFirstRow($sql);
    }

    public function searchGlobal($words, $exact, $offset, $forum_id)
    {
        $this->searchGlobalPaginated($words, $exact, $offset, $forum_id, 26);
    }

    public function searchGlobalPaginated($words, $exact, $offset, $forum_id, $limit)
    {
        $offset = $this->da->escapeInt($offset);
        $limit  = $this->da->escapeInt($limit);
        if ($exact === true) {
            $body    = $this->searchExactMatch($words);
            $subject = $this->searchExactMatch($words);
        } else {
            $body    = $this->searchExactMatch('forum.body', $words);
            $subject = $this->searchExactMatch('forum.subject', $words);
        }
        $forum_id = $this->da->escapeInt($forum_id);

        $sql = "SELECT forum.msg_id, forum.subject, forum.date, user.user_name
                FROM forum
                    JOIN user ON (user.user_id = forum.posted_by)
                WHERE ((forum.body LIKE $body) OR (forum.subject LIKE $subject))
                    AND forum.group_forum_id = $forum_id
                GROUP BY msg_id, subject, date, user_name
                LIMIT $offset, $limit";
        return $this->retrieve($sql);
    }

    public function updatePublicForumToPrivate($group_id)
    {
        $group_id = $this->da->escapeInt($group_id);
        $sql = "UPDATE forum_group_list
                SET is_public = 0
                WHERE group_id = $group_id
                AND is_public = 1";

        return $this->update($sql);
    }
}
