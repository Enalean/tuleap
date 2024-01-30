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

namespace Tuleap\Tracker\Test\Builders;

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

final class TrackerExternalFormElementBuilder
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
            public function accept(Tracker_FormElement_FieldVisitor $visitor)
            {
                $visitor->visitExternalField($this);
            }

            public static function getFactoryLabel()
            {
            }

            public static function getFactoryDescription()
            {
            }

            public static function getFactoryIconUseIt()
            {
            }

            public static function getFactoryIconCreate()
            {
            }

            public function getFormAdminVisitor(Tracker_FormElement_Field $element, array $used_element)
            {
            }

            protected function fetchAdminFormElement()
            {
            }

            public function getRESTAvailableValues()
            {
            }

            public function fetchCriteriaValue($criteria)
            {
            }

            public function fetchRawValue($value)
            {
            }

            public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
            {
                return Option::nothing(ParametrizedFromWhere::class);
            }

            protected function getCriteriaDao()
            {
            }

            protected function fetchArtifactValue(
                Artifact $artifact,
                ?Tracker_Artifact_ChangesetValue $value,
                array $submitted_values,
            ) {
            }

            public function fetchArtifactValueReadOnly(
                Artifact $artifact,
                ?Tracker_Artifact_ChangesetValue $value = null,
            ) {
            }

            protected function fetchSubmitValue(array $submitted_values)
            {
            }

            protected function fetchSubmitValueMasschange()
            {
            }

            protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
            {
            }

            protected function getValueDao()
            {
            }

            public function fetchRawValueFromChangeset($changeset)
            {
            }

            protected function validate(Artifact $artifact, $value)
            {
            }

            protected function saveValue(
                $artifact,
                $changeset_value_id,
                $value,
                ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
                CreatedFileURLMapping $url_mapping,
            ) {
            }

            public function getChangesetValue($changeset, $value_id, $has_changed)
            {
            }

            public function fetchChangesetValue(
                int $artifact_id,
                int $changeset_id,
                mixed $value,
                ?Tracker_Report $report = null,
                ?int $from_aid = null,
            ): string {
                return "";
            }
        };
        $field->setTracker($this->tracker);
        if ($this->user_with_read_permission !== null) {
            $field->setUserCanRead($this->user_with_read_permission, $this->read_permission);
        }
        return $field;
    }
}
