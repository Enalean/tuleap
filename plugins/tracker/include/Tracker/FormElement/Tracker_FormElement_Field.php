<?php
/**
 * Copyright (c) Enalean, 2015-Present. All Rights Reserved.
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
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

use Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface;
use Tuleap\Option\Option;
use Tuleap\Search\ItemToIndexQueue;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\XMLCriteriaValueCache;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use Tuleap\Tracker\Rule\TrackerRulesDateValidator;
use Tuleap\Tracker\Rule\TrackerRulesListValidator;
use Tuleap\Tracker\Semantic\CollectionOfSemanticsUsingAParticularTrackerField;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;

/**
 * The base class for fields in trackers. From int and string to selectboxes.
 * Composite fields are excluded.
 */

// phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
abstract class Tracker_FormElement_Field extends Tracker_FormElement implements Tracker_Report_Field, Tracker_FormElement_IAcceptFieldVisitor
{
    private const PREFIX_NAME_SQL_COLUMN = 'user_defined_';

    protected $has_errors = false;

    /**
     * Display the field value as a criteria
     * @return string
     * @see fetchCriteria
     */
    abstract public function fetchCriteriaValue($criteria);

    /**
     * Display the field as a Changeset value.
     * Used in CSV data export.
     *
     * Please override this method for specific field (if needed)
     *
     * @param int $artifact_id the corresponding artifact id
     * @param int $changeset_id the corresponding changeset
     * @param mixed $value the value of the field
     *
     * @return string the value of the field for artifact_id and changeset_id, formatted for CSV
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        return $this->fetchChangesetValue($artifact_id, $changeset_id, $value, $report);
    }

    public function isCSVImportable(): bool
    {
        return true;
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    abstract public function fetchRawValue($value);

    /**
     * Get the "from" statement to allow search with this field
     * You can join on 'c' which is a pseudo table used to retrieve
     * the last changeset of all artifacts.
     *
     * @return Option<ParametrizedFrom>
     */
    abstract public function getCriteriaFrom(Tracker_Report_Criteria $criteria): Option;

    /**
     * Get the "where" statement to allow search with this field
     *
     *
     * @return Option<ParametrizedSQLFragment>
     * @see getCriteriaFrom
     */
    abstract public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option;

    /**
     * Return the dao of the criteria value used with this field.
     * @return Tracker_Report_Criteria_ValueDao|null
     */
    abstract protected function getCriteriaDao();

    protected $criteria_value;
    /**
     * Search in the db the criteria value used to search against this field.
     * @param Tracker_Report_Criteria $criteria
     * @return mixed
     */
    public function getCriteriaValue($criteria)
    {
        if (! isset($this->criteria_value)) {
            $this->criteria_value = [];
        }

        if (! isset($this->criteria_value[$criteria->getReport()->getId()])) {
            $this->criteria_value[$criteria->getReport()->getId()] = null;
            $dao                                                   = $this->getCriteriaDao();
            if ($dao && $v = $dao->searchByCriteriaId($criteria->id)->getRow()) {
                $this->criteria_value[$criteria->getReport()->getId()] = $v['value'];
            }
        }
        return $this->criteria_value[$criteria->getReport()->getId()];
    }

    public function setCriteriaValue($criteria_value, $report_id)
    {
        $this->criteria_value[$report_id] = $criteria_value;
    }

    /**
     * @throws Tracker_Report_InvalidRESTCriterionException
     */
    public function setCriteriaValueFromREST(Tracker_Report_Criteria $criteria, array $rest_criteria_value)
    {
        $value    = $rest_criteria_value[Tracker_Report_REST::VALUE_PROPERTY_NAME];
        $operator = $rest_criteria_value[Tracker_Report_REST::OPERATOR_PROPERTY_NAME];

        if ($operator !== Tracker_Report_REST::OPERATOR_CONTAINS) {
            throw new Tracker_Report_InvalidRESTCriterionException("Unallowed operator for criterion field '$this->name' ($this->id). Allowed operators: [" . Tracker_Report_REST::OPERATOR_CONTAINS . "]");
        }

        if (! is_string($value) && ! is_numeric($value)) {
            throw new Tracker_Report_InvalidRESTCriterionException('Invalid value for field "' . $this->name . '"');
        }

        $this->setCriteriaValue($value, $criteria->report->id);
        return true;
    }

    /**
     * Format the criteria value submitted by the user for storage purpose (dao or session)
     *
     * @param mixed $value The criteria value submitted by the user
     *
     * @return mixed
     */
    public function getFormattedCriteriaValue($value)
    {
        return $value;
    }

    public function exportCriteriaValueToXML(Tracker_Report_Criteria $criteria, SimpleXMLElement $xml_criteria)
    {
        $criteria_value = $this->getCriteriaValue($criteria);
        if ((string) $criteria_value !== '') {
            $cdata_factory = new XML_SimpleXMLCDATAFactory();
            $cdata_factory->insertWithAttributes(
                $xml_criteria,
                'criteria_value',
                (string) $criteria_value,
                ['type' => 'text']
            );
        }
    }

    public function setCriteriaValueFromXML(
        Tracker_Report_Criteria $criteria,
        SimpleXMLElement $xml_criteria_value,
        array $xml_field_mapping,
    ) {
        if ((string) $xml_criteria_value['type'] !== 'text') {
            return;
        }
        $string_value = (string) $xml_criteria_value;

        $cache = XMLCriteriaValueCache::instance(spl_object_id($this));
        $cache->set($criteria->getReport()->getId(), $string_value);
    }

