<?php
/**
 * Copyright (c) Enalean, 2026-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202601081044_unique_index_openlist_value extends \Tuleap\ForgeUpgrade\Bucket
{
    #[Override]
    public function description(): string
    {
        return 'De-duplicate Open list value labels to prepare for the unique index.';
    }

    #[Override]
    public function up(): void
    {
        $this->removeDuplicationsInTable();
    }

    private function removeDuplicationsInTable(): void
    {
        $this->log->info('Identify duplicate labels');
        $rows = $this->api->dbh->query('SELECT id, field_id, label, is_hidden FROM tracker_field_openlist_value');

        /** @var array<int, array<string, array<int, bool>>> $duplicates array<field_id, array<label, array<openvalue_id, is_hidden>>> */
        $duplicates = [];
        foreach ($rows as $row) {
            if (! isset($duplicates[$row['field_id']])) {
                $duplicates[$row['field_id']] = [];
            }

            if (! isset($duplicates[$row['field_id']][$row['label']])) {
                $duplicates[$row['field_id']][$row['label']] = [];
            }

            $duplicates[$row['field_id']][$row['label']][$row['id']] = (bool) $row['is_hidden'];
        }

        // Remove everything that is not duplicated
        $nb_duplicate = 0;
        foreach ($duplicates as $field_id => $field) {
            foreach ($field as $label => $ids) {
                if (count($ids) === 1) {
                    unset($duplicates[$field_id][$label]);
                } else {
                    $nb_duplicate++;
                }
            }
        }
        $this->log->info("Found $nb_duplicate duplicates");
        if ($nb_duplicate === 0) {
            $this->log->info('No duplicates found, finished');
            return;
        }

        $this->createBackupTables();

        /** @var array{int, int, int, string, bool}[] $values array{changeset_value_id, field_id, openvalue_id, label, is_hidden}[] */
        $values = [];
        /** @var list<int> $all_duplicate_ids list of openvalue_id */
        $all_duplicate_ids = [];

        $this->log->info('Build backup data');
        foreach ($duplicates as $field_id => $labels) {
            foreach ($labels as $label => $ids) {
                foreach ($ids as $id => $is_hidden) {
                    $all_duplicate_ids[] = $id;
                    $changesets          = $this->api->dbh->query(sprintf('SELECT changeset_value_id FROM tracker_changeset_value_openlist WHERE openvalue_id = %d', $id));

                    foreach ($changesets as $row) {
                        $values[] = [$row['changeset_value_id'], $field_id, $id, $this->api->dbh->quote($label), $is_hidden];
                    }
                }
            }
        }

        $this->api->dbh->beginTransaction();

        $this->backupValuesAndChangesets($values);
        $this->backupReports($all_duplicate_ids);

        // Update changeset with first id of duplicates (or first not hidden) and remove all its duplicates
        try {
            foreach ($duplicates as $field_id => $labels) {
                foreach ($labels as $label => $ids) {
                    $this->log->info("Remove duplicates of '$label' for field $field_id");
                    $kept          = array_find_key($ids, static fn(bool $is_hidden) => ! $is_hidden) ?? array_keys($ids)[0];
                    $ids_to_delete = array_filter(array_keys($ids), static fn(int $id) => $id !== $kept);
                    $to_delete     = implode(',', $ids_to_delete);

                    $this->log->info(' - update changesets');
                    $this->api->dbh->exec(
                        <<<SQL
                        UPDATE tracker_changeset_value_openlist
                        JOIN tracker_field_openlist_value ON (tracker_field_openlist_value.id = tracker_changeset_value_openlist.openvalue_id)
                        SET tracker_changeset_value_openlist.openvalue_id = $kept
                        WHERE field_id = $field_id AND openvalue_id IN ($to_delete);
                        SQL
                    );

                    $this->log->info(' - update tracker report criterias');
                    $criteria_replace = implode('|', $ids_to_delete);
                    $this->api->dbh->exec(
                        <<<SQL
                        UPDATE tracker_report_criteria_openlist_value AS cr
                        SET cr.value = REGEXP_REPLACE(cr.value, 'o($criteria_replace)(,|$)', 'o$kept$2', 1, 0, 'c')
                        WHERE REGEXP_LIKE(cr.value, 'o($criteria_replace)(,|$)', 'c') = 1;
                        SQL
                    );

                    $this->log->info(' - remove values');
                    $this->api->dbh->exec("DELETE FROM tracker_field_openlist_value WHERE id IN ($to_delete)");
                }
            }
        } catch (Throwable $e) {
            $this->api->dbh->rollBack();
            throw $e;
        }

        $this->api->dbh->commit();
    }

    private function createBackupTables(): void
    {
        // Do a backup of duplicates values and their uses in changesets (in case of problem)
        $this->api->createTable(
            'plugin_tracker_backup_openlist_values_duplicates',
            <<<SQL
            CREATE TABLE IF NOT EXISTS plugin_tracker_backup_openlist_values_duplicates (
                changeset_value_id INT NOT NULL,
                field_id INT NOT NULL,
                openvalue_id INT NOT NULL,
                label VARCHAR(255) NOT NULL,
                is_hidden BOOL NOT NULL
            ) ENGINE=InnoDB
            SQL
        );
        // Do a backup of duplicate values uses in tracker reports
        $this->api->createTable(
            'plugin_tracker_backup_openlist_values_duplicates_in_reports',
            <<<SQL
            CREATE TABLE IF NOT EXISTS plugin_tracker_backup_openlist_values_duplicates_in_reports (
                criteria_id INT NOT NULL,
                value TEXT NOT NULL
            ) ENGINE=InnoDB
            SQL
        );
    }

    private function backupValuesAndChangesets(array $values): void
    {
        if ($values === []) {
            $this->log->info('No uses of duplicated values in changesets, skipping backup');
            return;
        }
        $sql_values = implode(
            ',',
            array_map(
                static fn(array $row) => sprintf('(%d, %d, %d, %s, %d)', ...$row),
                $values,
            )
        );
        $this->log->info('Insert backup of changesets');
        $sql = <<<SQL
        INSERT INTO plugin_tracker_backup_openlist_values_duplicates (changeset_value_id, field_id, openvalue_id, label, is_hidden)
        VALUES $sql_values
        SQL;
        $this->api->dbh->exec($sql);
    }

    private function backupReports(array $all_duplicate_ids): void
    {
        if ($all_duplicate_ids === []) {
            $this->log->info('No duplicates found, skipping backup');
        }
        $duplicate_ids_to_compare = implode('|', $all_duplicate_ids);
        $this->log->info('Insert backup of report criteria');
        $sql = <<<SQL
        INSERT INTO plugin_tracker_backup_openlist_values_duplicates_in_reports (criteria_id, value)
        SELECT cr.criteria_id, cr.value
        FROM tracker_report_criteria_openlist_value AS cr
        WHERE REGEXP_LIKE(cr.value, 'o($duplicate_ids_to_compare)(,|$)', 'c') = 1
        SQL;
        $this->api->dbh->exec($sql);
    }
}
