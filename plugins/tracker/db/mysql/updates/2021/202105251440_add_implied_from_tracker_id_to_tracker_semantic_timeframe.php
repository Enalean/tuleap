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
class b202105251440_add_implied_from_tracker_id_to_tracker_semantic_timeframe extends \Tuleap\ForgeUpgrade\Bucket
{
    public function description(): string
    {
        return 'Add implied_from_tracker_id to tracker_semantic_timeframe table.';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $this->db->alterTable(
            'tracker_semantic_timeframe',
            'tuleap',
            'implied_from_tracker_id',
            'ALTER TABLE tracker_semantic_timeframe ADD implied_from_tracker_id int(11) NULL'
        );

        $res = $this->db->dbh->exec(
            'ALTER TABLE tracker_semantic_timeframe MODIFY COLUMN start_date_field_id int(11) NULL'
        );

        if ($res === false) {
            throw new \Tuleap\ForgeUpgrade\Bucket\BucketUpgradeNotCompleteException('An error occurred while updating the table tracker_semantic_timeframe');
        }
    }
}