    public function saveCriteriaValueFromXML(Tracker_Report_Criteria $criteria)
    {
        $report_id = $criteria->getReport()->getId();
        $cache     = XMLCriteriaValueCache::instance(spl_object_id($this));

        if (! $cache->has($report_id)) {
            return;
        }

        $value = $cache->get($criteria->getReport()->getId());
        $this->updateCriteriaValue($criteria, $value);
    }

    final public function getQuerySelectName(): string
    {
        return \Tuleap\DB\DBFactory::getMainTuleapDBConnection()->getDB()->escapeIdentifier(
            $this->getPrefixedName()
        );
    }

    final public function getPrefixedName(): string
    {
        return self::PREFIX_NAME_SQL_COLUMN . '_' . $this->getTrackerId() . '_' . $this->getId();
    }

    /**
     * Get the "select" statement to retrieve field values
     * @see getQueryFrom
     */
    public function getQuerySelect(): string
    {
        $R = 'R_' . $this->id;
        return "$R.value_id AS " . $this->getQuerySelectName();
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFrom()
    {
        $R = 'R_' . $this->id;
        return "INNER JOIN tracker_changeset_value AS $R ON ($R.changeset_id = c.id)";
    }

    /**
     * Get the "order by" statement to retrieve field values
     */
    public function getQueryOrderby(): string
    {
        return $this->getQuerySelectName();
    }

    /**
     * Get the "group by" statement to retrieve field values
     */
    public function getQueryGroupby(): string
    {
        if (! $this->isUsed()) {
            return '';
        }
        $R = 'R_' . $this->id;
        return "$R.value_id";
    }

    public function fetchCriteria(Tracker_Report_Criteria $criteria)
    {
        return $this->buildReportCriteria($criteria, $this->criteriaCanBeAdvanced());
    }

    public function fetchCriteriaWithoutExpandFunctionnality(Tracker_Report_Criteria $criteria)
    {
        return $this->buildReportCriteria($criteria, false);
    }

    private function buildReportCriteria(Tracker_Report_Criteria $criteria, $advanced_criteria)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        if ($advanced_criteria) {
            $html .= '<table cellpadding="0" cellspacing="0"><tbody><tr><td>';
            $html .= $GLOBALS['HTML']->getImage(
                'ic/toggle_' . ($criteria->is_advanced ? 'minus' : 'plus' ) . '.png',
                ['class' => 'tracker_report_criteria_advanced_toggle']
            );
            $html .= '</td><td>';
        }
        $html .= '<label for="tracker_report_criteria_' . $purifier->purify($this->id) . '" title="#' .
            $purifier->purify($this->id) . '">' . $purifier->purify($this->getLabel());
        $html .= '<input type="hidden" id="tracker_report_criteria_' . $purifier->purify($this->id) .
            '_parent" value="' . $purifier->purify($this->parent_id) . '" />';
        $html .= '</label>';

        if ($advanced_criteria) {
            $html .=  '<div class="tracker_report_criteria">';
        }
        $html .= $this->fetchCriteriaValue($criteria);
        if ($advanced_criteria) {
            $html .= '</div></td></tr></tbody></table>';
        }
        return $html;
    }

    /**
     * Return the fieldset of this field
     * @return Tracker_FormElement_Field|null
     */
    public function getParent()
    {
        return Tracker_FormElementFactory::instance()->getFieldById($this->parent_id);
    }

    /**
     * Add some additionnal information beside the field in the artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @param $value the changeset value
     *
     * @return string
     */
    public function fetchArtifactAdditionnalInfo(?Tracker_Artifact_ChangesetValue $value, array $submitted_values)
    {
        return '';
    }

    /**
     * Add some additionnal information beside the field in the submit new artifact form.
     * This is up to the field. It can be html or inline javascript
     * to enhance the user experience
     * @return string
     */
    public function fetchSubmitAdditionnalInfo(array $submitted_values)
    {
        return '';
    }

    public function deleteChangesetValue(Tracker_Artifact_Changeset $changeset, $changeset_value_id)
    {
        return $this->getValueDao()->delete($changeset_value_id);
    }

    /**
     * Delete the criteria value
     * @param Criteria $criteria the corresponding criteria
     */
    public function deleteCriteriaValue($criteria)
    {
        $this->getCriteriaDao()->delete($criteria->report->id, $criteria->id);
        return $this;
    }

    /**
     * Update the criteria value
     * @param Tracker_Report_Criteria $criteria
     * @param mixed $value
     */
    public function updateCriteriaValue($criteria, $value)
    {
        $dao = $this->getCriteriaDao();
        if ($dao === null) {
            return;
        }
        $dao->save($criteria->id, $value);
    }

    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced()
    {
        return false;
    }

    /**
     * Fetch sql snippets needed to compute aggregate functions on this field.
     *
     * @param array $functions The needed function. @see getAggregateFunctions
     *
     * @return array of the form array('same_query' => string(sql snippets), 'separate' => array(sql snippets))
     *               example:
     *               array(
     *                   'same_query'       => "AVG(R2_1234.value) AS velocity_AVG, STD(R2_1234.value) AS velocity_AVG",
     *                   'separate_queries' => array(
     *                       array(
     *                           'function' => 'COUNT_GRBY',
     *                           'select'   => "R2_1234.value AS label, count(*) AS value",
     *                           'group_by' => "R2_1234.value",
     *                       ),
     *                       //...
     *                   )
     *              )
     *
     *              Same query handle all queries that can be run concurrently in one query. Example:
     *               - numeric: avg, count, min, max, std, sum
     *               - selectbox: count
     *              Separate queries handle all queries that must be run spearately on their own. Example:
     *               - numeric: count group by
     *               - selectbox: count group by
     *               - multiselectbox: all (else it breaks other computations)
     */
    public function getQuerySelectAggregate($functions)
    {
        return false;
    }

