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

class Tracker_FormElement_Field_Priority extends Tracker_FormElement_Field_Integer implements Tracker_FormElement_Field_ReadOnly
{

    public function getLabel($report = null)
    {
        return $this->label;
    }

    public function getCriteriaFrom($criteria)
    {
        return ' INNER JOIN tracker_artifact_priority_rank ON artifact.id = tracker_artifact_priority_rank.artifact_id';
    }

    public function getCriteriaWhere($criteria)
    {
        if ($criteria_value = $this->getCriteriaValue($criteria)) {
            return $this->buildMatchExpression('tracker_artifact_priority_rank.rank', $criteria_value);
        }
        return '';
    }

    /**
     * @param null|Tracker_Report|int $report
     */
    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report = null, $from_aid = null)
    {
        $value = $this->getArtifactRank($artifact_id);

        if (! $report instanceof Tracker_Report) {
            return $value;
        }

        $augmented_value = $this->getAugmentedFieldValue($artifact_id, $report);
        if ($augmented_value) {
            return $augmented_value;
        }

        return '<span class="non-displayable" title="' . $GLOBALS['Language']->getText('plugin_tracker_report', 'non_displayable_tooltip') . '">' . $GLOBALS['Language']->getText('plugin_tracker_report', 'non_displayable') . '</span>';
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        $augmented_value = $this->getAugmentedFieldValue($artifact_id, $report);
        if ($augmented_value) {
            return $augmented_value;
        }

        return $GLOBALS['Language']->getText('plugin_tracker_report', 'non_displayable');
    }

    private function getAugmentedFieldValue($artifact_id, Tracker_Report $report)
    {
        $result = '';

        EventManager::instance()->processEvent(
            TRACKER_EVENT_FIELD_AUGMENT_DATA_FOR_REPORT,
            array(
                'additional_criteria' => $report->getAdditionalCriteria(),
                'result'              => &$result,
                'artifact_id'         => $artifact_id,
                'field'               => $this
            )
        );

        return $result;
    }

    /**
     * Get the "select" statement to retrieve field values
     * @return string
     * @see getQueryFrom
     */
    public function getQuerySelect()
    {
        return "R_{$this->id}.rank AS `$this->name`";
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */
    public function getQueryFrom()
    {
        return "INNER JOIN tracker_artifact_priority_rank AS R_{$this->id} ON a.id = R_{$this->id}.artifact_id";
    }
    /**
     * @return array the available aggreagate functions for this field. empty array if none or irrelevant.
     */
    public function getAggregateFunctions()
    {
        return array();
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
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
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
        Tracker_Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text'
    ) {
        $output = '';
        switch ($format) {
            case 'html':
                $output  .= '<span>' . $this->getArtifactRank($artifact->getID()) . '</span>';
                break;
            default:
                $output .= $this->getArtifactRank($artifact->getID());
                break;
        }
        return $output;
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Tracker_Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values
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
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'priority_label');
    }

    public static function getFactoryDescription()
    {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'priority_description');
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
    protected function fetchTooltipValue(Tracker_Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        return $this->getArtifactRank($artifact->getID());
    }

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value)
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
     * @param Tracker_Artifact                $artifact             The artifact to check
     * @param mixed                           $submitted_value      The submitted value
     * @param Tracker_Artifact_ChangesetValue $last_changeset_value The last changeset value of the field (give null if no old value)
     *
     * @return bool true on success or false on failure
     */
    public function validateFieldWithPermissionsAndRequiredStatus(
        Tracker_Artifact $artifact,
        $submitted_value,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value = null,
        $is_submission = null
    ) {
        $is_valid = true;

        if ($submitted_value !== null && ! $this->userCanUpdate()) {
            $is_valid = true;
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'field_not_taken_account', array($this->getName())));
        }

        return $is_valid;
    }

    /**
     * Fetch the html code to display the field value in card
     *
     *
     * @return string
     */
    public function fetchCardValue(Tracker_Artifact $artifact, ?Tracker_CardDisplayPreferences $display_preferences = null)
    {
        //return $this->fetchTooltipValue($artifact, $artifact->getLastChangeset()->getValue($this));

        $artifact_id  = $artifact->getId();
        $changeset_id = $artifact->getLastChangeset()->getId();
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
