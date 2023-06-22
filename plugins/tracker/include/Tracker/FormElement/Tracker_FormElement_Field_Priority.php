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

use Tuleap\Option\Option;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Report\Query\ParametrizedFromWhere;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_Priority extends Tracker_FormElement_Field_Integer implements Tracker_FormElement_Field_ReadOnly
{
    /**
     * Event emitted when a field data can be augmented by plugins
     *
     * Parameters:
     *   'additional_criteria'    Tracker_Report_AdditionalCriteria[]  (IN)
     *   'result'                 String (OUT)
     *   'artifact_id'            Int (IN)
     *   'field'                  Tracker_FormElement_Field (IN)
     */
    public final const TRACKER_EVENT_FIELD_AUGMENT_DATA_FOR_REPORT = 'tracker_event_field_augment_data_for_report';

    /**
     * @psalm-mutation-free
     */
    public function getLabel($report = null)
    {
        return $this->label;
    }

    public function getCriteriaFromWhere(Tracker_Report_Criteria $criteria): Option
    {
        $criteria_value = $this->getCriteriaValue($criteria);
        if (! $criteria_value) {
            return Option::nothing(ParametrizedFromWhere::class);
        }

        return $this->buildMatchExpression('tracker_artifact_priority_rank.`rank`', $criteria_value)->mapOr(
            static fn (ParametrizedSQLFragment $match) => Option::fromValue(
                new ParametrizedFromWhere(
                    'INNER JOIN tracker_artifact_priority_rank ON artifact.id = tracker_artifact_priority_rank.artifact_id',
                    $match->sql,
                    [],
                    $match->parameters
                )
            ),
            Option::nothing(ParametrizedFromWhere::class)
        );
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        $value = $this->getArtifactRank($artifact_id);

        if (! $report instanceof Tracker_Report) {
            return (string) $value;
        }

        $augmented_value = $this->getAugmentedFieldValue($artifact_id, $report);
        if ($augmented_value) {
            return (string) $augmented_value;
        }

        return '<span class="non-displayable" title="' . dgettext('tuleap-tracker', 'The rank of an artifact only exists in the context of a milestone. You must filter by milestone to view artifact ranks.') . '">' . dgettext('tuleap-tracker', 'N/A') . '</span>';
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        $augmented_value = $this->getAugmentedFieldValue($artifact_id, $report);
        if ($augmented_value) {
            return $augmented_value;
        }

        return dgettext('tuleap-tracker', 'N/A');
    }

    private function getAugmentedFieldValue($artifact_id, Tracker_Report $report)
    {
        $result = '';

        EventManager::instance()->processEvent(
            self::TRACKER_EVENT_FIELD_AUGMENT_DATA_FOR_REPORT,
            [
                'additional_criteria' => $report->getAdditionalCriteria(false),
                'result'              => &$result,
                'artifact_id'         => $artifact_id,
                'field'               => $this,
            ]
        );

        return $result;
    }

    /**
     * Get the "select" statement to retrieve field values
     * @see getQueryFrom
     */
    public function getQuerySelect(): string
    {
        return "R_{$this->id}.rank AS " . $this->getQuerySelectName();
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFrom()
    {
        return "INNER JOIN tracker_artifact_priority_rank AS R_{$this->id} ON a.id = R_{$this->id}.artifact_id ";
    }

    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions()
    {
        return [];
    }

    /**
     * Fetch the html code to display the field value in artifact
     *
     * @param Artifact                        $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param array                           $submitted_values The value already submitted by the user
     *
     * @return string
     */
    protected function fetchArtifactValue(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Artifact                        $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        return '<span>' . $this->getArtifactRank($artifact->getID()) . '</span>';
    }

    private function getArtifactRank($artifact_id)
    {
        return $this->getPriorityManager()->getGlobalRank($artifact_id);
    }

    /**
     * Fetch artifact value for email
     * @param bool $ignore_perms
     * @param string $format
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
        $output = '';
        switch ($format) {
            case 'html':
                $output .= '<span>' . $this->getArtifactRank($artifact->getID()) . '</span>';
                break;
            default:
                $output .= $this->getArtifactRank($artifact->getID());
                break;
        }
        return $output;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    protected function fetchAdminFormElement()
    {
        return '<span>314116</span>';
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Rank');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Rank');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/priority.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/priority.png');
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        return $this->getArtifactRank($artifact->getID());
    }

    /**
     * Validate a value
     *
     * @param Artifact $artifact The artifact
     * @param mixed    $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Artifact $artifact, $value)
    {
        return true;
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit(array $submitted_values)
    {
        return '';
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmitMasschange()
    {
        return '';
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitPriority($this);
    }

    /**
     * Return REST value of the priority
     *
     *
     * @return mixed | null if no values
     */
    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        return $this->getFullRESTValue($user, $changeset);
    }

    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $artifact_field_value_full_representation = new Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation();
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            (int) $this->getArtifactRank($changeset->getArtifact()->getID())
        );
        return $artifact_field_value_full_representation;
    }

    private function getPriorityManager()
    {
        return new Tracker_Artifact_PriorityManager(
            new Tracker_Artifact_PriorityDao(),
            new Tracker_Artifact_PriorityHistoryDao(),
            UserManager::instance(),
            Tracker_ArtifactFactory::instance()
        );
    }

    /**
     * Validate a field
     *
     * @param Artifact                        $artifact             The artifact to check
     * @param mixed                           $submitted_value      The submitted value
     * @param Tracker_Artifact_ChangesetValue $last_changeset_value The last changeset value of the field (give null if no old value)
     *
     * @return bool true on success or false on failure
     */
    public function validateFieldWithPermissionsAndRequiredStatus(
        Artifact $artifact,
        $submitted_value,
        PFUser $user,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value = null,
        ?bool $is_submission = null,
    ): bool {
        $is_valid = true;

        if ($submitted_value !== null && ! $this->userCanUpdate($user)) {
            $is_valid = true;
            $GLOBALS['Response']->addFeedback('warning', sprintf(dgettext('tuleap-tracker', 'The field "%1$s" will not be taken into account.'), $this->getName()));
        }

        return $is_valid;
    }

    /**
     * Fetch the html code to display the field value in card
     *
     *
     * @return string
     */
    public function fetchCardValue(Artifact $artifact, ?Tracker_CardDisplayPreferences $display_preferences = null)
    {
        //return $this->fetchTooltipValue($artifact, $artifact->getLastChangeset()->getValue($this));

        $artifact_id  = $artifact->getId();
        $changeset_id = (int) $artifact->getLastChangeset()->getId();
        $value        = $artifact->getLastChangeset()->getValue($this);
        $report       = Tracker_ReportFactory::instance()->getDefaultReportsByTrackerId($artifact->getTracker()->getId());
        $request      = HTTPRequest::instance();

        if ($request->exist('report')) {
            $report = Tracker_ReportFactory::instance()->getReportById(
                $request->get('report'),
                UserManager::instance()->getCurrentUser()->getId()
            );
        }

        return $this->fetchChangesetValue($artifact_id, $changeset_id, $value, $report);
    }
}
