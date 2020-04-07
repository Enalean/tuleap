<?php
/**
 * Copyright (c) Enalean, 2019-Present. All Rights Reserved.
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

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
final class b201906191100_convert_tracker_colors_to_standardized_names extends ForgeUpgrade_Bucket
{
    public function description(): string
    {
        return 'Convert tracker colors to standardized names';
    }

    public function preUp(): void
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up(): void
    {
        $sql = 'ALTER TABLE tracker ALTER color SET DEFAULT "inca-silver"';
        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while changing the default tracker color to inca-silver');
        }

        $sql = 'UPDATE tracker SET color = REPLACE(color, "_",  "-")';
        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while converting the tracker colors');
        }

        $sql = "UPDATE tracker SET color = 'inca-silver' WHERE color NOT IN (
                    'inca-silver',
                    'chrome-silver',
                    'firemist-silver',
                    'red-wine',
                    'fiesta-red',
                    'coral-pink',
                    'teddy-brown',
                    'clockwork-orange',
                    'graffiti-yellow',
                    'army-green',
                    'neon-green',
                    'acid-green',
                    'sherwood-green',
                    'ocean-turquoise',
                    'surf-green',
                    'deep-blue',
                    'lake-placid-blue',
                    'daphne-blue',
                    'plum-crazy',
                    'ultra-violet',
                    'lilac-purple',
                    'panther-pink',
                    'peggy-pink',
                    'flamingo-pink'
            )";
        if ($this->db->dbh->exec($sql) === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('An error occurred while updating the invalid tracker color names');
        }
    }
}