    public function getQueryFromAggregate()
    {
        return $this->getQueryFrom();
    }

    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions()
    {
        return [];
    }

    /**
     * Get the html code to display the field for the given artifact
     *
     *
     * @return string html
     */
    public function fetchArtifact(
        Artifact $artifact,
        array $submitted_values,
        array $additional_classes,
    ) {
        $is_field_read_only = $this->getFrozenFieldDetector()->isFieldFrozen($artifact, $this);
        if (! $is_field_read_only && $this->userCanUpdate()) {
            $last_changeset = $artifact->getLastChangeset();
            if ($last_changeset) {
                $value       = $last_changeset->getValue($this);
                $html_value  = $this->fetchArtifactValue($artifact, $value, $submitted_values);
                $html_value .= $this->fetchArtifactAdditionnalInfo($value, $submitted_values);
                return $this->fetchArtifactField($artifact, $html_value, $additional_classes);
            }
            return '';
        }
        return $this->fetchArtifactReadOnly($artifact, $submitted_values);
    }

    public function fetchArtifactForOverlay(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchArtifact($artifact, $submitted_values, ['field-in-modal']);
    }

    public function fetchSubmitForOverlay(array $submitted_values)
    {
        return $this->fetchSubmit($submitted_values);
    }

    /**
     * Get the html code to display the field for the given artifact in read only mode
     *
     *
     * @return string html
     */
    public function fetchArtifactReadOnly(Artifact $artifact, array $submitted_values)
    {
        $last_changeset = $artifact->getLastChangeset();
        if ($last_changeset) {
            $value       = $last_changeset->getValue($this);
            $html_value  = $this->fetchArtifactValueForWebDisplay($artifact, $value, $submitted_values);
            $html_value .= $this->fetchArtifactAdditionnalInfo($value, $submitted_values);
            return $this->fetchArtifactField($artifact, $html_value, []);
        }
        return '';
    }

    /**
     * @see Tracker_FormElement::fetchArtifactCopyMode
     */
    public function fetchArtifactCopyMode(Artifact $artifact, array $submitted_values)
    {
        return $this->fetchArtifactReadOnly($artifact, $submitted_values);
    }

