<?php
/**
 * Copyright (c) Enalean, 2022-Present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202211280910_add_missing_artifacts_ranks extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add missing artifact ranks';
    }

    public function up(): void
    {
        $this->api->dbh->beginTransaction();
        $current_max_rank = $this->getMaxRank();
        foreach ($this->getArtifactsRowsWithMissingRank() as $row_artifact_without_rank) {
            $current_max_rank++;
            $this->insertNewRank($row_artifact_without_rank['id'], $current_max_rank);
        }
        $this->api->dbh->commit();
    }

    private function getMaxRank(): int
    {
        $sql    = 'SELECT MAX(tracker_artifact_priority_rank.`rank`) AS MAX_RANK FROM tracker_artifact_priority_rank';
        $result = $this->api->dbh->query($sql);
        $row    = $result->fetch();

        return $row['MAX_RANK'] ?? 0;
    }

    private function getArtifactsRowsWithMissingRank(): array
    {
        $sql = 'SELECT tracker_artifact.id
    FROM tracker_artifact
    LEFT JOIN tracker_artifact_priority_rank ON (tracker_artifact.id = tracker_artifact_priority_rank.artifact_id)
    WHERE tracker_artifact_priority_rank.artifact_id IS NULL;
        ';

        $result = $this->api->dbh->query($sql);
        $rows   = $result->fetchAll();

        return $rows;
    }

    private function insertNewRank(int $artifact_id, int $rank): void
    {
        $pdo_statement = $this->api->dbh->prepare(
            'INSERT INTO tracker_artifact_priority_rank (artifact_id, `rank`) VALUES (?,?)'
        );

        $pdo_statement->execute([$artifact_id, $rank]);
    }
}
