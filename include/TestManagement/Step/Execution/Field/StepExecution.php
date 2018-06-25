<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
use DataAccessObject;
use TemplateRendererFactory;
use Tracker_Artifact;
use Tracker_Artifact_Changeset;
use Tracker_Artifact_ChangesetValue;
use Tracker_FormElement_Field;
use Tracker_FormElement_FieldVisitor;
use Tuleap\TestManagement\Step\Execution\StepResult;
use Tuleap\TestManagement\Step\Execution\StepResultPresenter;
use Tuleap\TestManagement\Step\Execution\Tracker_;
use Tuleap\TestManagement\Step\Execution\Tracker_ReportCriteria;
use Tuleap\TestManagement\Step\Step;
use Tuleap\TestManagement\Step\StepPresenter;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;

class StepExecution extends Tracker_FormElement_Field implements TrackerFormElementExternalField
{
    const TYPE             = 'ttmstepexec';
    const UPDATE_VALUE_KEY = 'steps_results';

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        $visitor->visitExternalField($this);
    }

    /**
     * @return string the label of the formElement (mainly used in admin part)
     */
    public static function getFactoryLabel()
    {
        return dgettext('tuleap-testmanagement', 'Step execution');
    }

    /**
     * @return string the description of the formElement (mainly used in admin part)
     */
    public static function getFactoryDescription()
    {
        return dgettext('tuleap-testmanagement', 'Execution result of a step');
    }

    /**
     * @return string the path to the icon to use an element
     */
    public static function getFactoryIconUseIt()
    {
        return TESTMANAGEMENT_BASE_URL . '/themes/default/images/ic/tick-circle.png';
    }

    /**
     * @return string the path to the icon to create an element
     */
    public static function getFactoryIconCreate()
    {
        return TESTMANAGEMENT_BASE_URL . '/themes/default/images/ic/tick-circle--plus.png';
    }

    public static function getFactoryUniqueField()
    {
        return true;
    }

    /**
     * Display the html f in the admin ui
     *
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        return '<ol><li><span>First step definition</span> <span class="label">passed</span></li></ol>';
    }

    public function getSOAPAvailableValues()
    {
        return null;
    }

    public function canBeUsedAsReportCriterion()
    {
        return false;
    }

    public function canBeUsedAsReportColumn()
    {
        return false;
    }

    /**
     * Display the field value as a criteria
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     * @see fetchCriteria
     */
    public function fetchCriteriaValue($criteria)
    {
        return '';
    }

    /**
     * Fetch the value
     *
     * @param mixed $value the value of the field
     *
     * @return string
     */
    public function fetchRawValue($value)
    {
        return '';
    }

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     */
    public function getCriteriaFrom($criteria)
    {
        return '';
    }

    /**
     * Get the "where" statement to allow search with this field
     *
     * @param Tracker_ReportCriteria $criteria
     *
     * @return string
     * @see getCriteriaFrom
     */
    public function getCriteriaWhere($criteria)
    {
        return '';
    }

    public function getQuerySelect()
    {
        return '';
    }

    /**
     * Return the dao of the criteria value used with this field.
     * @return DataAccessObject
     */
    protected function getCriteriaDao()
    {
        return null;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $value = null,
        $submitted_values = []
    ) {
        return '<div class="alert">'
            . dgettext(
                'tuleap-testmanagement',
                'Direct edition of steps results is not allowed. Please use TestManagement service instead.'
            )
            . '</div>'
            . $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $value = null
    ) {
        $renderer = TemplateRendererFactory::build()->getRenderer(TESTMANAGEMENT_BASE_DIR . '/templates');

        $purifier       = Codendi_HTMLPurifier::instance();
        $no_value_label = $this->getNoValueLabel();

        return $renderer->renderToString(
            'step-exec-readonly',
            [
                'steps'                   => $this->getStepResultPresentersFromChangesetValue($value),
                'purified_no_value_label' => $purifier->purify($no_value_label, CODENDI_PURIFIER_FULL)
            ]
        );
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $value = null,
        $submitted_values = []
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) .
            $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    protected function fetchSubmitValue()
    {
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    protected function fetchSubmitValueMasschange()
    {
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact                $artifact
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of the field
     *
     * @return string
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null)
    {
        return '';
    }

    public function fetchAddTooltip($used, $prefix = '')
    {
        return '';
    }

    /**
     * @return StepExecutionChangesetValueDao
     */
    protected function getValueDao()
    {
        return new StepExecutionChangesetValueDao();
    }

    /**
     * Fetch the value to display changes in followups
     *
     * @param Tracker_ $artifact
     * @param array    $from the value(s) *before*
     * @param array    $to   the value(s) *after*
     *
     * @return string
     */
    public function fetchFollowUp($artifact, $from, $to)
    {
        return '';
    }

    /**
     * Fetch the value in a specific changeset
     *
     * @param Tracker_Artifact_Changeset $changeset
     *
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        return '';
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
    {
        return true;
    }

    public function hasChanges(
        Tracker_Artifact $artifact,
        Tracker_Artifact_ChangesetValue $old_value,
        $new_value
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

    /**
     * Save the value and return the id
     *
     * @param Tracker_Artifact                $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value
     * @param mixed                           $value                   The value submitted by the user
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        Tracker_Artifact_ChangesetValue $previous_changesetvalue = null
    ) {
        return $this->getValueDao()->create($changeset_value_id, $value[self::UPDATE_VALUE_KEY]);
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param boolean                    $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
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

    /**
     * Display the field as a Changeset value.
     * Used in report table
     *
     * @param int   $artifact_id  the corresponding artifact id
     * @param int   $changeset_id the corresponding changeset
     * @param mixed $value        the value of the field
     * @param int   $report_id    the id of the calling report
     *
     * @return string
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report_id = null, $from_aid = null)
    {
        return '';
    }

    public function getFormAdminVisitor(Tracker_FormElement_Field $element, array $used_element)
    {
        return new ViewAdmin($element, $used_element);
    }

    /**
     * @param Tracker_Artifact_ChangesetValue|null $value
     *
     * @return StepResultPresenter[]
     */
    private function getStepResultPresentersFromChangesetValue(Tracker_Artifact_ChangesetValue $value = null)
    {
        $step_results = [];
        if ($value) {
            $step_results = $value->getValue();
        }

        return array_map(
            function (StepResult $step_result) {
                $step_presenter = new StepPresenter($step_result->getStep(), $this->getTracker()->getProject());

                return new StepResultPresenter($step_presenter, $step_result);
            },
            $step_results
        );
    }
}
