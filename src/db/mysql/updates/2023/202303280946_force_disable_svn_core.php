<?php
/**
 * Copyright (c) Enalean, 2023-Present. All Rights Reserved.
 *
 *  This file is a part of Tuleap.
 *
 *  Tuleap is free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  Tuleap is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License
 *  along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202303280946_force_disable_svn_core extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Force disable of SVN Core service';
    }

    public function up(): void
    {
        $date = new DateTimeImmutable();
        $sql  = <<<SQL
        SELECT service_id, `groups`.group_id
        FROM service
            JOIN `groups` ON (`groups`.group_id = service.group_id)
        WHERE short_name = 'svn'
        AND status = 'A';
        SQL;

        $statement = $this->api->dbh->query($sql);
        $insert    = $this->api->dbh->prepare('INSERT INTO group_history (group_id, field_name, mod_by, date) VALUES (?, "svn_core_removal", 100, ?)');
        $delete    = $this->api->dbh->prepare('DELETE FROM service WHERE service_id = ?');
        foreach ($statement->fetchAll() as $row) {
            $insert->execute([$row['group_id'], $date->getTimestamp()]);
            $delete->execute([$row['service_id']]);
        }
    }
}
