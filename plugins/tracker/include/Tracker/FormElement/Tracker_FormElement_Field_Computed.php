<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\ChangesetValueComputed;
use Tuleap\Tracker\DAO\ComputedDao;
use Tuleap\Tracker\FormElement\ComputedFieldCalculator;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDao;
use Tuleap\Tracker\FormElement\Field\Computed\ComputedFieldDaoCache;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\FieldCalculator;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use Tuleap\Tracker\REST\Artifact\ArtifactFieldComputedValueFullRepresentation;

class Tracker_FormElement_Field_Computed extends Tracker_FormElement_Field_Float //phpcs:ignore
{
    public const FIELD_VALUE_IS_AUTOCOMPUTED = 'is_autocomputed';
    public const FIELD_VALUE_MANUAL          = 'manual_value';

    public $default_properties = [
        'target_field_name' => [
            'value' => null,
            'type'  => 'string',
            'size'  => 40,
        ],
        'fast_compute' => [
            'value' => null,
            'type'  => 'upgrade_button',
        ],
        'default_value' => [
            'value' => '',
            'type'  => 'string',
            'size'  => 40,
        ],
    ];

    public function __construct(
        $id,
        $tracker_id,
        $parent_id,
        $name,
        $label,
        $description,
        $use_it,
        $scope,
        $required,
        $notifications,
        $rank,
        ?Tracker_FormElement $original_field = null,
    ) {
        parent::__construct(
            $id,
            $tracker_id,
            $parent_id,
            $name,
            $label,
            $description,
            $use_it,
            $scope,
            $required,
            $notifications,
            $rank,
            $original_field
        );

        $this->doNotDisplaySpecialPropertiesAtFieldCreation();
    }

    private function doNotDisplaySpecialPropertiesAtFieldCreation()
    {
        $this->clearFastCompute();
        $this->clearTargetFieldName();
        $this->clearCache();
    }

    private function clearFastCompute()
    {
        if ($this->getProperty('fast_compute') === null) {
            unset($this->default_properties['fast_compute']);
        }
    }

    private function clearTargetFieldName()
    {
        if ($this->getName() === null) {
            unset($this->default_properties['target_field_name']);
        }
    }

    private function clearCache()
    {
        $this->cache_specific_properties = null;
    }

    public function isCSVImportable(): bool
    {
        return false;
    }

    /**
     * @return float|null if there are no data (/!\ it's on purpose, otherwise we can mean to distinguish if there is data but 0 vs no data at all, for the graph plot)
     */
    public function getComputedValue(
        PFUser $user,
        Artifact $artifact,
        $timestamp = null,
    ) {
        return $this->getCalculator()->calculate(
            [$artifact->getId()],
            $timestamp,
            true,
            $this->getName(),
            $this->getId()
        );
    }

    public function getComputedValueWithNoStopOnManualValue(Artifact $artifact)
    {
        $computed_children_to_fetch    = [];
        $artifact_ids_to_fetch         = [];
        $has_manual_value_in_children  = false;
        $target_field_name             = $this->getName();
        $dar                           = $this->getDao()->getComputedFieldValues(
            [$artifact->getId()],
            $target_field_name,
            $this->getId(),
            false
        );
        $manual_value_for_current_node = $this->getValueDao()->getManuallySetValueForChangeset(
            $artifact->getLastChangeset()->getId(),
            $this->getId()
        );

        if ($dar) {
            foreach ($dar as $row) {
                if ($row['id'] !== null) {
                    $artifact_ids_to_fetch[] = $row['id'];
                }
                if ($row['type'] === 'computed') {
                    $computed_children_to_fetch[] = $row['id'];
                }
                if (isset($row[$row['type'] . '_value'])) {
                    $has_manual_value_in_children = true;
                }
            }
        }

        if (($manual_value_for_current_node['value'] ?? null) !== null && $has_manual_value_in_children) {
            $computed_children = 0;
            if (count($computed_children_to_fetch) > 0) {
                $computed_children = $this->getStandardCalculationMode($computed_children_to_fetch);
            }
            $manually_set_children = $this->getStopAtManualSetFieldMode([$artifact->getId()]);
            return $manually_set_children + $computed_children;
        }

        if (count($artifact_ids_to_fetch) === 0 && $has_manual_value_in_children) {
            return $this->getStopAtManualSetFieldMode([$artifact->getId()]);
        }

        if ($has_manual_value_in_children && ($manual_value_for_current_node['value'] ?? null) === null) {
            return $this->getStandardCalculationMode([$artifact->getId()]);
        }

        if (count($artifact_ids_to_fetch) === 0) {
            return null;
        }

        return $this->getStandardCalculationMode($artifact_ids_to_fetch);
    }

