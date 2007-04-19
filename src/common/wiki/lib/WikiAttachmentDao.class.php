<?php
/*
 * Copyright STMicroelectronics, 2006
 * Originally written by Manuel VACELET, STMicroelectronics, 2006 
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
 * along with CodeX; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 * $Id$
 *
 */

require_once('common/dao/include/DataAccessObject.class.php');

class WikiAttachmentDao extends DataAccessObject {

    /**
     * Constructs WikiAttachmentDao
     *
     * @param DataAccess $da instance of the DataAccess class
     */
    function WikiAttachmentDao(&$da) {
        DataAccessObject::DataAccessObject($da);
    }

    /**
     * Create a new attachement.
     *
     * @param integer $gid Group id
     * @param string $filename Attachement name
     * @return boolean
     */
    function create($gid, $filename) {
        $qry = sprintf(' INSERT INTO wiki_attachment (group_id, name)'
                       .' VALUES (%d, %s)',
                       $gid,
                       $this->da->quoteSmart($filename));

        $res = $this->update($qry);
        return $res;
    }
    
    /**
     * Retrun one DB entry corresponding to the given id.
     *
     * @param integer $id Attachement id
     * @return DataAccessResult
     */
    function &read($id) {
        $qry = sprintf('SELECT * FROM wiki_attachment'
                       .' WHERE id=%d',
                       $id);

        return $this->retrieve($qry);
    }

    /**
     * Delete the entry corresponding to the given id.
     *
     * @param integer $id Attachement id
     * @return boolean
     */
    function delete($id) {
        $qry = sprintf('DELETE FROM wiki_attachment'
                       .' WHERE id=%d',                       
                       $id);
         
        $res = $this->update($qry);
        return $res;
    }

    /**
     * Get the list of attachment for a project
     *
     * @param integer $gid Group id
     * @return DataAccessResult
     */
    function &getList($gid) {
        $qry = sprintf('SELECT * FROM wiki_attachment'
                       .' WHERE group_id=%d',
                       $gid);

        return $this->retrieve($qry);
    }

    /**
     * Get the list of attachment, the number of revisions ordered by date.
     *
     * For a given group_id this function returns all the attachments, ordered
     * by date of creation of their revisions (FILO). The result also contains
     * the number of revisions for each attachement.
     *
     * @param integer $gid Group id
     * @return DataAccessResult
     */
    function &getListWithCounterOrderedByRevDate($gid) {
        $qry = sprintf('SELECT wa.id, wa.group_id, wa.name, count(*) as nb, MAX(war.date) as max_date'
                       .' FROM wiki_attachment_revision AS war, wiki_attachment AS wa'
                       .' WHERE wa.group_id=%d'
                       .' AND war.attachment_id=wa.id'
                       .' GROUP BY attachment_id'
                       .' ORDER BY max_date DESC',
                       $gid);

        return $this->retrieve($qry);
    }
    
    /**
     * Return attachment id for a file in a project.
     *
     * @param integer $gid group id
     * @param string  $filename attachement name
     * @return DataAccessResult
     */
    function &getIdFromFilename($gid, $filename) {
        $qry = sprintf('SELECT id FROM wiki_attachment'
                       .' WHERE name=%s'
                       .' AND group_id=%d',
                       $this->da->quoteSmart($filename),
                       $gid);

        return $this->retrieve($qry);
    }
}
?>
