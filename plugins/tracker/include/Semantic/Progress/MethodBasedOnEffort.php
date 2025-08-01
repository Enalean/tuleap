<?php
/**
 * Copyright (c) Enalean, 2021 - present. All Rights Reserved.
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


namespace Tuleap\Tracker\Semantic\Progress;

use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\NumericField;

class MethodBasedOnEffort implements IComputeProgression
{
    private const METHOD_NAME = 'effort-based';

    /**
     * @var NumericField
     */
    private $total_effort_field;
    /**
     * @var NumericField
     */
    private $remaining_effort_field;
    /**
     * @var SemanticProgressDao
     */
    private $dao;

    public function __construct(
        SemanticProgressDao $dao,
        NumericField $total_effort_field,
        NumericField $remaining_effort_field,
    ) {
        $this->dao                    = $dao;
        $this->total_effort_field     = $total_effort_field;
        $this->remaining_effort_field = $remaining_effort_field;
    }

    public static function getMethodName(): string
    {
        return self::METHOD_NAME;
    }

    public static function getMethodLabel(): string
    {
        return dgettext('tuleap-tracker', 'Effort based');
    }

    public function getTotalEffortFieldId(): int
    {
        return $this->total_effort_field->getId();
    }

    public function getRemainingEffortFieldId(): int
    {
        return $this->remaining_effort_field->getId();
    }

    public function getCurrentConfigurationDescription(): string
    {
        return sprintf(
            dgettext(
                'tuleap-tracker',
                'The progress of artifacts is based on effort and will be computed by dividing the current value of the "%s" field by the current value of the "%s" field.'
            ),
            $this->remaining_effort_field->getLabel(),
            $this->total_effort_field->getLabel()
        );
    }

    public function isFieldUsedInComputation(\Tracker_FormElement_Field $field): bool
    {
        $field_id = $field->getId();

        return $field_id === $this->total_effort_field->getId()
            || $field_id === $this->remaining_effort_field->getId();
    }

    public function computeProgression(Artifact $artifact, \PFUser $user): ProgressionResult
    {
        if (! $this->canUserReadBothFields($user)) {
            return new ProgressionResult(null, '');
        }

        $total_effort = $this->getNumericFieldValue(
            $this->total_effort_field,
            $artifact,
            $user
        );

        $remaining_effort = $this->getNumericFieldValue(
            $this->remaining_effort_field,
            $artifact,
            $user
        );

        if ($total_effort === null && $remaining_effort === null) {
            return new ProgressionResult(null, '');
        }

        if (! $total_effort) {
            return new ProgressionResult(null, dgettext('tuleap-tracker', 'There is no total effort.'));
        }

        if ($total_effort < 0) {
            return new ProgressionResult(null, dgettext('tuleap-tracker', 'Total effort cannot be negative.'));
        }

        if ($remaining_effort === null) {
            return new ProgressionResult(null, dgettext('tuleap-tracker', 'There is no remaining effort.'));
        }

        if ($remaining_effort < 0) {
            return new ProgressionResult(null, dgettext('tuleap-tracker', 'Remaining effort cannot be negative.'));
        }

        if ($remaining_effort > $total_effort) {
            return new ProgressionResult(null, dgettext('tuleap-tracker', 'Remaining effort cannot be greater than total effort.'));
        }

        return new ProgressionResult(1 - ($remaining_effort / $total_effort), '');
    }

    private function getNumericFieldValue(\Tuleap\Tracker\FormElement\Field\NumericField $numeric_field, Artifact $artifact, \PFUser $user): ?float
    {
        if ($numeric_field instanceof \Tuleap\Tracker\FormElement\Field\Computed\ComputedField) {
            return $numeric_field->getComputedValue($user, $artifact) ?? 0.0;
        }

        $last_changeset = $numeric_field->getLastChangesetValue($artifact);
        if ($last_changeset === null) {
            return null;
        }

        if (! ($last_changeset instanceof \Tracker_Artifact_ChangesetValue_Numeric)) {
            return null;
        }

        return (float) $last_changeset->getNumeric();
    }

    private function canUserReadBothFields(\PFUser $user): bool
    {
        return $this->total_effort_field->userCanRead($user) &&
            $this->remaining_effort_field->userCanRead($user);
    }

    public function isConfiguredAndValid(): bool
    {
        return true;
    }

    public function isConfigured(): bool
    {
        return true;
    }

    public function getErrorMessage(): string
    {
        return '';
    }

    public function exportToREST(\PFUser $user): ?IRepresentSemanticProgress
    {
        if (! $this->canUserReadBothFields($user)) {
            return null;
        }

        return new SemanticProgressBasedOnEffortRepresentation(
            $this->total_effort_field->getId(),
            $this->remaining_effort_field->getId()
        );
    }

    public function exportToXMl(\SimpleXMLElement $root, array $xml_mapping): void
    {
        $total_effort_field_ref     = array_search($this->total_effort_field->getId(), $xml_mapping);
        $remaining_effort_field_ref = array_search($this->remaining_effort_field->getId(), $xml_mapping);

        if (! $total_effort_field_ref || ! $remaining_effort_field_ref) {
            return;
        }

        $xml_semantic_progress = $root->addChild('semantic');
        $xml_semantic_progress->addAttribute('type', SemanticProgress::NAME);

        $xml_semantic_progress->addChild('total_effort_field')->addAttribute('REF', $total_effort_field_ref);
        $xml_semantic_progress->addChild('remaining_effort_field')->addAttribute('REF', $remaining_effort_field_ref);
    }

    public function saveSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool
    {
        return $this->dao->save(
            $tracker->getId(),
            $this->total_effort_field->getId(),
            $this->remaining_effort_field->getId(),
            null
        );
    }

    public function deleteSemanticForTracker(\Tuleap\Tracker\Tracker $tracker): bool
    {
        return $this->dao->delete($tracker->getId());
    }
}
