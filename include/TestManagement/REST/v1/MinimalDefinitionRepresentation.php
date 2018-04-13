<?php
/**
 * Copyright (c) Enalean, 2014-2018. All Rights Reserved.
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

use PFUser;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_FormElementFactory;
use Tuleap\REST\JsonCast;


class MinimalDefinitionRepresentation
{
    const ROUTE = 'testmanagement_definitions';

    const FIELD_SUMMARY  = 'summary';
    const FIELD_CATEGORY = 'category';

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

    /**
     * @var Tracker_Artifact_Changeset|null
     */
    private $changeset;

    public function build(
        Tracker_Artifact $artifact,
        Tracker_FormElementFactory $form_element_factory,
        PFUser $user,
        Tracker_Artifact_Changeset $changeset = null
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->artifact             = $artifact;
        $this->tracker_id           = $artifact->getTrackerId();
        $this->user                 = $user;
        $this->id                   = JsonCast::toInt($artifact->getId());
        $this->uri                  = self::ROUTE . '/' . $this->id;

        $this->changeset            = $changeset ?: $artifact->getLastChangeset();

        $this->summary  = $this->getFieldValue(self::FIELD_SUMMARY)->getText();
        $this->category = $this->getCategory();
    }

    protected function getFieldValue($field_shortname)
    {
        $field = $this->form_element_factory->getUsedFieldByNameForUser(
            $this->tracker_id,
            $field_shortname,
            $this->user
        );

        return $this->artifact->getValue($field, $this->changeset);
    }

    private function getCategory()
    {
        /** @var \Tracker_FormElement_Field_List $field_status */
        $field_status = $this->form_element_factory->getUsedFieldByNameForUser(
            $this->tracker_id,
            self::FIELD_CATEGORY,
            $this->user
        );

        if (! $field_status) {
            return null;
        }

        return $field_status->getFirstValueFor($this->changeset);
    }
}
