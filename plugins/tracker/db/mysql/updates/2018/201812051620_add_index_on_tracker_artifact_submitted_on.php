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
 */

class b201812051620_add_index_on_tracker_artifact_submitted_on extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add index on tracker_artifact submitted_on';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->addIndex(
            'Tuleap\Artifact\Artifact',
            'idx_submitted_on',
            'alter table tracker_artifact add index idx_submitted_on(submitted_on)'
        );
    }
}
