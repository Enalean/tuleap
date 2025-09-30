<?php
/**
 * Copyright (c) Enalean, 2020-Present. All Rights Reserved.
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
final class b202011301645_add_index_artifact_id_pending_mirrors extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add index on program_artifact_id column on the plugin_scaled_agile_pending_mirrors table';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->addIndex(
            'plugin_scaled_agile_pending_mirrors',
            'idx_program_artifact_id',
            'ALTER TABLE plugin_scaled_agile_pending_mirrors ADD INDEX idx_program_artifact_id(program_artifact_id)'
        );
    }
}
