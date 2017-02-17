<?php
/**
 * Copyright (c) Enalean, 2017. All Rights Reserved.
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

class b20170207_remove_snippet_reference extends ForgeUpgrade_Bucket
{
    public function description()
    {
        return 'Remove snippet reference and cross-references';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->deleteReference();
        $this->deleteCrossReferences();
    }

    private function deleteReference()
    {
        $sql = 'DELETE FROM reference WHERE id = 70';

        if (! $this->db->dbh->query($sql)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'The snippet reference has not been properly removed'
            );
        }
    }

    private function deleteCrossReferences()
    {
        $sql = 'DELETE FROM cross_references WHERE source_type = "snippet" OR target_type = "snippet"';

        if (! $this->db->dbh->query($sql)) {
            throw new ForgeUpgrade_Bucket_Exception_UpgradeNotComplete(
                'The snippet cross-references has not been properly removed'
            );
        }
    }
}
