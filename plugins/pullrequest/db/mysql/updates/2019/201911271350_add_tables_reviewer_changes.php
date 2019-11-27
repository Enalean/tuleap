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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
final class b201911271350_add_tables_reviewer_changes extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add tables about pullrequest reviewer changes';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->createTable(
            'plugin_pullrequest_reviewer_change',
            'CREATE TABLE plugin_pullrequest_reviewer_change (
                change_id INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                pull_request_id INT(11) NOT NULL,
                user_id INT(11) UNSIGNED NOT NULL,
                change_date INT(11) NOT NULL,
                INDEX idx_pr_pull_request_id(pull_request_id)
            )'
        );

        $this->db->createTable(
            'plugin_pullrequest_reviewer_change_user',
            'CREATE TABLE plugin_pullrequest_reviewer_change_user (
               change_id INT(11) UNSIGNED NOT NULL,
               user_id INT(11) UNSIGNED NOT NULL,
               is_removal BOOLEAN NOT NULL,
               PRIMARY KEY (change_id, user_id)
            )'
        );

        $this->migrateExistingReviewers();

        $this->db->dropTable('plugin_pullrequest_reviewer_user');
    }

    private function migrateExistingReviewers(): void
    {
        if ($this->db->dbh->beginTransaction() === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Not able to start the migration of the reviewers storage'
            );
        }

        $sql_create_change = 'INSERT INTO plugin_pullrequest_reviewer_change(pull_request_id, user_id, change_date)
            SELECT DISTINCT pull_request_id, 100, UNIX_TIMESTAMP() FROM plugin_pullrequest_reviewer_user';
        if ($this->db->dbh->exec($sql_create_change) === false) {
            $this->db->dbh->rollBack();
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Can not create reviewer changes'
            );
        }

        $sql_create_change_user = '
            INSERT INTO plugin_pullrequest_reviewer_change_user (change_id, user_id, is_removal)
            SELECT change_id, plugin_pullrequest_reviewer_user.user_id, FALSE
            FROM plugin_pullrequest_reviewer_user
            JOIN plugin_pullrequest_reviewer_change ON (plugin_pullrequest_reviewer_change.pull_request_id = plugin_pullrequest_reviewer_user.pull_request_id)
        ';
        if ($this->db->dbh->exec($sql_create_change_user) === false) {
            $this->db->dbh->rollBack();
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Can not migrate reviewer users to the new data model'
            );
        }

        if ($this->db->dbh->commit() === false) {
            $this->db->dbh->rollBack();
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Can not commit migration of the reviewers'
            );
        }
    }
}
