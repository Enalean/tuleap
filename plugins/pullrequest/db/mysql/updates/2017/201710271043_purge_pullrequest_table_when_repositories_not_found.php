<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b201710271043_purge_pullrequest_table_when_repositories_not_found extends ForgeUpgrade_Bucket // phpcs:ignore
{
    public function description()
    {
        return 'purge plugin_pullrequest_review table';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = 'DELETE  plugin_pullrequest_review.*
                  FROM plugin_pullrequest_review
                  LEFT JOIN plugin_git AS repository_source
                    ON plugin_pullrequest_review.repository_id = repository_source.repository_id
                  LEFT JOIN plugin_git AS repository_destination
                    ON plugin_pullrequest_review.repo_dest_id = repository_destination.repository_id
                WHERE repository_source.repository_deletion_date != "0000-00-00 00:00:00"
                  OR repository_destination.repository_deletion_date != "0000-00-00 00:00:00"';

        if (! $this->db->dbh->query($sql)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'The purge of pullrequest not linked to existing repositories have failed'
            );
        }
    }
}
