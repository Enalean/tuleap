<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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

namespace Tuleap\TestManagement\Step\Execution\Field;

use Codendi_HTMLPurifier;
use TemplateRendererFactory;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field;
use Tracker_FormElement_FieldVisitor;
use Tracker_Report_Criteria;
use Tuleap\Option\Option;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\TestManagement\Step\Execution\StepResultPresenter;
use Tuleap\TestManagement\Step\Step;
use Tuleap\TestManagement\Step\StepPresenter;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

class StepExecution extends Tracker_FormElement_Field implements TrackerFormElementExternalField
{
    public const TYPE             = 'ttmstepexec';
    public const UPDATE_VALUE_KEY = 'steps_results';

    /**
     * @return void
     */
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        $visitor->visitExternalField($this);
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-testmanagement', 'Step execution');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-testmanagement', 'Execution result of a step');
    }

    public static function getFactoryIconUseIt()
    {
        return TESTMANAGEMENT_BASE_URL . '/images/ic/tick-circle.png';
    }

    public static function getFactoryIconCreate()
    {
        return TESTMANAGEMENT_BASE_URL . '/images/ic/tick-circle--plus.png';
    }

    public static function getFactoryUniqueField()
    {
        return true;
    }

    protected function fetchAdminFormElement()
    {
        return '<ol><li><span>First step definition</span> <span class="label">passed</span></li></ol>';
    }

    /**
     * @return null
     */
    public function getRESTAvailableValues()
    {
        return null;
    }

    /**
     * @return false
     */
    public function canBeUsedAsReportCriterion()
    {
        return false;
    }

    /**
     * @return false
     */
    public function canBeUsedAsReportColumn()
    {
        return false;
    }

    /**
     * @param mixed $criteria
     */
    public function fetchCriteriaValue($criteria)
    {
        return '';
    }

    public function fetchRawValue($value)
    {
        return '';
    }

    public function getCriteriaFrom(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedFrom::class);
    }

    public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedSQLFragment::class);
    }

    public function getQuerySelect(): string
    {
        return '';
    }

    protected function getCriteriaDao()
    {
        return null;
    }

    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return '<div class="alert">'
            . dgettext(
                'tuleap-testmanagement',
                'Direct edition of steps results is not allowed. Please use TestManagement service instead.'
            )
            . '</div>'
            . $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    public function fetchArtifactValueReadOnly(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
    ) {
        $renderer = TemplateRendererFactory::build()->getRenderer(TESTMANAGEMENT_BASE_DIR . '/templates');

        $purifier       = Codendi_HTMLPurifier::instance();
        $no_value_label = $this->getNoValueLabel();

        return $renderer->renderToString(
            'step-exec-readonly',
            [
                'steps'                   => $this->getStepResultPresentersFromChangesetValue($value),
                'purified_no_value_label' => $purifier->purify($no_value_label, CODENDI_PURIFIER_FULL),
            ]
        );
    }

    /**
     * @return string
     */
    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        array $submitted_values = [],
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) .
            $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    protected function fetchSubmitValue(array $submitted_values)
    {
        return '';
    }

    protected function fetchSubmitValueMasschange()
    {
        return '';
    }

    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        return '';
    }

    public function fetchAddCardFields(array $used_fields, string $prefix = ''): string
    {
        return '';
    }

    public function canBeDisplayedInTooltip(): bool
    {
        return false;
    }

    /**
     * @return StepExecutionChangesetValueDao
     */
    protected function getValueDao()
    {
        return new StepExecutionChangesetValueDao();
    }

    public function fetchRawValueFromChangeset($changeset)
    {
        return '';
    }

    protected function validate(Artifact $artifact, $value)
    {
        return true;
    }

    public function hasChanges(
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $old_value,
        $new_value,
    ) {
        $old_values = [];
        /** @var StepResult[] $old_steps */
        $old_steps = $old_value->getValue();
        foreach ($old_steps as $step_result) {
            $old_values[$step_result->getStep()->getId()] = $step_result->getStatus();
        }
        $new_values = $new_value[self::UPDATE_VALUE_KEY];

        return array_diff_assoc($new_values, $old_values) !== [] || array_diff_assoc($old_values, $new_values) !== [];
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        return $this->getValueDao()->create($changeset_value_id, $value[self::UPDATE_VALUE_KEY]);
    }

    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $steps = [];
        foreach ($this->getValueDao()->searchById($value_id) as $row) {
            $step = new Step(
                $row['id'],
                $row['description'],
                $row['description_format'],
                $row['expected_results'],
                $row['expected_results_format'],
                $row['rank']
            );

            $steps[] = new StepResult($step, $row['status']);
        }

        return new StepExecutionChangesetValue($value_id, $changeset, $this, $has_changed, $steps);
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?\Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        return '';
    }

    /**
     * @return ViewAdmin
     */
    public function getFormAdminVisitor(Tracker_FormElement_Field $element, array $used_element)
    {
        return new ViewAdmin($element, $used_element);
    }

    /**
     *
     * @return StepResultPresenter[]
     */
    private function getStepResultPresentersFromChangesetValue(?Tracker_Artifact_ChangesetValue $value = null)
    {
        $step_results = [];
        if ($value) {
            $step_results = $value->getValue();
        }

        $tracker = $this->getTracker();
        if (! $tracker) {
            return [];
        }

        return array_map(
            static function (StepResult $step_result) use ($tracker) {
                $step_presenter = new StepPresenter($step_result->getStep(), $tracker->getProject());

                return new StepResultPresenter($step_presenter, $step_result);
            },
            $step_results
        );
    }
}
