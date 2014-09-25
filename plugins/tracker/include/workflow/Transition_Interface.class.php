<?php
/**
 * Copyright (c) Enalean, 2014. All Rights Reserved.
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

interface Transition_Interface {

    /**
     * Validate that transition can occur
     *
     * @param Array $fields_data Request field data (array[field_id] => data)
     *
     * @return bool, true if the transition can occur, false otherwise
     */
    public function validate($fields_data, Tracker_Artifact $artifact);

    /**
     * Execute actions before transition happens
     *
     * @param Array $fields_data Request field data (array[field_id] => data)
     * @param PFUser  $current_user The user who are performing the update
     *
     * @return void
     */
    public function before(&$fields_data, PFUser $current_user);

    /**
     * Execute actions after transition happenstype
     *
     * @param Tracker_Artifact_Changeset $changeset
     * @return void
     */
    public function after(Tracker_Artifact_Changeset $changeset);
}