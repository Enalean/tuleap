<?php
/**
 * Copyright (c) Enalean, 2024 - Present. All Rights Reserved.
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
final class b202412100956_introduce_freetext_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Introduce plugin_artidoc_section_freetext table';
    }

    public function up(): void
    {
        $this->api->createTable(
            'plugin_artidoc_section_freetext',
            <<<EOS
            CREATE TABLE plugin_artidoc_section_freetext
            (
                id          BINARY(16)       NOT NULL PRIMARY KEY,
                title       TEXT             NOT NULL DEFAULT '',
                description MEDIUMTEXT       NOT NULL DEFAULT ''
            ) ENGINE = InnoDB
            EOS
        );
        $this->api->dbh->exec(
            <<<EOS
            ALTER TABLE plugin_artidoc_document
                MODIFY COLUMN artifact_id INT(11) NULL,
                ADD COLUMN freetext_id BINARY(16) NULL
            EOS
        );
    }
}