    public function getStopAtManualSetFieldMode(array $artifact_ids)
    {
        return $this->getCalculator()->calculate(
            $artifact_ids,
            null,
            false,
            $this->getName(),
            $this->getId()
        );
    }

    public function getFieldEmptyMessage()
    {
        return dgettext('tuleap-tracker', 'Empty');
    }

    public function getStandardCalculationMode(array $artifact_ids)
    {
        return $this->getCalculator()->calculate(
            $artifact_ids,
            null,
            true,
            $this->getName(),
            $this->getId()
        );
    }

    protected function getNoValueLabel()
    {
        return "<span class='empty_value auto-computed-label'>" . $this->getFieldEmptyMessage() . "</span>";
    }

    protected function getComputedValueWithNoLabel(Artifact $artifact, PFUser $user, $stop_on_manual_value)
    {
        if ($stop_on_manual_value) {
            $empty_array    = [];
            $computed_value = $this->getComputedValue($user, $artifact, null, $empty_array);
        } else {
            $computed_value = $this->getComputedValueWithNoStopOnManualValue($artifact);
        }

        return ($computed_value !== null) ? $computed_value : $this->getFieldEmptyMessage();
    }

    protected function processUpdate(
        Tracker_IDisplayTrackerLayout $layout,
        $request,
        $current_user,
        $redirect = false,
    ) {
        $formElement_data = $request->get('formElement_data');

        if ($formElement_data !== false) {
            $default_specific_properties   = [
                'fast_compute'      => '1',
                'target_field_name' => $formElement_data['name'],
            ];
            $submitted_specific_properties = isset($formElement_data['specific_properties']) ? $formElement_data['specific_properties'] : [];

            $merged_specific_properties = array_merge(
                $default_specific_properties,
                $submitted_specific_properties
            );

            $formElement_data['specific_properties'] = $merged_specific_properties;
            $request->set('formElement_data', $formElement_data);

            $GLOBALS['Response']->addFeedback(
                'warning',
                sprintf(dgettext('tuleap-tracker', 'You will not able to edit by hand the field "%1$s" since no update and submit permissions will be granted.'), $this->getName())
            );
        }

        parent::processUpdate(
            $layout,
            $request,
            $current_user,
            $redirect
        );
    }

    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
        $form_element_data['specific_properties']['fast_compute']      = '1';
        $form_element_data['specific_properties']['target_field_name'] = $this->name;
        $this->storeProperties($form_element_data['specific_properties']);

