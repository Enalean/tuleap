<?php
/**
 * Copyright (c) Enalean, 2025-Present. All Rights Reserved.
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

use Tuleap\ForgeUpgrade\Bucket;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202501170934_convert_default_query_to_expert extends Bucket
{
    public function description(): string
    {
        return 'Move all queries to the new table plugin_crosstracker_query and convert default queries to expert';
    }

    /**
     * @throws Throwable
     */
    public function up(): void
    {
        if (
            $this->api->tableNameExists('plugin_crosstracker_query')
            || ! $this->api->tableNameExists('plugin_crosstracker_report')
            || ! $this->api->tableNameExists('plugin_crosstracker_report_tracker')
        ) {
            $this->log->info('Migration was already performed');
            return;
        }

        // Create the new table
        $this->api->createTable(
            'plugin_crosstracker_query',
            <<<SQL
            CREATE TABLE plugin_crosstracker_query
            (
                id          INT  NOT NULL PRIMARY KEY AUTO_INCREMENT,
                query       TEXT NOT NULL,
                title       TEXT NOT NULL,
                description TEXT NOT NULL DEFAULT ''
            ) ENGINE=InnoDB;
            SQL
        );

        if (! $this->api->dbh->beginTransaction()) {
            throw new LogicException('Failed to create transaction');
        }

        try {
            // Migrate expert queries
            $this->api->dbh->exec(
                <<<SQL
                INSERT INTO plugin_crosstracker_query (id, query)
                SELECT id, expert_query
                FROM plugin_crosstracker_report
                WHERE expert_mode = 1
                SQL
            );

            // Migrate default queries
            $queries = $this->api->dbh->query('SELECT expert_query AS query, id FROM plugin_crosstracker_report WHERE expert_mode = 0');
            foreach ($queries->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $this->migrateQuery($row);
            }

            // Done
            $this->api->dbh->commit();
        } catch (Throwable $e) {
            $this->log->warning('Rollback (' . $e->getMessage() . ')' . "\n" . $e->getTraceAsString());
            $this->api->dbh->rollBack();
            throw $e;
        }

        $nb_query = $this->api->dbh->query('SELECT count(*) FROM plugin_crosstracker_query')->fetch()[0];
        $this->log->info("Migrated $nb_query queries");
    }

    /**
     * @param array{id: int, query: string} $query_row
     */
    private function migrateQuery(array $query_row): void
    {
        $query_id = $query_row['id'];
        $query    = $query_row['query'];
        if ($query === '') {
            $query = '@status = OPEN()';
        }

        // Collect projects and trackers
        $statement = $this->api->dbh->prepare(
            <<<SQL
            SELECT tracker.item_name AS tracker_name, `groups`.unix_group_name AS project_name
            FROM plugin_crosstracker_report_tracker AS report_tracker
            INNER JOIN tracker ON (report_tracker.tracker_id = tracker.id)
            INNER JOIN `groups` ON (tracker.group_id = `groups`.group_id)
            WHERE report_tracker.report_id = ?
            SQL
        );
        $statement->execute([$query_id]);
        $pairs         = [];
        $project_names = [];
        $tracker_names = [];
        foreach ($statement->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $project_names[] = "'" . $row['project_name'] . "'";
            $tracker_names[] = "'" . $row['tracker_name'] . "'";
            $pairs[]         = "'" . $row['tracker_name'] . ' from project ' . $row['project_name'] . "'";
        }
        $project_names = array_unique($project_names);
        $tracker_names = array_unique($tracker_names);

        // Create Query
        if ($project_names === [] || $tracker_names === []) {
            $from = 'FROM @project = MY_PROJECTS()';
        } else {
            $from = 'FROM @project.name IN (' . implode(', ', $project_names) . ') AND @tracker.name IN (' . implode(', ', $tracker_names) . ')';
        }
        $select    = 'SELECT @id, @tracker.name, @project.name, @last_update_date, @submitted_by';
        $where     = 'WHERE ' . $query;
        $new_query = "$select\n$from\n$where";

        // Insert into new table
        $insert_stmt = $this->api->dbh->prepare('INSERT INTO plugin_crosstracker_query (id, query, title, description) VALUES (?, ?, ?, ?)');
        $insert_stmt->execute([
            $query_id,
            $new_query,
            'Migrated query',
            'Converted from simple query on ' . implode(', ', $pairs),
        ]);
    }
}
