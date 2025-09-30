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
final class b202303160931_add_is_error_to_ongoing_initializations_table extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add is_error to mediawiki standalone ongoing initializations table';
    }

    public function up(): void
    {
        $this->api->dbh->query(
            'ALTER TABLE plugin_mediawiki_standalone_ongoing_initializations ADD COLUMN is_error BOOL DEFAULT FALSE'
        );
    }
}
