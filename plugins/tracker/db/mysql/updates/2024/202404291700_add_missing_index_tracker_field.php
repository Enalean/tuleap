<?php
/**
 * Copyright (c) Enalean, 2024-Present. All Rights Reserved.
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
final class b202404291700_add_missing_index_tracker_field extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add missing index in the tracker_field table';
    }

    public function up(): void
    {
        $this->api->addIndex(
            'tracker_field',
            'idx_tracker_usage_type',
            'ALTER TABLE tracker_field ADD INDEX idx_tracker_usage_type(tracker_id, formElement_type, use_it)'
        );

        if ($this->api->indexNameExists('tracker_field', 'idx_fk_tracker_id')) {
            $this->api->dbh->exec('ALTER TABLE tracker_field DROP INDEX idx_fk_tracker_id');
        }
    }
}
