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

class Tracker_CardFields
{

    /**
     * @var Array
     */
    private $displayed_fields;

    /**
     * @var PFUser
     */
    private $user_manager;


    public function __construct()
    {
        $this->displayed_fields     = array(Tracker::REMAINING_EFFORT_FIELD_NAME,
                                            Tracker::ASSIGNED_TO_FIELD_NAME,
                                            Tracker::IMPEDIMENT_FIELD_NAME);
        $this->user_manager                 = UserManager::instance();
        $this->form_element_factory = Tracker_FormElementFactory::instance();
    }

    /**
     *
     *
     * @return Tracker_FormElement_Field[]
     */
    public function getFields(Tracker_Artifact $artifact)
    {
        $diplayed_fields = array();
        $tracker_id      = $artifact->getTrackerId();

        foreach ($this->displayed_fields as $diplayed_field_name) {
            $field = $this->form_element_factory->getUsedFieldByNameForUser(
                $tracker_id,
                $diplayed_field_name,
                $this->user_manager->getCurrentUser()
            );
            if ($field) {
                $diplayed_fields[] = $field;
            }
        }

        return $diplayed_fields;
    }
}
