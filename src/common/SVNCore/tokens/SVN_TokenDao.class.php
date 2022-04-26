<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

class SVN_TokenDao extends DataAccessObject
{
    public function getSVNTokensForUser($user_id)
    {
        $user_id = $this->da->escapeInt($user_id);

        $sql = "SELECT *
                FROM svn_token
                WHERE user_id = $user_id";

        return $this->retrieve($sql);
    }

    public function generateSVNTokenForUser($user_id, $token, $comment)
    {
        $user_id        = $this->da->escapeInt($user_id);
        $token          = $this->da->quoteSmart($token);
        $generated_date = time();
        $comment        = $this->da->quoteSmart($comment);

        $sql = "INSERT INTO svn_token (user_id, token, generated_date, comment)
                VALUES ($user_id, $token, $generated_date, $comment)";

        return $this->update($sql);
    }

    public function deleteSVNTokensForUser($user_id, $tokens_to_be_deleted)
    {
        $user_id              = $this->da->escapeInt($user_id);
        $tokens_to_be_deleted = $this->da->escapeIntImplode($tokens_to_be_deleted);

        $sql = "DELETE FROM svn_token
                WHERE user_id = $user_id
                  AND id IN ($tokens_to_be_deleted)";

        return $this->update($sql);
    }
}
