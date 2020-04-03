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
final class b201907191700_move_link_version_column_approval extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Update approval table to avoid conflicts between links and files';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        if (! $this->db->columnNameExists('plugin_docman_approval', 'link_version_id')) {
            $res = $this->db->dbh->exec('ALTER TABLE plugin_docman_approval ADD COLUMN link_version_id INT(11) UNSIGNED NULL DEFAULT NULL');

            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                    'An error occurred while adding the column link_version_id to the plugin_docman_approval table'
                );
            }
        }

        if (! $this->db->columnNameExists('plugin_docman_approval', 'might_be_corrupted')) {
            $res = $this->db->dbh->exec('ALTER TABLE plugin_docman_approval ADD COLUMN might_be_corrupted BOOL DEFAULT FALSE');

            if ($res === false) {
                throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                    'An error occurred while adding the column might_be_corrupted to the plugin_docman_approval table'
                );
            }
        }

        $this->db->addIndex(
            'plugin_docman_approval',
            'uniq_link_version_id',
            'ALTER TABLE plugin_docman_approval ADD INDEX uniq_link_version_id (link_version_id)'
        );

        $this->db->dbh->beginTransaction();
        $res_table_rows = $this->db->dbh->query('SELECT table_id, version_id FROM plugin_docman_approval WHERE version_id IS NOT NULL');
        if ($res_table_rows === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Failed to retrieve all the versions used in the approval table'
            );
        }
        foreach ($res_table_rows as $row) {
            $does_link_version_exist = $this->doesLinkVersionExist((int) $row['version_id']);
            $does_file_version_exist = $this->doesFileVersionExist((int) $row['version_id']);
            if ($does_link_version_exist && ! $does_file_version_exist) {
                $this->fixLinkApprovalTable((int) $row['table_id']);
            }
            if ($does_link_version_exist && $does_file_version_exist) {
                $this->markApprovalTableAsPotentiallyCorrupted((int) $row['table_id']);
            }
        }
        $this->db->dbh->commit();
    }

    private function doesLinkVersionExist(int $link_version_id): bool
    {
        $statement = $this->db->dbh->prepare('SELECT id FROM plugin_docman_link_version WHERE id = ?');
        if ($statement->execute([$link_version_id]) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Failure encountered while checking existence of link version #' . $link_version_id
            );
        }

        return (bool) $statement->fetchColumn();
    }

    private function doesFileVersionExist(int $file_version_id): bool
    {
        $statement = $this->db->dbh->prepare('SELECT id FROM plugin_docman_version WHERE id = ?');
        if ($statement->execute([$file_version_id]) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Failure encountered while checking existence of file/embedded file version #' . $file_version_id
            );
        }

        return (bool) $statement->fetchColumn();
    }

    private function fixLinkApprovalTable(int $table_id): void
    {
        $statement = $this->db->dbh->prepare(
            'UPDATE plugin_docman_approval SET link_version_id = version_id, version_id = NULL WHERE table_id = ?'
        );
        if ($statement->execute([$table_id]) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Failure encountered while updating table #' . $table_id . ' to fix the link version'
            );
        }
    }

    private function markApprovalTableAsPotentiallyCorrupted(int $table_id): void
    {
        $statement = $this->db->dbh->prepare(
            'UPDATE plugin_docman_approval SET might_be_corrupted = TRUE WHERE table_id = ?'
        );
        if ($statement->execute([$table_id]) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Failure encountered while marking table #' . $table_id . ' has corrupted'
            );
        }
    }
}
