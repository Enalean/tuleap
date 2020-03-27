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
    public const ROUTE = 'testmanagement_definitions';

    public const FIELD_SUMMARY         = 'summary';
    public const FIELD_CATEGORY        = 'category';
    public const FIELD_AUTOMATED_TESTS = 'automated_tests';

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
     * @var string | null
     */
    public $category;

    /**
     * @var string
     */
    public $automated_tests;

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

    /**
     * @return void
     */
    public function build(
        Tracker_Artifact $artifact,
        Tracker_FormElementFactory $form_element_factory,
        PFUser $user,
        ?Tracker_Artifact_Changeset $changeset = null
    ) {
        $this->form_element_factory = $form_element_factory;
        $this->artifact             = $artifact;
        $this->tracker_id           = $artifact->getTrackerId();
        $this->user                 = $user;
        $this->id                   = JsonCast::toInt($artifact->getId());
        $this->uri                  = self::ROUTE . '/' . $this->id;

        $this->changeset            = $changeset ?: $artifact->getLastChangeset();

        $this->summary         = $this->getTextFieldValue(self::FIELD_SUMMARY);
        $this->category        = $this->getCategory();
        $this->automated_tests = $this->getTextFieldValue(self::FIELD_AUTOMATED_TESTS);
    }

    /**
     * @param string $field_shortname
     *
     * @return string
     */
    protected function getTextFieldValue($field_shortname)
    {
        $field_value = $this->getFieldValue($field_shortname);
        \assert($field_value instanceof \Tracker_Artifact_ChangesetValue_Text);
        if (! $field_value) {
            return '';
        }

        return $field_value->getText();
    }

    /**
     * @param string $field_shortname
     *
     * @return \Tracker_Artifact_ChangesetValue|null
     */
    protected function getFieldValue($field_shortname)
    {
        $field = $this->form_element_factory->getUsedFieldByNameForUser(
            $this->tracker_id,
            $field_shortname,
            $this->user
        );

        if (! $field) {
            return null;
        }

        return $this->artifact->getValue($field, $this->changeset);
    }

    private function getCategory(): ?string
    {
        $field_status = $this->form_element_factory->getSelectboxFieldByNameForUser(
            $this->tracker_id,
            self::FIELD_CATEGORY,
            $this->user
        );
        \assert($field_status instanceof \Tracker_FormElement_Field_List);

        if (! $field_status || ! $this->changeset) {
            return null;
        }

        return $field_status->getFirstValueFor($this->changeset);
    }
}
