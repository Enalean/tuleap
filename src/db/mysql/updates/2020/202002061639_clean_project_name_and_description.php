<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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
class b202002061639_clean_project_name_and_description extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Clean projects name and description';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = <<<EOT
        UPDATE `groups`
        SET group_name =
            REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(group_name, '&quot;', '\"'),
                        '&gt;',
                        '>'
                    ),
                    '&lt;',
                    '<'
                ),
                '&amp;',
                '&'
            ),
            short_description = REPLACE(
                REPLACE(
                    REPLACE(
                        REPLACE(short_description, '&quot;', '\"'),
                        '&gt;',
                        '>'
                    ),
                    '&lt;',
                    '<'
                ),
                '&amp;',
                '&'
            )
        EOT;

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to clean projects name and description');
        }
    }
}
