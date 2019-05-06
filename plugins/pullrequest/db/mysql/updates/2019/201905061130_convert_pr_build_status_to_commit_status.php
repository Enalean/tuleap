<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b201905061130_convert_pr_build_status_to_commit_status extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Move PR build status to commit status';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        if ($this->db->dbh->beginTransaction() === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Not able to start the migration from pull request build statuses to commit statuses'
            );
        }
        $sql = 'INSERT INTO plugin_git_commit_status(repository_id, commit_reference, date, status)
                SELECT repository_id, sha1_src, last_build_date, 0 FROM plugin_pullrequest_review WHERE last_build_status = "S"';
        if ($this->db->dbh->exec($sql) === false) {
            $this->db->dbh->rollBack();
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Not able to migrate to pull request build statuses as commit statuses'
            );
        }

        $sql = 'INSERT INTO plugin_git_commit_status(repository_id, commit_reference, date, status)
                SELECT repository_id, sha1_src, last_build_date, 1 FROM plugin_pullrequest_review WHERE last_build_status = "F"';
        if ($this->db->dbh->exec($sql) === false) {
            $this->db->dbh->rollBack();
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Not able to migrate to pull request build statuses as commit statuses'
            );
        }

        if (! $this->db->dbh->commit()) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Not able to commit the transaction for the migration from pull request build statuses to commit statuses'
            );
        }
    }
}
