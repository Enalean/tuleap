<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

class b201806202247_remove_dark_variants extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Remove Dark* theme variants from user preferences';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $sql = "UPDATE user_preferences
                SET preference_value = CASE preference_value
                  WHEN 'FlamingParrot_DarkBlue' THEN 'FlamingParrot_Blue'
                  WHEN 'FlamingParrot_DarkBlueGrey' THEN 'FlamingParrot_BlueGrey'
                  WHEN 'FlamingParrot_DarkGreen' THEN 'FlamingParrot_Green'
                  WHEN 'FlamingParrot_DarkOrange' THEN 'FlamingParrot_Orange'
                  WHEN 'FlamingParrot_DarkRed' THEN 'FlamingParrot_Red'
                  ELSE 'FlamingParrot_Purple'
                  END
                WHERE preference_name = 'theme_variant'
                  AND preference_value IN (
                  'FlamingParrot_DarkBlue',
                  'FlamingParrot_DarkBlueGrey',
                  'FlamingParrot_DarkGreen',
                  'FlamingParrot_DarkOrange',
                  'FlamingParrot_DarkRed',
                  'FlamingParrot_DarkPurple'
                )
                  ";

        $res = $this->db->dbh->exec($sql);
        if ($res === false) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete('Unable to remove Dark* theme variants from user preferences.');
        }
    }
}
