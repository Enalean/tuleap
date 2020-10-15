<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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
use Tracker_Artifact_Changeset;
use Tracker_FormElementFactory;
use Tuleap\REST\JsonCast;
use Tuleap\Tracker\Artifact\Artifact;

/**
 * @psalm-immutable
 */
class MinimalDefinitionRepresentation
{
    public const ROUTE = 'testmanagement_definitions';

    public const FIELD_SUMMARY         = 'summary';
    public const FIELD_CATEGORY        = 'category';
    public const FIELD_AUTOMATED_TESTS = 'automated_tests';

    /**
     * @var int ID of the artifact
     *
     * @psalm-readonly
     */
    public $id;

    /**
     * @var String
     *
     * @psalm-readonly
     */
    public $uri;

    /**
     * @var String
     *
     * @psalm-readonly
     */
    public $summary;

    /**
     * @var string | null
     *
     * @psalm-readonly
     */
    public $category;

    /**
     * @var string
     *
     * @psalm-readonly
     */
    public $automated_tests;

    public function __construct(
        Artifact $artifact,
        Tracker_FormElementFactory $form_element_factory,
        PFUser $user,
        ?Tracker_Artifact_Changeset $changeset = null
    ) {
        $tracker_id = $artifact->getTrackerId();
        $this->id   = JsonCast::toInt($artifact->getId());
        $this->uri  = self::ROUTE . '/' . $this->id;

        $changeset = $changeset ?: self::getArtifactLastChangeset($artifact);

        $this->summary         = self::getTextFieldValue(
            $form_element_factory,
            $tracker_id,
            $user,
            $artifact,
            $changeset,
            self::FIELD_SUMMARY
        );
        $this->category        = self::getCategory($form_element_factory, $tracker_id, $user, $changeset);
        $this->automated_tests = self::getTextFieldValue(
            $form_element_factory,
            $tracker_id,
            $user,
            $artifact,
            $changeset,
            self::FIELD_AUTOMATED_TESTS
        );
    }

    private static function getArtifactLastChangeset(Artifact $artifact): ?Tracker_Artifact_Changeset
    {
        return $artifact->getLastChangeset();
    }

    /**
     * @param string $field_shortname
     *
     * @return string
     */
    final protected static function getTextFieldValue(
        Tracker_FormElementFactory $form_element_factory,
        int $tracker_id,
        PFUser $user,
        Artifact $artifact,
        ?Tracker_Artifact_Changeset $changeset,
        $field_shortname
    ) {
        $field_value = self::getFieldValue($form_element_factory, $tracker_id, $user, $artifact, $changeset, $field_shortname);
        if (! $field_value) {
            return '';
        }
        \assert($field_value instanceof \Tracker_Artifact_ChangesetValue_Text);

        return $field_value->getText();
    }

    /**
     * @param string $field_shortname
     *
     * @return \Tracker_Artifact_ChangesetValue|null
     */
    final protected static function getFieldValue(
        Tracker_FormElementFactory $form_element_factory,
        int $tracker_id,
        PFUser $user,
        Artifact $artifact,
        ?Tracker_Artifact_Changeset $changeset,
        $field_shortname
    ) {
        $field = $form_element_factory->getUsedFieldByNameForUser(
            $tracker_id,
            $field_shortname,
            $user
        );

        if (! $field) {
            return null;
        }

        return $artifact->getValue($field, $changeset);
    }

    private static function getCategory(
        Tracker_FormElementFactory $form_element_factory,
        int $tracker_id,
        PFUser $user,
        ?Tracker_Artifact_Changeset $changeset
    ): ?string {
        $field_status = $form_element_factory->getSelectboxFieldByNameForUser(
            $tracker_id,
            self::FIELD_CATEGORY,
            $user
        );

        if (! $field_status || ! $changeset) {
            return null;
        }
        \assert($field_status instanceof \Tracker_FormElement_Field_List);

        return $field_status->getFirstValueFor($changeset);
    }
}
