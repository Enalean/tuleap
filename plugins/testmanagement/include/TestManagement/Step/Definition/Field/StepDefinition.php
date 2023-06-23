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

namespace Tuleap\TestManagement\Step\Definition\Field;

use Codendi_HTMLPurifier;
use EventManager;
use LogicException;
use Luracast\Restler\RestException;
use PFUser;
use ReferenceManager;
use TemplateRendererFactory;
use Tracker_Artifact_ChangesetValue;
use Tracker_Artifact_ChangesetValue_Text;
use Tracker_FormElement_Field;
use Tracker_FormElement_FieldVisitor;
use Tracker_FormElementFactory;
use Tracker_Report_Criteria;
use Tuleap\Option\Option;
use Tuleap\Search\ItemToIndex;
use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Search\ItemToIndexQueueEventBased;
use Tuleap\TestManagement\Step\Definition\Field\XML\XMLStepDefinition;
use Tuleap\TestManagement\Step\Step;
use Tuleap\TestManagement\Step\StepChecker;
use Tuleap\TestManagement\Step\StepPresenter;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Artifact\FileUploadDataProvider;
use Tuleap\Tracker\Artifact\UploadDataAttributesForRichTextEditorBuilder;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\File\FileURLSubstitutor;
use Tuleap\Tracker\FormElement\FieldContentIndexer;
use Tuleap\Tracker\FormElement\TrackerFormElementExternalField;
use Tuleap\Tracker\FormElement\XML\XMLFormElement;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;

class StepDefinition extends Tracker_FormElement_Field implements TrackerFormElementExternalField
{
    public const START_RANK = 1;
    public const TYPE       = 'ttmstepdef';