    /**
     * @param string           $html_value in html
     *
     * @return string html
     */
    private function fetchArtifactField(Artifact $artifact, $html_value, array $additional_classes)
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        if ($this->userCanRead()) {
            $is_field_read_only = $this->getFrozenFieldDetector()->isFieldFrozen($artifact, $this);
            $required           = $this->required ? ' <span class="highlight">*</span>' : '';
            $html              .= '<div class="' . $this->getClassNames($additional_classes, $is_field_read_only) . '"
                data-field-id="' . $this->id . '"
                data-test="tracker-artifact-value-' . $this->getName() . '"
                data-is-required="' . ($this->required ? 'true' : 'false') . '">';

            if (! $is_field_read_only && $this->userCanUpdate()) {
                $title = $purifier->purify(sprintf(dgettext('tuleap-tracker', 'Edit the field "%1$s"'), $this->getLabel()));
                $html .= '<button type="button" title="' . $title . '"
                                class="tracker_formelement_edit"
                                data-test="edit-field-' . $this->getName() . '">' .
                    $purifier->purify($this->getLabel()) . $required .
                    '</button>';
            }

            $html .= '<label id="tracker_artifact_' . $this->id . '" for="tracker_artifact_' . $this->id . '" title="' . $purifier->purify($this->description) . '" class="tracker_formelement_label">' .  $purifier->purify($this->getLabel())  . $required . '</label>';

            $html .= $html_value;
            $html .= '</div>';
        }
        return $html;
    }

    /**
     *
     * @return string
     */
    public function fetchMailArtifact($recipient, Artifact $artifact, $format = 'text', $ignore_perms = false)
    {
        if (! $ignore_perms && ! $this->userCanRead($recipient)) {
            return '';
        }

        $value                = $artifact->getLastChangeset()->getValue($this);
        $mail_formatted_value = $this->fetchMailArtifactValue($artifact, $recipient, $ignore_perms, $value, $format);

        if ($format == 'text') {
            $output = ' * ' . $this->getLabel() . ' : ' . $mail_formatted_value;
        } else {
            $hp     = Codendi_HTMLPurifier::instance();
            $output = '<tr>
                <td valign="top" align="left" >
                    <label id = "tracker_artifact_' . $this->id . '"
                        for = "tracker_artifact_' . $this->id . '"
                        title = "' . $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) . '"
                        class = "tracker_formelement_label"
                    >
                        <b>' .
                            $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '
                        </b>
                    </label>
                </td>
                <td align = "left">' .
                    $mail_formatted_value . '
                </td>
            </tr>';
        }
        return $output;
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit(array $submitted_values)
    {
        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($this->userCanSubmit()) {
            $required = $this->required ? ' <span class="highlight">*</span>' : '';
            $html    .= '<div class="' . $this->getClassNamesForSubmit() . '"
                data-field-id="' . $this->id . '"
                data-is-required="' . ($this->required ? 'true' : 'false') . '">';
            $html    .= '<label for="tracker_artifact_' . $this->id . '" title="' . $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) . '"  class="tracker_formelement_label">' .  $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML)  . $required . '</label>';

            $html .= $this->fetchSubmitValue($submitted_values);
            $html .= $this->fetchSubmitAdditionnalInfo($submitted_values);
            $html .= '</div>';
        }
        return $html;
    }

    protected function getTargetFieldsIds(): array
    {
        $tracker = $this->getTracker();
        if ($tracker === null) {
            return [];
        }

        $tracker_formelement_factory = Tracker_FormElementFactory::instance();
        $tracker_rules_manager       = new Tracker_RulesManager(
            $tracker,
            $tracker_formelement_factory,
            new FrozenFieldsDao(),
            new TrackerRulesListValidator($tracker_formelement_factory),
            new TrackerRulesDateValidator($tracker_formelement_factory),
            TrackerFactory::instance()
        );
        return $tracker_rules_manager->getFieldTargets($this);
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmitMasschange()
    {
        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($this->userCanUpdate()) {
            $required = $this->required ? ' <span class="highlight">*</span>' : '';
            $html    .= '<div class="field-masschange ' . $this->getClassNames([], false) . '">';
            $html    .= '<label for="tracker_artifact_' . $this->id . '" title="' . $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) . '"  class="tracker_formelement_label">' .  $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML)  . $required . '</label>';

            $html .= $this->fetchSubmitValueMasschange();
            $html .= $this->fetchSubmitAdditionnalInfo([]);
            $html .= '</div>';
        }
        return $html;
    }

    private function getClassNames(array $additional_classes, bool $is_field_read_only)
    {
        $classnames  = 'tracker_artifact_field';
        $classnames .= ' tracker_artifact_field-' . $this->getFormElementFactory()->getType($this);
        if ($this->has_errors) {
            $classnames .= ' has_errors';
        }
        if (! $is_field_read_only && $this->userCanUpdate()) {
            $classnames .= ' editable';
        }

        foreach ($additional_classes as $additional_class) {
            $classnames .= " $additional_class";
        }

        return $classnames;
    }

    private function getClassNamesForSubmit()
    {
        $classnames  = 'tracker_artifact_field';
        $classnames .= ' tracker_artifact_field-' . $this->getFormElementFactory()->getType($this);
        if ($this->has_errors) {
            $classnames .= ' has_errors';
        }

        return $classnames;
    }

    /**
     * Get the html code to display the field in a tooltip
     *
     * @param Artifact $artifact
     *
     * @return string html
     */
    public function fetchTooltip($artifact)
    {
        $hp   = Codendi_HTMLPurifier::instance();
        $html = '';
        if ($this->userCanRead()) {
            $html .= '<tr><td>';
            $html .= '<label>' .  $hp->purify($this->getLabel(), CODENDI_PURIFIER_CONVERT_HTML) . '</label>';
            $html .= '</td><td>';
            $value = $artifact->getLastChangeset()->getValue($this);
            $html .= $this->fetchTooltipValue($artifact, $value);
            $html .= '</td></tr>';
        }
        return $html;
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @return string
     */
    abstract protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    );

    /**
     * Fetch the html code to display the field value in artifact in read only
     *
     * @param Artifact                        $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    abstract public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null);

    /**
     * Fetch the HMTL code to display the field in the web browser
     *
     * @return string
     */
    public function fetchArtifactValueForWebDisplay(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        $is_field_read_only = $this->getFrozenFieldDetector()->isFieldFrozen($artifact, $this);
        if (! $is_field_read_only && $this->userCanUpdate()) {
            return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value, $submitted_values);
        }

        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    protected function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    protected function getNoValueLabel()
    {
        return "<span class='empty_value'>" . dgettext('tuleap-tracker', 'Empty') . "</span>";
    }

    protected function getHiddenArtifactValueForEdition(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return '<div class="tracker_hidden_edition_field" data-field-id="' .
            $this->getId() . '">' .
            $this->fetchArtifactValue($artifact, $value, $submitted_values) .
            '</div>';
    }

    /**
     * Fetch the html code to display the field value in new artifact submission form
     *
     * @return string html
     */
    abstract protected function fetchSubmitValue(array $submitted_values);

    /**
     * Return a value from user submitted request (if any) or from default value (if any)
     *
     * @return mixed
     */
    protected function getValueFromSubmitOrDefault(array $submitted_values)
    {
        $value = '';
        if (isset($submitted_values[$this->getId()])) {
            $value = $submitted_values[$this->getId()];
        } elseif ($this->hasDefaultValue()) {
            $value = $this->getDefaultValue();
        }
        return $value;
    }

    /**
     * Fetch the html code to display the field value in masschange submission form
     *
     * @return string html
     */
    abstract protected function fetchSubmitValueMasschange();

    /**
     * Fetch the html code to display the field value in tooltip
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of the field
     * @return string
     */
    abstract protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null);

    /**
     * Fetch the html code to display the field value in card
     *
     *
     * @return string
     */
    public function fetchCardValue(Artifact $artifact, ?Tracker_CardDisplayPreferences $display_preferences = null)
    {
        return $this->fetchTooltipValue($artifact, $artifact->getLastChangeset()->getValue($this));
    }

    /**
     * Fetch the html code to display the field in card
     *
     *
     * @return string
     */
    public function fetchCard(Artifact $artifact, Tracker_CardDisplayPreferences $display_preferences)
    {
        $value           = $this->fetchCardValue($artifact, $display_preferences);
        $data_field_id   = '';
        $data_field_type = '';

        $purifier = Codendi_HTMLPurifier::instance();

        $is_field_frozen = $this->getFrozenFieldDetector()->isFieldFrozen($artifact, $this);
        if ($this->userCanUpdate() && ! $is_field_frozen) {
            $data_field_id   = 'data-field-id="' . $purifier->purify($this->getId()) . '"';
            $data_field_type = 'data-field-type="' . $purifier->purify($this->getFormElementFactory()->getType($this)) . '"';
        }

        $html = '<tr>
                    <td>' . $purifier->purify($this->getLabel()) . ':
                    </td>
                    <td class="valueOf_' . $purifier->purify($this->getName()) . '"' .
                        $data_field_id .
                        $data_field_type .
                        '>' .
                        $value .
                    '</td>
                </tr>';

        return $html;
    }

    /**
     * Get the value corresponding to the $value_id
     * @param int $value_id
     * @return array
     */
    public function getValue($value_id)
    {
        return $this->getValueDao()->searchById($value_id, $this->id)->getRow();
    }

    abstract protected function getValueDao();

    /**
     * Returns null because a Field object is not of the type FieldComposite
     *
     * @return null
     */
    public function getFields()
    {
        return null;
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    abstract public function fetchRawValueFromChangeset($changeset);

    public function fetchAdmin($tracker)
    {
        $hp       = Codendi_HTMLPurifier::instance();
        $html     = '';
        $required = $this->required ? ' <span class="highlight">*</span>' : '';

        $usage_in_semantics = $this->getUsagesInSemantics();

        $html         .= '<div class="tracker-admin-field" id="tracker-admin-formElements_' . $this->id . '">';
        $html         .= '<div class="tracker-admin-field-controls">';
                $html .= '<a class="edit-field" href="' . $this->getAdminEditUrl() . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', ['alt' => 'edit']) . '</a> ';
        if ($usage_in_semantics->areThereSemanticsUsingField() === false && $this->canBeRemovedFromUsage()) {
            $html .= '<a href="?' . http_build_query([
                'tracker'  => $tracker->id,
                'func'     => 'admin-formElement-remove',
                'formElement'    => $this->id,
            ]) . '">' . $GLOBALS['HTML']->getImage('ic/cross.png', ['alt' => 'remove']) . '</a>';
        } else {
            $cannot_remove_message = $usage_in_semantics->getUsages() . ' ' . $this->getCannotRemoveMessage();
            $html                 .= '<span style="color:gray;" title="' . $cannot_remove_message . '">';
            $html                 .= $GLOBALS['HTML']->getImage('ic/cross-disabled.png', ['alt' => 'remove']);
            $html                 .= '</span>';
        }
        $html .= '</div>';

        $html .= '<label title="' . $hp->purify($this->description) . '" class="tracker_formelement_label">' .
            $hp->purify($this->getLabel()) . $required . '</label>';
        $html .= $this->fetchAdminFormElement();
        $html .= '</div>';

        return $html;
    }

    /**
     * Say if this fields suport notifications
     *
     * @return bool
     */
    public function isNotificationsSupported()
    {
        return false;
    }

    /**
     * Tells if the field takes two columns
     * Ugly legacy hack to display fields in columns
     * @return bool
     */
    public function takesTwoColumns()
    {
        return false;
    }

    /**
     * Fetch the "add criteria" box
     *
     * @param array $used Current used fields as criteria.
     * @param string $prefix Prefix to add before label in optgroups
     *
     * @return string
     */
    public function fetchAddCriteria($used, $prefix = '')
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        $class    = 'tracker_report_add_criteria_unused';
        if (isset($used[$this->id])) {
            $class = 'tracker_report_add_criteria_used';
        }
        $html .= '<option value="' . $this->id . '" class="' . $class . '">' . $purifier->purify($this->getLabel()) . '</option>';
        return $html;
    }

    /**
     * Fetch the "add column" box in table renderer
     *
     * @param array $used Current used fields as column.
     * @param string $prefix Prefix to add before label in optgroups
     *
     * @return string
     */
    public function fetchAddColumn($used, $prefix = '')
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        $class    = 'tracker_report_table_add_column_unused';
        if (isset($used[$this->id])) {
            $class = 'tracker_report_table_add_column_used';
        }
        $html .= '<option value="' . $this->id . '" class="' . $class . '">' . $purifier->purify($this->getLabel()) . '</option>';
        return $html;
    }

    public function fetchAddCardFields(array $used_fields, string $prefix = ''): string
    {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        if (! isset($used_fields[$this->id])) {
            $html .= '<option value="' . $this->id . '">' . $purifier->purify($this->getLabel()) . '</option>';
        }
        return $html;
    }

    public function canBeDisplayedInTooltip(): bool
    {
        return true;
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return bool true if Tracler is ok
     */
    public function testImport()
    {
        return true;
    }

    public function getUsagesInSemantics(): CollectionOfSemanticsUsingAParticularTrackerField
    {
        $sm = new Tracker_SemanticManager($this->getTracker());
        return $sm->getSemanticsTheFieldBelongsTo($this);
    }

    /**
     * Is the field used in workflow?
     *
     * @return bool returns true if the field is used in workflow, false otherwise
     */
    public function isUsedInWorkflow()
    {
        return $this->getWorkflowFactory()->isFieldUsedInWorkflow($this);
    }

    /** @return WorkflowFactory */
    protected function getWorkflowFactory()
    {
        return WorkflowFactory::instance();
    }

     /**
     * Is the field used in a field dependency?
     *
     * @return bool returns true if the field is used in field dependency, false otherwise
     */
    public function isUsedInFieldDependency()
    {
        $tracker = $this->getTracker();
        if ($tracker === null) {
            return false;
        }

        $tracker_formelement_factory = Tracker_FormElementFactory::instance();
        $tracker_rules_manager       = new Tracker_RulesManager(
            $tracker,
            $tracker_formelement_factory,
            new FrozenFieldsDao(),
            new TrackerRulesListValidator($tracker_formelement_factory),
            new TrackerRulesDateValidator($tracker_formelement_factory),
            TrackerFactory::instance()
        );
        return $tracker_rules_manager->isUsedInFieldDependency($this);
    }

    /**
     * Is the form element can be removed from usage?
     * This method is to prevent tracker inconsistency
     *
     * @return string returns null if the field can be unused, a message otherwise
     */
    public function getCannotRemoveMessage()
    {
        $message = '';

        if ($this->isUsedInWorkflow()) {
            $message .= dgettext('tuleap-tracker', 'Impossible to delete this field (used in workflow)') . ' ';
        }

        if ($this->isUsedInTrigger()) {
            $message .= dgettext('tuleap-tracker', 'Impossible to delete this field (used in triggers)') . ' ';
        }

        if ($this->isUsedInFieldDependency()) {
            $message .= dgettext('tuleap-tracker', 'Impossible to delete this field (field dependencies)') . ' ';
        }

        return $message;
    }

    /**
     *
     * @return bool
     */
    public function canBeRemovedFromUsage()
    {
        $is_used = $this->isUsedInWorkflow() ||
            $this->isUsedInFieldDependency() ||
            $this->isUsedInTrigger();

        if ($is_used === true) {
            return false;
        }

        return true;
    }

    /**
     * @return bool true if the field is considered to be required
     */
    public function isRequired()
    {
        return $this->required;
    }

    /**
     * Validate a field and check perms and if it has a value if it is required
     *
     * @param mixed                           $submitted_value      The submitted value
     * @param bool $is_submission true if artifact submission, false if artifact update
     *
     * @return bool true on success or false on failure
     */
    public function validateFieldWithPermissionsAndRequiredStatus(Artifact $artifact, $submitted_value, PFUser $user, ?Tracker_Artifact_ChangesetValue $last_changeset_value = null, ?bool $is_submission = null)
    {
        $is_valid      = true;
        $hasPermission = $this->userCanUpdate($user);
        if ($is_submission) {
            $hasPermission = $this->userCanSubmit($user);
        }
        if ($last_changeset_value === null && ((! is_array($submitted_value) && $submitted_value === null) || (is_array($submitted_value) && empty($submitted_value))) && $hasPermission && $this->isRequired()) {
            $is_valid = false;
            $this->setHasErrors(true);

            $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-tracker', 'The field %1$s is required.'), $this->getLabel() . ' (' . $this->getName() . ')'));
        } elseif (((! is_array($submitted_value) && $submitted_value !== null) || (is_array($submitted_value) && ! empty($submitted_value))) && ! $hasPermission) {
            $is_valid = false;
            $this->setHasErrors(true);
            $GLOBALS['Response']->addFeedback('error', sprintf(dgettext('tuleap-tracker', 'You are not allowed to update the field %1$s.'), $this->getLabel()));
        } elseif ($submitted_value !== null && $hasPermission) {
            $is_valid = $this->isValidRegardingRequiredProperty($artifact, $submitted_value) && $this->validateField($artifact, $submitted_value);
        }
        return $is_valid;
    }

    /**
     * Validate a required field
     *
     * @param Artifact $artifact        The artifact to check
     * @param mixed    $submitted_value The submitted value
     *
     * @return bool true on success or false on failure
     */
    public function isValidRegardingRequiredProperty(Artifact $artifact, $submitted_value)
    {
        if (($submitted_value === null || $submitted_value === '') && $this->isRequired()) {
            $this->addRequiredError();
            return false;
        }

        return true;
    }

    protected function addRequiredError()
    {
        $this->has_errors = true;
        $GLOBALS['Response']->addFeedback(
            'error',
            sprintf(dgettext('tuleap-tracker', 'The field %1$s is required.'), $this->getLabel() . ' (' . $this->getName() . ')')
        );
    }

    /**
     * Validate a field
     *
     * @param Artifact $artifact        The artifact to check
     * @param mixed    $submitted_value The submitted value
     *
     * @return bool true on success or false on failure
     */
    public function validateField(Artifact $artifact, $submitted_value)
    {
        $is_valid = true;
        if ($submitted_value !== null) {
            $is_valid = $this->isValid($artifact, $submitted_value);
        }

        return $is_valid;
    }

    /**
     * Say if the value is valid. If not valid set the internal has_error to true.
     *
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    public function isValid(Artifact $artifact, $value)
    {
        $this->has_errors = ! ($this->validate($artifact, $value));

        return (! $this->has_errors);
    }

    public function isEmpty($value, Artifact $artifact)
    {
        return ($value === null || $value === '');
    }

    /**
     * @return bool true if the field has errors. Default is false
     * @see isValid
     */
    public function hasErrors()
    {
        return $this->has_errors;
    }

    /**
     * Force the has_error flag for the field
     *
     * @param boolean true if the field has errors. Default is false
     *
     * @return void
     */
    public function setHasErrors($has_errors)
    {
        $this->has_errors = $has_errors;
    }

    /**
     * Validate a value
     *
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request. May be string or array.
     *
     * @return bool true if the value is considered ok
     */
    abstract protected function validate(Artifact $artifact, $value);

    /**
     * Save the value submitted by the user in the new changeset
     *
     * @param Artifact                   $artifact           The artifact
     * @param Tracker_Artifact_Changeset $old_changeset      The old changeset. null if it is the first one
     * @param int                        $new_changeset_id   The id of the new changeset
     * @param mixed                      $submitted_value    The value submitted by the user
     * @param PFUser                     $submitter          The user who made the modification
     * @param bool                       $is_submission      True if artifact submission, false if artifact update
     * @param bool                       $bypass_permissions If true, permissions to update/submit the value on field is not checked
     *
     * @return bool true if success
     */
    public function saveNewChangeset(
        Artifact $artifact,
        ?Tracker_Artifact_Changeset $old_changeset,
        int $new_changeset_id,
        $submitted_value,
        PFUser $submitter,
        bool $is_submission,
        bool $bypass_permissions,
        CreatedFileURLMapping $url_mapping,
    ) {
        $updated        = false;
        $save_new_value = false;
        $dao            = $this->getChangesetValueDao();

        if ($this instanceof Tracker_FormElement_Field_ReadOnly) {
            return true;
        }

        if ($bypass_permissions) {
            $hasPermission = true;
        } else {
            $hasPermission = $this->userCanUpdate($submitter);
            //If a field is not submitable, but has a required default value, the value has to  be submitted ...
            if ($is_submission) {
                $hasPermission = $this->userCanSubmit($submitter) ||
                    (! $this->userCanSubmit($submitter) && $this->isrequired() && $this->getDefaultValue() != null);
            }
        }

        $previous_changesetvalue = $this->getPreviousChangesetValue($old_changeset);
        if ($previous_changesetvalue) {
            if ($submitted_value === null || ! $hasPermission || ! $this->hasChanges($artifact, $previous_changesetvalue, $submitted_value)) {
                //keep the old value
                if ($changeset_value_id = $dao->save($new_changeset_id, $this->id, 0)) {
                    $updated = $this->keepValue($artifact, $changeset_value_id, $previous_changesetvalue);
                }
            } else {
                $save_new_value = true;
            }
        } elseif ($submitted_value === null) {
            return true;
        } elseif ($submitted_value !== null && $hasPermission) {
            $save_new_value = true;
        }

        if ($save_new_value) {
            //Save the new value
            if ($changeset_value_id = $dao->save($new_changeset_id, $this->id, 1)) {
                $updated = $this->saveValue($artifact, $changeset_value_id, $submitted_value, $previous_changesetvalue, $url_mapping);
            }
        }

        return $updated;
    }

    protected function getChangesetValueDao()
    {
        return new Tracker_Artifact_Changeset_ValueDao();
    }

    protected function getPreviousChangesetValue($old_changeset)
    {
        $previous_changesetvalue = null;
        if ($old_changeset) {
            $previous_changesetvalue = $old_changeset->getValue($this);
        }
        return $previous_changesetvalue;
    }

    /**
     * Save the value and return the id
     *
     * @param Artifact                        $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value
     * @param mixed                           $value                   The value submitted by the user
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return bool
     */
    abstract protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    );

    /**
     * Keep the value
     *
     * @param Artifact                        $artifact                The artifact
     * @param int                             $changeset_value_id      The id of the changeset_value
     * @param Tracker_Artifact_ChangesetValue $previous_changesetvalue The data previously stored in the db
     *
     * @return int or array of int
     */
    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue)
    {
        return $this->getValueDao()->keep($previous_changesetvalue->getId(), $changeset_value_id);
    }

    /**
     * Check if there are changes between old and new value for this field
     *
     * @param Artifact                        $artifact  The current artifact
     * @param Tracker_Artifact_ChangesetValue $old_value The data stored in the db
     * @param mixed                           $new_value May be string or array
     *
     * @return bool true if there are differences
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        return false;
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param bool $has_changed If the changeset value has changed from the rpevious one
     *
     * @return Tracker_Artifact_ChangesetValue|null null if not found
     */
    abstract public function getChangesetValue($changeset, $value_id, $has_changed);

    /**
     * Return REST value of a field for a given changeset
     *
     *
     * @return mixed | null if no values
     */
    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        return $this->getFullRESTValue($user, $changeset);
    }

    /**
     * Return full REST value of a field for a given changeset
     *
     *
     * @return mixed | null if no values
     */
    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $value = $changeset->getValue($this);
        if ($value) {
            return $value->getFullRESTValue($user);
        }
        return null;
    }

    public function getJsonValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        if ($this->userCanRead($user)) {
            $value = $changeset->getValue($this);
            return $value ? $value->getJsonValue() : '';
        }
        return null;
    }

    /**
     * Get the field data for artifact submission
     *
     * @param string $value
     *
     * @return mixed the field data corresponding to the value for artifact submision
     */
    public function getFieldData($value)
    {
        // for atomic fields, the field data is the value (int, float, date, string, text)
        return $value;
    }

    public function getRestFieldData($value)
    {
        return $this->getFieldData($value);
    }

    /**
     * Transform REST representation of field into something that artifact createArtifact or updateArtifact can proceed
     *
     * @param array    $value    PHP representation of submitted Json value
     * @param Artifact $artifact Artifact to update if any (null during creation)
     *
     * @return mixed
     */
    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        if (! isset($value['value'])) {
            throw new Tracker_FormElement_InvalidFieldValueException(
                'Expected format for field ' . $this->id .
                 ' : {"field_id" : 15458, "value" : some_value'
            );
        }

        return $this->getRestFieldData($value['value']);
    }

    /**
     * Transform REST representation of field into something that artifact createArtifact or updateArtifact can proceed
     *
     * @return mixed
     */
    public function getFieldDataFromRESTValueByField(array $value, ?Artifact $artifact = null)
    {
        if (! array_key_exists('value', $value)) {
            throw new Tracker_FormElement_InvalidFieldValueException(
                'value attribute is missing for field ' . $this->id
            );
        }

        return $this->getRestFieldData($value['value']);
    }

    /**
     * Get data from CSV value in order to be saved in DB (create/update DB)
     *
     * @param string $csv_value
     *
     * @return mixed
     */
    public function getFieldDataFromCSVValue($csv_value, ?Artifact $artifact = null)
    {
        return $this->getFieldData($csv_value);
    }

    /**
     * Get the field data for CSV import
     *
     * @param string the CSV field value
     *
     * @return string the field data corresponding to the CSV preview value for CSV import
     */
    public function getFieldDataForCSVPreview($csv_value)
    {
        // for most of atomic fields, the field data is the same value (int, float, string, text)
        $purifier = Codendi_HTMLPurifier::instance();
        return $purifier->purify($csv_value, CODENDI_PURIFIER_CONVERT_HTML);
    }

    /**
     * Returns true if field has a default value defined, false otherwise
     *
     * @return bool true if field has a default value defined, false otherwise
     */
    public function hasDefaultValue()
    {
        return ($this->getProperty('default_value') !== null);
    }

    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    public function getDefaultValue()
    {
        return $this->getProperty('default_value');
    }

    public function getDefaultRESTValue()
    {
        return $this->getDefaultValue();
    }

    /**
     * Extract data from request
     * Some fields like files doesn't have their value submitted in POST or GET
     * Let them populate $fields_data[field_id] if needed
     *
     * @param array &$fields_data The user submitted value
     *
     * @return void
     */
    public function augmentDataFromRequest(&$fields_data)
    {
        //Do nothing for the majority of fields
    }

    /**
     * get the permissions for this field
     *
     * @return array
     */
    public function getPermissionsByUgroupId()
    {
        if (! $this->cache_permissions) {
            $this->cache_permissions = [];
            //berk... legacy permission code... legacy db functions... berk!
            $sql = "SELECT ugroup_id, permission_type
                  FROM permissions
                  WHERE permission_type LIKE 'PLUGIN_TRACKER_FIELD%'
                    AND object_id='" . db_ei($this->getId()) . "'
                  ORDER BY ugroup_id";

            $res = db_query($sql);
            if (db_numrows($res) > 0) {
                while ($row = db_fetch_array($res)) {
                    $this->cache_permissions[$row['ugroup_id']][] = $row['permission_type'];
                }
            }
        }
        return $this->cache_permissions;
    }

    /**
     *
     * @param array $form_element_data
     * @param bool $tracker_is_empty
     */
    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
        if (! $tracker_is_empty) {
            $value_dao = $this->getValueDao();
            if ($value_dao) {
                $value_dao->createNoneValue($this->getTrackerId(), $this->id);
            }
        }
    }

    /**
     * Get the last ChangesetValue of the field
     *
     * @return Tracker_Artifact_ChangesetValue|null
     */
    public function getLastChangesetValue(Artifact $artifact)
    {
        return $artifact->getValue($this);
    }

    /**
     * Do something after *all* fields are saved as new changset
     */
    public function postSaveNewChangeset(
        Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        array $fields_data,
        ?Tracker_Artifact_Changeset $previous_changeset = null,
    ) {
    }

    public function canBeUsedAsReportCriterion()
    {
        return true;
    }

    public function canBeUsedAsReportColumn()
    {
        return true;
    }

    public function isMultiple(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function canBeUsedToSortReport()
    {
        return false;
    }

    /** @return bool */
    public function hasCustomFormatForAggregateResults()
    {
        return false;
    }

    /**
     * Please note that the result may be not a DataAccessResult:
     *
     * In case of a simple query that can be computed alongside others, result will be a string (the result from mysql).
     * In case of a complex query that must be run alone, result will be the DataAccessResult.
     *
     * @see Tracker_Report_Renderer_Table::fetchAddAggregatesUsedFunctionsValue()
     *
     * @param string                  $function AVG, SUM, …
     * @param LegacyDataAccessResultInterface|string $result
     *
     * @return string
     */
    public function formatAggregateResult($function, $result)
    {
        return '';
    }

    /**
     * @return FrozenFieldDetector
     */
    protected function getFrozenFieldDetector()
    {
        return new FrozenFieldDetector(
            new TransitionRetriever(
                new StateFactory(
                    TransitionFactory::instance(),
                    new SimpleWorkflowDao()
                ),
                new TransitionExtractor()
            ),
            FrozenFieldsRetriever::instance(),
        );
    }

    public function addChangesetValueToSearchIndex(ItemToIndexQueue $index_queue, Tracker_Artifact_ChangesetValue $changeset_value): void
    {
    }
}
