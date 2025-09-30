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
final class b202212021050_add_index_on_ugroup_id extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add index on ugroup_id to mediawiki standalone permissions table';
    }

    public function up(): void
    {
        $this->api->addIndex(
            'plugin_mediawiki_standalone_permissions',
            'idx_ugroup',
            'ALTER TABLE plugin_mediawiki_standalone_permissions ADD INDEX idx_ugroup(ugroup_id)'
        );
    }
}
