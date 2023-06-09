<?php
/**
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Option\Option;
use Tuleap\Project\REST\UserGroupRepresentation;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\File\CreatedFileURLMapping;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\ChangesChecker;
use Tuleap\Tracker\FormElement\Field\PermissionsOnArtifact\PermissionsOnArtifactFieldValueDao;
use Tuleap\Tracker\FormElement\PermissionsOnArtifactUGroupRetriever;
use Tuleap\Tracker\FormElement\PermissionsOnArtifactUsageFormatter;
use Tuleap\Tracker\FormElement\PermissionsOnArtifactValidator;
use Tuleap\Tracker\Report\Query\ParametrizedFrom;
use Tuleap\Tracker\Report\Query\ParametrizedSQLFragment;
use Tuleap\Tracker\REST\v1\TrackerFieldsRepresentations\PermissionsOnArtifacts;
use Tuleap\User\UserGroup\NameTranslator;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Tracker_FormElement_Field_PermissionsOnArtifact extends Tracker_FormElement_Field
{
    public const GRANTED_GROUPS     = 'granted_groups';
    public const USE_IT             = 'use_artifact_permissions';
    public const IS_USED_BY_DEFAULT = false;
    public const PERMISSION_TYPE    = 'PLUGIN_TRACKER_ARTIFACT_ACCESS';

    public $default_properties = [];


    /**
     * Returns the default value for this field, or nullif no default value defined
     *
     * @return mixed The default value for this field, or null if no default value defined
     */
    public function getDefaultValue()
    {
    }

    /**
     * The field is permanently deleted from the db
     * This hooks is here to delete specific properties,
     * or specific values of the field.
     * (The field itself will be deleted later)
     *
     * @return bool true if success
     */
    public function delete()
    {
        return true;
    }

    /**
     * @return string
     */
    private function fetchChangesetRegardingPermissions($artifact_id, $changeset_id)
    {
        $values   = [];
        $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
        if ($artifact->useArtifactPermissions()) {
            $dao = new Tracker_Artifact_Changeset_ValueDao();
            $row = $dao->searchByFieldId($changeset_id, $this->id);
            if ($row === null) {
                return '';
            }
            $changeset_value_id = $row['id'];

            foreach ($this->getValueDao()->searchById($changeset_value_id) ?: [] as $value) {
                $values[] = \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) $value['ugroup_name']);
            }

            return implode(',', $values);
        }
        return '';
    }

    public function fetchChangesetValue(
        int $artifact_id,
        int $changeset_id,
        mixed $value,
        ?Tracker_Report $report = null,
        ?int $from_aid = null,
    ): string {
        return $this->fetchChangesetRegardingPermissions($artifact_id, $changeset_id);
    }

    /**
     * @return string
     */
    public function fetchCSVChangesetValue($artifact_id, $changeset_id, $value, $report)
    {
        return $this->fetchChangesetRegardingPermissions($artifact_id, $changeset_id);
    }

    /**
     * Fetch the value
     * @param mixed $value the value of the field
     * @return string
     */
    public function fetchRawValue($value)
    {
        return $this->values[$value]->getLabel();
    }

    /**
     * Fetch the value in a specific changeset
     * @param Tracker_Artifact_Changeset $changeset
     * @return string
     */
    public function fetchRawValueFromChangeset($changeset)
    {
        return '';
    }

   /**
    * Returns the PermissionsOnArtifactDao
    *
    * @return PermissionsOnArtifactFieldValueDao The dao
    */
    protected function getValueDao()
    {
        return new PermissionsOnArtifactFieldValueDao();
    }

    private function getPermissionsOnArtifactUsageRetriever()
    {
        return new PermissionsOnArtifactUsageFormatter($this->getPermissionsValidator());
    }

    /**
     * @param array $submitted_values
     *
     * @return string html
     */
    protected function fetchSubmitValue(array $submitted_values)
    {
        $value = $this->getValueFromSubmitOrDefault($submitted_values);
        $value = $this->getPermissionsOnArtifactUGroupRetriever()->initializeUGroupsIfNoUGroupsAreChoosen($value);

        $is_disabled = false;
        $is_checked  = ($this->getPermissionsValidator()->isArtifactPermissionChecked($value) === true);

        return $this->getArtifactValueHTML($this->getId(), $is_checked, $is_disabled);
    }

    /**
     * @return string
     */
    protected function fetchSubmitValueMasschange()
    {
        $is_checked  = false;
        $is_disabled = false;

        return $this->getArtifactValueHTML($this->getId(), $is_checked, $is_disabled);
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
        $is_read_only = false;
        return $this->fetchArtifactValueCommon($is_read_only, $artifact, $value, $submitted_values);
    }

    /**
     * Fetch the field value in artifact to be displayed in mail
     *
     * @param Artifact                        $artifact The artifact
     * @param PFUser                          $user     The user who will receive the email
     * @param bool                            $ignore_perms
     * @param Tracker_Artifact_ChangesetValue $value    The actual value of the field
     * @param string                          $format   mail format
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
        $output    = '';
        $separator = '&nbsp;';
        if ($format == 'text') {
            $separator = PHP_EOL;
            $output   .= dgettext('tuleap-tracker', 'Restrict access to this artifact for the following user groups:');
        }

        $ugroups = permission_fetch_selected_ugroups(self::PERMISSION_TYPE, $artifact->getId(), $this->getTracker()->getGroupId());
        $output .= $separator . implode(', ', $ugroups);
        return $output;
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
        $is_read_only = true;
        return $this->fetchArtifactValueCommon($is_read_only, $artifact, $value, []);
    }

    public function fetchArtifactValueWithEditionFormIfEditable(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        return $this->fetchArtifactValueReadOnly($artifact, $value) . $this->getHiddenArtifactValueForEdition(
            $artifact,
            $value,
            $submitted_values
        );
    }

    protected function getHiddenArtifactValueForEdition(
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        $is_field_frozen = $this->getFrozenFieldDetector()->isFieldFrozen($artifact, $this);

        return '<div class="tracker_hidden_edition_field" data-field-id="' . $this->getId() . '">' .
                $this->fetchArtifactValueCommon($is_field_frozen, $artifact, $value, $submitted_values) .
            '</div>';
    }

    private function getArtifactValueHTML($artifact_id, $can_user_restrict_permissions_to_nobody, $is_read_only)
    {
        $changeset_values   = $this->getLastChangesetValues($artifact_id);
        $is_expecting_input = $this->isRequired() && empty($changeset_values);

        $html  = $this->fetchRestrictCheckbox($can_user_restrict_permissions_to_nobody, $is_read_only, $is_expecting_input);
        $html .= $this->fetchUserGroupList($is_read_only, $changeset_values);

        return $html;
    }

    private function fetchUserGroupList($is_read_only, array $changeset_values)
    {
        $field_id     = $this->getId();
        $element_name = 'artifact[' . $field_id . '][u_groups][]';

        $hp    = Codendi_HTMLPurifier::instance();
        $html  = '<select '
            . 'name="' . $hp->purify($element_name) . '" '
            . 'id="' . $hp->purify(str_replace('[]', '', $element_name)) . '" '
            . 'multiple '
            . 'size="8" '
            . (($this->isRequired()) ? 'required="required"' : '' )
            . (($is_read_only) ? 'disabled="disabled"' : '' )
            . '>';
        $html .= $this->getOptions($this->getAllUserGroups(), $changeset_values);
        $html .= '</select>';

        return $html;
    }

    private function getLastChangesetValues($artifact_id)
    {
        $user_group_ids = [];

        $db_res = permission_db_authorized_ugroups(self::PERMISSION_TYPE, $artifact_id);
        while ($row = db_fetch_array($db_res)) {
            $user_group_ids[] = $row['ugroup_id'];
        }

        return $user_group_ids;
    }

    /**
     * @see fetchArtifactValueReadOnly
     * @see fetchArtifactValue
     *
     * @param bool                            $is_read_only
     * @param array                           $submitted_values
     *
     * @return string html
     */
    protected function fetchArtifactValueCommon(
        $is_read_only,
        Artifact $artifact,
        ?Tracker_Artifact_ChangesetValue $value,
        array $submitted_values,
    ) {
        if (isset($submitted_values[$this->getId()]) && is_array($submitted_values[$this->getId()])) {
            $is_checked = $this->getPermissionsValidator()->isArtifactPermissionChecked($submitted_values[$this->getId()]);
        } else {
            $is_checked = $artifact->useArtifactPermissions();
        }

        return $this->getArtifactValueHTML($artifact->getId(), $is_checked, $is_read_only);
    }

    /**
     * @return string
     */
    protected function fetchAdminFormElement()
    {
        $changeset_values = $this->getLastChangesetValues(0);

        $html  = $this->fetchRestrictCheckbox(false, true, false);
        $html .= $this->fetchUserGroupList(true, $changeset_values);

        return $html;
    }

    public static function getFactoryLabel()
    {
        return dgettext('tuleap-tracker', 'Permissions on artifact');
    }

    public static function getFactoryDescription()
    {
        return dgettext('tuleap-tracker', 'Allow user to give permissions to an artifact');
    }

    public static function getFactoryIconUseIt()
    {
        return $GLOBALS['HTML']->getImagePath('ic/lock.png');
    }

    public static function getFactoryIconCreate()
    {
        return $GLOBALS['HTML']->getImagePath('ic/lock--plus.png');
    }

    /**
     * @return bool say if the field is a unique one
     */
    public static function getFactoryUniqueField()
    {
        return true;
    }

    /**
     * Fetch the html code to display the field value in tooltip
     *
     * @param Tracker_Artifact_ChangesetValue_PermissionsOnArtifact $value The changeset value for this field
     * @return string
     */
    protected function fetchTooltipValue(Artifact $artifact, ?Tracker_Artifact_ChangesetValue $value = null)
    {
        $html = '';
        if ($value && $artifact->useArtifactPermissions()) {
            $ugroup_dao = $this->getUGroupDao();

            $perms      = $value->getPerms();
            $perms_name = [];
            foreach ($perms as $perm) {
                $row          = $ugroup_dao->searchByUGroupId($perm)->getRow();
                $perms_name[] = \Tuleap\User\UserGroup\NameTranslator::getUserGroupDisplayKey((string) $row['name']);
            }
            $html .= implode(",", $perms_name);
        }
        return $html;
    }

   /**
    * Returns the UGroupDao
    *
    * @return UGroupDao The dao
    */
    protected function getUGroupDao()
    {
        return new UGroupDao(CodendiDataAccess::instance());
    }

    public function getCriteriaFrom(Tracker_Report_Criteria $criteria): Option
    {
        //Only filter query if field is used
        if ($this->isUsed()) {
            $criteria_value = $this->getCriteriaValue($criteria);
            if ($criteria_value && count($criteria_value) === 1 && array_key_exists("100", $criteria_value)) {
                $a    = 'A_' . $this->id;
                $b    = 'B_' . $this->id;
                 $sql = " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = ?)
                          INNER JOIN tracker_artifact AS $b ON ($b.last_changeset_id = $a.changeset_id AND
                            $b.use_artifact_permissions = 0) ";

                 return Option::fromValue(new ParametrizedFrom($sql, [$this->id]));
            } elseif ($criteria_value) {
                $a = 'A_' . $this->id;
                $b = 'B_' . $this->id;
                $c = 'C_' . $this->id;

                $in = \ParagonIE\EasyDB\EasyStatement::open()->in('?*', array_keys($criteria_value));

                $sql = " INNER JOIN tracker_changeset_value AS $a ON ($a.changeset_id = c.id AND $a.field_id = ?)
                         INNER JOIN tracker_changeset_value_permissionsonartifact AS $b ON ($b.changeset_value_id = $a.id
                            AND $b.ugroup_id IN ($in)
                      )";

                return Option::fromValue(new ParametrizedFrom($sql, [$this->id, ...$in->values()]));
            }
        }

        return Option::nothing(ParametrizedFrom::class);
    }

    /**
     * Get the "from" statement to retrieve field values
     * You can join on artifact AS a, tracker_changeset AS c
     * which tables used to retrieve the last changeset of matching artifacts.
     * @return string
     */

    public function getQueryFrom()
    {
        return '';
    }

     /**
     * @see getQueryFrom
     */
    public function getQuerySelect(): string
    {
        return '';
    }

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

        if (isset($this->criteria_value[$criteria->report->id]) && $this->criteria_value[$criteria->report->id]) {
            $values                                      = $this->criteria_value[$criteria->report->id];
            $this->criteria_value[$criteria->report->id] = [];

            foreach ($values as $value) {
                foreach ($value as $v) {
                    if ($v != '') {
                        $this->criteria_value[$criteria->report->id][$v] = $value;
                    } else {
                        return '';
                    }
                }
            }
        } elseif (! isset($this->criteria_value[$criteria->report->id])) {
            $this->criteria_value[$criteria->report->id] = [];
            $dao                                         = $this->getCriteriaDao();
            if ($dao !== null) {
                foreach ($dao->searchByCriteriaId($criteria->id) as $row) {
                    $this->criteria_value[$criteria->report->id][$row['value']] = $row;
                }
            }
        }

        return $this->criteria_value[$criteria->report->id];
    }

    public function exportCriteriaValueToXML(Tracker_Report_Criteria $criteria, SimpleXMLElement $xml_criteria)
    {
        return;
    }

    public function getCriteriaWhere(Tracker_Report_Criteria $criteria): Option
    {
        return Option::nothing(ParametrizedSQLFragment::class);
    }

    public function fetchCriteriaValue($criteria)
    {
        $html           = '';
        $criteria_value = $this->getCriteriaValue($criteria);
        $multiple       = ' ';
        $size           = ' ';
        $name           = "criteria[$this->id][values][]";

        $user_groups = $this->getAllUserGroups();

        if (! $user_groups) {
            $html .= "<p><b>" . $GLOBALS['Language']->getText('global', 'error') . "</b>: " . $GLOBALS['Language']->getText('project_admin_permissions', 'perm_type_not_def', '');
            return $html;
        }

        if ($criteria->is_advanced) {
            $multiple = ' multiple="multiple" ';
            $size     = ' size="' . min(7, count($user_groups) + 2) . '" ';
        }

        $html .= '<select id="tracker_report_criteria_' . ($criteria->is_advanced ? 'adv_' : '') . $this->id . '"
                          name="' . $name . '" ' .
                          $size .
                          $multiple . '>';
        //Any value
        $selected = ($criteria_value !== '' && count($criteria_value)) ? '' : 'selected="selected"';
        $html    .= '<option value="" ' . $selected . '>' . $GLOBALS['Language']->getText('global', 'any') . '</option>';
        //None value
        $selected = isset($criteria_value[100]) ? 'selected="selected"' : '';
        $html    .= '<option value="100" ' . $selected . '>' . $GLOBALS['Language']->getText('global', 'none') . '</option>';

        if (! is_array($criteria_value)) {
            $criteria_value = [];
        }

        $html .= $this->getOptions($user_groups, array_keys($criteria_value));
        $html .= '</select>';
        return $html;
    }

    private function getOptions($user_groups, $selected_ids = [])
    {
        $options = '';
        foreach ($user_groups as $user_group) {
            $id       = $user_group->getId();
            $selected = (in_array($id, $selected_ids)) ? 'selected="selected"' : '';
            $options .= '<option value="' . $id . '" ' . $selected . '>';
            $options .= NameTranslator::getUserGroupDisplayName($user_group->getName());
            $options .= '</option>';
        }

        return $options;
    }

    /**
     * @return ProjectUGroup[]
     */
    private function getAllUserGroups()
    {
        $user_groups     = [];
        $permission_type = self::PERMISSION_TYPE;

        $sql = "SELECT ugroup_id FROM permissions_values WHERE permission_type='$permission_type'";
        $res = db_query($sql);

        $predefined_ugroups = '';
        if (db_numrows($res) < 1) {
            return $user_groups;
        } else {
            while ($row = db_fetch_array($res)) {
                if ($predefined_ugroups) {
                    $predefined_ugroups .= ' ,';
                }
                $predefined_ugroups .= db_ei($row['ugroup_id']);
            }
        }

        $sql = "SELECT * FROM ugroup WHERE group_id=" . db_ei($this->getTracker()->getGroupId()) . " OR ugroup_id IN (" . $predefined_ugroups . ") ORDER BY ugroup_id";
        $res = db_query($sql);

        while ($row = db_fetch_array($res)) {
            $user_groups[] = new ProjectUGroup($row);
        }

        return $user_groups;
    }

    protected function getCriteriaDao()
    {
        return new Tracker_Report_Criteria_PermissionsOnArtifact_ValueDao();
    }

    /**
     * @param mixed            $value
     *
     * @return bool true if the value is considered ok
     */
    protected function validate(Artifact $artifact, $value)
    {
        if ($this->getPermissionsValidator()->isArtifactPermissionChecked($value) === true) {
            return $this->getPermissionsValidator()->isNoneGroupSelected($value) === false;
        }

        return $this->getPermissionsValidator()->hasAGroupSelected($value);
    }

    /**
     * @param mixed $submitted_value
     *
     */
    public function validateFieldWithPermissionsAndRequiredStatus(
        Artifact $artifact,
        $submitted_value,
        PFUser $user,
        ?Tracker_Artifact_ChangesetValue $last_changeset_value = null,
        ?bool $is_submission = null,
    ): bool {
        if ($last_changeset_value === null && $this->isRequired() === true && $this->isAtLeastOneUGroupSelected($submitted_value) === false) {
            $this->addRequiredError();

            return false;
        }

        if ($this->isSelectBoxChecked($submitted_value) === false) {
            return true;
        }

        if ($this->isAtLeastOneUGroupSelected($submitted_value) === false) {
            $this->addRequiredError();

            return false;
        }

        return true;
    }

    private function isAtLeastOneUGroupSelected($submitted_value)
    {
        return isset($submitted_value['u_groups']) === true && count($submitted_value['u_groups']) > 0;
    }

    private function isSelectBoxChecked($submitted_value): bool
    {
        return (isset($submitted_value['use_artifact_permissions']) === true && (bool) $submitted_value['use_artifact_permissions'] === true);
    }

    private function getPermissionsValidator()
    {
        return new PermissionsOnArtifactValidator();
    }

    private function getPermissionsOnArtifactUGroupRetriever()
    {
        return new PermissionsOnArtifactUGroupRetriever();
    }

    protected function saveValue(
        $artifact,
        $changeset_value_id,
        $value,
        ?Tracker_Artifact_ChangesetValue $previous_changesetvalue,
        CreatedFileURLMapping $url_mapping,
    ) {
        $value = $this->getPermissionsOnArtifactUsageRetriever()->setRestrictAccessForArtifact($value, $this);
        $value = $this->getPermissionsOnArtifactUGroupRetriever()->initializeUGroupsIfNoUGroupsAreChoosenWithRequiredCondition($value, $this);
        $value = $this->getPermissionsOnArtifactUsageRetriever()->alwaysUseRestrictedPermissionsForRequiredField($value, $this);

        $artifact->setUseArtifactPermissions($value[self::USE_IT]);
        permission_clear_all($this->getTracker()->getGroupId(), self::PERMISSION_TYPE, $artifact->getId(), false);

        if (! empty($value['u_groups'])) {
            $this->addPermissions($value['u_groups'], $artifact->getId());

            return $this->getValueDao()->create($changeset_value_id, $value[self::USE_IT], $value['u_groups']);
        }

        return true;
    }

    /**
     * @see Tracker_FormElement_Field::hasChanges()
     */
    public function hasChanges(Artifact $artifact, Tracker_Artifact_ChangesetValue $old_value, $new_value)
    {
        assert($old_value instanceof Tracker_Artifact_ChangesetValue_PermissionsOnArtifact);
        return (new ChangesChecker())->hasChanges($old_value, $new_value);
    }

    /**
     * Get the value of this field
     *
     * @param Tracker_Artifact_Changeset $changeset   The changeset (needed in only few cases like 'lud' field)
     * @param int                        $value_id    The id of the value
     * @param bool $has_changed If the changeset value has changed from the previous one
     *
     * @return Tracker_Artifact_ChangesetValue or null if not found
     */
    public function getChangesetValue($changeset, $value_id, $has_changed)
    {
        $value_ids = $this->getValueDao()->searchById($value_id) ?: [];
        $ugroups   = [];

        foreach ($value_ids as $v) {
            $ugroups[(int) $v['ugroup_id']] = $v['ugroup_name'];
        }

        return new Tracker_Artifact_ChangesetValue_PermissionsOnArtifact($value_id, $changeset, $this, $has_changed, $changeset->getArtifact()->useArtifactPermissions(), $ugroups);
    }

    public function getFieldDataFromRESTValue(array $value, ?Artifact $artifact = null)
    {
        if (isset($value['value'][self::GRANTED_GROUPS])) {
            if (
                isset($value['value']['is_used_by_default']) === true
                && $value['value']['is_used_by_default'] === true
                && count($value['value'][self::GRANTED_GROUPS]) === 0
            ) {
                throw new Tracker_FormElement_InvalidFieldException(
                    'Permission field is required please choose a group in list'
                );
            }

            $user_groups = $this->getUserGroupsFromREST($value['value'][self::GRANTED_GROUPS]);

            return $this->getFieldDataFromArray($user_groups);
        }
        throw new Tracker_FormElement_InvalidFieldException(
            'Permission field values must be passed as an array of ugroup ids e.g. "value" : {"granted_groups" : [158, "142_3"]}'
        );
    }

    /**
     * @return int[]
     * @throws Tracker_FormElement_InvalidFieldException
     */
    private function getUserGroupsFromREST($user_groups)
    {
        if (! is_array($user_groups)) {
            throw new Tracker_FormElement_InvalidFieldException("'granted_groups' must be an array. E.g. [2, '124_3']");
        }

        $project_groups = [];
        foreach ($user_groups as $user_group) {
            try {
                UserGroupRepresentation::checkRESTIdIsAppropriate($user_group);
                $value = UserGroupRepresentation::getProjectAndUserGroupFromRESTId($user_group);

                if ($value['project_id'] && $value['project_id'] != $this->getTracker()->getProject()->getID()) {
                    throw new Tracker_FormElement_InvalidFieldException('Invalid value "' . $user_group . '" for field ' . $this->getId());
                }

                $project_groups[] = $value['user_group_id'];
            } catch (Exception $e) {
                if (is_numeric($user_group) && $user_group < ProjectUGroup::DYNAMIC_UPPER_BOUNDARY) {
                    $project_groups[] = $user_group;
                } else {
                    throw new Tracker_FormElement_InvalidFieldException($e->getMessage());
                }
            }
        }

        return $project_groups;
    }

    public function getFieldDataFromRESTValueByField(array $value, ?Artifact $artifact = null)
    {
        throw new Tracker_FormElement_RESTValueByField_NotImplementedException();
    }

     /**
     * Get the field data for artifact submission
     *
     * @param string $value
     *
     * @return mixed the field data corresponding to the value for artifact submission
     */
    public function getFieldData($value)
    {
        return $this->getFieldDataFromArray(explode(',', $value));
    }

    private function getFieldDataFromArray(array $values)
    {
        $ugroup_ids = array_filter(array_map('intval', $values));
        if (count($ugroup_ids) == 0) {
            return [
                self::USE_IT => 0,
                'u_groups'   => [],
            ];
        } else {
            return [
                self::USE_IT => 1,
                'u_groups'   => $ugroup_ids,
            ];
        }
    }

    /**
     * @return bool
     */
    protected function criteriaCanBeAdvanced()
    {
        return true;
    }

    /**
     * Adds permissions in the database
     *
     * @param Array $ugroups the list of ugroups
     * @param int          $artifact_id  The id of the artifact
     *
     * @return bool
     */
    public function addPermissions($ugroups, $artifact_id)
    {
        $pm              = PermissionsManager::instance();
        $permission_type = self::PERMISSION_TYPE;
        foreach ($ugroups as $ugroup) {
            if (! $pm->addPermission($permission_type, $artifact_id, $ugroup)) {
                return false;
            }
        }
        return true;
    }

    public function accept(Tracker_FormElement_FieldVisitor $visitor)
    {
        return $visitor->visitPermissionsOnArtifact($this);
    }

    /**
     * Return REST value of a field for a given changeset
     *
     *
     * @return mixed | null if no values
     */
    public function getRESTValue(PFUser $user, Tracker_Artifact_Changeset $changeset)
    {
        $value = $changeset->getValue($this);
        if ($value) {
            return $value->getFullRESTValue($user);
        }
    }

    /**
     * @return PermissionsOnArtifacts
     */
    public function getRESTAvailableValues()
    {
        $representation = new PermissionsOnArtifacts();
        $project_id     = $this->getTracker()->getGroupId();
        $representation->build($project_id, self::IS_USED_BY_DEFAULT, $this->getAllUserGroups());

        return $representation;
    }

    /**
     * @param bool $can_user_restrict_permissions_to_nobody
     * @param bool $disabled
     *
     * @return string
     */
    private function fetchRestrictCheckbox($can_user_restrict_permissions_to_nobody, $disabled, $is_expecting_input)
    {
        $empty_value_class = '';
        if ($is_expecting_input) {
            $empty_value_class = 'empty_value';
        }

        $html = '<p class="tracker_field_permissionsonartifact ' . $empty_value_class . '">';
        if ($this->isRequired() == false) {
            if (! $disabled) {
                $html .= '<input type="hidden" name="artifact[' . $this->getId() . '][use_artifact_permissions]" value="0" />';
            }
            $html .= '<label class="checkbox" for="artifact_' . $this->getId() . '_use_artifact_permissions">';
            $html .= '<input type="checkbox"
                        name="artifact[' . $this->getId() . '][use_artifact_permissions]"
                        id="artifact_' . $this->getId() . '_use_artifact_permissions"
                        value="1" ' .
                (($can_user_restrict_permissions_to_nobody == true) ? 'checked="checked"' : '') .
                (($disabled == true) ? 'disabled="disabled"' : '') .
                '/>';
        } else {
            $html .= '<input type="hidden" name="artifact[' . $this->getId(
            ) . '][use_artifact_permissions]" value="1" />';
        }

        $html .= dgettext('tuleap-tracker', 'Restrict access to this artifact for the following user groups:') . '</label>';
        $html .= '</p>';

        return $html;
    }

    public function isCSVImportable(): bool
    {
        return false;
    }
}
