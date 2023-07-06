<?php
/**
 * Copyright (c) Enalean, 2011 - Present. All Rights Reserved.
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
use Tuleap\Tracker\Artifact\ChangesetValue\AddDefaultValuesToFieldsData;
use Tuleap\Tracker\FormElement\Event\ImportExternalElement;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\RetrieveUsedArtifactLinkFields;
use Tuleap\Tracker\FormElement\Field\FieldDao;
use Tuleap\Tracker\FormElement\Field\RetrieveUsedFields;
use Tuleap\Tracker\FormElement\Field\Shareable\PropagatePropertiesDao;
use Tuleap\Tracker\FormElement\FieldNameFormatter;
use Tuleap\Tracker\FormElement\RetrieveFieldType;
use Tuleap\Tracker\FormElement\RetrieveFormElementsForTracker;
use Tuleap\Tracker\FormElement\View\Admin\FilterFormElementsThatCanBeCreatedForTracker;
use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

require_once __DIR__ . '/../../tracker_permissions.php';

class Tracker_FormElementFactory implements RetrieveUsedFields, AddDefaultValuesToFieldsData, RetrieveUsedArtifactLinkFields, RetrieveFormElementsForTracker, RetrieveFieldType //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
{
    public const FIELD_STRING_TYPE           = 'string';
    public const FIELD_TEXT_TYPE             = 'text';
    public const FIELD_FLOAT_TYPE            = 'float';
    public const FIELD_INTEGER_TYPE          = 'int';
    public const FIELD_DATE_TYPE             = 'date';
    public const FIELD_LAST_UPDATE_DATE_TYPE = 'lud';
    public const FIELD_SUBMITTED_ON_TYPE     = 'subon';
    public const FIELD_SUBMITTED_BY_TYPE     = 'subby';
    public const FIELD_ARTIFACT_ID_TYPE      = 'aid';
    public const FIELD_SELECT_BOX_TYPE       = 'sb';
    public const FIELD_RADIO_BUTTON_TYPE     = 'rb';
    public const FIELD_MULTI_SELECT_BOX_TYPE = 'msb';
    public const FIELD_CHECKBOX_TYPE         = 'cb';
    public const FIELD_OPEN_LIST_TYPE        = 'tbl';
    public const FIELD_FILE_TYPE             = 'file';
    public const FIELD_ARTIFACT_LINKS        = 'art_link';
    public const FIELD_CROSS_REFERENCES      = 'cross';
    public const FIELD_COMPUTED              = 'computed';
    public const FIELD_BURNDOWN              = 'burndown';
    public const FIELD_LAST_MODIFIED_BY      = 'luby';

    public const CONTAINER_COLUMN_TYPE   = 'column';
    public const CONTAINER_FIELDSET_TYPE = 'fieldset';

    /**
     * Cache formElements
     * @var array
     */
    protected $formElements                         = [];
    protected $formElements_by_parent               = [];
    protected $formElements_by_name                 = [];
    protected $used_formElements                    = [];
    private array $used_form_element_fields_by_name = [];
    protected $used                                 = [];

    /**
     * @var array of Tracker_FormElement
     */
    private $cache_used_form_elements_by_tracker_and_type = [];

    // Please use unique key for each element
    protected $classnames = [
        self::FIELD_STRING_TYPE           => Tracker_FormElement_Field_String::class,
        self::FIELD_TEXT_TYPE             => Tracker_FormElement_Field_Text::class,
        self::FIELD_FLOAT_TYPE            => Tracker_FormElement_Field_Float::class,
        self::FIELD_DATE_TYPE             => Tracker_FormElement_Field_Date::class,
        self::FIELD_SELECT_BOX_TYPE       => Tracker_FormElement_Field_Selectbox::class,
        self::FIELD_RADIO_BUTTON_TYPE     => Tracker_FormElement_Field_Radiobutton::class,
        self::FIELD_MULTI_SELECT_BOX_TYPE => Tracker_FormElement_Field_MultiSelectbox::class,
        self::FIELD_FILE_TYPE             => Tracker_FormElement_Field_File::class,
        self::FIELD_CHECKBOX_TYPE         => Tracker_FormElement_Field_Checkbox::class,
        self::FIELD_INTEGER_TYPE          => Tracker_FormElement_Field_Integer::class,
        self::FIELD_OPEN_LIST_TYPE        => Tracker_FormElement_Field_OpenList::class,
        self::FIELD_ARTIFACT_LINKS        => Tracker_FormElement_Field_ArtifactLink::class,
        'perm'                            => Tracker_FormElement_Field_PermissionsOnArtifact::class,
        'shared'                          => Tracker_FormElement_Shared::class,
    ];

    protected $special_classnames     = [
        self::FIELD_LAST_UPDATE_DATE_TYPE  => Tracker_FormElement_Field_LastUpdateDate::class,
        self::FIELD_ARTIFACT_ID_TYPE       => Tracker_FormElement_Field_ArtifactId::class,
        self::FIELD_SUBMITTED_ON_TYPE      => Tracker_FormElement_Field_SubmittedOn::class,
        'atid'                             => Tracker_FormElement_Field_PerTrackerArtifactId::class,
        self::FIELD_SUBMITTED_BY_TYPE      => Tracker_FormElement_Field_SubmittedBy::class,
        self::FIELD_LAST_MODIFIED_BY       => Tracker_FormElement_Field_LastModifiedBy::class,
        self::FIELD_CROSS_REFERENCES       => Tracker_FormElement_Field_CrossReferences::class,
        self::FIELD_BURNDOWN               => Tracker_FormElement_Field_Burndown::class,
        self::FIELD_COMPUTED               => Tracker_FormElement_Field_Computed::class,
        'priority'                         => Tracker_FormElement_Field_Priority::class,
    ];
    protected $group_classnames       = [
        self::CONTAINER_FIELDSET_TYPE => Tracker_FormElement_Container_Fieldset::class,
        self::CONTAINER_COLUMN_TYPE   => Tracker_FormElement_Container_Column::class,
    ];
    protected $staticfield_classnames = [
        'linebreak'      => Tracker_FormElement_StaticField_LineBreak::class,
        'separator'      => Tracker_FormElement_StaticField_Separator::class,
        'staticrichtext' => Tracker_FormElement_StaticField_RichText::class,
    ];

    /**
     * Get the classname of additional form elements
     *
     * Params:
     *  - fields  => (in/out) array of {'type' => 'classname'} Regular fields (int, float, …)
     *  - dynamic => (in/out) array of {'type' => 'classname'} Fields that are computed (computed, per tracker art id, …)
     *  - group   => (in/out) array of {'type' => 'classname'} Containers of fields (column, fieldset, …)
     *  - static  => (in/out) array of {'type' => 'classname'} Static fields (static text, separators, …)
     */
    public const GET_CLASSNAMES = 'tracker_formelement_get_classnames';

    /**
     * Get the visitor responsible of the display of create interface for the element
     *
     * Params:
     *  - all_used_elements => Tracker_FormElement[]
     *  - visitor           => (output) Tracker_FormElement_View_Admin_CreateVisitor
     */
    public const VIEW_ADMIN_CREATE_VISITOR = 'tracker_formelement_factory_view_admin_create_visitor';

    /**
     * A protected constructor; prevents direct creation of object
     */
    protected function __construct()
    {
        $this->getEventManager()->processEvent(
            self::GET_CLASSNAMES,
            [
                'fields'  => &$this->classnames,
                'dynamic' => &$this->special_classnames,
                'group'   => &$this->group_classnames,
                'static'  => &$this->staticfield_classnames,
            ]
        );
    }

    /**
     * Hold an instance of the class
     *
     * @var self
     */
    protected static $instance;

    public static function instance(): self
    {
        if (! isset(self::$instance)) {
            self::setInstance(new self());
        }
        return self::$instance;
    }

    /**
     * Allows to inject a fake factory for test. DO NOT USE IT IN PRODUCTION!
     *
     */
    public static function setInstance(Tracker_FormElementFactory $factory): void
    {
        self::$instance = $factory;
    }

    /**
     * Allows clear factory instance for test. DO NOT USE IT IN PRODUCTION!
     */
    public static function clearInstance(): void
    {
        self::$instance = null;
    }

    public function clearCaches()
    {
        $this->formElements                     = [];
        $this->formElements_by_parent           = [];
        $this->formElements_by_name             = [];
        $this->used_formElements                = [];
        $this->used_form_element_fields_by_name = [];
        $this->used                             = [];

        self::clearInstance();
    }

    public function clearElementFromCache(Tracker_FormElement $form_element): void
    {
        unset($this->formElements[$form_element->getId()]);
    }

    public function getType(Tracker_FormElement $form_element): string
    {
        $all_classnames = array_merge(
            $this->classnames,
            $this->special_classnames,
            $this->group_classnames,
            $this->staticfield_classnames
        );
        return (string) array_search($form_element::class, $all_classnames);
    }

    /**
     * Return if type is a prefix
     *
     * @param the type of the field
     *
     * @return string the name
     */
    protected function isTypeAPrefix($type)
    {
        return (
            isset($this->group_classnames[$type])
            || isset($this->special_classnames[$type])
            || isset($this->staticfield_classnames[$type])
        );
    }

    /**
     * Get a formElement by id
     * @param int $form_element_id the id of the formElement to retrieve
     * @return Tracker_FormElement_Field
     */
    public function getFormElementById($form_element_id)
    {
        if (! array_key_exists($form_element_id, $this->formElements)) {
            if ($form_element_id && ($row = $this->getDao()->searchById($form_element_id)->getRow())) {
                return $this->getCachedInstanceFromRow($row);
            }
            $this->formElements[$form_element_id] = null;
        }
        return $this->formElements[$form_element_id];
    }

    /**
     * @return Tracker_FormElement_Field
     */
    public function getFormElementFieldById($id)
    {
        $field = $this->getFormElementById($id);
        if ($field instanceof Tracker_FormElement_Field) {
            return $field;
        }
    }

    public function getUsedFormElementFieldById(int $id): ?Tracker_FormElement_Field
    {
        $field = $this->getUsedFormElementById($id);
        if ($field instanceof Tracker_FormElement_Field) {
            return $field;
        }
        return null;
    }

    /**
     * @return Tracker_FormElement_Field_List|null
     */
    public function getFormElementListById($field_id)
    {
        $field = $this->getFormElementById($field_id);
        if ($field instanceof Tracker_FormElement_Field_List) {
            return $field;
        }
        return null;
    }

    /**
     * Get a formElement by its short name
     *
     * @param int $tracker_id the tracker of the formElement to retrieve
     * @param string $name the name of the formElement to retrieve
     *
     * @return Tracker_FormElement_Field
     */
    public function getFormElementByName($tracker_id, $name)
    {
        if (! isset($this->formElements_by_name[$tracker_id][$name])) {
            if ($row = $this->getDao()->searchByTrackerIdAndName($tracker_id, $name)->getRow()) {
                $this->formElements_by_name[$tracker_id][$name] = $this->getCachedInstanceFromRow($row);
            } else {
                $this->formElements_by_name[$tracker_id][$name] = null;
            }
        }
        return $this->formElements_by_name[$tracker_id][$name];
    }

    /**
     * Get a formElement by id
     * @param int $id the id of the formElement to retrieve
     * @return Tracker_FormElement_Field
     */
    public function getUsedFormElementById($id)
    {
        if (! isset($this->used_formElements[$id])) {
            $this->used_formElements[$id] = null;
            $form_element                 = $this->getFormElementById($id);
            if ($form_element && $form_element->isUsed()) {
                $this->used_formElements[$id] = $form_element;
            }
        }
        return $this->used_formElements[$id];
    }

    public function getUsedFieldByName(int $tracker_id, string $field_name): ?Tracker_FormElement_Field
    {
        if (! isset($this->used_form_element_fields_by_name[$tracker_id])) {
            $this->used_form_element_fields_by_name[$tracker_id] = [];
        }
        if (array_key_exists($field_name, $this->used_form_element_fields_by_name[$tracker_id])) {
            return $this->used_form_element_fields_by_name[$tracker_id][$field_name];
        }

        $row = $this->getDao()->searchUsedByTrackerIdAndName($tracker_id, $field_name)->getRow();
        if ($row !== false) {
            $form_element = $this->getCachedInstanceFromRow($row);
            if ($form_element instanceof Tracker_FormElement_Field) {
                $this->used_form_element_fields_by_name[$tracker_id][$field_name] = $form_element;
                return $form_element;
            }
        }

        $this->used_form_element_fields_by_name[$tracker_id][$field_name] = null;
        return null;
    }

    /**
     * Get the field by name if user is allowed to see it
     *
     * @param int    $tracker_id
     * @param string $field_name
     *
     */
    public function getUsedFieldByNameForUser($tracker_id, $field_name, PFUser $user): ?Tracker_FormElement_Field
    {
        $field = $this->getUsedFieldByName($tracker_id, $field_name);
        if ($field !== null && $field->userCanRead($user)) {
            return $field;
        }
        return null;
    }

    public function getNumericFieldByNameForUser(Tracker $tracker, PFUser $user, $name)
    {
        $field = $this->getNumericFieldByName($tracker, $name);
        if (! $field) {
            return null;
        }

        return $this->getUsedFieldByNameForUser(
            $tracker->getId(),
            $name,
            $user
        );
    }

    public function getNumericFieldByName(Tracker $tracker, $name)
    {
        return $this->getFieldByNameAndType($tracker, [self::FIELD_INTEGER_TYPE, self::FIELD_FLOAT_TYPE, self::FIELD_COMPUTED], $name);
    }

    public function getDateFieldByNameForUser(Tracker $tracker, PFUser $user, $name)
    {
        $field = $this->getFieldByNameAndType($tracker, ['date'], $name);
        if (! $field) {
            return null;
        }

        return $this->getUsedFieldByNameForUser(
            $tracker->getId(),
            $name,
            $user
        );
    }

    private function getFieldByNameAndType(Tracker $tracker, array $accepted_type, $name)
    {
        $field = $tracker->hasFormElementWithNameAndType(
            $name,
            $accepted_type
        );

        if (! $field) {
             return null;
        }

        return $this->getUsedFieldByName($tracker->getId(), $name);
    }

    /**
     * @return null|\Tracker_FormElement_Field
     */
    public function getUsedFormElementFieldByNameForUser($tracker_id, $field_name, PFUser $user)
    {
        $field = $this->getUsedFieldByNameForUser($tracker_id, $field_name, $user);
        if ($field && array_search($field::class, array_merge($this->classnames, $this->special_classnames))) {
            return $field;
        }

        return null;
    }

    /**
     * Return a selectbox field. This field is used and the user can see its value.
     *
     * @param int    $tracker_id
     * @param string $field_name
     *
     * @return Tracker_FormElement_Field_Selectbox | null
     */
    public function getSelectboxFieldByNameForUser($tracker_id, $field_name, PFUser $user)
    {
        $field = $this->getUsedFieldByNameForUser($tracker_id, $field_name, $user);
        if ($field && $field instanceof Tracker_FormElement_Field_Selectbox) {
            return $field;
        }
        return null;
    }

    /**
     * Return a field that provides a computable value. This field is used and the user can see its value.
     *
     * @param int    $tracker_id
     * @param string $field_name
     *
     * @return Tracker_FormElement_IComputeValues
     */
    public function getComputableFieldByNameForUser($tracker_id, $field_name, PFUser $user)
    {
        $field = $this->getUsedFieldByNameForUser($tracker_id, $field_name, $user);
        if (
            $field
            && $field instanceof Tracker_FormElement_IComputeValues
        ) {
            return $field;
        }
        return null;
    }

    /**
     * Get used formElements by parent id
     * @param int parent_id
     * @return array
     */
    public function getUsedFormElementsByParentId($parent_id)
    {
        if (! isset($this->formElements_by_parent[$parent_id])) {
            $this->formElements_by_parent[$parent_id] = [];
            foreach ($this->getDao()->searchUsedByParentId($parent_id) as $row) {
                $form_element = $this->getCachedInstanceFromRow($row);
                if ($form_element) {
                    $this->formElements_by_parent[$parent_id][$form_element->getId()] = $form_element;
                }
            }
        }
        return $this->formElements_by_parent[$parent_id];
    }

    /**
     * Get all formElements by tracker id
     *
     * @param Tracker $tracker
     *
     * @return array
     */
    public function getAllFormElementsForTracker($tracker)
    {
        $all        = [];
        $tracker_id = $tracker->getId();
        foreach ($this->getDao()->searchByTrackerId($tracker_id) as $row) {
            $form_element_id       = $row['id'];
            $all[$form_element_id] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($all);
    }

    /**
     * Get all formElements by parent id
     * @param int parent_id
     * @return Tracker_FormElement[]
     */
    public function getAllFormElementsByParentId($parent_id): array
    {
        $all = [];
        foreach ($this->getDao()->searchByParentId($parent_id) as $row) {
            $form_element_id       = $row['id'];
            $all[$form_element_id] = $this->getCachedInstanceFromRow($row);
        }
        return $all;
    }

    /**
     *
     * @todo Check the type of the field.
     *
     * @return null|Tracker_FormElement_Field or null if not found or not a Field
     */
    public function getFieldById($id)
    {
        $field = $this->getFormElementById($id);
        if (! $field instanceof Tracker_FormElement_Field) {
            $field = null;
        }
        return $field;
    }

    public function getFieldsetById($id)
    {
        $fieldset = $this->getFormElementById($id);
        if (! $fieldset instanceof Tracker_FormElement_Container_Fieldset) {
            $fieldset = null;
        }
        return $fieldset;
    }

    /**
     * @return null|Tracker_FormElement_Field_Shareable or null
     */
    public function getShareableFieldById($id)
    {
        $field = $this->getFieldById($id);
        if ($field instanceof Tracker_FormElement_Field_Shareable) {
            return $field;
        }
        return null;
    }

    /**
     * All fields used by the tracker
     * @return Tracker_FormElement_Field[]
     */
    public function getUsedFields(\Tracker $tracker): array
    {
        return $this->getUsedFormElementsByType($tracker, $this->getFieldsSQLTypes());
    }

    /**
     * Returns FormElements used by a tracker, except those already in REST Basic Info
     *
     *
     * @return Tracker_FormElement_Field[]
     */
    public function getUsedFieldsForREST(Tracker $tracker)
    {
        return $this->getUsedFormElementsByType($tracker, $this->getFieldsSQLTypes());
    }

    /**
     * Augment fields_data with fields which have a default value defined
     *
     *
     * @return Array $fields_data
     */
    public function getUsedFieldsWithDefaultValue(Tracker $tracker, array $fields_data, PFUser $user): array
    {
        $fields = $this->getUsedFields($tracker);
        foreach ($fields as $field) {
            if ($field->userCanSubmit($user)) {
                $fields_data = $this->augmentFieldsDataWithDefaultValue($field, $fields_data);
            }
        }
        return $fields_data;
    }

    private function augmentFieldsDataWithDefaultValue($field, $fields_data)
    {
        if (! array_key_exists($field->getId(), $fields_data)) {
            $fields_data[$field->getId()] = $field->getDefaultValue();
        }
        return $fields_data;
    }

    private function getFieldsSQLTypes()
    {
        $field_classnames = array_merge($this->classnames, $this->special_classnames);

        return array_keys($field_classnames);
    }

    /**
     * @param Tracker $tracker
     * @return array of Tracker_FormElement - All non dynamic fields used by the tracker
     */
    public function getUsedNonDynamicFields($tracker)
    {
        $field_classnames = $this->classnames;

        return $this->getUsedFormElementsByType($tracker, array_keys($field_classnames));
    }

    /**
     * @param Tracker $tracker
     * @return Tracker_FormElement_Field[] - All fields used and  unused by the tracker
     */
    public function getFields($tracker)
    {
        $field_classnames = array_merge($this->classnames, $this->special_classnames);

        return $this->getFormElementsByType($tracker, array_keys($field_classnames));
    }

    public function getUsedFieldByIdAndType(Tracker $tracker, $field_id, $type)
    {
        $tracker_id = $tracker->getId();
        if ($row = $this->getDao()->searchUsedByIdAndType($tracker_id, $field_id, $type)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }

    /**
     * @param Tracker $tracker
     * @return array All submitted by formElements used by the tracker
     */
    public function getUsedSubmittedByFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, [self::FIELD_SUBMITTED_BY_TYPE]);
    }

    /**
     * @param Tracker $tracker
     * @return array All date formElements used by the tracker
     */
    public function getUsedDateFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['date', 'lud', 'subon']);
    }

    /**
     * @param Tracker $tracker
     * @return Tracker_FormElement_Field_File[]
     */
    public function getUsedFileFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['file']);
    }

    /**
     * @return Tracker_FormElement_Field[] All custom date formElements used by the tracker
     */
    public function getUsedCustomDateFields(Tracker $tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['date']);
    }

    /**
     * @return Tracker_FormElement_Field[] All core date formElements of the tracker
     */
    public function getCoreDateFields(Tracker $tracker)
    {
        return $this->getFormElementsByType($tracker, ['lud', 'subon'], false);
    }

    /**
     * @param int     $field_id
     *
     * @return Tracker_FormElement_Field_Date|null
     */
    public function getUsedDateFieldById(Tracker $tracker, $field_id)
    {
        $date_field = $this->getUsedFieldByIdAndType($tracker, $field_id, ['date', 'subon', 'lud']);
        assert($date_field === null || $date_field instanceof Tracker_FormElement_Field_Date);

        return $date_field;
    }

    /**
     * @param Tracker $tracker
     * @return array All int formElements used by the tracker
     */
    public function getUsedIntFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, self::FIELD_INTEGER_TYPE);
    }

    /**
     * @param Tracker $tracker
     * @return array All numeric formElements used by the tracker
     */
    public function getUsedNumericFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, [self::FIELD_INTEGER_TYPE, self::FIELD_FLOAT_TYPE]);
    }

    /**
     * It retrieves simple value fields that can potentially contain numeric values
     * @return Tracker_FormElement_Field[] All numeric or computed formElements used by the tracker
     */
    public function getUsedPotentiallyContainingNumericValueFields(Tracker $tracker)
    {
        return $this->getUsedFormElementsByType($tracker, [self::FIELD_INTEGER_TYPE, self::FIELD_FLOAT_TYPE, self::FIELD_COMPUTED, 'sb', 'rb']);
    }

    /**
     * @param Tracker $tracker
     * @return Tracker_FormElement_Field_List[] All (multi) selectboxes formElements used by the tracker
     */
    public function getUsedListFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['sb', 'msb', 'tbl', self::FIELD_CHECKBOX_TYPE, 'rb']);
    }

    /**
     * @param Tracker $tracker
     * @return array All (multi) selectboxes formElements used by the tracker
     */
    public function getUsedClosedListFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['sb', 'msb', self::FIELD_CHECKBOX_TYPE, 'rb', self::FIELD_SUBMITTED_BY_TYPE, self::FIELD_LAST_MODIFIED_BY]);
    }

    /**
     * @return Tracker_FormElement_Field_ArtifactLink[]
     */
    public function getUsedArtifactLinkFields(Tracker $tracker): array
    {
        return $this->getUsedFormElementsByType($tracker, [self::FIELD_ARTIFACT_LINKS]);
    }

    /**
     * @param Tracker $tracker
     * @return Tracker_FormElement_Container_Fieldset[]
     */
    public function getUsedFieldsets($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['fieldset']);
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getUsedFieldsBindedToUserGroups($tracker)
    {
        $fields = [];

        $permission_field = $this->getUsedFormElementsByType($tracker, ['perm']);
        if ($permission_field) {
            $fields[] = $permission_field[0];
        }

        $binded_fields = (array) $this->getUsedFormElementsByType($tracker, ['tbl', 'sb', 'msb']);
        foreach ($binded_fields as $field) {
            if ($field->getBind()->getType() === Tracker_FormElement_Field_List_Bind_Ugroups::TYPE) {
                $fields[] = $field;
            }
        }

        return $fields;
    }

    /**
     * Return the first (and only one) ArtifactLink field (if any)
     *
     * @return Tracker_FormElement_Field_ArtifactLink|null
     */
    public function getAnArtifactLinkField(PFUser $user, Tracker $tracker)
    {
        $artifact_link_fields = $this->getUsedArtifactLinkFields($tracker);
        if (count($artifact_link_fields) > 0 && $artifact_link_fields[0]->userCanRead($user)) {
            return $artifact_link_fields[0];
        }
        return null;
    }

    /**
     * @param Tracker $tracker
     *
     * @return Tracker_FormElement_Field_Burndown[]
     */
    public function getUsedBurndownFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, [self::FIELD_BURNDOWN]);
    }

    /**
     * @return Tracker_FormElement_Field_Burndown|null
     */
    public function getABurndownField(PFUser $user, Tracker $tracker)
    {
        $burndown_fields = $this->getUsedBurndownFields($tracker);
        if (count($burndown_fields) > 0 && $burndown_fields[0]->userCanRead($user)) {
            return $burndown_fields[0];
        }

        return null;
    }

    /**
     * @param Tracker $tracker
     *
     * @return array All lists formElements bind to users used by the tracker
     */
    public function getUsedUserListFields($tracker)
    {
        $form_elements = [];
        $tracker_id    = $tracker->getId();
        foreach ($this->getDao()->searchUsedUserListFieldByTrackerId($tracker_id) as $row) {
            $form_elements[] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($form_elements);
    }

    public function getUsedUserListFieldById($tracker, $field_id)
    {
        $tracker_id = $tracker->getId();
        if ($row = $this->getDao()->getUsedUserListFieldById($tracker_id, $field_id)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }

    /**
     *
     * @return array<Tracker_FormElement_Field_Selectbox|Tracker_FormElement_Field_Checkbox|Tracker_FormElement_Field_MultiSelectbox|Tracker_FormElement_Field_Radiobutton>
     */
    public function searchUsedUserClosedListFields(Tracker $tracker): array
    {
        $form_elements = [];
        foreach ($this->getDao()->searchUsedUserClosedListFieldsByTrackerId($tracker->getId()) as $row) {
            $list = $this->getCachedInstanceFromRow($row);
            assert(
                $list instanceof Tracker_FormElement_Field_Selectbox
                || $list instanceof Tracker_FormElement_Field_Checkbox
                || $list instanceof Tracker_FormElement_Field_MultiSelectbox
                || $list instanceof Tracker_FormElement_Field_Radiobutton
            );
            $form_elements[] = $list;
        }
        return array_filter($form_elements);
    }

    public function getUsedUserClosedListFieldById($tracker, $field_id)
    {
        $tracker_id = $tracker->getId();
        if ($row = $this->getDao()->getUsedUserClosedListFieldById($tracker_id, $field_id)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }

    /**
     * Return all selectbox and multiselectbox fields that bind to static values
     *
     *
     * @return \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface
     */
    public function getUsedStaticSbFields(Tracker $tracker)
    {
        return $this->getDao()->searchUsedStaticSbFieldByTrackerId($tracker->getId())
                    ->instanciateWith([$this, 'getCachedInstanceFromRow']);
    }

    public function getUsedListFieldById($tracker, $field_id)
    {
        return $this->getUsedFieldByIdAndType($tracker, $field_id, ['sb', 'msb', 'tbl', 'cb', 'rb']);
    }

    public function getUsedSbFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['sb', 'msb']);
    }

    /**
     * @param Tracker $tracker
     * @return Tracker_FormElement_Field_Text[] All text formElements used by the tracker
     */
    public function getUsedTextFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['text', 'string', 'ref']);
    }

    public function getUsedTextFieldById($tracker, $field_id)
    {
        return $this->getUsedFieldByIdAndType($tracker, $field_id, ['text', 'string', 'ref']);
    }

    public function getUsedFieldsForExpertModeUserCanRead(Tracker $tracker, PFUser $user)
    {
        $fields = array_merge(
            $this->getUsedNumericFields($tracker),
            $this->getUsedTextFields($tracker),
            $this->getUsedDateFields($tracker),
            $this->getUsedClosedListFields($tracker),
            $this->getUsedFileFields($tracker)
        );

        foreach ($fields as $key => $field) {
            if (! $field->userCanRead($user)) {
                unset($fields[$key]);
            }
        }

        return $fields;
    }

    /**
     * @param Tracker $tracker
     * @param int $field_id
     * @return Tracker_FormElement | void
     */
    public function getUsedNumericFieldById($tracker, $field_id)
    {
        return $this->getUsedFieldByIdAndType($tracker, $field_id, [self::FIELD_INTEGER_TYPE, self::FIELD_FLOAT_TYPE, self::FIELD_COMPUTED]);
    }

    /**
     * It retrieves by its Id a simple value field that can potentially contain numeric values
     * @param Tracker $tracker
     * @param int $field_id
     * @return Tracker_FormElement | void
     */
    public function getUsedPotentiallyContainingNumericValueFieldById($tracker, $field_id)
    {
        return $this->getUsedFieldByIdAndType($tracker, $field_id, [self::FIELD_INTEGER_TYPE, self::FIELD_FLOAT_TYPE, self::FIELD_COMPUTED, 'sb', 'rb']);
    }

    /**
     * @param Tracker $tracker
     * @return array All string formElements used by the tracker
     */
    public function getUsedStringFields($tracker)
    {
        return $this->getUsedFormElementsByType($tracker, ['string', 'ref']);
    }

    /**
     * Duplicate a formElement
     * @param int $from_tracker_id
     * @param int $to_tracker_id
     * @return list<array{from: int, to: int, values: array, workflow: bool}> the mapping between old formElements and new ones
     */
    public function duplicate($from_tracker_id, $to_tracker_id)
    {
        $mapping = [];

        foreach ($this->getDao()->searchByTrackerId($from_tracker_id) as $from_row) {
            $has_workflow = false;
            if (in_array($from_row['formElement_type'], ['sb', 'rb'])) {
                $field = $this->getFieldById($from_row['id']);
                if ($field !== null && $field->fieldHasDefineWorkflow()) {
                    $has_workflow = true;
                }
            }
            //First duplicate formElement info
            if ($id = $this->getDao()->duplicate($from_row['id'], $to_tracker_id)) {
                $created_form_element = $this->getFormElementById($id);
                if ($created_form_element) {
                    $created_values = $created_form_element->duplicate($from_row['id'], $id);
                    if ($has_workflow) {
                        $workflow = $this->getFormElementById($from_row['id'])->getWorkflow();
                    }
                    $mapping[] = [
                        'from'    => (int) $from_row['id'],
                        'to'      => $id,
                        'values'  => $created_values,
                        'workflow' => $has_workflow,
                    ];
                    $type      = $this->getType($created_form_element);
                }
                $tracker = TrackerFactory::instance()->getTrackerByid($to_tracker_id);
            }
        }
        $this->getDao()->mapNewParentsAfterDuplication($to_tracker_id, $mapping);
        return $mapping;
    }

    /**
     * @param Tracker $tracker
     * @param mixed   $type    The type (string) or types (array of) you are looking for
     * @param bool $used Check if the type is used or not
     *
     * @return Array of Tracker_FormElement All formElements used by the tracker
     */
    public function getFormElementsByType($tracker, $type, $used = null)
    {
        return $this->getCachedInstancesFromDAR($this->getDao()->searchUsedByTrackerIdAndType($tracker->id, $type, $used));
    }

    /**
     * @param \Tuleap\DB\Compat\Legacy2018\LegacyDataAccessResultInterface $dar the db collection of FormElements to instantiate
     *
     * @return array All text formElements used by the tracker
     */
    protected function getCachedInstancesFromDAR(LegacyDataAccessResultInterface $dar)
    {
        $form_elements = [];
        foreach ($dar as $row) {
            $form_elements[] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($form_elements);
    }

    /**
     * @param Tracker $tracker
     * @param mixed $type the type (string) or types (array of) you are looking for
     * @return array All text formElements used by the tracker
     */
    public function getUsedFormElementsByType($tracker, $type)
    {
        $key        = md5(serialize($type));
        $tracker_id = $tracker->getId();

        if (! isset($this->cache_used_form_elements_by_tracker_and_type[$tracker_id][$key])) {
            $used                                                                  = true;
            $used_form_elements_result                                             = $this->getDao()->searchUsedByTrackerIdAndType($tracker_id, $type, $used);
            $this->cache_used_form_elements_by_tracker_and_type[$tracker_id][$key] = $this->getCachedInstancesFromDAR($used_form_elements_result);
        }

        return $this->cache_used_form_elements_by_tracker_and_type[$tracker_id][$key];
    }

    public function getAllUsedFormElementOfAnyTypesForTracker(Tracker $tracker)
    {
        $classes = array_keys(array_merge($this->classnames, $this->special_classnames, $this->group_classnames, $this->staticfield_classnames));

        return $this->getUsedFormElementsByType($tracker, $classes);
    }

    public function getUnusedFormElementForTracker(Tracker $tracker)
    {
        $unused     = [];
        $tracker_id = $tracker->getId();
        foreach ($this->getDao()->searchUnusedByTrackerId($tracker_id) as $row) {
            $form_element_id          = $row['id'];
            $unused[$form_element_id] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($unused);
    }

    /**
     * @return Tracker_FormElement[]
     */
    public function getUsedFormElementForTracker(Tracker $tracker): array
    {
        $tracker_id = $tracker->getId();
        if (! isset($this->used[$tracker_id])) {
            $this->used[$tracker_id] = [];
            foreach ($this->getDao()->searchUsedByTrackerId($tracker_id) as $row) {
                $form_element = $this->getCachedInstanceFromRow($row);
                if ($form_element) {
                    $form_element_id                           = $row['id'];
                    $this->used[$tracker_id][$form_element_id] = $form_element;
                    $this->used_formElements[$form_element_id] = $form_element;
                }
            }
        }
        return $this->used[$tracker_id];
    }

    protected function getFormElementClass($form_element_type)
    {
        $properties = ['classnames', 'special_classnames', 'group_classnames', 'staticfield_classnames'];
        foreach ($properties as $class_names) {
            $classes = $this->$class_names;
            if (array_key_exists($form_element_type, $classes)) {
                return $classes[$form_element_type];
            }
        }
    }

    /**
     * @param array $row Raw data (typically from the db) of the form element
     *
     * @return Tracker_FormElement
     */
    public function getCachedInstanceFromRow($row)
    {
        $form_element_id = $row['id'];
        if (! isset($this->formElements[$form_element_id])) {
            $this->formElements[$form_element_id] = $this->getInstanceFromRow($row);
        }
        return $this->formElements[$form_element_id];
    }

    /**
     * @param array the row allowing the construction of a Tracker_FormElement
     *
     * @return Tracker_FormElement Object
     */
    public function getInstanceFromRow($row)
    {
        $form_element_type = $row['formElement_type'];
        $klass             = $this->getFormElementClass($form_element_type);

        if (! $klass) {
            return null;
        }

        $original_field = null;
        if ($row['original_field_id']) {
            $original_field = $this->getFormElementById($row['original_field_id']);
        }
        $form_element = new $klass(
            $row['id'],
            $row['tracker_id'],
            $row['parent_id'],
            $row['name'],
            $row['label'],
            $row['description'],
            $row['use_it'],
            $row['scope'],
            $row['required'],
            $row['notifications'],
            $row['rank'],
            $original_field
        );

        return $form_element;
    }

    /**
     * Create an array of FormElement based on a array of field definitions (database row for instance).
     *
     * @param array $rows
     *
     * @return Tracker_FormElement[]
     */
    private function getCachedInstancesFromRows($rows): array
    {
        $fields = [];
        foreach ($rows as $row) {
            $fields[] = $this->getCachedInstanceFromRow($row);
        }
        return $fields;
    }

    /**
     * Creates a Tracker_FormElement Object
     *
     * @param Tracker          $tracker     the new tracker
     * @param SimpleXMLElement $xml         containing the structure of the imported Tracker_FormElement
     * @param array            &$xmlMapping where the newly created formElements indexed by their XML IDs are stored
     *
     * @return Tracker_FormElement | null
     */
    public function getInstanceFromXML(
        Tracker $tracker,
        $xml,
        &$xmlMapping,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        TrackerXmlImportFeedbackCollector $feedback_collector,
    ) {
        $att = $xml->attributes();
        assert($att !== null);
        if ($xml->getName() === 'externalField') {
            $external_element_event = new ImportExternalElement($xml, $tracker->getProject(), $feedback_collector);
            $this->getEventManager()->processEvent($external_element_event);
            $curElem = $external_element_event->getFormElement();
        } else {
            $row     = [
                'formElement_type'  => (string) $att['type'],
                'name'              => (string) $xml->name,
                'label'             => (string) $xml->label,
                'rank'              => (int) $att['rank'],
                'use_it'            => isset($att['use_it']) ? (int) $att['use_it'] : 1,
                'scope'             => isset($att['scope']) ? (string) $att['scope'] : 'P',
                'required'          => isset($att['required']) ? (int) $att['required'] : 0,
                'notifications'     => isset($att['notifications']) ? (int) $att['notifications'] : 0,
                'description'       => (string) $xml->description,
                'id'                => 0,
                'tracker_id'        => 0,
                'parent_id'         => 0,
                'original_field_id' => null,
            ];
            $curElem = $this->getInstanceFromRow($row);
        }
        if ($curElem) {
            $curElem->setTracker($tracker);
            $xmlMapping[(string) $xml['ID']] = $curElem;
            $curElem->continueGetInstanceFromXML($xml, $xmlMapping, $user_finder, $feedback_collector);
            return $curElem;
        }
        $feedback_collector->addWarnings(
            sprintf(
                dgettext(
                    'tuleap-tracker',
                    "Type '%s' does not exist. This field is ignored. (Name : '%s', ID: '%s')."
                ),
                (string) $att['type'],
                (string) $xml->name,
                (string) $att['ID']
            )
        );

        return null;
    }

    protected function getDao()
    {
        return new FieldDao();
    }

    /**
     * @return PropagatePropertiesDao
     */
    protected function getPropagatePropertiesDao()
    {
        return new PropagatePropertiesDao();
    }

    /**
     * format a tracker field short name
     * @param string $label
     * @return string
     */
    private function deductNameFromLabel($label)
    {
        return FieldNameFormatter::getFormattedName($label);
    }

    /**
     * Returns the FormElements that are a copy of given element
     *
     *
     * @return Tracker_FormElement[]
     */
    public function getSharedTargets(Tracker_FormElement $element): array
    {
        $dar = $this->getDao()->searchSharedTargets($element->getId());
        return $this->getCachedInstancesFromRows($dar);
    }

    /**
     * Returns the FormElements that are exported by this tracker
     *
     * @return Array of Tracker_FormElement_Field
     */
    public function getProjectSharedFields(Project $project)
    {
        $dar = $this->getDao()->searchProjectSharedFieldsOriginals($project->getID());
        return $this->getCachedInstancesFromRows($dar);
    }

    /**
     * Fixes the original field id of the shared fields originating from the duplicated project
     *
     * @param int $new_project_id
     * @param int $template_project_id
     * @param list<array{from: int, to: int, values: array, workflow: bool}> $field_mapping
     */
    public function fixOriginalFieldIdsAfterDuplication($new_project_id, $template_project_id, array $field_mapping)
    {
        $field_dao             = $this->getDao();
        $project_shared_fields = $field_dao->searchProjectSharedFieldsTargets($new_project_id);
        $template_field_ids    = $field_dao->searchFieldIdsByGroupId($template_project_id);

        foreach ($project_shared_fields as $project_shared_field) {
            if ($this->originalFieldIsInTemplateProject($project_shared_field, $template_field_ids)) {
                $id                = $project_shared_field['id'];
                $original_field_id = $project_shared_field['original_field_id'];

                $this->fixSharedFieldOriginalId($id, $original_field_id, $field_mapping);
                $this->fixSharedFieldOriginalValueIds($id, $original_field_id, $field_mapping);
            }
        }
    }

    private function fixSharedFieldOriginalId($field_id, $old_original_field_id, $field_mapping)
    {
        $new_original_field_id = $this->getNewOriginalFieldIdFromMapping($old_original_field_id, $field_mapping);
        $this->getDao()->updateOriginalFieldId($field_id, $new_original_field_id);
    }

    private function fixSharedFieldOriginalValueIds($field_id, $old_original_field_id, $field_mapping)
    {
        $field = $this->getShareableFieldById($field_id);
        if ($field === null) {
            return;
        }
        $value_mapping = $this->getValueMappingFromFieldMapping($old_original_field_id, $field_mapping);

        $field->fixOriginalValueIds($value_mapping);
    }

    private function getValueMappingFromFieldMapping($original_field_id, $field_mapping)
    {
        foreach ($field_mapping as $row) {
            if ($row['from'] == $original_field_id) {
                return $row['values'];
            }
        }
        return [];
    }

    private function getNewOriginalFieldIdFromMapping($old_field_id, array $field_mapping)
    {
        foreach ($field_mapping as $row) {
            if ($row['from'] == $old_field_id) {
                return $row['to'];
            }
        }
    }

    private function originalFieldIsInTemplateProject(array $shared_field, array $original_shared_field_ids)
    {
        return in_array($shared_field['original_field_id'], $original_shared_field_ids);
    }

    /**
     * Returns the shared field originals of which the user can either the orginal
     * or atleast one of the targets
     *
     * @return Array of Tracker_FormElement_Field
     */
    public function getSharedFieldsReadableBy(PFUser $user, Project $project)
    {
        $fields = $this->getProjectSharedFields($project);
        foreach ($fields as $k => $field) {
            if (! $this->userCanReadSharedField($user, $field)) {
                unset($fields[$k]);
            }
        }
        return $fields;
    }

    protected function userCanReadSharedField(PFUser $user, Tracker_FormElement $field)
    {
        return ($field->userCanRead($user) && $this->canReadAllTargets($user, $field));
    }

    private function canReadAllTargets(PFUser $user, Tracker_FormElement $field)
    {
        foreach ($this->getSharedTargets($field) as $target_field) {
            if (! $target_field->userCanRead($user)) {
                return false;
            }
        }
        return true;
    }

    public function updateFormElement(Tracker_FormElement $form_element, $form_element_data)
    {
        //check that the new name is not already used
        if (isset($form_element_data['name'])) {
            if (trim($form_element_data['name'])) {
                $form_element_data['name'] = $this->deductNameFromLabel($form_element_data['name']);
                if ($existing_field = $this->getFormElementByName($form_element->getTracker()->getId(), $form_element_data['name'])) {
                    if ($existing_field->getId() != $form_element->getId()) {
                        $GLOBALS['Response']->addFeedback('error', dgettext('tuleap-tracker', 'Unable to change the name of the element, it is already in use by another one'));
                        unset($form_element_data['name']);
                    }
                }
            } else {
                //Do not erase the field name
                unset($form_element_data['name']);
            }
        }

        $rank = isset($form_element_data['rank']) ? $form_element_data['rank'] : '--';
        //extract the parent_id from rank if needed
        //rank = <parent_id>:<rank> | <rank>
        $parent_id = isset($form_element_data['parent_id']) ? $form_element_data['parent_id'] : 0;
        if (strpos($rank, ':') !== false) {
            [$parent_id, $rank] = explode(':', $rank);
        }
        $form_element_data['parent_id'] = $parent_id;
        $form_element_data['rank']      = $rank;
        if ($form_element->updateProperties($form_element_data)) {
            if ($this->getDao()->save($form_element)) {
                return $this->getPropagatePropertiesDao()->propagateProperties($form_element);
            }
        }
        return false;
    }

    /**
     * Unuse the formElement
     * @param boolean true if success
     */
    public function removeFormElement($form_element_id)
    {
        $success = false;
        if ($form_element = $this->getFormElementById($form_element_id)) {
            //Don't use anymore the field
            $form_element->use_it = false;
            //remove the field from its container
            $form_element->parent_id = 0;
            $success                 = $this->getDao()->save($form_element);
        }
        return $success;
    }

    /**
     * Add the formElement
     * @param boolean true if success
     */
    public function addFormElement($form_element_id)
    {
        $success = false;
        if ($form_element = $this->getFormElementById($form_element_id)) {
            $form_element->use_it = true;
            $form_element->rank   = 'beginning';

            if ($success = $this->getDao()->save($form_element)) {
                unset($this->formElements_by_parent[$form_element->parent_id]);
                //Set permissions if no permission set
                $perms = $form_element->getPermissionsByUgroupId();
                //WARNING : here must be transformed the permissions array structure in order to pass it to the function that process form data permissions
                //see this::createFormElement to know how to convert permissions data
                if (empty($perms)) {
                    //Set default permissions
                    $permissions = [ $form_element_id =>
                         [
                             ProjectUGroup::ANONYMOUS        => plugin_tracker_permission_get_input_value_from_permission(Tracker_FormElement_Field::PERMISSION_READ),
                             ProjectUGroup::REGISTERED       => plugin_tracker_permission_get_input_value_from_permission(Tracker_FormElement_Field::PERMISSION_SUBMIT),
                             ProjectUGroup::PROJECT_MEMBERS  => plugin_tracker_permission_get_input_value_from_permission(Tracker_FormElement_Field::PERMISSION_UPDATE),
                         ],
                    ];
                    $tracker     = $form_element->getTracker();
                    plugin_tracker_permission_process_update_fields_permissions(
                        $tracker->getGroupID(),
                        $tracker->getID(),
                        $this->getUsedFields($tracker),
                        $permissions
                    );
                }
            }
        }
        return $success;
    }

    /**
     * Delete permanently the formElement
     * @param boolean true if success
     */
    public function deleteFormElement($form_element_id)
    {
        $success = false;
        if ($form_element = $this->getFormElementById($form_element_id)) {
            //TODO: remove changeset values? or simply mark the formElement as deleted to be able to retrieve history?
            if ($success = $this->getDao()->delete($form_element)) {
                unset($this->formElements[$form_element_id]);
                unset($this->formElements_by_formElementcomponent[$form_element->parent_id]);
            }
        }
        return $success;
    }

    /**
     * Display the HTML for "field usage" admininistration
     *
     * @return void
     */
    public function displayFactories(Tracker $tracker)
    {
        $hp = Codendi_HTMLPurifier::instance();

        $w = new Widget_Static(dgettext('tuleap-tracker', 'Fields'));
        $w->setContent($this->fetchFactoryButtons($this->classnames, $tracker));
        $w->display();

        $w = new Widget_Static(dgettext('tuleap-tracker', 'Dynamic fields'));
        $w->setContent($this->fetchFactoryButtons($this->special_classnames, $tracker));
        $w->display();

        $w = new Widget_Static(dgettext('tuleap-tracker', 'Structural elements'));
        $w->setContent($this->fetchFactoryButtons(array_merge($this->group_classnames, $this->staticfield_classnames), $tracker));
        $w->display();
    }

    private function fetchFactoryButtons(array $klasses, Tracker $tracker)
    {
        $event = new FilterFormElementsThatCanBeCreatedForTracker($klasses, $tracker);
        $this->getEventManager()->processEvent($event);

        $html  = '';
        $html .= '<div class="tracker-admin-palette-content">';
        foreach ($event->getKlasses() as $type => $klass) {
            $html .= $this->getFactoryButton($klass, 'create-formElement[' .  urlencode($type) . ']', $tracker);
        }
        $html .= '</div>';

        return $html;
    }

    public function getFactoryButton($klass, $name, Tracker $tracker, $label = null, $description = null, $icon = null, $isUnique = null)
    {
        $hp           = Codendi_HTMLPurifier::instance();
        $button       = '';
        $button_class = 'button';
        if (! $label) {
            $label = $klass::getFactoryLabel();
        }
        if ($description === null) {
            $description = $klass::getFactoryDescription();
        }
        if (! $icon) {
            $icon = $klass::getFactoryIconCreate();
        }
        if ($this->isFieldUniqueAndAlreadyUsed($klass, $tracker, $isUnique)) {
            $button_class = 'button_disabled';
        }

        if (! $description) {
            $description = $label;
        }

        $button .= '<a class="' . $button_class . '" name="' . $name . '" title="' . $hp->purify($description, CODENDI_PURIFIER_CONVERT_HTML) . '"><span>';
        $button .= '<img width="16" height="16" alt="" src="' . $icon . '" />';
        $button .=  $hp->purify($label, CODENDI_PURIFIER_CONVERT_HTML);
        $button .= '</span></a>';

        return $button;
    }

    private function isFieldUniqueAndAlreadyUsed($klass, Tracker $tracker, $isUnique)
    {
        if ($isUnique === null) {
            $isUnique = $klass::getFactoryUniqueField();
        }

        if ($isUnique) {
            $type     = array_search($klass, array_merge($this->classnames, $this->special_classnames), true);
            $used     = true;
            $elements = $this->getFormElementsByType($tracker, $type, $used);
            if ($elements) {
                return true;
            }
        }
        return false;
    }

    public function displayAdminCreateFormElement(TrackerManager $tracker_manager, Codendi_Request $request, PFUser $current_user, $type, Tracker $tracker)
    {
        $row = [
            'formElement_type'  => $type,
            'id'                => 0,
            'tracker_id'        => $tracker->getId(),
            'parent_id'         => null,
            'name'              => null,
            'label'             => '',
            'description'       => null,
            'use_it'            => null,
            'scope'             => null,
            'rank'              => null,
            'required'          => 0,
            'notifications'     => 0,
            'original_field_id' => null,
        ];
        if ($form_element = $this->getInstanceFromRow($row)) {
            $form_element->setTracker($tracker);

            $klasses = array_merge($this->classnames, $this->special_classnames, $this->group_classnames, $this->staticfield_classnames);
            $klass   = $klasses[$type];

            //Waiting for PHP5.3 and $klass::staticMethod()
            $getFactoryLabel = new ReflectionMethod($klass, 'getFactoryLabel');
            $label           = $getFactoryLabel->invoke(null);

            $allUsedElements = $this->getUsedFormElementForTracker($tracker);
            if ($form_element instanceof Tracker_FormElement_Shared) {
                $visitor = new Tracker_FormElement_View_Admin_CreateSharedVisitor($allUsedElements);
            } else {
                $visitor = new Tracker_FormElement_View_Admin_CreateVisitor($allUsedElements);
                $this->getEventManager()->processEvent(
                    self::VIEW_ADMIN_CREATE_VISITOR,
                    [
                        'all_used_elements' => $allUsedElements,
                        'visitor'           => &$visitor,
                    ]
                );
            }

            $visitor->setType($type);
            $visitor->setLabel($label);

            $form_element->accept($visitor);
            $visitor->display($tracker_manager, $request);
        }
    }

    /**
     * @return EventManager
     */
    protected function getEventManager()
    {
        return EventManager::instance();
    }

    protected function typeIsValid($type)
    {
        return (
            isset($this->classnames[$type])
            || isset($this->special_classnames[$type])
            || isset($this->group_classnames[$type])
            || isset($this->staticfield_classnames[$type])
        );
    }

    public function createFormElement(Tracker $tracker, $type, $form_element_data, $tracker_is_empty, $force_absolute_ranking)
    {
        //Check that the label has been submitted
        if (isset($form_element_data['label']) && trim($form_element_data['label'])) {
            $label       = trim($form_element_data['label']);
            $description = isset($form_element_data['description']) ? trim($form_element_data['description']) : '';

            $rank = isset($form_element_data['rank']) ? $form_element_data['rank'] : 'end';

            //Check that the type is valid
            if ($this->typeIsValid($type)) {
                //extract the parent_id from rank if needed
                //rank = <parent_id>:<rank> | <rank>
                $parent_id = isset($form_element_data['parent_id']) ? $form_element_data['parent_id'] : 0;
                if (strpos($rank, ':') !== false) {
                    [$parent_id, $rank] = explode(':', $rank);
                }

                //Check that parent_id is valid
                if ($parent_id == 0 || $this->getFormElementById($parent_id)) {
                    $name = null;
                    if (isset($form_element_data['name']) && trim($form_element_data['name'])) {
                        $name = $form_element_data['name'];
                    } elseif ($label) {
                        $name = $this->deductNameFromLabel($label);
                    }
                    if ($name) {
                        $uniq = null;
                        while (! $uniq && $this->getFormElementByName($tracker->getId(), $name)) {
                            if ($uniq === null) {
                                $name .= '_';
                            }
                            $name .= '1';
                            $uniq  = false;
                        }
                    }

                    if ($this->isTypeAPrefix($type)) {
                        $prefix = $type;
                    } else {
                        $prefix = 'field_';
                    }

                    $is_required       = isset($form_element_data['required']) ? (bool) $form_element_data['required'] : false;
                    $notify            = isset($form_element_data['notifications']) ? (bool) $form_element_data['notifications'] : false;
                    $original_field_id = isset($form_element_data['original_field_id']) ? $form_element_data['original_field_id'] : null;

                    //Create the element
                    if (
                        $id = $this->getDao()->create(
                            $type,
                            $tracker->id,
                            $parent_id,
                            $name,
                            $prefix,
                            $label,
                            $description,
                            $form_element_data['use_it'],
                            'P',
                            $is_required,
                            $notify,
                            $rank,
                            $original_field_id,
                            $force_absolute_ranking
                        )
                    ) {
                        //Set permissions
                        if (! array_key_exists($type, array_merge($this->group_classnames, $this->staticfield_classnames))) {
                            $ugroups_permissions = $this->getPermissionsFromFormElementData($id, (array) $form_element_data);
                            if ($ugroups_permissions) {
                                plugin_tracker_permission_process_update_fields_permissions(
                                    $tracker->group_id,
                                    $tracker->id,
                                    $this->getFields($tracker),
                                    $ugroups_permissions
                                );
                            }
                        }

                        //Announce to the world that an element has been created
                        EventManager::instance()->processEvent(
                            'tracker_formElement_justcreated',
                            ['id' => $id,
                                'row' => $form_element_data,
                                'type' => $type,
                            ]
                        );
                        //Clear some internal cache
                        unset($this->formElements_by_parent[$parent_id]);

                        if ($form_element = $this->getFormElementById($id)) {
                            if (isset($form_element_data['specific_properties']) && is_array($form_element_data['specific_properties'])) {
                                $form_element->storeProperties($form_element_data['specific_properties']);
                            }

                            //All is done, the field may want to do some things depending on the request
                            $form_element->afterCreate((array) $form_element_data, $tracker_is_empty);

                            return $id;
                        }
                    }
                } else {
                    //Parent doesn't exist
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', 'Asked type is unknown');
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', 'Label is needed !');
        }
        return false;
    }

    /**
     * This function process formelement data
     * @param Array $form_element_data
     */
    public function getPermissionsFromFormElementData($elmtId, $form_element_data)
    {
        //WARNING : READ/UPDATE is actual when last is READ, UPDATE liste (weird case, but good to know)
        if (isset($form_element_data['permissions'])) {
            if ($ugroups_permissions = $form_element_data['permissions']) {
                foreach ($ugroups_permissions as $ugroup_id => $perms) {
                    $ugroups_permissions[$ugroup_id] = [];
                    foreach ($perms as $key => $value) {
                        $new_value                       = plugin_tracker_permission_get_input_value_from_permission($value);
                        $ugroups_permissions[$ugroup_id] = array_merge($ugroups_permissions[$ugroup_id], $new_value);
                    }
                }
                if ($ugroups_permissions) {
                    $ugroups_permissions = [$elmtId => $ugroups_permissions];
                }
            }
        } else {
            $ugroups_permissions = [$elmtId =>
                [
                    ProjectUGroup::ANONYMOUS       => plugin_tracker_permission_get_input_value_from_permission(Tracker_FormElement_Field::PERMISSION_READ),
                    ProjectUGroup::REGISTERED      => plugin_tracker_permission_get_input_value_from_permission(Tracker_FormElement_Field::PERMISSION_SUBMIT),
                    ProjectUGroup::PROJECT_MEMBERS => plugin_tracker_permission_get_input_value_from_permission(Tracker_FormElement_Field::PERMISSION_UPDATE),
                ],
            ];
        }
        return $ugroups_permissions;
    }

    /**
     * Creates new Tracker_Form element in the database
     *
     * @param Tracker $tracker of the created tracker
     * @param Tracker_FormElement $form_element
     * @param int $parent_id the id of the newly created parent formElement (0 when no parent)
     *
     * @return mixed the id of the newly created FormElement
     */
    public function saveObject(Tracker $tracker, $form_element, $parent_id, $tracker_is_empty, $force_absolute_ranking)
    {
        $form_element_data = $form_element->getFormElementDataForCreation($parent_id);
        $type              = $this->getType($form_element);

        if ($id = $this->createFormElement($tracker, $type, $form_element_data, $tracker_is_empty, $force_absolute_ranking)) {
            $form_element->setId($id);
            $form_element->afterSaveObject($tracker, $tracker_is_empty, $force_absolute_ranking);
        }
        return $id;
    }

    public function getGroupsByTrackerId($tracker_id)
    {
        $form_elements = [];
        foreach ($this->getDao()->searchByTrackerIdAndType($tracker_id, array_keys($this->group_classnames)) as $row) {
            $form_elements[] = $this->getCachedInstanceFromRow($row);
        }
        return array_filter($form_elements);
    }

    /**
     * Get the next used sibbling of an element.
     *
     * @param Tracker_FormElement $element
     *
     * @return Tracker_FormElement|null null if not found
     */
    public function getNextSibling($element)
    {
        $tracker_id = $element->getTrackerId();
        $element_id = $element->getId();
        if ($row = $this->getDao()->searchNextUsedSibling($tracker_id, $element_id)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }

    /**
     * Get the previous used sibbling of an element.
     *
     * @param Tracker_FormElement $element
     *
     * @return Tracker_FormElement | null if not found
     */
    public function getPreviousSibling($element)
    {
        $tracker_id = $element->getTrackerId();
        $element_id = $element->getId();
        if ($row = $this->getDao()->searchPreviousUsedSibling($tracker_id, $element_id)->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }

    /**
     * @return Tracker_FormElement
     */
    public function getFieldFromTrackerAndSharedField(Tracker $tracker, Tracker_FormElement $shared)
    {
        $dar = $this->getDao()->searchFieldFromTrackerIdAndSharedFieldId($tracker->getId(), $shared->getId());
        return $this->getInstanceFromDar($dar);
    }

    public function getInstanceFromDar($dar)
    {
        if ($dar && $row = $dar->getRow()) {
            return $this->getCachedInstanceFromRow($row);
        }
    }

    public function isFieldASimpleListField(Tracker_FormElement_Field $field)
    {
        return in_array($this->getType($field), ["sb", "rb"]);
    }

    /**
     * @return bool
     */
    public function isFieldAFileField(Tracker_FormElement_Field $field)
    {
        return $this->getType($field) === 'file';
    }
}
