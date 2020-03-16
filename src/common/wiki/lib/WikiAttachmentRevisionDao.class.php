<?php
/*
 * Copyright STMicroelectronics, 2006
 * Originally written by Manuel VACELET, STMicroelectronics, 2006
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

class WikiAttachmentRevisionDao extends DataAccessObject
{
    /**
     * Create a new attachment revision
     *
     * @return bool success or failure
     */
    public function create($attachmentId, $ownerId, $date, $revision, $type, $size)
    {
        $sql = sprintf(
            'INSERT INTO wiki_attachment_revision SET'
                       . '  attachment_id = %d'
                       . ', user_id       = %d'
                       . ', date          = %d'
                       . ', revision      = %d'
                       . ', mimetype      = "%s"'
                       . ', size          = %d',
            $attachmentId,
            $ownerId,
            $date,
            $revision,
            $this->da->quoteSmart($type),
            $size
        );

        $inserted = $this->update($sql);
        return $inserted;
    }

    public function log($attachmentId, $revision, $groupId, $userId, $date)
    {
        $sql = sprintf(
            'INSERT INTO wiki_attachment_log SET'
                       . '  user_id                     = %d'
                       . ', group_id                    = %d'
                       . ', wiki_attachment_id          = %d'
                       . ', wiki_attachment_revision_id = %d'
                       . ', time                        = %d',
            $userId,
            $groupId,
            $attachmentId,
            $revision,
            $date
        );

        $inserted = $this->update($sql);
        return $inserted;
    }

    /**
     * Get one revision
     */
    public function getRevision($attachmentId, $revision)
    {
        $sql = sprintf(
            'SELECT * FROM wiki_attachment_revision'
                       . ' WHERE attachment_id=%d'
                       . ' AND revision=%d',
            $attachmentId,
            $revision
        );

         return $this->retrieve($sql);
    }

    /**
     * Fetch all revisions of a given attachment
     */
    public function getAllRevisions($id)
    {
        $sql = sprintf(
            'SELECT * FROM wiki_attachment_revision'
                       . ' WHERE attachment_id=%d'
                       . ' ORDER BY date DESC',
            $id
        );

        return $this->retrieve($sql);
    }
}
