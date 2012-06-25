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

class Tracker_FormElement_Field_Aggregate extends Tracker_FormElement_Field implements Tracker_FormElement_Field_ReadOnly {

    public function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
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
    public function fetchArtifactValueReadOnly(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        $user = UserManager::instance()->getCurrentUser();
        $linked_artifacts = $artifact->getLinkedArtifacts($user);
        $sum = 0;
        foreach ($linked_artifacts as $linked_artifact) {
            $field = $this->getFormElementFactory()->getFormElementByName($linked_artifact->getTracker()->getId(), 'remaining_effort');
            if ($field) {
                if ($field instanceof Tracker_FormElement_Field_Aggregate) {
                    $sum += $field->fetchArtifactValueReadOnly($linked_artifact);
                } else {
                    $value = $linked_artifact->getValue($field);
                    if ($value) {
                        $sum += $value->getValue();
                    }
                }
            }
        }
        return $sum;
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
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact $artifact
     * @param Tracker_Artifact_ChangesetValue_Integer $value The changeset value of this field
     * @return string The html code to display the field value in tooltip
     */
    protected function fetchTooltipValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null) {
        return $this->fetchArtifactValueReadOnly($artifact, $value);
    }

    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $from_aid = null) {
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
        return $this->fetchArtifactValueReadOnly($artifact);
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
        return $GLOBALS['HTML']->getImagePath('ic/burndown.png');
    }

    /**
     * @return the path to the icon
     */
    public static function getFactoryIconCreate() {
        return $GLOBALS['HTML']->getImagePath('ic/burndown--plus.png');
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
