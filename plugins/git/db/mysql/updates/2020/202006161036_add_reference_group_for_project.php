<?php
/**
 * Copyright (c) Enalean, 2019 - present. All Rights Reserved.
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
final class b202006161036_add_reference_group_for_project extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Add reference_group for project with git service with no git reference';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $reference_id = (int) $this->getGitReference();
        $group_ids    = $this->getGroupIdsWithBrokenGitReferences($reference_id);
        $this->insertReferenceGroup($reference_id, $group_ids);
    }

    /**
     * @return string[]
     */
    private function getGroupIdsWithBrokenGitReferences(int $reference_id): array
    {
        $sql = "SELECT group_id
                FROM service
                WHERE short_name = 'plugin_git'
                    AND is_active = 1
                    AND group_id != 0
                    AND group_id NOT IN (
                        SELECT group_id
                        FROM reference_group
                        WHERE reference_id = ?
                )";

        $pdo_statement = $this->db->dbh->prepare($sql);

        if ($pdo_statement->execute([$reference_id]) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Error while retrieving groups with broken git references.'
            );
        }

        return $pdo_statement->fetchAll();
    }

    private function getGitReference(): string
    {
        $sql = "SELECT id
                FROM reference
                WHERE service_short_name = 'plugin_git'
                LIMIT 1";

        $result = $this->db->dbh->query($sql)->fetch()[0];

        if ($result === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'Error while retrieving git reference.'
            );
        }

        return $result;
    }

    private function insertReferenceGroup(int $reference_id, array $group_ids): void
    {
        $sql           = "INSERT INTO reference_group (reference_id, group_id, is_active) VALUES (?, ?, ?)";
        $pdo_statement = $this->db->dbh->prepare($sql);

        if (! $pdo_statement) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete("Error while preparing insert request");
        }

        $this->db->dbh->beginTransaction();

        foreach ($group_ids as $group_id) {
            $data_to_insert = [$reference_id, $group_id['group_id'], 1];
            if ($pdo_statement->execute($data_to_insert) === false) {
                $this->rollBackOnError('Error while inserting new reference_group in ' . $group_id);
            }
        }

        if (! $this->db->dbh->commit()) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Error while committing.');
        }
    }

    private function rollBackOnError($message): void
    {
        $this->db->dbh->rollBack();
        throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete($message);
    }
}
