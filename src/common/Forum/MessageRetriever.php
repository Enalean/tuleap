<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Forum;

class MessageRetriever
{
    /**
     * @throws PermissionToAccessForumException
     * @throws MessageNotFoundException
     */
    public function getMessage(int $id): Message
    {
        /*
            Figure out which group this message is in, for the sake of the admin links
        */
        $result = db_query('SELECT forum_group_list.group_id,forum_group_list.forum_name,forum.group_forum_id,forum.thread_id
            FROM forum_group_list,forum
            WHERE forum_group_list.group_forum_id=forum.group_forum_id
              AND forum_group_list.is_public IN (0, 1)
              AND forum.msg_id=' . db_ei($id));

        if (! $result || db_numrows($result) < 1) {
            throw new MessageNotFoundException('message not found.');
        }

        $group_id   = db_result($result, 0, 'group_id');
        $forum_id   = db_result($result, 0, 'group_forum_id');
        $forum_name = db_result($result, 0, 'forum_name');

        // Check permissions
        if (! forum_utils_access_allowed($forum_id)) {
            throw new PermissionToAccessForumException(_('Forum is restricted'));
        }

        //check if the message is a comment on a piece of news.  If so, check permissions on this news
        $qry = 'SELECT * FROM news_bytes WHERE forum_id=' . db_ei($forum_id);
        $res = db_query($qry);
        if (db_numrows($res) > 0) {
            if (! forum_utils_news_access($forum_id)) {
                throw new PermissionToAccessForumException($GLOBALS['Language']->getText('news_admin_index', 'permission_denied'));
            }
        }

        $sql = 'SELECT user.user_name,forum.group_forum_id,forum.thread_id,forum.subject,forum.date,forum.body ' .
            'FROM forum,user WHERE user.user_id=forum.posted_by AND forum.msg_id=' . db_ei($id);

        $result = db_query($sql);

        if (! $result || db_numrows($result) < 1) {
            throw new MessageNotFoundException('message not found.');
        }

        return new Message(
            db_result($result, 0, 'subject'),
            db_result($result, 0, 'body'),
            db_result($result, 0, 'user_name'),
            (int) db_result($result, 0, 'date'),
            (int) $group_id,
            (int) db_result($result, 0, 'thread_id'),
            (int) $forum_id,
            $forum_name,
        );
    }
}
