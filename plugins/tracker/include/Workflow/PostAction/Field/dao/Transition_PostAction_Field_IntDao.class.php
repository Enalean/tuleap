<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


/**
 * Data Acces object which deals with tracker_workflow_transition_postactions_field_int table
 */
class Transition_PostAction_Field_IntDao extends Transition_PostAction_FieldDao
{

    public function __construct()
    {
        parent::__construct();
        $this->table_name = 'tracker_workflow_transition_postactions_field_int';
    }

    /**
     * @see Transition_PostAction_FieldDao
     */
    protected function escapeValue($value)
    {
        return $this->da->escapeInt($value);
    }
}
