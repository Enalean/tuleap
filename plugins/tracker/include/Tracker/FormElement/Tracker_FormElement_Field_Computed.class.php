<?php
/**
 * Copyright (c) Enalean, 2012 - 2016. All Rights Reserved.
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
            'value' => null,
            'type'  => 'string',
            'size'  => 40,
        ),
        'fast_compute' => array(
            'value' => null,
            'type'  => 'upgrade_button',
        ),
    );

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
        Tracker_FormElement $original_field = null
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

        $this->clearFastCompute();
        $this->clearTargetFieldName();
        $this->clearCache();
    }

    private function clearFastCompute()
    {
        if (is_null($this->getProperty('fast_compute'))
            || $this->useFastCompute()
        ) {
            unset($this->default_properties['fast_compute']);
        }
    }

    private function clearTargetFieldName()
    {
        if (is_null($this->getProperty('target_field_name'))
            || (
                $this->name === $this->getProperty('target_field_name')
                && $this->useFastCompute()
            )
        ) {
            unset($this->default_properties['target_field_name']);
        }
    }

    private function clearCache()
    {
        $this->cache_specific_properties = null;
    }

    private function useFastCompute() {
        return $this->getProperty('fast_compute') == 1;
    }

    /**
     * Given an artifact, return a numerical value of the field for this artifact.
     *
     * @param PFUser             $user                  The user who see the results
     * @param Tracker_Artifact $artifact              The artifact on which the value is computed
     * @param Array            $computed_artifact_ids Hash map to store artifacts already computed (avoid cycles)
     *
     * @return float|null if there are no data (/!\ it's on purpose, otherwise we can mean to distinguish if there is data but 0 vs no data at all, for the graph plot)
     */
    public function getComputedValue(PFUser $user, Tracker_Artifact $artifact, $timestamp = null, array &$computed_artifact_ids = array()) {
        if ($this->useFastCompute()) {
            return $this->getFastComputedValue($artifact->getId(), $timestamp);
        }

        $computed_artifact_ids[$artifact->getId()] = true;

        if ($timestamp === null) {
            $dar = $this->getDao()->getFieldValues(array($artifact->getId()), $this->getProperty('target_field_name'));
        } else {
            $dar = Tracker_FormElement_Field_ComputedDaoCache::instance()->getFieldValuesAtTimestamp($artifact->getId(), $this->getProperty('target_field_name'), $timestamp);
        }
        return $this->computeValuesVersion($dar, $user, $timestamp, $computed_artifact_ids);
    }

    protected function processUpdate(
        Tracker_IDisplayTrackerLayout $layout,
        $request,
        $current_user,
        $redirect = false
    ) {
        $formElement_data = $request->get('formElement_data');

        if ($formElement_data !== false) {
            $default_specific_properties = array(
                'fast_compute'      => '1',
                'target_field_name' => $formElement_data['name']
            );
            $submitted_specific_properties = isset($formElement_data['specific_properties']) ? $formElement_data['specific_properties'] : array();

            $merged_specific_properties = array_merge(
                $default_specific_properties,
                $submitted_specific_properties
            );

            $formElement_data['specific_properties'] = $merged_specific_properties;
            $request->set('formElement_data', $formElement_data);
        }

        parent::processUpdate(
            $layout,
            $request,
            $current_user,
            $redirect
        );
    }

    public function afterCreate($formElement_data = array())
    {
        $formElement_data['specific_properties']['fast_compute']      = '1';
        $formElement_data['specific_properties']['target_field_name'] = $this->name;
        $this->storeProperties($formElement_data['specific_properties']);

        parent::afterCreate($formElement_data);
    }

    private function getFastComputedValue($artifact_id, $timestamp = null) {
        $sum                   = null;
        $target_field_name     = $this->getProperty('target_field_name');
        $artifact_ids_to_fetch = array($artifact_id);
        $already_seen          = array($artifact_id => true);
        do {
            if ($timestamp !== null) {
                $dar = $this->getDao()->getFieldValuesAtTimestamp($artifact_ids_to_fetch, $target_field_name, $timestamp);
            } else {
                $dar = $this->getDao()->getFieldValues($artifact_ids_to_fetch, $target_field_name);
            }
            $artifact_ids_to_fetch = array();
            foreach ($dar as $row) {
                if (! isset($already_seen[$row['id']])) {
                    $already_seen[$row['id']] = true;
                    if ($row['type'] == 'computed') {
                        $artifact_ids_to_fetch[] = $row['id'];
                    } elseif (isset($row[$row['type'].'_value'])) {
                        $sum += $row[$row['type'].'_value'];
                    }
                }
            }
            $dar->freeMemory();
        } while(count($artifact_ids_to_fetch) > 0);

        return $sum;
    }

    private function computeValuesVersion(DataAccessResult $dar, PFUser $user, $timestamp, array &$computed_artifact_ids) {
        $sum = null;

        foreach ($dar as $row) {
            if (! isset($computed_artifact_ids[$row['id']])) {
                $linked_artifact = Tracker_ArtifactFactory::instance()->getInstanceFromRow($row);
                if ($linked_artifact->userCanView($user)) {
                    $this->addIfNotNull($sum, $this->getValueOrContinueComputing($user, $linked_artifact, $row, $timestamp, $computed_artifact_ids));
                }
            }
        }

        return $sum;
    }

    private function addIfNotNull(&$sum, $value) {
        if ($value !== null) {
            $sum += $value;
        }
    }

    private function getValueOrContinueComputing(PFUser $user, Tracker_Artifact $linked_artifact, array $row, $timestamp, array &$computed_artifact_ids) {
        if ($row['type'] == 'computed') {
            return $this->getFieldValue($user, $linked_artifact, $timestamp, $computed_artifact_ids);
        } else if (! isset($row[$row['type'].'_value'])) {
            $computed_artifact_ids[$row['id']] = true;
            return null;
        } else {
            $computed_artifact_ids[$row['id']] = true;
            return $row[$row['type'].'_value'];
        }
    }

    private function getFieldValue(PFUser $user, Tracker_Artifact $artifact, $timestamp, array &$computed_artifact_ids) {
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
        if (! $this->getComputedValue($current_user, $artifact)) {
            return $this->getNoValueLabel();
        }
        return $this->getComputedValue($current_user, $artifact);
    }


    /**
     * Fetch data to display the field value in mail
     *
     * @param Tracker_Artifact                $artifact         The artifact
     * @param PFUser                          $user             The user who will receive the email
     * @param Tracker_Artifact_ChangesetValue $value            The actual value of the field
     * @param string                          $format           output format
     *
     * @return string
     */
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, PFUser $user, Tracker_Artifact_ChangesetValue $value = null, $format = 'text') {
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

    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report=null, $from_aid = null) {
        $current_user = UserManager::instance()->getCurrentUser();
        $artifact     = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
        return $this->getComputedValue($current_user, $artifact);
    }

    public function getSoapValue(PFUser $user, Tracker_Artifact_Changeset $changeset) {
        if ($this->userCanRead($user)) {
            return array(
                'field_name'  => $this->getName(),
                'field_label' => $this->getLabel(),
                'field_value' => array('value' => (string) $this->getComputedValue($user, $changeset->getArtifact()))
            );
        }
        return null;
    }

    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset) {
        return $this->getFullRESTValue($user, $changeset);
    }

    public function getFullRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset) {
        $classname_with_namespace = 'Tuleap\Tracker\REST\Artifact\ArtifactFieldValueFullRepresentation';
        $artifact_field_value_full_representation = new $classname_with_namespace;
        $artifact_field_value_full_representation->build(
            $this->getId(),
            Tracker_FormElementFactory::instance()->getType($this),
            $this->getLabel(),
            $this->getComputedValue($user, $changeset->getArtifact())
        );
        return $artifact_field_value_full_representation;
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

    public function accept(Tracker_FormElement_FieldVisitor $visitor) {
        return $visitor->visitComputed($this);
    }

    /**
     * @return int | null if no value found
     */
    public function getCachedValue(PFUser $user, Tracker_Artifact $artifact, $timestamp = null) {
        $dao   = Tracker_FormElement_Field_ComputedDaoCache::instance();
        $value = $dao->getCachedFieldValueAtTimestamp($artifact->getId(), $this->getId(), $timestamp);

        if ($value === false) {
            $value = $this->getComputedValue($user, $artifact, $timestamp);
            $dao->saveCachedFieldValueAtTimestamp($artifact->getId(), $this->getId(), $timestamp, $value);
        }

        return $value;
    }

    public function canBeUsedAsReportCriterion() {
        return false;
    }
}
