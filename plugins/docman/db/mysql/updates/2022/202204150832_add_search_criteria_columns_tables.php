<?php
/**
 * Copyright (c) Enalean, 2022 - Present. All Rights Reserved.
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
final class b202204150832_add_search_criteria_columns_tables extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add plugin_document_search_criteria and plugin_document_search_columns tables';
    }

    public function up(): void
    {
        $this->api->createTable(
            'plugin_document_search_criteria',
            <<<EOS
            CREATE TABLE plugin_document_search_criteria
            (
                id         INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                project_id int(11)          NOT NULL,
                name       varchar(255)     NOT NULL default '',
                INDEX project_idx(project_id)
            ) ENGINE = InnoDB;
            EOS
        );

        $this->api->createTable(
            'plugin_document_search_columns',
            <<<EOS
            CREATE TABLE plugin_document_search_columns
            (
                id         INT(11) UNSIGNED NOT NULL PRIMARY KEY AUTO_INCREMENT,
                project_id int(11)          NOT NULL,
                name       varchar(255)     NOT NULL default '',
                INDEX project_idx(project_id)
            ) ENGINE = InnoDB;
            EOS
        );
    }
}
