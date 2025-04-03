<?php
/**
 * Copyright (c) Enalean, 2023 - present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\Tracker\Test\Builders\Fields;

use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field;
use Tracker_FormElement_FieldVisitor;
use Tracker_Report;
use Tracker_Report_Criteria;
use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Test\Builders\TrackerTestBuilder;

final class ExternalFieldBuilder
{
    private string $name                        = 'external_field';
    private ?\PFUser $user_with_read_permission = null;
    private bool $read_permission               = false;
    private \Tracker $tracker;

    private function __construct(private readonly int $id)
    {
        $this->tracker = TrackerTestBuilder::aTracker()->withId(10)->build();
    }

    public static function anExternalField(int $id): self
    {
        return new self($id);
    }

    public function withName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function inTracker(\Tracker $tracker): self
    {
        $this->tracker = $tracker;
        return $this;
    }

    public function withReadPermission(\PFUser $user, bool $user_can_read): self
    {
        $this->user_with_read_permission = $user;
        $this->read_permission           = $user_can_read;
        return $this;
    }

    public function build(): Tracker_FormElement_Field
    {
        $field = new class (
            $this->id,
            $this->tracker->getId(),
            15,
            $this->name,
            '',
            '',
            true,
            '',
            false,
            false,
            10,
            null
        ) extends Tracker_FormElement_Field implements TrackerFormElementExternalField {
            /** @return void */
            public function accept(Tracker_FormElement_FieldVisitor $visitor)
            {
                $visitor->visitExternalField($this);
            }

            /** @return string */
            public static function getFactoryLabel()
            {
                return '';
            }

            /** @return string */
            public static function getFactoryDescription()
            {
                return '';
            }

            /** @return string */
            public static function getFactoryIconUseIt()
            {
                return '';
            }

            /** @return string */
            public static function getFactoryIconCreate()
            {
                return '';
            }

            /** @return void */
            public function getFormAdminVisitor(Tracker_FormElement_Field $element, array $used_element)
            {
            }

            /** @return string */
            protected function fetchAdminFormElement()
            {
                return '';
            }

            /** @return void */
            public function getRESTAvailableValues()
            {
            }

            public function fetchCriteriaValue(Tracker_Report_Criteria $criteria): string
            {
                return '';
            }

            public function fetchRawValue(mixed $value): string
            {
                return '';
            }

            public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
            {
                return Option::nothing(ParametrizedFromWhere::class);
            }

            /** @return void */
            protected function getCriteriaDao()
            {
            }

            protected function fetchArtifactValue(
                Artifact $artifact,
                ?Tracker_Artifact_ChangesetValue $value,
                array $submitted_values,
            ): string {
                return '';
            }

            /** @return string */
            public function fetchArtifactValueReadOnly(
                Artifact $artifact,
                ?Tracker_Artifact_ChangesetValue $value = null,
            ) {
                return '';
            }

            protected function fetchSubmitValue(array $submitted_values): string
            {
                return '';
            }

            protected function fetchSubmitValueMasschange(): string
            {
                return '';
            }

            protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null): string
            {
                return '';
            }

            /** @return void */
            protected function getValueDao()
            {
            }

            public function fetchRawValueFromChangeset(Tracker_Artifact_Changeset $changeset): string
            {
                return '';
            }

            /**
             * @param mixed $value
             * @return bool
             */
            protected function validate(Artifact $artifact, $value)
            {
                return true;
            }

            /**
             * @param Artifact $artifact
             * @param int $changeset_value_id
             * @param mixed $value
             * @return bool
             */
            protected function saveValue(
                $artifact,
                $changeset_value_id,
                $value,
                ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
                CreatedFileURLMapping $url_mapping,
            ) {
                return true;
            }

            /**
             * @param \Tracker_Artifact_Changeset $changeset
             * @param int $value_id
             * @param bool $has_changed
             * @return \Tracker_Artifact_ChangesetValue|null
             */
            public function getChangesetValue($changeset, $value_id, $has_changed)
            {
                return null;
            }

            public function fetchChangesetValue(
                int $artifact_id,
                int $changeset_id,
                mixed $value,
                ?Tracker_Report $report = null,
                ?int $from_aid = null,
            ): string {
                return '';
            }

            public function isAlwaysInEditMode(): bool
            {
                return false;
            }
        };
        $field->setTracker($this->tracker);
        if ($this->user_with_read_permission !== null) {
            $field->setUserCanRead($this->user_with_read_permission, $this->read_permission);
        }
        return $field;
    }
}