        parent::afterCreate($form_element_data, $tracker_is_empty);
    }

    public function exportPropertiesToXML(&$root)
    {
        $default_value = $this->getDefaultValue();
        if ($default_value === null) {
            return;
        }

        $child_properties = $root->addChild('properties');
        $child_properties->addAttribute('default_value', (string) $default_value[self::FIELD_VALUE_MANUAL]);
    }

    /**
     * for testing purpose
     *
     * @return FieldCalculator
     */
    protected function getCalculator()
    {
        return new FieldCalculator(new ComputedFieldCalculator(new ComputedFieldDao()));
    }

    public function validateValue($value)
    {
        if (! is_array($value)) {
            return false;
        }

        if (! isset($value[self::FIELD_VALUE_MANUAL]) && ! isset($value[self::FIELD_VALUE_IS_AUTOCOMPUTED])) {
            return false;
        }

        if (
            isset($value[self::FIELD_VALUE_MANUAL]) && isset($value[self::FIELD_VALUE_IS_AUTOCOMPUTED]) &&
                $value[self::FIELD_VALUE_IS_AUTOCOMPUTED]
        ) {
            return $value[self::FIELD_VALUE_MANUAL] === '';
        }

        if (isset($value[self::FIELD_VALUE_MANUAL])) {
            $is_a_float = preg_match('/^' . $this->pattern . '$/', $value[self::FIELD_VALUE_MANUAL]) === 1;
            if (! $is_a_float) {
                $GLOBALS['Response']->addFeedback('error', $this->getValidatorErrorMessage());
            }
            return $is_a_float;
        }

        return true;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) .
            $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    protected function getHiddenArtifactValueForEdition(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        $purifier       = Codendi_HTMLPurifier::instance();
        $current_user   = UserManager::instance()->getCurrentUser();
        $computed_value = $this->getComputedValueWithNoLabel($artifact, $current_user, false);

        $html  = '<div class="tracker_hidden_edition_field" data-field-id="' . $purifier->purify($this->getId()) . '">
                    <div class="input-append">';
        $html .= $this->fetchArtifactValue($artifact, $value, $submitted_values);
        $html .= $this->fetchBackToAutocomputedButton(false);
        $html .= '</div>';
        $html .= $this->fetchComputedValueWithLabel($computed_value);
        $html .= '</div>';

        return $html;
    }

    private function fetchBackToAutocomputedButton($is_disabled)
    {
        $disabled = '';
        if ($is_disabled) {
            $disabled = 'disabled="disabled"';
        }
        $html  = '<a class="btn btn-small auto-compute" ' . $disabled . ' data-test="switch-to-autocompute">
                    <i class="fas fa-redo fa-flip-horizontal"></i>';
        $html .= dgettext('tuleap-tracker', 'Auto-compute');
        $html .= '</a>';

        return $html;
    }

    private function fetchComputedValueWithLabel($computed_value)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        $html  = '<span class="original-value">';
        $html .= dgettext('tuleap-tracker', 'Computed value:');
        $html .= $purifier->purify($computed_value) . '</span>';

        return $html;
    }

    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        $displayed_value = null;
        $is_autocomputed = true;
        if ($value !== null) {
            $displayed_value = $value->getValue();
            $is_autocomputed = ! $value->isManualValue();
        }

        if (isset($submitted_values[$this->getId()][self::FIELD_VALUE_MANUAL])) {
            $displayed_value = $submitted_values[$this->getId()][self::FIELD_VALUE_MANUAL];
        }

        return $this->fetchComputedInputs($displayed_value, $is_autocomputed);
    }

    private function fetchComputedInputs($displayed_value, $is_autocomputed)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '<input type="text" class="field-computed"
            data-test="' . $this->getName() . '"
            name="artifact[' . $purifier->purify($this->getId()) . '][' . self::FIELD_VALUE_MANUAL . ']"
            value="' . $purifier->purify($displayed_value) . '" />';
        $html    .= '<input type="hidden"
            name="artifact[' . $purifier->purify($this->getId()) . '][' . self::FIELD_VALUE_IS_AUTOCOMPUTED . ']"
            value="' . $purifier->purify((int) $is_autocomputed) . '" />';

        return $html;
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact                        $artifact The artifact
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $changeset_value = null,
    ) {
        $value    = null;
        $purifier = Codendi_HTMLPurifier::instance();

        if ($changeset_value && $changeset_value->isManualValue()) {
            $value = $changeset_value->getValue();
        }

        $computed_value = $this->getComputedValueWithNoStopOnManualValue($artifact);
        if ($computed_value === null) {
            $html_computed_value = '<span class="auto-computed">' . $purifier->purify($this->getFieldEmptyMessage()) . '</span>';
        } else {
            $html_computed_value = $purifier->purify($computed_value);
        }

        $html_computed_complete_value = $html_computed_value . '<span class="auto-computed"> (' .
            dgettext('tuleap-tracker', 'autocomputed') . ')</span>';

        if ($value === null) {
            $value = $html_computed_complete_value;
        }

        $user              = $this->getCurrentUser();
        $time_frame_helper = $this->getArtifactTimeframeHelper();

        if ($time_frame_helper->artifactHelpShouldBeShownToUser($user, $this)) {
            $value = $value . '<span class="artifact-timeframe-helper"> (' . $time_frame_helper->getEndDateArtifactHelperForReadOnlyView($user, $artifact) . ')</span>';
        }

        $html = '<div class="auto-computed-label" data-test="computed-value">' . $value . '</div>' .
            '<div class="back-to-autocompute">' . $html_computed_complete_value . '</div>';

        return $html;
    }

    /**
     * Fetch data to display the field value in mail
     *
     * @param Artifact                        $artifact The artifact
     * @param PFUser                          $user     The user who will receive the email
     * @param bool                            $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     * @param string                          $format   output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(
        Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text',
    ) {
        $changeset      = $artifact->getLastChangesetWithFieldValue($this);
        $computed_value = null;
        if ($changeset !== null) {
            $computed_value = $this->getComputedValue($user, $changeset->getArtifact(), $changeset->getSubmittedOn());
        }

        return (string) ($computed_value ?? "-");
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $changeset      = $artifact->getLastChangesetWithFieldValue($this);
        $computed_value = null;
        if ($changeset !== null) {
            $current_user   = UserManager::instance()->getCurrentUser();
            $computed_value = $this->getComputedValue($current_user, $changeset->getArtifact(), $changeset->getSubmittedOn());
        }

        return (string) ($computed_value ?? "-");
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        $current_user = UserManager::instance()->getCurrentUser();
        $artifact     = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);

        $changeset = $this->getTrackerChangesetFactory()->getChangeset($artifact, $changeset_id);

        return (string) $this->getComputedValue($current_user, $changeset->getArtifact(), $changeset->getSubmittedOn());
    }

    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        return $this->getFullRESTValue($user, $changeset);
    }

    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $computed_value = $this->getComputedValueWithNoStopOnManualValue($changeset->getArtifact());
        $manual_value   = $this->getManualValueForChangeset($changeset);

        $artifact_field_value_full_representation = new ArtifactFieldComputedValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            $manual_value === null,
            $computed_value,
            $this->getManualValueForChangeset($changeset)
        );

        return $artifact_field_value_full_representation;
    }

    /**
     * @return int|float|null
     */
    private function getManualValueForChangeset(Tracker_Artifact_Changeset $artifact_changeset)
    {
        $changeset_value = $artifact_changeset->getValue($this);
        if ($changeset_value && $changeset_value->isManualValue()) {
            return $changeset_value->getNumeric();
        }

        return null;
    }

    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        if ($this->isAutocomputedDisabledAndNoManualValueProvided($value) || isset($value['value'])) {
            throw new Tracker_FormElement_InvalidFieldValueException(
                'Expected format for a computed field ' .
                ' : {"field_id" : 15458, "manual_value" : 12} or {"field_id" : 15458, "is_autocomputed" : true}'
            );
        }

        return $this->getRestFieldData($value);
    }

    /**
     * @return bool
     */
    private function isAutocomputedDisabledAndNoManualValueProvided(array $value)
    {
        return isset($value[self::FIELD_VALUE_IS_AUTOCOMPUTED]) && $value[self::FIELD_VALUE_IS_AUTOCOMPUTED] === false
            && (! isset($value[self::FIELD_VALUE_MANUAL]) || $value[self::FIELD_VALUE_MANUAL] === null);
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    public function fetchAdminFormElement()
    {
        $html = '<div class="input-append">';

        $default_value          = $this->getDefaultValue();
        $default_value_in_input = '';
        if ($default_value !== null) {
            $default_value_in_input = (string) $default_value[self::FIELD_VALUE_MANUAL];
        }

        $html .= $this->fetchComputedInputs($default_value_in_input, true);
        $html .= $this->fetchBackToAutocomputedButton(true);
        $html .= $this->fetchComputedValueWithLabel($this->getFieldEmptyMessage());
        $html .= "</div>";

        return $html;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Computed value');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Compute value (sum of numerical field) from linked artifacts.<p><strong>Note</strong>: <ul><li>Calculation will not check linked artifacts permissions AND will only calculate the field values of linked artifacts that have the same field name.</li></ul>');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/sum.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/sum.png');
    }

    protected function getDao(): ComputedFieldDao
    {
        return new ComputedFieldDao();
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

    public function getQueryFrom()
    {
        return '';
    }

    public function fetchCriteriaValue($criteria)
    {
        return '';
    }

    public function fetchRawValue($value)
    {
    }

    protected function getCriteriaDao()
    {
    }

    public function getAggregateFunctions()
    {
        return [];
    }

    public function fetchSubmitForOverlay(array $submitted_values)
    {
        return $this->buildFieldForSubmission(
            'tracker-formelement-edit-for-modal',
            'auto-computed-for-modal'
        );
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit(array $submitted_values)
    {
        return $this->buildFieldForSubmission(
            'tracker-formelement-edit-for-submit',
            'auto-computed-for-submit'
        );
    }

    private function buildFieldForSubmission(string $submit_class, string $auto_computed_class)
    {
        if (! $this->userCanSubmit()) {
            return '';
        }

        $default_value = $this->getDefaultValue();
        $extra_class   = '';
        if ($default_value !== null) {
            $extra_class = "in-edition with-default-value";
        }

        $purifier = Codendi_HTMLPurifier::instance();
        $required = $this->required ? ' <span class="highlight">*</span>' : '';

        $html  = '<div>';
        $html .= '<div class="tracker_artifact_field tracker_artifact_field-computed editable ' . $extra_class . '">';

        $title = $purifier->purify(sprintf(dgettext('tuleap-tracker', 'Edit the field "%1$s"'), $this->getLabel()));
        $html .= '<button type="button" title="' . $title . '" class="tracker_formelement_edit ' . $submit_class . '">' . $purifier->purify($this->getLabel())  . $required . '</button>';
        $html .= '<label for="tracker_artifact_' . $this->id . '" title="' . $purifier->purify($this->description) .
            '" class="tracker_formelement_label">' . $purifier->purify($this->getLabel())  . $required . '</label>';

        $html .= '<div class="input-append" data-field-id="' . $this->getId() . '">';

        $default_value_in_input = '';
        $is_autocomputed        = true;
        if ($default_value !== null) {
            $default_value_in_input = (string) $default_value[self::FIELD_VALUE_MANUAL];
            $is_autocomputed        = false;
        }

        $html .= $this->fetchComputedInputs($default_value_in_input, $is_autocomputed);
        $html .= $this->fetchBackToAutocomputedButton(false);
        $html .= '</div>';
        $html .= $this->fetchComputedValueWithLabel(
            dgettext('tuleap-tracker', 'Empty')
        );

        $html .= '</div></div>';

        return $html;
    }

    /**
     * Returns the default value for this field, or null if no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    public function getDefaultValue()
    {
        $property = $this->getProperty('default_value');
        if ($property === null) {
            return null;
        }

        return [
            self::FIELD_VALUE_IS_AUTOCOMPUTED => false,
            self::FIELD_VALUE_MANUAL => (float) $property,
        ];
    }

    public function getDefaultRESTValue()
    {
        $property = $this->getProperty('default_value');
        if ($property === null) {
            return null;
        }

        return [
            'type'  => self::FIELD_VALUE_MANUAL,
            'value' => (float) $property,
        ];
    }

    public function fetchSubmitMasschange()
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        $required = $this->isRequired() ? ' <span class="highlight">*</span>' : '';

        if ($this->userCanUpdate()) {
            $html .= '<div class="field-masschange tracker_artifact_field tracker_artifact_field-computed editable"
                         data-field-id="' . $purifier->purify($this->getId()) . '">';

            $html .= '<div class="edition-mass-change">';
            $html .= '<label for="tracker_artifact_' . $purifier->purify($this->getId()) . '"
                        title="' . $purifier->purify($this->description) . '"  class="tracker_formelement_label">' .
                        $purifier->purify($this->getLabel()) . $required . '</label>';
            $html .= '<div class=" input-append">';
            $html .= $this->fetchSubmitValueMasschange();
            $html .= '</div>';
            $html .= '</div>';

            $html .= '<div class="display-mass-change display-mass-change-hidden">';
            $html .= '<button class="tracker_formelement_edit edit-mass-change-autocompute" type="button">' .
                        $purifier->purify($this->getLabel()) . $required . '</button>';
            $html .= '<span class="auto-computed">';
            $html .= $purifier->purify(ucfirst(dgettext('tuleap-tracker', 'autocomputed')));
            $html .= '</span>';
            $html .= '</div>';

            $html .= '</div>';
        }

        return $html;
    }

    protected function fetchSubmitValueMasschange()
    {
        $unchanged = dgettext('tuleap-tracker', 'Unchanged');
        $html      = $this->fetchComputedInputs($unchanged, false);
        $html     .= $this->fetchBackToAutocomputedButton(false);
        return $html;
    }

    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
        $purifier       = Codendi_HTMLPurifier::instance();
        $computed_value = $this->getComputedValueWithNoStopOnManualValue($artifact);
        if ($computed_value === null) {
            $computed_value = $this->getFieldEmptyMessage();
        }
        $autocomputed_label = ' (' . dgettext('tuleap-tracker', 'autocomputed') . ')';
        $class              = 'auto-computed';

        $last_changset = $artifact->getLastChangesetWithFieldValue($this);
        $changeset     = null;
        if ($last_changset) {
            $changeset = $last_changset->getValue($this);
        }
        $required = $this->required ? ' <span class="highlight">*</span>' : '';

        $html = "";
        if (! $this->userCanRead()) {
            return $html;
        }

        $is_field_read_only = $this->getFrozenFieldDetector()->isFieldFrozen($artifact, $this);
        if ($is_field_read_only || ! $this->userCanUpdate()) {
            if (isset($changeset) && $changeset->getValue() !== null) {
                $computed_value     = $changeset->getValue();
                $autocomputed_label = '';
                $class              = '';
            }

            if (isset($submitted_values[$this->getId()][self::FIELD_VALUE_MANUAL])) {
                $computed_value     = $submitted_values[$this->getId()][self::FIELD_VALUE_MANUAL];
                $autocomputed_label = '';
                $class              = '';
            }

            $html .= '<div class="tracker_artifact_field tracker_artifact_field-computed">';
            $html .= '<label for="tracker_artifact_' . $this->id . '" title="' . $purifier->purify($this->description) .
                    '" class="tracker_formelement_label">' . $purifier->purify($this->getLabel()) . $required . '</label>';

            $html .= '<span class="' . $class . '">' . $computed_value . $autocomputed_label . '</span></div>';

            return $html;
        }

        $html .= '<div class="tracker_artifact_field tracker_artifact_field-computed editable">';

        $title = $purifier->purify(sprintf(dgettext('tuleap-tracker', 'Edit the field "%1$s"'), $this->getLabel()));
        $html .= '<button type="button" title="' . $title . '" class="tracker_formelement_edit tracker-formelement-edit-for-modal">' . $purifier->purify($this->getLabel())  . $required . '</button>';
        $html .= '<label for="tracker_artifact_' . $this->id . '" title="' . $purifier->purify($this->description) .
                '" class="tracker_formelement_label">' . $purifier->purify($this->getLabel()) . $required . '</label>';

        $html .= '<span class="auto-computed auto-computed-for-modal">' . $computed_value . ' (' .
        dgettext('tuleap-tracker', 'autocomputed') . ')</span>';

        $html .= '<div class="input-append add-field auto-computed-for-modal-append" data-field-id="' . $this->getId() . '">';
        $html .= '<div>';
        $html .= $this->fetchArtifactValue($artifact, $changeset, $submitted_values);
        $html .= $this->fetchBackToAutocomputedButton(false);
        $html .= '</div>';
        $html .= '<div>';
        $html .= $this->fetchComputedValueWithLabel($computed_value);
        $html .= '</div>';

        $html .= '</div></div>';

        return $html;
    }

    protected function getValueDao()
    {
        return new ComputedDao();
    }

    public function isArtifactValueAutocomputed(Artifact $artifact)
    {
        if (! $artifact->getLastChangeset()->getValue($this)) {
            return true;
        }
        return $artifact->getLastChangeset()->getValue($this)->getValue() === null;
    }

    /**
     * Fetch the html code to display the field in card
     *
     *
     * @return string
     */
    public function fetchCard(Artifact $artifact, Tracker_CardDisplayPreferences $display_preferences)
    {
        $value                      = $this->fetchCardValue($artifact, $display_preferences);
        $computed_value             = $this->getComputedValueWithNoStopOnManualValue($artifact);
        $data_field_id              = '';
        $data_field_type            = '';
        $data_field_is_autocomputed = '';
        $data_field_old_value       = '';
        $is_autocomputed            = $this->isArtifactValueAutocomputed($artifact);
        $purifier                   = Codendi_HTMLPurifier::instance();

        $is_field_frozen = $this->getFrozenFieldDetector()->isFieldFrozen($artifact, $this);
        if ($this->userCanUpdate() && ! $is_field_frozen) {
            $data_field_id              = 'data-field-id="' . $purifier->purify($this->getId()) . '"';
            $data_field_type            = 'data-field-type="' . $purifier->purify($this->getFormElementFactory()->getType($this)) . '"';
            $data_field_is_autocomputed = 'data-field-is-autocomputed="' . $is_autocomputed . '"';
            $data_field_old_value       = 'data-field-old-value="' . $value . '"';
        }

        $html = '<tr>
                    <td>' . $purifier->purify($this->getLabel()) . ':
                    </td>
                    <td class="autocomputed_override">' .
                        $this->fetchComputedValueWithLabel($computed_value) .
                        '<a href="#" ' . $data_field_id . '><i class="fas fa-redo fa-flip-horizontal"></i>' .
                        dgettext('tuleap-tracker', 'Auto-compute')
                        . '</a>' .
                    '</td>
                    <td class="valueOf_' . $purifier->purify($this->getName()) . '"' .
                        $data_field_id .
                        $data_field_type .
                        $data_field_is_autocomputed .
                        $data_field_old_value .
                    '>' .
                        $value .
                    '</td>
                </tr>';

        return $html;
    }

    public function fetchRawValueFromChangeset($changeset)
    {
    }

    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $row = $this->getValueDao()->searchById($value_id, $this->id)->getRow();

        if ($row && $row['value'] !== null) {
            $is_manual_value = true;

            return new ChangesetValueComputed(
                $value_id,
                $changeset,
                $this,
                $has_changed,
                $row['value'],
                $is_manual_value
            );
        }

        $user  = $this->getCurrentUser();
        $value = $this->getComputedValue($user, $changeset->getArtifact(), $changeset->getSubmittedOn());

        $is_manual_value = false;

        return new ChangesetValueComputed($value_id, $changeset, $this, $has_changed, $value, $is_manual_value);
    }

    private function getTrackerChangesetFactory()
    {
        $factory_builder = new Tracker_Artifact_ChangesetFactoryBuilder();
        return $factory_builder::build();
    }

    /** For testing purpose */
    protected function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        $user = $this->getCurrentUser();
        if (! $this->userCanUpdate($user)) {
            return true;
        }
        $new_value = $this->getStorableValue($value);

        return $this->getValueDao()->create($changeset_value_id, $new_value);
    }

    private function getStorableValue($value)
    {
        $new_value = '';

        if (! is_array($value)) {
            return $this->retrieveValueFromJson($value);
        }

        if (isset($value[self::FIELD_VALUE_MANUAL])) {
            $new_value = $value[self::FIELD_VALUE_MANUAL];
        }

        return $new_value;
    }

    private function retrieveValueFromJson($value)
    {
        $new_value = json_decode($value);

        if (! isset($new_value->manual_value)) {
            return null;
        }
        return $new_value->manual_value;
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $previous_changeset_value, $value)
    {
        if (
            ! $previous_changeset_value->isManualValue() &&
            isset($value[self::FIELD_VALUE_IS_AUTOCOMPUTED]) &&
            $value[self::FIELD_VALUE_IS_AUTOCOMPUTED]
        ) {
            return false;
        }

        $new_value = $this->getStorableValue($value);

        if ($previous_changeset_value->getNumeric() === null && $new_value === '') {
            return false;
        }

        if ($previous_changeset_value->getNumeric() === null && $new_value !== '') {
            return true;
        }

        if ($new_value === '' && $previous_changeset_value->getNumeric() === 0.0) {
            return true;
        }

        return (float) $previous_changeset_value->getNumeric() !== (float) $new_value;
    }

    public function getRESTAvailableValues()
    {
    }

    public function testImport()
    {
        return true;
    }

    protected function validate(Artifact $artifact, $value)
    {
        return $this->validateValue($value);
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitComputed($this);
    }

    /**
     * @return int | null if no value found
     */
    public function getCachedValue(PFUser $user, Artifact $artifact, $timestamp = null)
    {
        $dao   = ComputedFieldDaoCache::instance();
        $value = $dao->getCachedFieldValueAtTimestamp($artifact->getId(), $this->getId(), $timestamp);

        if ($value === false) {
            return null;
        }
        return $value;
    }

    public function canBeUsedAsReportCriterion()
    {
        return false;
    }

    public function canBeUsedToSortReport()
    {
        return false;
    }

    public function validateFieldWithPermissionsAndRequiredStatus(
        Artifact $artifact,
        $submitted_value,
        PFUser $user,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value = null,
        ?bool $is_submission = null,
    ): bool {
        $hasPermission = $this->userCanUpdate();
        if ($is_submission) {
            $hasPermission = $this->userCanSubmit();
        }
        if ($last_changeset_value === null && ( $this->isAnEmptyValue($submitted_value) || $this->isAnEmptyArray($submitted_value)) && $hasPermission && $this->isRequired()) {
            $this->setHasErrors(true);

            $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-tracker', 'The field %1$s is required.'), $this->getLabel() . ' (' . $this->getName() . ')'));
            return false;
        } elseif ($hasPermission) {
            if (
                ! isset($submitted_value[self::FIELD_VALUE_IS_AUTOCOMPUTED])
                && ! isset($submitted_value[self::FIELD_VALUE_MANUAL])
            ) {
                return true;
            }
            if (
                ! isset($submitted_value[self::FIELD_VALUE_IS_AUTOCOMPUTED])
                || ! $submitted_value[self::FIELD_VALUE_IS_AUTOCOMPUTED]
            ) {
                return $this->isValidRegardingRequiredProperty($artifact, $submitted_value)
                    && $this->validateField($artifact, $submitted_value);
            }
        }

        return true;
    }

    public function isValidRegardingRequiredProperty(Artifact $artifact, $submitted_value)
    {
        if ($this->isAnEmptyArray($submitted_value)) {
            $this->addRequiredError();
            return false;
        }

        return true;
    }

    private function isAnEmptyArray($value)
    {
        return is_array($value) && empty($value);
    }

    private function isAnEmptyValue($value)
    {
        return ! is_array($value) && $value === null;
    }
}
