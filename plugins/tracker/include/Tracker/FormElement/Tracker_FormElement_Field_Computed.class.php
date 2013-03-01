<?php
/**
 * Copyright (c) Enalean, 2012. All Rights Reserved.
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


class Tracker_FormElement_Field_Computed extends Tracker_FormElement_Field implements Tracker_FormElement_Field_ReadOnly, Tracker_FormElement_IComputeValues {

    public $default_properties = array(
        'target_field_name' => array(
            'value' => '',
            'type'  => 'string',
            'size'  => 40,
        ),
    );

    /**
     * Given an artifact, return a numerical value of the field for this artifact.
     *
     * @param PFUser             $user                  The user who see the results
     * @param Tracker_Artifact $artifact              The artifact on which the value is computed
     * @param Array            $computed_artifact_ids Hash map to store artifacts already computed (avoid cycles)
     *
     * @return float
     */
    public function getComputedValue(PFUser $user, Tracker_Artifact $artifact, $timestamp = null, &$computed_artifact_ids = array()) {
        $sum = null;
        foreach ($artifact->getLinkedArtifacts($user) as $linked_artifact) {
            $value = $this->getUniqueFieldValue($user, $linked_artifact, $timestamp, $computed_artifact_ids);
            $sum   = $this->sumIfNotNull($sum, $value);
        }
        return $sum;
    }

    private function sumIfNotNull($sum, $value) {
        if ($value !== null) {
            $sum += $value;
        }
        return $sum;
    }

    private function getUniqueFieldValue(PFUser $user, Tracker_Artifact $artifact, $timestamp, &$computed_artifact_ids) {
        if ($this->notAlreadyComputed($artifact, $computed_artifact_ids)) {
            return $this->getFieldValue($user, $artifact, $timestamp, $computed_artifact_ids);
        }
        return null;
    }

    private function notAlreadyComputed(Tracker_Artifact $artifact, &$computed_artifact_ids) {
        if (!isset($computed_artifact_ids[$artifact->getId()])) {
            $computed_artifact_ids[$artifact->getId()] = true;
            return true;
        }
        return false;
    }

    private function getFieldValue(PFUser $user, Tracker_Artifact $artifact, $timestamp, &$computed_artifact_ids) {
        $field = $this->getTargetField($user, $artifact);
        if ($field) {
            return $field->getComputedValue($user, $artifact, $timestamp, $computed_artifact_ids);
        }
        return null;
    }

    private function getTargetField(PFUser $user, Tracker_Artifact $artifact) {
        return $this->getFormElementFactory()->getComputableFieldByNameForUser(
            $artifact->getTracker()->getId(),
            $this->getProperty('target_field_name'),
            $user
        );
    }

    /**
     *
     * @param Tracker_Artifact                $artifact
     * @param Tracker_Artifact_ChangesetValue $value
     * @param Array                           $submitted_values
     *
     * @return string
     */
    public function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        $current_user = UserManager::instance()->getCurrentUser();
        return $this->getComputedValue($current_user, $artifact);
    }

    /**
     * Fetch the html code to display the field value in artifact in read only mode
     *
     * @param Tracker_Artifact                $artifact The artifact
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     *
     * @return string
     */
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $current_user = UserManager::instance()->getCurrentUser();
        return $this->getComputedValue($current_user, $artifact);
    }


    /**
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $format = 'text') {
        $current_user = UserManager::instance()->getCurrentUser();
        return $this->getComputedValue($current_user, $artifact);
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_Integer $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $current_user = UserManager::instance()->getCurrentUser();
        return $this->getComputedValue($current_user, $artifact);
    }

    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $current_user = UserManager::instance()->getCurrentUser();
        $artifact     = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
        return $this->getComputedValue($current_user, $artifact);
    }


    /**
     * Display the html field in the admin ui
     * @return string html
     */
    public function fetchAdminFormElement() {
        $html = '9001';
        return $html;
    }

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'aggregate_label');
    }

    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'aggregate_description');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconUseIt() {
        return $GLOBALS['HTML']->getImagePath('ic/sum.png');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/sum.png');
    }

    protected function getDao() {
        return new Tracker_FormElement_Field_ComputedDao();
    }

    public function getCriteriaFrom($criteria) {
    }

    public function getCriteriaWhere($criteria) {
    }

    public function getQuerySelect() {
    }

    public function getQueryFrom() {
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value) {
    }

    public function fetchCriteriaValue($criteria) {
    }

    public function fetchRawValue($value) {
    }

    protected function getCriteriaDao() {
    }

    protected function fetchSubmitValue() {
    }

    protected function fetchSubmitValueMasschange() {
    }

    protected function getValueDao() {
    }

    public function afterCreate() {
    }

    public function fetchFollowUp($artifact, $from, $to) {
    }

    public function fetchRawValueFromChangeset($changeset) {
    }

    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
    }

    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue) {
    }

    public function getChangesetValue($changeset, $value_id, $has_changed) {
    }

    public function getSoapAvailableValues() {
    }

    public function testImport() {
        return true;
    }

    protected function validate(Tracker_Artifact $artifact, $value) {
        return true;
    }

    public function fetchSubmit() {
        return '';
    }

    public function fetchSubmitMasschange() {
    }
}

?>
