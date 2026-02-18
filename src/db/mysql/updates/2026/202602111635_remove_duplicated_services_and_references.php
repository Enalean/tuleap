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

final class b202602111635_remove_duplicated_services_and_references extends \Tuleap\ForgeUpgrade\Bucket // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
{
    public function description(): string
    {
        return 'Remove duplicated services and references from projects';
    }

    public function up(): void
    {
        $this->removeDuplicatedReferences();
        $this->removeDuplicatedServices();
    }

    private function removeDuplicatedReferences(): void
    {
        $this->log->info('Remove duplicated references');
        $this->api->createTable(
            'backup_duplicated_reference_group_request_46767',
            <<<EOS
            CREATE TABLE backup_duplicated_reference_group_request_46767 (
              reference_id int NOT NULL default '0',
              group_id int NOT NULL default '0',
              is_active tinyint NOT NULL default '0'
            ) ENGINE=InnoDB
            EOS
        );

        $this->api->dbh->beginTransaction();

        $this->api->dbh->query(
            <<<EOS
            INSERT INTO backup_duplicated_reference_group_request_46767
                (reference_id, group_id, is_active)
            SELECT duplicate.reference_id, duplicate.group_id, duplicate.is_active
            FROM reference_group AS truth
            INNER JOIN reference_group AS duplicate
                ON (truth.group_id = duplicate.group_id
                        AND truth.reference_id = duplicate.reference_id
                        AND truth.is_active = duplicate.is_active
                    )
            WHERE truth.id < duplicate.id
            EOS
        );

        $this->api->dbh->query(
            <<<EOS
            DELETE duplicate.*
            FROM reference_group AS truth
            INNER JOIN reference_group AS duplicate
                ON (truth.group_id = duplicate.group_id
                        AND truth.reference_id = duplicate.reference_id
                        AND truth.is_active = duplicate.is_active
                    )
            WHERE truth.id < duplicate.id
            EOS
        );

        $count = $this->api->dbh->query('SELECT COUNT(*) FROM backup_duplicated_reference_group_request_46767')->fetchColumn();

        $this->api->dbh->commit();

        if ($count > 0) {
            $this->log->info($count . ' duplicated references were removed');
        } else {
            $this->api->dbh->query('DROP TABLE backup_duplicated_reference_group_request_46767');
        }
    }

    private function removeDuplicatedServices(): void
    {
        $this->log->info('Remove duplicated services');
        $this->api->createTable(
            'backup_duplicated_service_request_46767',
            <<<EOS
            CREATE TABLE backup_duplicated_service_request_46767
            LIKE service
            EOS
        );

        $this->api->dbh->beginTransaction();

        $this->api->dbh->query(
            <<<EOS
            INSERT INTO backup_duplicated_service_request_46767 (
                service_id,
                group_id,
                label,
                description,
                short_name,
                link,
                is_active,
                is_used,
                scope,
                `rank`,
                location,
                server_id,
                is_in_iframe,
                is_in_new_tab,
                icon
            )
            SELECT DISTINCT duplicate.service_id,
                   duplicate.group_id,
                   duplicate.label,
                   duplicate.description,
                   duplicate.short_name,
                   duplicate.link,
                   duplicate.is_active,
                   duplicate.is_used,
                   duplicate.scope,
                   duplicate.`rank`,
                   duplicate.location,
                   duplicate.server_id,
                   duplicate.is_in_iframe,
                   duplicate.is_in_new_tab,
                   duplicate.icon
            FROM service AS truth
            INNER JOIN service AS duplicate
                ON (truth.group_id = duplicate.group_id
                        AND truth.scope = 'system'
                        AND duplicate.scope = 'system'
                        AND truth.short_name = duplicate.short_name
                    )
            WHERE truth.service_id < duplicate.service_id
            EOS
        );

        $this->api->dbh->query(
            <<<EOS
            DELETE duplicate.*
            FROM service AS truth
            INNER JOIN service AS duplicate
                ON (truth.group_id = duplicate.group_id
                        AND truth.scope = 'system'
                        AND duplicate.scope = 'system'
                        AND truth.short_name = duplicate.short_name
                    )
            WHERE truth.service_id < duplicate.service_id
            EOS
        );

        $count = $this->api->dbh->query('SELECT COUNT(*) FROM backup_duplicated_service_request_46767')->fetchColumn();

        $this->api->dbh->commit();

        if ($count > 0) {
            $this->log->info($count . ' duplicated services were removed');
        } else {
            $this->api->dbh->query('DROP TABLE backup_duplicated_service_request_46767');
        }
    }
}
