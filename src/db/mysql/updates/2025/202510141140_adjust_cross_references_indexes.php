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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotPascalCase
final class b202510141140_adjust_cross_references_indexes extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Adjust the indexes of the cross references table';
    }

    public function up(): void
    {
        $this->api->addIndex(
            'cross_references',
            'source_to_target_idx',
            'ALTER TABLE cross_references ADD INDEX source_to_target_idx(source_id(10), source_type(10), target_id(10)), ADD INDEX target_to_source_idx(target_id(10), target_type(10), source_id(10)), DROP INDEX source_idx, DROP INDEX target_idx'
        );
    }
}
