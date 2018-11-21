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

class b201811211412_add_missing_indexes_for_workflow extends ForgeUpgrade_Bucket // @codingStandardsIgnoreLine
{
    public function description()
    {
        return 'Add index on tracker workflow tables';
    }

    public function preUp()
    {
        $this->db = $this->getApi('ForgeUpgrade_Bucket_Db');
    }

    public function up()
    {
        $this->db->addIndex(
            'tracker_changeset_value_openlist',
            'idx_bindvalue_id',
            'alter table tracker_changeset_value_openlist add index idx_bindvalue_id(bindvalue_id, changeset_value_id)'
        );

        $this->db->addIndex(
            'tracker_changeset_value',
            'idx_value_field_id',
            'alter table tracker_changeset_value drop index field_idx, add index idx_value_field_id(field_id, id)'
        );

        $this->db->addIndex(
            'tracker_field_list_bind_static_value',
            'idx_bind_value_field_id',
            'alter table tracker_field_list_bind_static_value drop index field_id_idx, add index idx_bind_value_field_id(field_id, id)'
        );
    }
}