    /**
     * @return mixed
     */
    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitExternalField($this);
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-testmanagement', 'Step definition');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-testmanagement', 'Definition of a step');
    }

    public static function getFactoryIconUseIt()
    {
        return TESTMANAGEMENT_BASE_URL . '/images/ic/tick-white.png';
    }

    public static function getFactoryIconCreate()
    {
        return TESTMANAGEMENT_BASE_URL . '/images/ic/tick-white--plus.png';
    }

    public static function getFactoryUniqueField()
    {
        return true;
    }

    protected function fetchAdminFormElement()
    {
        return '<textarea></textarea>';
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
    public function fetchCriteriaValue($criteria): string
    {
        return '';
    }

    /**
     * @param mixed $value
     */
    public function fetchRawValue($value): string
    {
        return '';
    }

    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedFromWhere::class);
    }

    public function getQueryFrom(): string
    {
        return '';
    }

    public function getQuerySelect(): string
    {
        return '';
    }

    /**
     * @return null
     */
    protected function getCriteriaDao()
    {
        return null;
    }

    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
        array $submitted_values = [],
    ): string {
        $steps = $this->getStepsPresentersFromSubmittedValues($submitted_values);
        if (empty($steps)) {
            $steps = $this->getStepsPresentersFromChangesetValue($value);
        }

        return $this->renderStepEditionToString($artifact, $steps);
    }

    private function getDefaultFormat(PFUser $user): string
    {
        $user_preference = $user->getPreference(PFUser::EDITION_DEFAULT_FORMAT);

        if ($user_preference === Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT) {
            return Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
        }

        if (! $user_preference || $user_preference === Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT) {
            return Tracker_Artifact_ChangesetValue_Text::COMMONMARK_CONTENT;
        }

        return Tracker_Artifact_ChangesetValue_Text::HTML_CONTENT;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ): string {
        return $this->fetchArtifactValueReadOnly($artifact, $value) .
            $this->getHiddenArtifactValueForEdition($artifact, $value, $submitted_values);
    }

    public function fetchArtifactValueReadOnly(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
    ): string {
        $renderer = TemplateRendererFactory::build()->getRenderer(TESTMANAGEMENT_BASE_DIR . '/templates');

        $purifier       = Codendi_HTMLPurifier::instance();
        $no_value_label = $this->getNoValueLabel();

        return $renderer->renderToString(
            'step-def-readonly',
            [
                'steps' => $this->getStepsPresentersFromChangesetValue($value),
                'purified_no_value_label' => $purifier->purify($no_value_label, CODENDI_PURIFIER_FULL),
            ]
        );
    }

    protected function fetchSubmitValue(array $submitted_values): string
    {
        $submitted_values = $submitted_values ?: [];

        $steps = $this->getStepsPresentersFromSubmittedValues($submitted_values);

        return $this->renderStepEditionToString(null, $steps);
    }

    protected function fetchSubmitValueMasschange(): string
    {
        return '';
    }

    protected function fetchTooltipValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value = null,
    ): string {
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
     * @return StepDefinitionChangesetValueDao
     */
    protected function getValueDao()
    {
        return new StepDefinitionChangesetValueDao();
    }

    public function fetchRawValueFromChangeset($changeset): string
    {
        return '';
    }

    /**
     * @throws RestException
     */
    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        if (! isset($value['value'])) {
            return null;
        }
        if (! is_array($value['value'])) {
            throw new RestException(400, sprintf('The value of step definition is expected to be an array, got %s', gettype($value)));
        }
        if ($this->areWeTryingToUpdateAnExistingStepDefinition($artifact)) {
            return StepDefinitionDataConverter::convertStepDefinitionFromRESTUpdateFormatToDBCompatibleFormat($value['value']);
        }
        return StepDefinitionDataConverter::convertStepDefinitionFromRESTPostFormatToDBCompatibleFormat($value["value"]);
    }

    /**
     * @throws RestException
     */
    public function getFieldDataFromRESTValueByField(array $value, ?Artifact $artifact = null)
    {
        return $this->getFieldDataFromRESTValue($value, $artifact);
    }

    private function areWeTryingToUpdateAnExistingStepDefinition(?Artifact $artifact): bool
    {
        return isset($artifact);
    }

    protected function validate(Artifact $artifact, $value)
    {
        if ($this->doesUserWantToRemoveAllSteps($value)) {
            return true;
        }

        $rule = new \Rule_String();
        foreach ($value['description'] as $key => $submitted_step_description) {
            if (! isset($value['expected_results'][$key])) {
                return false;
            }

            if (! $rule->isValid($submitted_step_description) || ! $rule->isValid($value['expected_results'][$key])) {
                $GLOBALS['Response']->addFeedback(
                    \Feedback::ERROR,
                    sprintf(dgettext('tuleap-tracker', '%1$s is not a text.'), $this->getLabel())
                );

                return false;
            }

            if (! isset($value['description_format'][$key]) || ! isset($value['expected_results_format'][$key])) {
                return false;
            }

            if (
                ! StepChecker::isSubmittedFormatValid($value['description_format'][$key])
                || ! StepChecker::isSubmittedFormatValid($value['expected_results_format'][$key])
            ) {
                return false;
            }
        }

        return true;
    }

    private function doesUserWantToRemoveAllSteps(array $value): bool
    {
        return isset($value['no_steps']) && $value['no_steps'];
    }

    public function hasChanges(
        Artifact $artifact,
        Tracker_Artifact_ChangesetValue $old_value,
        $new_value,
    ) {
        if ($new_value === null) {
            return false;
        }

        assert(is_array($new_value));

        $existing_steps = $old_value->getValue();
        if ($this->doesUserWantToRemoveAllSteps($new_value)) {
            return ! empty($existing_steps);
        }

        $submitted_steps = [];
        $rank            = self::START_RANK;
        foreach ($new_value['description'] as $key => $submitted_step_description) {
            $submitted_description_format = Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
            if (isset($new_value['description_format'][$key])) {
                $submitted_description_format = $new_value['description_format'][$key];
            }
            $submitted_expected_results = '';
            if (isset($new_value['expected_results'][$key])) {
                $submitted_expected_results = $new_value['expected_results'][$key];
            }
            $submitted_expected_results_format = Tracker_Artifact_ChangesetValue_Text::TEXT_CONTENT;
            if (isset($new_value['expected_results_format'][$key])) {
                $submitted_expected_results_format = $new_value['expected_results_format'][$key];
            }
            $submitted_step_id = 0;
            if (isset($new_value['id'][$key])) {
                $submitted_step_id = $new_value['id'][$key];
            }

            $submitted_steps[] = new Step(
                $submitted_step_id,
                $submitted_step_description,
                $submitted_description_format,
                $submitted_expected_results,
                $submitted_expected_results_format,
                $rank
            );
            $rank++;
        }

        return array_diff($submitted_steps, $existing_steps) !== [] ||
            array_diff($existing_steps, $submitted_steps) !== [];
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        $steps = $this->transformSubmittedValuesIntoArrayOfStructuredSteps($value, $url_mapping);

        $res = $this->getValueDao()->create($changeset_value_id, $steps) &&
            $this->extractCrossRefs($artifact, $value);

        if ($res) {
            $this->addRawValueToSearchIndex(new ItemToIndexQueueEventBased(EventManager::instance()), $artifact, $steps);
        }

        return $res;
    }

    public function addChangesetValueToSearchIndex(ItemToIndexQueue $index_queue, Tracker_Artifact_ChangesetValue $changeset_value): void
    {
        assert($changeset_value instanceof StepDefinitionChangesetValue);
        $this->addRawValueToSearchIndex(
            $index_queue,
            $changeset_value->getChangeset()->getArtifact(),
            $changeset_value->getValue(),
        );
    }

    /**
     * @param Step[] $steps
     */
    private function addRawValueToSearchIndex(ItemToIndexQueue $index_queue, Artifact $artifact, array $steps): void
    {
        $content_to_index = '';

        foreach ($steps as $step) {
            $content_to_index .= Tracker_Artifact_ChangesetValue_Text::getContentHasTextFromRawInfo(
                $step->getDescription(),
                $step->getDescriptionFormat(),
            ) . "\n";
            $content_to_index .= Tracker_Artifact_ChangesetValue_Text::getContentHasTextFromRawInfo(
                $step->getExpectedResults() ?? '',
                $step->getExpectedResultsFormat(),
            ) . "\n";
        }

        $event_dispatcher = EventManager::instance();
        (new FieldContentIndexer($index_queue, $event_dispatcher))->indexFieldContent(
            $artifact,
            $this,
            $content_to_index,
            ItemToIndex::CONTENT_TYPE_PLAINTEXT,
        );
    }

    /**
     * @return Step[]
     *
     * @psalm-return list<Step>
     */
    private function transformSubmittedValuesIntoArrayOfStructuredSteps(
        array $submitted_values,
        CreatedFileURLMapping $url_mapping,
    ): array {
        return (new StepDefinitionSubmittedValuesTransformator(new FileURLSubstitutor()))
            ->transformSubmittedValuesIntoArrayOfStructuredSteps($submitted_values, $url_mapping);
    }

    private function extractCrossRefs(Artifact $artifact, array $submitted_steps): bool
    {
        if (! isset($submitted_steps['description']) && ! isset($submitted_steps['expected_results'])) {
            return true;
        }

        $concatenated_descriptions     = implode(PHP_EOL, $submitted_steps['description']);
        $concatenated_expected_results = implode(PHP_EOL, $submitted_steps['expected_results']);

        $text = $concatenated_descriptions . PHP_EOL . $concatenated_expected_results;

        $tracker = $this->getTracker();
        if (! $tracker) {
            return true;
        }

        return ReferenceManager::instance()->extractCrossRef(
            $text,
            $artifact->getId(),
            Artifact::REFERENCE_NATURE,
            $tracker->getGroupID(),
            $this->getCurrentUser()->getId(),
            $tracker->getItemName()
        );
    }

    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $steps = [];
        $rank  = self::START_RANK;
        foreach ($this->getValueDao()->searchById($value_id) as $row) {
            $steps[] = new Step(
                $row['id'],
                $row['description'],
                $row['description_format'],
                $row['expected_results'],
                $row['expected_results_format'],
                $rank
            );
            $rank++;
        }

        return new StepDefinitionChangesetValue($value_id, $changeset, $this, $has_changed, $steps);
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

    public function isCSVImportable(): bool
    {
        return false;
    }

    /**
     * @return ViewAdmin
     */
    public function getFormAdminVisitor(Tracker_FormElement_Field $element, array $used_element)
    {
        return new ViewAdmin($element, $used_element);
    }

    /**
     * @return StepPresenter[]
     */
    private function getStepsPresentersFromChangesetValue(?Tracker_Artifact_ChangesetValue $value = null)
    {
        $steps = [];
        if ($value) {
            $steps = $value->getValue();
        }

        return array_filter(array_map(
            function (Step $step) {
                return $this->getStepPresenter($step);
            },
            $steps
        ));
    }

    private function getStepPresenter(Step $step): ?StepPresenter
    {
        $tracker = $this->getTracker();
        if (! $tracker) {
            return null;
        }

        return new StepPresenter($step, $tracker->getProject());
    }

    private function getEmptyStepPresenter(): ?StepPresenter
    {
        $default_format = $this->getDefaultFormat($this->getCurrentUser());
        $empty_step     = new Step(0, '', $default_format, '', $default_format, 0);

        return $this->getStepPresenter($empty_step);
    }

    /**
     * @param StepPresenter[] $steps_presenters
     *
     * @return String
     */
    protected function renderStepEditionToString(?Artifact $artifact, array $steps_presenters)
    {
        $tracker = $this->getTracker();
        if (! $tracker) {
            throw new LogicException(self::class . ' # ' . $this->getId() . ' must have a valid tracker');
        }

        $renderer = TemplateRendererFactory::build()->getRenderer(TESTMANAGEMENT_BASE_DIR . '/templates');

        $empty_step_presenter = $this->getEmptyStepPresenter();

        $rich_textarea_provider = new UploadDataAttributesForRichTextEditorBuilder(
            new FileUploadDataProvider(
                $this->getFrozenFieldDetector(),
                Tracker_FormElementFactory::instance()
            )
        );

        $data_attributes = array_merge(
            [
                [
                    'name' => 'field-id',
                    'value' => $this->id,
                ],
                [
                    'name' => 'steps',
                    'value' => json_encode($steps_presenters),
                ],

                [
                    'name' => 'empty-step',
                    'value' => json_encode($empty_step_presenter),
                ],
                [
                    'name' => 'project-id',
                    'value' => (int) $tracker->getGroupId(),
                ],
            ],
            $rich_textarea_provider->getDataAttributes($tracker, $this->getCurrentUser(), $artifact)
        );

        return $renderer->renderToString(
            'step-def-edit',
            [
                'data_attributes' => $data_attributes,
            ]
        );
    }

    /**
     * @param array $submitted_values
     *
     * @return StepPresenter[]
     */
    private function getStepsPresentersFromSubmittedValues(array $submitted_values)
    {
        $steps = [];

        $submitted_steps = $this->getValueFromSubmitOrDefault($submitted_values);
        if ($submitted_steps) {
            $url_mapping = new CreatedFileURLMapping();
            $steps       = array_filter(array_map(
                function (Step $step) {
                    return $this->getStepPresenter($step);
                },
                $this->transformSubmittedValuesIntoArrayOfStructuredSteps(
                    $submitted_steps,
                    $url_mapping
                )
            ));
        }

        return $steps;
    }

    protected function getXMLInternalRepresentation(): XMLFormElement
    {
        return new XMLStepDefinition(
            $this->getXMLId(),
            $this->getName(),
        );
    }
}
