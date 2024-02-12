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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;

class WikiAttachmentDao extends DataAccessObject
{
    /**
     * Create a new attachement.
     *
     * @param int $gid Group id
     * @param string $filename Attachement name
     * @return bool
     */
    public function create($gid, $filename, $filesystemName)
    {
        $qry = sprintf(
            ' INSERT INTO wiki_attachment (group_id, name, filesystem_name)'
                       . ' VALUES (%d, %s, %s)',
            $gid,
            $this->da->quoteSmart($filename),
            $this->da->quoteSmart($filesystemName)
        );

        $res = $this->update($qry);
        return $res;
    }

    /**
     * Retrun one DB entry corresponding to the given id.
     *
     * @param int $id Attachement id
     * @return LegacyDataAccessResultInterface
     */
    public function read($id)
    {
        $qry = sprintf(
            'SELECT * FROM wiki_attachment'
                       . ' WHERE id=%d',
            $id
        );

        return $this->retrieve($qry);
    }

    /**
     * Set the status of the attachment to deleted and add the entry to the wiki_attchment_deleted table.
     *
     * @param int $id Attachement id
     *
     * @return bool
     */
    public function delete($id)
    {
        $sql = 'UPDATE wiki_attachment SET delete_date=' . $this->da->escapeInt(\Tuleap\Request\RequestTime::getTimestamp()) .
               ' WHERE id = ' . $this->da->escapeInt($id);
        if ($this->update($sql)) {
            $sql = 'INSERT INTO wiki_attachment_deleted(id, group_id, name, delete_date)' .
               ' SELECT id, group_id, name, delete_date' .
               ' FROM wiki_attachment' .
               ' WHERE id = ' . $this->da->escapeInt($id);
            $res = $this->update($sql);
            return $res;
        }
        return false;
    }

    /**
     * Get the list of attachment for a project
     *
     * @param int $gid Group id
     * @return LegacyDataAccessResultInterface
     */
    public function getList($gid)
    {
        $qry = sprintf(
            'SELECT * FROM wiki_attachment'
                       . ' WHERE group_id=%d',
            $gid
        );

        return $this->retrieve($qry);
    }

    /**
     * Get the list of attachment, the number of revisions ordered by date.
     *
     * For a given group_id this function returns all the attachments, ordered
     * by date of creation of their revisions (FILO). The result also contains
     * the number of revisions for each attachement.
     *
     * @param int $gid Group id
     * @return LegacyDataAccessResultInterface
     */
    public function getListWithCounterOrderedByRevDate($gid)
    {
        $qry = sprintf(
            'SELECT wa.id, wa.group_id, wa.name, count(*) as nb, MAX(war.date) as max_date'
                       . ' FROM wiki_attachment_revision AS war, wiki_attachment AS wa'
                       . ' WHERE wa.group_id=%d'
                       . ' AND war.attachment_id=wa.id'
                       . ' AND wa.delete_date IS NULL'
                       . ' GROUP BY attachment_id'
                       . ' ORDER BY max_date DESC',
            $gid
        );

        return $this->retrieve($qry);
    }

    /**
     * Return attachment id for a file in a project.
     * The BINARY string usage enforce case sensitivity within where clause
     *
     * @param int $gid group id
     * @param string  $filename attachement name
     * @return LegacyDataAccessResultInterface
     */
    public function getIdFromFilename($gid, $filename)
    {
        $qry = sprintf(
            'SELECT id FROM wiki_attachment'
                       . ' WHERE name = BINARY %s'
                       . ' AND group_id=%d'
                       . ' AND delete_date IS NULL',
            $this->da->quoteSmart($filename),
            $gid
        );

        return $this->retrieve($qry);
    }

    /**
     * Retrieve all deleted Attachment not purged yet after a given period of time
     *
     * @param int $time Timestamp of the date to start the search
     * @param int $groupId
     * @param int $offset
     * @param int $limit
     *
     * @return LegacyDataAccessResultInterface
     */
    public function searchAttachmentToPurge($time, $groupId = 0, $offset = 0, $limit = 0)
    {
        $where = '';
        if ($groupId != 0) {
            $where .= ' AND attachment.group_id = ' . $this->da->escapeInt($groupId);
        }
        $sql = 'SELECT attachment.* ' .
               ' FROM wiki_attachment_deleted attachment' .
               ' WHERE attachment.delete_date <= ' . $this->da->escapeInt($time) .
               ' AND attachment.purge_date IS NULL' .
               $where .
               ' ORDER BY attachment.delete_date DESC';
        return $this->retrieve($sql);
    }

    /**
     * Restore deleted wiki attachments
     *
     * @param int $id
     *
     * @return bool
     */
    public function restoreAttachment($id)
    {
        $sql = 'UPDATE wiki_attachment SET delete_date = NULL ' .
                       'WHERE id = ' . $this->da->escapeInt($id);
        if ($this->update($sql)) {
            $sql = 'DELETE FROM wiki_attachment_deleted WHERE id = ' . $this->da->escapeInt($id);
            return $this->update($sql);
        }
        return false;
    }

    /**
     * Save the purge date of a deleted attachment
     *
     * @param int $id
     * @param int $time
     *
     * @return bool
     */
    public function setPurgeDate($id, $time)
    {
        $sql = 'UPDATE wiki_attachment_deleted SET purge_date =' . $this->da->escapeInt($time) .
                       ' WHERE id = ' . $this->da->escapeInt($id);
        return $this->update($sql);
    }
}
