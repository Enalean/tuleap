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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b202305231154_index_cross_references extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Improve cross references index coverage for Git';
    }

    public function up(): void
    {
        $this->api->dbh->exec(<<<SQL
            ALTER TABLE cross_references
                DROP INDEX source_idx,
                DROP INDEX target_idx,
                ADD INDEX source_idx(source_id(32), source_type(10)),
                ADD INDEX target_idx(target_id(32), source_type(10))
            SQL
        );
    }
}
