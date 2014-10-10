<?php
/**
 * Copyright (c) Enalean SAS 2014. All rights reserved
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

class b201410101520_run_fts_systemevents_as_appowner extends ForgeUpgrade_Bucket {

    public function description() {
        return 'Change the owner of FTS system events to "app" instead of "root"';
    }

    public function preUp() {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up() {
        $sql = "UPDATE system_event
                SET owner = 'app'
                WHERE type IN (
                    'FULLTEXTSEARCH_DOCMAN_INDEX',
                    'FULLTEXTSEARCH_DOCMAN_EMPTY_INDEX',
                    'FULLTEXTSEARCH_DOCMAN_LINK_INDEX',
                    'FULLTEXTSEARCH_DOCMAN_FOLDER_INDEX',
                    'FULLTEXTSEARCH_DOCMAN_REINDEX_PROJECT',
                    'FULLTEXTSEARCH_DOCMAN_COPY',
                    'FULLTEXTSEARCH_DOCMAN_UPDATE',
                    'FULLTEXTSEARCH_DOCMAN_UPDATE_PERMISSIONS',
                    'FULLTEXTSEARCH_DOCMAN_UPDATE_METADATA',
                    'FULLTEXTSEARCH_DOCMAN_DELETE',
                    'FULLTEXTSEARCH_DOCMAN_APPROVAL_TABLE_COMMENT',
                    'FULLTEXTSEARCH_DOCMAN_WIKI_INDEX',
                    'FULLTEXTSEARCH_DOCMAN_WIKI_UPDATE',
                    'FULLTEXTSEARCH_TRACKER_ARTIFACT_UPDATE',
                    'FULLTEXTSEARCH_WIKI_INDEX',
                    'FULLTEXTSEARCH_WIKI_UPDATE',
                    'FULLTEXTSEARCH_WIKI_UPDATE_PERMISSIONS',
                    'FULLTEXTSEARCH_WIKI_UPDATE_SERVICE_PERMISSIONS',
                    'FULLTEXTSEARCH_WIKI_DELETE',
                    'FULLTEXTSEARCH_WIKI_REINDEX_PROJECT'
                )";
        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occured: '.implode(', ', $this->db->dbh->errorInfo()));
        }
    }
}
