<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotPascalCase
class b202104301336_add_artifact_link_nature_column_in_tracker_semantic_progress extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add artifact_link_type to tracker_semantic_progress table.';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'ALTER TABLE tracker_semantic_progress ADD artifact_link_type TEXT NULL';
        $this->db->alterTable(
            'tracker_semantic_progress',
            'tuleap',
            'artifact_link_type',
            $sql
        );
    }
}
