<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

namespace Tuleap\TestManagement\REST\v1;

use \Tuleap\REST\JsonCast;
use \Tracker_Artifact;
use \Tracker_FormElementFactory;
use \PFUser;


class DefinitionRepresentation {

    const ROUTE = 'testmanagement_definitions';

    const FIELD_SUMMARY     = 'summary';
    const FIELD_DESCRIPTION = 'details';
    const FIELD_CATEGORY    = 'category';

    /**
     * @var int ID of the artifact
     */
    public $id;

    /**
     * @var String
     */
    public $uri;

    /**
     * @var String
     */
    public $summary;

    /**
     * @var String
     */
    public $description;

    /**
     * @var String
     */
    public $category;

    /**
     * @var Tracker_FormElementFactory
     */
    private $form_element_factory;

    /**
     * @var int
     */
    private $tracker_id;

    /**
     * @var Tracker_Artifact
     */
    private $artifact;

    /**
     * @var PFUser
     */
    private $user;


    public function build(Tracker_Artifact $artifact, Tracker_FormElementFactory $form_element_factory, PFUser $user) {
        $this->form_element_factory = $form_element_factory;
        $this->artifact             = $artifact;
        $this->tracker_id           = $artifact->getTrackerId();
        $this->user                 = $user;

        $this->id                   = JsonCast::toInt($artifact->getId());
        $this->uri                  = self::ROUTE . '/' . $this->id;
        $this->summary              = $this->getFieldValue(self::FIELD_SUMMARY)->getText();
        $this->description          = $this->getFieldValue(self::FIELD_DESCRIPTION)->getValue();
        $this->category             = $this->getCategory();
    }

    private function getFieldValue($field_shortname) {
        $field = $this->form_element_factory->getUsedFieldByNameForUser($this->tracker_id, $field_shortname, $this->user);

        return $this->artifact->getValue($field);
    }

    private function getCategory() {
        $field_status = $this->form_element_factory->getUsedFieldByNameForUser($this->tracker_id, self::FIELD_CATEGORY, $this->user);

        if (! $field_status) {
            return null;
        }

        $last_changeset = $this->artifact->getLastChangeset();

        return $field_status->getFirstValueFor($last_changeset);
    }
}
