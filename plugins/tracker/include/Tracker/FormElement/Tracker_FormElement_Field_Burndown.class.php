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

use Tuleap\Tracker\FormElement\BurndownCacheIsCurrentlyCalculatedException;
use Tuleap\Tracker\FormElement\SystemEvent\SystemEvent_BURNDOWN_GENERATE;

require_once 'common/chart/ErrorChart.class.php';
require_once 'common/date/TimePeriodWithWeekEnd.class.php';
require_once 'common/date/TimePeriodWithoutWeekEnd.class.php';

class Tracker_FormElement_Field_Burndown extends Tracker_FormElement_Field implements Tracker_FormElement_Field_ReadOnly {

    /**
     * Request parameter to display burndown image
     */
    const FUNC_SHOW_BURNDOWN          = 'show_burndown';

    const REMAINING_EFFORT_FIELD_NAME = 'remaining_effort';
    const DURATION_FIELD_NAME         = 'duration';
    const START_DATE_FIELD_NAME       = 'start_date';
    const CAPACITY_FIELD_NAME         = 'capacity';

    /**
     * @var Tracker_HierarchyFactory
     */
    private $hierarchy_factory;

    /**
     * @return the label of the field (mainly used in admin part)
     */
    public static function getFactoryLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'burndown_label');
    }

    /**
     * @return the description of the field (mainly used in admin part)
     */
    public static function getFactoryDescription() {
        return $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'burndown_description');
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

    /**
     * Returns the previously injected factory (e.g. in tests), or a new
     * instance (e.g. in production).
     *
     * @return Tracker_HierarchyFactory
     */
    public function getHierarchyFactory() {
        if ($this->hierarchy_factory == null) {
            $this->hierarchy_factory = Tracker_HierarchyFactory::instance();
        }
        return $this->hierarchy_factory;
    }

    /**
     * Provides a way to inject the HierarchyFactory, since it cannot be done
     * in the constructor.
     *
     * @param Tracker_HierarchyFactory $hierarchy_factory
     */
    public function setHierarchyFactory($hierarchy_factory) {
        $this->hierarchy_factory = $hierarchy_factory;
    }

    public function fetchArtifactValue(Tracker_Artifact $artifact, Tracker_Artifact_ChangesetValue $value = null, $submitted_values = array()) {
        return $this->fetchArtifactValueWithEditionFormIfEditable($artifact, $value);
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
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        $html    .= '<img src="'.$this->getBurndownImageUrl($artifact).'" alt="'.$purifier->purify($this->getLabel()).'" width="640" height="480" />';
        return $html;
    }

    public function fetchArtifactForOverlay(Tracker_Artifact $artifact, $submitted_values = array()) {
        $purifier = Codendi_HTMLPurifier::instance();
        $html     = '';
        $html    .= '<img src="'.$this->getBurndownImageUrl($artifact).'" alt="'.$purifier->purify($this->getLabel()).'" width="390" height="400" />';
        return $html;
    }

    /**
     *
     * @param Tracker_IDisplayTrackerLayout $layout
     * @param Codendi_Request               $request
     * @param PFUser                        $current_user
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        switch ($request->get('func')) {
            case self::FUNC_SHOW_BURNDOWN:
                try  {
                    $artifact_id = $request->getValidated('src_aid', 'uint', 0);
                    $artifact    = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
                    if (! $artifact) {
                        return false;
                    }
                    $this->fetchBurndownImage($artifact, $current_user);
                } catch (Tracker_FormElement_Field_BurndownException $e) {
                    $this->displayErrorImage($e->getMessage());
                } catch (BurndownCacheIsCurrentlyCalculatedException $e) {
                    $this->displayErrorImage($GLOBALS['Language']->getText('plugin_tracker', 'burndown_cache_generating'));
                }
                break;
            default:
                parent::process($layout, $request, $current_user);
        }
    }

    /**
     * Render a burndown image based on $artifact artifact links
     *
     * @param Tracker_Artifact $artifact
     *
     * @throws Tracker_FormElement_Field_BurndownException
     * @throws BurndownCacheIsCurrentlyCalculatedException
     */
    public function fetchBurndownImage(Tracker_Artifact $artifact, PFUser $user) {
        if ($this->userCanRead($user)) {
            $burndown_data = $this->buildBurndownData($user, $artifact);
            if ($burndown_data->isBeingCalculated() === true) {
                throw new BurndownCacheIsCurrentlyCalculatedException();
            } else {
                $this->getBurndown($burndown_data)->display();
            }
        } else {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_permission_denied')
            );
        }
    }

    /**
     * Export form element properties into a SimpleXMLElement
     *
     * @param SimpleXMLElement &$root The root element of the form element
     *
     * @return void
     */
    public function exportPropertiesToXML(&$root) {
        $child = $root->addChild('properties');

        $child->addAttribute('use_cache', '1');
    }

    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset) {
        $classname_with_namespace = 'Tuleap\Tracker\REST\Artifact\ArtifactFieldValueRepresentation';
        $artifact = $changeset->getArtifact();

        try{
            $start_date = $this->getBurndownStartDate($artifact, $user);
        } catch (Tracker_FormElement_Field_BurndownException $ex) {
            $start_date = null;
        }

        try{
            $duration = $this->getBurndownDuration($artifact, $user);
        } catch (Tracker_FormElement_Field_BurndownException $ex) {
            $duration = null;
        }

        $artifact_field_value_representation = new $classname_with_namespace;
        $artifact_field_value_representation->build(
            $this->getId(),
            $this->getLabel(),
            $this->getBurndownData(
                $artifact,
                $user,
                $start_date,
                $duration
            )->getRESTRepresentation()
        );

        return $artifact_field_value_representation;
    }

    /**
     * @param PFUser $user
     * @param Tracker_Artifact $artifact
     * @return Tracker_Chart_Data_Burndown
     */
    private function buildBurndownData(PFUser $user, Tracker_Artifact $artifact) {
        $start_date = $this->getBurndownStartDate($artifact, $user);
        $duration   = $this->getBurndownDuration($artifact, $user);

        return $this->getBurndownData(
            $artifact,
            $user,
            $start_date,
            $duration
        );
    }

    /**
     *
     * @param Tracker_Artifact $artifact
     * @param PFUser $user
     * @param int $start_date
     * @param int $duration
     *
     * @return Tracker_Chart_Data_Burndown
     * @throws BurndownCacheIsCurrentlyCalculatedException
     */
    public function getBurndownData(Tracker_Artifact $artifact, PFUser $user, $start_date, $duration)
    {
        $capacity = null;
        if ($this->doesCapacityFieldExist()) {
            $capacity = $this->getCapacity($artifact);
        }
        $time_period   = new TimePeriodWithoutWeekEnd($start_date, $duration);
        $burndown_data = new Tracker_Chart_Data_Burndown($time_period, $capacity);

        $this->addRemainingEffortData($burndown_data, $time_period, $artifact, $user, $start_date);

        if ($this->isCacheCompleteForBurndown($burndown_data, $artifact, $user) === false
            && $this->isCacheBurndownAlreadyAsked($artifact) === false
        ) {
            $this->forceBurndownCacheGeneration($artifact->getId());
            $burndown_data->setIsBeingCalculated(true);
        } else if ($this->isCacheBurndownAlreadyAsked($artifact)) {
            $burndown_data->setIsBeingCalculated(true);
        }

        return $burndown_data;
    }

    private function forceBurndownCacheGeneration($artifact_id)
    {
        $this->getSystemEventManager()->createEvent(
            'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_GENERATE::NAME,
            $artifact_id.SystemEvent::PARAMETER_SEPARATOR ,
            SystemEvent::PRIORITY_MEDIUM,
            SystemEvent::OWNER_APP
        );
    }

    public function isCacheBurndownAlreadyAsked(Tracker_Artifact $artifact)
    {
        return $this->getSystemEventManager()->isThereAnEventAlreadyOnGoingMatchingFirstParameter(
            'Tuleap\\Tracker\\FormElement\\SystemEvent\\' . SystemEvent_BURNDOWN_GENERATE::NAME, $artifact->getId()
        );
    }

    private function getSystemEventManager()
    {
        return SystemEventManager::instance();
    }

    private function isCacheCompleteForBurndown(
        Tracker_Chart_Data_Burndown $burndown_data,
        Tracker_Artifact $artifact,
        PFUser $user
    ) {
        $days = $burndown_data->getTimePeriod()->getCountDayUntilDate($_SERVER['REQUEST_TIME']);

        if ($this->hasRemainingEffort($artifact->getTracker())
            && $this->hasStartDate($artifact, $user)) {
            $cached_days = $this->getComputedDao()->getCachedDays(
                $artifact->getId(),
                $this->getBurndownRemainingEffortField($artifact, $user)->getId()
            );

            if ($this->isTodayAWeekDayAndIsTodayBeforeTimePeriodEnd($burndown_data)) {
                return $this->compareCachedDaysWhenLastDayIsAComputedValue((int) $cached_days['cached_days'], $days);
            } else {
                return $this->compareCachedDaysWithPeriodDays((int) $cached_days['cached_days'], $days);
            }
        }

        return true;
    }

    private function isTodayAWeekDayAndIsTodayBeforeTimePeriodEnd(Tracker_Chart_Data_Burndown $burndown_data)
    {
        return $burndown_data->getTimePeriod()->isTodayWithinTimePeriod()
            && $burndown_data->getTimePeriod()->isNotWeekendDay($_SERVER['REQUEST_TIME']);
    }

    private function compareCachedDaysWhenLastDayIsAComputedValue($cache_days, $number_of_days_for_period)
    {
        return $cache_days === $number_of_days_for_period -1;
    }

    private function compareCachedDaysWithPeriodDays($cache_days, $number_of_days_for_period)
    {
        return $cache_days === $number_of_days_for_period;
    }

    private function addRemainingEffortData(
        Tracker_Chart_Data_Burndown $burndown_data,
        TimePeriodWithoutWeekEnd $time_period,
        Tracker_Artifact $artifact,
        PFUser $user,
        $start_date
    ) {
        $field = $this->getBurndownRemainingEffortField($artifact, $user);
        if (! $field) {
            return;
        }

        $date = new DateTime();
        $date->setTimestamp($start_date);

        $tonight = new DateTime();
        $tonight->setTime(0, 0, 0);

        $offset_days = 0;

        $artifact_list = array($artifact);
        while($offset_days <= $time_period->getDuration()) {
            if ($time_period->isNotWeekendDay($date->getTimestamp()) && $date < $tonight) {
                $remaining_effort = $field->getCachedValue(new Tracker_UserWithReadAllPermission($user), $artifact, $date->getTimestamp());
                $burndown_data->addEffortAt($offset_days, $remaining_effort);
                $offset_days++;
            }

            if ($date >= $tonight) {
                $remaining_effort =  $field->getComputedValue($user, $artifact, $date->getTimestamp(), $artifact_list, true);
                $burndown_data->addEffortAt($offset_days, $remaining_effort);

                break;
            }

            $date->modify('+1 day');
        }
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    public function fetchSubmit() {
        return '';
    }

    /**
     * Fetch the element for the submit masschange form
     *
     * @return string html
     */
    public function fetchSubmitMasschange() {
    }


    /**
     * @return bool
     */
    public function doesCapacityFieldExist() {
        if ($this->getCapacityField() === null) {
            return false;
        }
        return true;
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
    public function fetchMailArtifactValue(Tracker_Artifact $artifact, PFUser $user, Tracker_Artifact_ChangesetValue $value = null, $format='text') {
        $purifier = Codendi_HTMLPurifier::instance();
        $output   = '';
        if ($format == Codendi_Mail::FORMAT_HTML) {
            $output .= '<img src="'.get_server_url().$this->getBurndownImageUrl($artifact).'" alt="'.$purifier->purify($this->getLabel()).'" width="640" height="480" />';
            $output .= '<p><em>'.$GLOBALS['Language']->getText('plugin_tracker', 'burndown_email_as_of_today').'</em></p>';
        }
        return $output;
    }

    /**
     * Display the html field in the admin ui
     * @return string html
     */
    public function fetchAdminFormElement() {
        $html  = '';
        $html .= $this->fetchErrors();
        $html .= $this->fetchWarnings();
        $html .= '<img src="'. TRACKER_BASE_URL .'/images/fake-burndown-admin.png" />';
        return $html;
    }


    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return true if Tracler is ok
     */
    public function testImport() {
        return true;
    }

    public function getCriteriaFrom($criteria) {
    }

    public function getCriteriaWhere($criteria) {
    }

    public function getQuerySelect() {
    }

    public function getQueryFrom() {
    }

    public function fetchChangesetValue($artifact_id, $changeset_id, $value, $report=null, $from_aid = null) {
    }

    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report) {
    }

    public function fetchCriteriaValue($criteria) {
    }

    public function fetchRawValue($value) {
    }

    public function afterCreate() {
    }

    public function fetchFollowUp($artifact, $from, $to) {
    }

    public function fetchRawValueFromChangeset($changeset) {
    }

    public function getChangesetValue($changeset, $value_id, $has_changed) {
    }

    public function getSoapAvailableValues() {
    }

    protected function saveValue($artifact, $changeset_value_id, $value, Tracker_Artifact_ChangesetValue $previous_changesetvalue = null) {
    }

    protected function keepValue($artifact, $changeset_value_id, Tracker_Artifact_ChangesetValue $previous_changesetvalue) {
    }

    protected function getCriteriaDao() {
    }

    protected function fetchSubmitValue() {
    }

    protected function fetchSubmitValueMasschange() {
    }

    protected function getValueDao() {
    }

    /**
     * Returns the children of the burndown field tracker.
     *
     * @return array of Tracker
     */
    protected function getChildTrackers() {
        return $this->getHierarchyFactory()->getChildren($this->getTrackerId());
    }

    /**
     * Display a png image with the given error message
     *
     * @param String $msg
     */
    protected function displayErrorImage($msg) {
        $error = new ErrorChart($GLOBALS['Language']->getText('plugin_tracker', 'unable_to_render_the_chart'), $msg, 640, 480);
        $error->Stroke();
    }

    /**
     * Returns a Burndown rendering object for given data
     *
     * @param Tracker_Chart_Data_Burndown $burndown_data
     *
     * @return \Tracker_Chart_BurndownView
     */
    protected function getBurndown(Tracker_Chart_Data_Burndown $burndown_data) {
        return new Tracker_Chart_BurndownView($burndown_data);
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

    /**
     * Validate a value
     *
     * @param Tracker_Artifact $artifact The artifact
     * @param mixed            $value    data coming from the request.
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Tracker_Artifact $artifact, $value) {
        //No need to validate artifact id (read only for all)
        return true;
    }

    /**
     * Return the relative url to the burndown chart image.
     *
     * @param Tracker_Artifact $artifact
     *
     * @return String
     */
    private function getBurndownImageUrl(Tracker_Artifact $artifact) {
        $url_query = http_build_query(array('formElement' => $this->getId(),
            'func'        => self::FUNC_SHOW_BURNDOWN,
            'src_aid'     => $artifact->getId()));
        return TRACKER_BASE_URL .'/?'.$url_query;
    }

    private function getBurndownRemainingEffortField(Tracker_Artifact $artifact, PFUser $user) {
        return $this->getFormElementFactory()->getComputableFieldByNameForUser($artifact->getTracker()->getId(), self::REMAINING_EFFORT_FIELD_NAME, $user);
    }

    /**
     * @throws Tracker_FormElement_Field_BurndownException
     */
    private function getBurndownStartDateField(Tracker_Artifact $artifact, PFUser $user) {
        $form_element_factory = $this->getFormElementFactory();
        $start_date_field     = $form_element_factory->getUsedFieldByNameForUser(
            $artifact->getTracker()->getId(),
            self::START_DATE_FIELD_NAME,
            $user
        );

        if (! $start_date_field) {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_start_date_warning')
            );
        }

        return $start_date_field;
    }

    /**
     * Returns the sprint start_date as a Timestamp field value of given artifact
     *
     * @param Tracker_Artifact $artifact
     *
     * @return Integer
     *
     * @throws Tracker_FormElement_Field_BurndownException
     */
    private function getBurndownStartDate(Tracker_Artifact $artifact, PFUser $user) {
        $start_date_field = $this->getBurndownStartDateField($artifact, $user);
        $timestamp        = $artifact->getValue($start_date_field)->getTimestamp();

        if (! $timestamp) {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_start_date_warning')
            );
        }

        return $timestamp;
    }

    private function hasStartDate(Tracker_Artifact $artifact, PFUser $user) {
        $start_date_field = $this->getBurndownStartDateField($artifact, $user);
        $timestamp        = $artifact->getValue($start_date_field)->getTimestamp();

        return $timestamp !== null;
    }

    /**
     * @throws Tracker_FormElement_Field_BurndownException
     */
    private function getBurndownDurationField(Tracker_Artifact $artifact, PFUser $user) {
        $field = $this->getFormElementFactory()->getUsedFieldByNameForUser($artifact->getTracker()->getId(), self::DURATION_FIELD_NAME, $user);

        if (! $field) {
            throw new Tracker_FormElement_Field_BurndownException(
                $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_duration_warning')
            );
        }

        return $field;
    }

    /**
     * @param Tracker_Artifact $artifact
     *
     * @return null|int
     */
    public function getCapacity(Tracker_Artifact $artifact) {
        $field = $this->getCapacityField();
        if (! $field) {
            return null;
        }

        $changeset_value = $artifact->getValue($field);
        if (! $changeset_value) {
            return 0;
        }

        return $changeset_value->getValue();
    }

    private function getCapacityField() {
        return $this->getFormElementFactory()->getUsedFieldByName(
            $this->getTrackerId(),
            self::CAPACITY_FIELD_NAME
        );
    }

    /**
     * Returns the sprint duration for burndown rendering
     *
     * @param Tracker_Artifact $artifact
     *
     * @return Integer
     *
     * @throws Tracker_FormElement_Field_BurndownException
     */
    private function getBurndownDuration(Tracker_Artifact $artifact, PFUser $user) {
        $field    = $this->getBurndownDurationField($artifact, $user);
        $duration = $artifact->getValue($field)->getValue();

        if ($duration <= 0) {
            throw new Tracker_FormElement_Field_BurndownException($GLOBALS['Language']->getText('plugin_tracker', 'burndown_empty_duration_warning'));
        }

        return $duration;
    }

    /**
     * Renders all the possible errors for this field.
     *
     * @return String
     */
    private function fetchErrors() {
        $errors  = '';

        if ($errors) {
            return '<ul class="feedback_error">'.$errors.'</ul>';
        }
    }

    /**
     * Renders all the possible warnings for this field.
     *
     * @return String
     */
    private function fetchWarnings() {
        $warnings  = '';
        $warnings .= $this->fetchMissingFieldWarning(self::START_DATE_FIELD_NAME, 'date');
        $warnings .= $this->fetchMissingFieldWarning(self::DURATION_FIELD_NAME, 'int');
        $warnings .= $this->fetchMissingRemainingEffortWarning();

        if ($warnings) {
            return '<ul class="feedback_warning">'.$warnings.'</ul>';
        }
    }

    /**
     * Renders a warning concerning some missing field in the tracker.
     *
     * @param String $name
     * @param String $type
     * @return String
     */
    private function fetchMissingFieldWarning($name, $type) {
        if (! $this->getTracker()->hasFormElementWithNameAndType($name, $type)) {
            $key     = "burndown_missing_${name}_warning";
            $warning = $GLOBALS['Language']->getText('plugin_tracker', $key);

            return '<li>'.$warning.'</li>';
        }
    }

    /**
     * Renders a warning concerning some child tracker(s) missing a remaining
     * effort field.
     *
     * @return String
     */
    private function fetchMissingRemainingEffortWarning() {
        $tracker_links = implode(', ', $this->getLinksToChildTrackersWithoutRemainingEffort());

        if ($tracker_links) {
            $warning = $GLOBALS['Language']->getText('plugin_tracker', 'burndown_missing_remaining_effort_warning');
            return "<li>$warning $tracker_links.</li>";
        }
    }

    /**
     * Returns the names of child trackers missing a remaining effort.
     *
     * @return array of String
     */
    private function getLinksToChildTrackersWithoutRemainingEffort() {
        return array_map(array($this, 'getLinkToTracker'),
            $this->getChildTrackersWithoutRemainingEffort());
    }

    /**
     * Renders a link to the given tracker.
     *
     * @param Tracker $tracker
     * @return String
     */
    private function getLinkToTracker(Tracker $tracker) {
        $tracker_id   = $tracker->getId();
        $tracker_name = $tracker->getName();
        $tracker_url  = TRACKER_BASE_URL."/?tracker=$tracker_id&func=admin-formElements";

        $hp = Codendi_HTMLPurifier::instance();
        return '<a href="'.$tracker_url.'">'.$hp->purify($tracker_name).'</a>';
    }

    /**
     * Returns the child trackers missing a remaining effort.
     *
     * @return array of Tracker
     */
    private function getChildTrackersWithoutRemainingEffort() {
        return array_filter($this->getChildTrackers(),
            array($this, 'missesRemainingEffort'));
    }

    /**
     * Returns true if the given tracker misses a remaining effort field.
     *
     * @param Tracker $tracker
     * @return Boolean
     */
    private function missesRemainingEffort(Tracker $tracker) {
        return ! $this->hasRemainingEffort($tracker);
    }

    /**
     * Returns true if the given tracker has a remaining effort field.
     *
     * @param Tracker $tracker
     * @return Boolean
     */
    private function hasRemainingEffort(Tracker $tracker) {
        return $tracker->hasFormElementWithNameAndType(self::REMAINING_EFFORT_FIELD_NAME, array('int', 'float', 'computed'));
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor) {
        return $visitor->visitBurndown($this);
    }

    /**
     * @return Tracker_FormElement_Field_BurndownDao The dao
     */
    protected function getDao() {
        return new Tracker_FormElement_Field_BurndownDao();
    }
    /**
     * Return the Field_Date_Dao
     *
     * @return Tracker_FormElement_Field_ComputedDao The dao
     */
    protected function getComputedDao() {
        return new Tracker_FormElement_Field_ComputedDao();
    }

    public function canBeUsedAsReportCriterion() {
        return false;
    }

    /**
     * @see Tracker_FormElement_Field::postSaveNewChangeset()
     */
    public function postSaveNewChangeset(
        Tracker_Artifact $artifact,
        PFUser $submitter,
        Tracker_Artifact_Changeset $new_changeset,
        Tracker_Artifact_Changeset $previous_changeset = null
    ) {

        try {
            $start_date_field = $this->getBurndownStartDateField($artifact, $submitter);
            $duration_field   = $this->getBurndownDurationField($artifact, $submitter);

            if (
                $previous_changeset !== null &&
                $this->isCacheBurndownAlreadyAsked($artifact) === false &&
                $this->getBurndownRemainingEffortField($artifact, $submitter)
            ) {
                if ($this->hasFieldChanged($new_changeset, $start_date_field)
                    || $this->hasFieldChanged($new_changeset, $duration_field)
                ) {
                    $this->forceBurndownCacheGeneration($artifact->getId());
                }
            }
        } catch (Tracker_FormElement_Field_BurndownException $e) {
        }
    }

    private function hasFieldChanged(
        Tracker_Artifact_Changeset $new_changeset,
        Tracker_FormElement_Field $field
    ) {
        return $new_changeset->getValue($field) && $new_changeset->getValue($field)->hasChanged();
    }
}