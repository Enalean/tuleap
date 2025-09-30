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
use Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException;
use function Psl\Str\replace;

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202502041341_add_columns_widget_id_and_is_default extends Bucket
{
    public function description(): string
    {
        return 'Add columns widget_id and is_default to table plugin_crosstracker_query. Plus set a default title to all query without one';
    }

    /**
     * @throws Throwable
     */
    public function up(): void
    {
        if (! $this->api->tableNameExists('plugin_crosstracker_query')) {
            throw new BucketUpgradeNotCompleteException('Table plugin_crosstracker_query not found. Cannot execute the upgrade bucket. Maybe the legacy default query has not been migrated');
        }

        $this->updateTable();
        $this->createTableWidget();
        $this->setDefaultTitle();
    }

    private function createTableWidget(): void
    {
        $this->api->createTable(
            'plugin_crosstracker_widget',
            <<<SQL
            CREATE TABLE plugin_crosstracker_widget
            (
                id INT NOT NULL PRIMARY KEY AUTO_INCREMENT
            ) ENGINE=InnoDB;
            SQL
        );

        $queries = $this->api->dbh->query('SELECT widget_id FROM plugin_crosstracker_query');
        foreach ($queries->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $update = $this->api->dbh->prepare('INSERT INTO plugin_crosstracker_widget (id) VALUE (?)');
            $update->execute([$row['widget_id']]);
        }
    }

    private function updateTable(): void
    {
        // Add columns widget_id and is_default
        $this->api->dbh->exec('ALTER TABLE plugin_crosstracker_query ADD COLUMN widget_id INT');
        $this->api->dbh->exec('UPDATE plugin_crosstracker_query SET widget_id = id');
        $this->api->dbh->exec('ALTER TABLE plugin_crosstracker_query MODIFY COLUMN widget_id INT NOT NULL');
        $this->api->dbh->exec('ALTER TABLE plugin_crosstracker_query ADD COLUMN is_default TINYINT NOT NULL DEFAULT false');

        // Update column id to be binary
        $this->api->addNewUUIDColumnToReplaceAutoIncrementedID('plugin_crosstracker_query', 'id', 'uuid');
        $this->api->dbh->exec('ALTER TABLE plugin_crosstracker_query DROP COLUMN id, RENAME COLUMN uuid TO id, ADD PRIMARY KEY (id)');
    }

    /**
     * @throws Throwable
     */
    private function setDefaultTitle(): void
    {
        if (! $this->api->dbh->beginTransaction()) {
            throw new LogicException('Failed to create transaction');
        }

        try {
            $queries = $this->api->dbh->query("SELECT id, query FROM plugin_crosstracker_query WHERE title = ''");
            foreach ($queries->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $output_array = [];
                preg_match('/WHERE\s*(?<where>.*?)(\s*ORDER BY.*)?$/im', $row['query'], $output_array);
                if (! isset($output_array['where'])) {
                    throw new LogicException('Query ' . $row['id'] . ' has no WHERE part');
                }

                $title  = replace($output_array['where'], "\n", ' ');
                $update = $this->api->dbh->prepare('UPDATE plugin_crosstracker_query SET title = ? WHERE id = ?');
                $update->execute([$title, $row['id']]);
            }

            $this->api->dbh->commit();
        } catch (Throwable $e) {
            $this->log->warning('Rollback (' . $e->getMessage() . ')');
            $this->api->dbh->rollBack();
            throw $e;
        }
    }
}
