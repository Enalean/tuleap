<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 * Copyright (c) Enalean, 2015 - Present. All Rights Reserved.
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

use Tuleap\Tracker\XML\TrackerXmlImportFeedbackCollector;

/**
 * Base class for all fields in trackers, from fieldsets to selectboxes
 */
abstract class Tracker_FormElement implements Tracker_FormElement_Interface, Tracker_FormElement_IProvideFactoryButtonInformation, Tracker_IProvideJsonFormatOfMyself
{
    public const PERMISSION_READ   = 'PLUGIN_TRACKER_FIELD_READ';
    public const PERMISSION_UPDATE = 'PLUGIN_TRACKER_FIELD_UPDATE';
    public const PERMISSION_SUBMIT = 'PLUGIN_TRACKER_FIELD_SUBMIT';

    public const REST_PERMISSION_READ   = 'read';
    public const REST_PERMISSION_UPDATE = 'update';
    public const REST_PERMISSION_SUBMIT = 'submit';

    public const PROJECT_HISTORY_UPDATE = 'tracker_formelement_update';

    public const XML_ID_PREFIX          = 'F';
    public const XML_TAG_EXTERNAL_FIELD = 'externalField';
    public const XML_TAG                = 'formElement';

    /**
     * Get the visitor responsible of the display of update interface for the element
     *
     * Params:
     *  - all_used_elements => Tracker_FormElement[]
     *  - visitor           => (output) Tracker_FormElement_View_Admin_UpdateVisitor
     */
    public const VIEW_ADMIN_UPDATE_VISITOR = 'tracker_formelement_view_admin_update_visitor';

    /**
     * The field id
     */
    public $id;

    /**
     * The tracker id
     */
    public $tracker_id;

    /**
     * @var Tracker
     */
    private $tracker;

    /**
     * Id of the fieldcomposite this field belongs to
     */
    public $parent_id;

    /**
     * The name
     *
     * @var string $name
     */
    public $name;

    /**
     * The label
     */
    public $label;

    /**
     * The description
     *
     * @var string $description
     */
    public $description;

    /**
     * Is the field used?
     */
    public $use_it;

    /**
     * The scope of the field: S: system or P:project
     */
    public $scope;

    /**
     * Is the field is required?
     */
    public $required;

    /**
     * Is the field has notifications
     */
    public $notifications;

    /**
     * The rank
     *
     * @var string $rank
     */
    public $rank;

    /**
     * @var Tracker_FormElement
     */
    protected $original_field = null;

    /**
     * @var Tracker_FormElementFactory
     */
    private $formElementFactory;

    /**
     * Base constructor
     *
     * @param int    $id                          The id of the field
     * @param int    $tracker_id                  The id of the tracker this field belongs to
     * @param int    $parent_id                   The id of the parent element
     * @param string $name                        The short name of the field
     * @param string $label                       The label of the element
     * @param string $description                 The description of the element
     * @param bool   $use_it                      Is the element used?
     * @param string $scope                       The scope of the plugin 'S' | 'P'
     * @param bool   $required                    Is the element required? Todo: move this in field?
     * @param int    $rank                        The rank of the field (in the parent)
     * @param Tracker_FormElement $original_field The field the current field is refering to (null if no references)
     *
     * @return void
     */
    public function __construct($id, $tracker_id, $parent_id, $name, $label, $description, $use_it, $scope, $required, $notifications, $rank, ?Tracker_FormElement $original_field = null)
    {
        $this->id             = $id;
        $this->tracker_id     = $tracker_id;
        $this->parent_id      = $parent_id;
        $this->name           = $name;
        $this->label          = $label;
        $this->description    = $description;
        $this->use_it         = $use_it;
        $this->scope          = $scope;
        $this->required       = $required;
        $this->notifications  = $notifications;
        $this->rank           = $rank;
        $this->original_field = $original_field;
    }

    public function getScope()
    {
        return $this->scope;
    }
    public function getParentId()
    {
        return $this->parent_id;
    }
    public function getRank()
    {
        return $this->rank;
    }

    /**
     *  Return true if the field is used
     *
     * @return bool
     */
    public function isUsed()
    {
        return( $this->use_it );
    }

    /**
     * @return array
     */
    public function getFormElementDataForCreation($parent_id)
    {
        $form_element_data = array(
            'name'          => $this->name,
            'label'         => $this->label,
            'parent_id'     => $parent_id,
            'description'   => $this->description,
            'label'         => $this->label,
            'use_it'        => $this->use_it,
            'scope'         => $this->scope,
            'required'      => $this->required,
            'notifications' => $this->notifications,
            'rank'          => $this->rank,
            'permissions'   => $this->getPermissionsByUgroupId(),
            'specific_properties' => $this->getFlattenPropertiesValues()
        );

        return $form_element_data;
    }

    public function isCSVImportable()
    {
        return false;
    }

    private function getTriggerManager()
    {
        return TrackerFactory::instance()->getTriggerRulesManager();
    }

    protected function isUsedInTrigger()
    {
        return $this->getTriggerManager()->isUsedInTrigger($this);
    }

    /**
     * Process the request
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data coming from the user
     * @param PFUser                           $current_user    The user who mades the request
     *
     * @return void
     */
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        switch ($request->get('func')) {
            case 'admin-formElement-update':
                $this->processUpdate($layout, $request, $current_user);
                $this->displayAdminFormElement($layout, $request, $current_user);
                break;
            case 'admin-formElement-remove':
                if ($this->isUsedInTrigger()) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'used_in_triggers'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . (int) $this->tracker_id . '&func=admin-formElements');
                }

                if (Tracker_FormElementFactory::instance()->removeFormElement($this->id)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'field_removed'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . (int) $this->tracker_id . '&func=admin-formElements');
                }
                $this->getTracker()->displayAdminFormElements($layout, $request, $current_user);
                break;
            case 'admin-formElement-delete':
                if ($this->delete() && Tracker_FormElementFactory::instance()->deleteFormElement($this->id)) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'field_deleted'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . (int) $this->tracker_id . '&func=admin-formElements');
                }
                $this->getTracker()->displayAdminFormElements($layout, $request, $current_user);
                break;
            default:
                break;
        }
    }

    /**
     * Update the form element
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data coming from the user
     * @param PFUser                           $current_user    The user who mades the request
     * @param bool                           $redirect        Do we need to redirect? default is false
     *
     * @return void
     */
    protected function processUpdate(Tracker_IDisplayTrackerLayout $layout, $request, $current_user, $redirect = false)
    {
        if (is_array($request->get('formElement_data'))) {
            $formElement_data = $request->get('formElement_data');
            //First store the specific properties if needed
            if (!isset($formElement_data['specific_properties']) || !is_array($formElement_data['specific_properties']) || $this->storeProperties($formElement_data['specific_properties'])) {
                //Then store the formElement itself
                if (Tracker_FormElementFactory::instance()->updateFormElement($this, $formElement_data)) {
                    $history_dao = new ProjectHistoryDao();
                    $history_dao->groupAddHistory(
                        self::PROJECT_HISTORY_UPDATE,
                        '#' . $this->getId() . ' ' . $this->getLabel() . ' (' . $this->getTracker()->getName() . ')',
                        $this->getTracker()->getProject()->getId()
                    );
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'field_updated'));
                    if ($request->isAjax()) {
                        echo $this->fetchAdminFormElement();
                        exit;
                    } else {
                        $redirect = true;
                    }
                }
            }
        } elseif ($request->get('change-type')) {
            if (Tracker_FormElementFactory::instance()->changeFormElementType($this, $request->get('change-type'))) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'field_type_changed'));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'field_type_not_changed'));
            }
            $redirect = true;
        }
        if ($redirect) {
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . (int) $this->tracker_id . '&func=admin-formElements');
        }
    }

    /**
     * Return the tracker of this formElement
     *
     * @return Tracker|null
     */
    public function getTracker()
    {
        if (!$this->tracker) {
            $tracker = TrackerFactory::instance()->getTrackerById($this->tracker_id);
            if ($tracker === null) {
                throw new RuntimeException('Tracker does not exist');
            }
            $this->tracker = $tracker;
        }
        return $this->tracker;
    }

    public function setTracker(Tracker $tracker)
    {
        $this->tracker    = $tracker;
        $this->tracker_id = $tracker->getId();
    }

    /**
     * Return the tracker id of this formElement
     *
     * @return int
     */
    public function getTrackerId()
    {
        return $this->tracker_id;
    }

    /**
     * Fetch the "add criteria" box in query form
     *
     * @param array  $used   Current used formElements as criteria.
     * @param string $prefix Prefix to add before label in optgroups
     *
     * @return string
     */

    abstract public function fetchAddCriteria($used, $prefix = '');

    /**
     * Fetch the "add column" box in table renderer
     *
     * @param array  $used   Current used formElements as column.
     * @param string $prefix Prefix to add before label in optgroups
     *
     * @return string
     */
    abstract public function fetchAddColumn($used, $prefix = '');

    /**
     * Fetch the "add tooltip" box in admin
     *
     * @param array  $used   Current used fields as column.
     * @param string $prefix Prefix to add before label in optgroups
     *
     * @return string
     */
    abstract public function fetchAddTooltip($used, $prefix = '');

    /**
     *
     * @param Tracker_Artifact $artifact
     * @param string $format
     * @return string
     */
    public function fetchMailFormElements($artifact, $format = 'text', $ignore_perms = false)
    {
        $text = '';
        foreach ($this->getFormElements() as $formElement) {
            $text .= $formElement->getLabel();
            $text .= ' : ';
            $text .= $formElement->fetchMailArtifact($artifact, $format, $ignore_perms);
            $text .= PHP_EOL;
        }
        return $text;
    }

    /**
     * Duplicate a field. If the field has custom properties,
     * they should be propagated to the new one
     *
     * @param int $from_field_id The id of the field
     *
     * @return array the mapping between old values and new ones
     */
    public function duplicate($from_field_id)
    {
        $dao = $this->getDao();
        if ($dao) {
            $dao->duplicate($from_field_id, $this->getId());
        }
        return array();
    }

    /**
     * Display the form to administrate the element
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The data coming from the user
     * @param PFUser                           $current_user    The user who mades the request
     *
     * @return void
     */
    public function displayAdminFormElement(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        $allUsedElements = $this->getFormElementFactory()->getUsedFormElementForTracker($this->getTracker());
        if ($this->isTargetSharedField()) {
            $visitor = new Tracker_FormElement_View_Admin_UpdateSharedVisitor($allUsedElements);
        } else {
            $visitor = new Tracker_FormElement_View_Admin_UpdateVisitor($allUsedElements);
            EventManager::instance()->processEvent(
                self::VIEW_ADMIN_UPDATE_VISITOR,
                array(
                    'all_used_elements' => $allUsedElements,
                    'visitor'           => &$visitor
                )
            );
        }
        $this->accept($visitor);
        $visitor->display($layout, $request);
    }

    public function setFormElementFactory(Tracker_FormElementFactory $factory)
    {
        $this->formElementFactory = $factory;
    }

    /**
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory()
    {
        if (!$this->formElementFactory) {
            $this->formElementFactory = Tracker_FormElementFactory::instance();
        }
        return $this->formElementFactory;
    }

    /**
     * Get the rank structure for the selectox
     *
     * @return array html
     */
    public function getRankSelectboxDefinition()
    {
        return array(
            'id'   => $this->id,
            'name' => $this->getLabel(),
            'rank' => $this->rank,
        );
    }

    public function fetchFormattedForJson()
    {
        return array(
            'id'    => $this->id,
            'name'  => $this->getName(),
            'label' => $this->getLabel(),
        );
    }

    /**
     * Get the use_it row for the element
     *
     * @return string html
     */
    public function fetchAdminAdd()
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $html .= '<tr><td>';
        $html .= Tracker_FormElementFactory::instance()->getFactoryButton(self::class, 'add-formElement[' . $this->id . ']', $this->getTracker(), $this->label, $this->description, $this->getFactoryIconUseIt());
        $html .= '</td><td>';
        $html .= '<a href="' . $this->getAdminEditUrl() . '" title="' . $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'edit_field') . '">' . $GLOBALS['HTML']->getImage('ic/edit.png', array('alt' => 'edit')) . '</a> ';
        $confirm = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'delete_field') . ' ' . $this->getLabel() . '?';
        $query = http_build_query(
            array(
                'tracker'  => $this->getTracker()->id,
                'func'     => 'admin-formElement-delete',
                'formElement'    => $this->id,
            )
        );
        $html .= '<a class="delete-field"
                     onclick="return confirm(\'' . $hp->purify($confirm, CODENDI_PURIFIER_JS_QUOTE) . '\')"
                     title="' . $hp->purify($confirm) . '"
                     href="?' . $query . '">' . $GLOBALS['HTML']->getImage('ic/bin_closed.png', array('alt' => 'delete')) . '</a>';
        $html .= '</td></tr>';
        return $html;
    }

    abstract public function fetchAdmin(Tracker $tracker);

    public $default_properties = array();
    protected $cache_specific_properties;

    /**
     * Get a property value identified by its key
     *
     * @param string $key The key of the property
     *
     * @return mixed or null if not found
     */
    public function getProperty($key)
    {
        return $this->getPropertyValueInCollection($this->getProperties(), $key);
    }

    /**
     * Retreive a property value in the recursive collection $array
     *
     * @param array  $array The collection or subcollection to search for
     * @param string $key   The property to search
     *
     * @return mixed the value or null if not found
     */
    protected function getPropertyValueInCollection($array, $key)
    {
        $found = null;
        if (isset($array[$key])) {
            $found = $array[$key]['value'];
        } else {
            foreach ($array as $k => $v) {
                if ($v['type'] == 'radio') {
                    if (($found = $this->getPropertyValueInCollection($v['choices'], $key)) !== null) {
                        break;
                    }
                }
            }
        }
        return $found;
    }

    /**
     * Get the dao of the field
     *
     * @return DataAccessObject
     */
    protected function getDao()
    {
        return null;
    }

    /**
     * Get the properties of the field
     *
     * @return array
     */
    public function getProperties()
    {
        if (!$this->cache_specific_properties) {
            $this->cache_specific_properties = $this->default_properties;
            if ($this->getDao() && ($row = $this->getDao()->searchByFieldId($this->id)->getRow())) {
                foreach ($row as $key => $value) {
                    $this->setPropertyValue($this->cache_specific_properties, $key, $value);
                }
            }
        }
        return $this->cache_specific_properties;
    }

    /** @deprecated For unit tests only */
    public function setCacheSpecificProperties(array $cache_specific_properties)
    {
        $this->cache_specific_properties = $cache_specific_properties;
    }

    /**
     * Get the properties as a unique, flattened array
     *
     * @return array
     */
    protected function getFlattenProperties($p)
    {
        $properties = array();
        foreach ($p as $key => $property) {
            $properties[$key] = $property;
            if (!empty($property['type'])) {
                switch ($property['type']) {
                    case 'radio':
                        $properties = array_merge($properties, $this->getFlattenProperties($property['choices']));
                        break;
                    default:
                        break;
                }
            }
        }
        return $properties;
    }

    /**
     * Get the properties values as a unique, flattened array
     *
     * @return array
     */
    public function getFlattenPropertiesValues()
    {
        $properties = array();
        foreach ($this->getFlattenProperties($this->getProperties()) as $key => $prop) {
            if (is_array($prop)) {
                $properties[$key] = $prop['value'];
            } else {
                $properties[$key] = $prop;
            }
        }
        return $properties;
    }

    /**
     * Look for a suitable property and set its value
     *
     * @param mixed &$array The array or subarray storing properties
     * @param mixed $key    The property to search
     * @param array $value  The value to set if the property is found
     *
     * @see getProperties
     * @return void
     */
    protected function setPropertyValue(&$array, $key, $value)
    {
        if ($key !== 'field_id') {
            if (isset($array[$key])) {
                $array[$key]['value'] = $value;
            } else {
                foreach ($array as $k => $v) {
                    if ($v['type'] == 'radio') {
                        $this->setPropertyValue($array[$k]['choices'], $key, $value);
                    }
                }
            }
        }
    }

    /**
     * Store the specific properties of the formElement
     *
     * @param array $properties The properties
     *
     * @return bool true if success
     */
    public function storeProperties($properties)
    {
        $success = true;
        $dao = $this->getDao();

        if ($dao && ($success = $dao->save($this->id, $properties))) {
            $this->cache_specific_properties = null; //force reload
        }
        return $success;
    }

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    abstract public function fetchSubmit(array $submitted_values);

    abstract public function fetchSubmitForOverlay(array $submitted_values);

    /**
     * Fetch the element for the submit new artifact form
     *
     * @return string html
     */
    abstract public function fetchSubmitMasschange();

    /**
     * Fetch the element for the update artifact form
     *
     * @return string html
     */
    abstract public function fetchArtifact(
        Tracker_Artifact $artifact,
        array $submitted_values,
        array $additional_classes
    );

    abstract public function fetchArtifactForOverlay(Tracker_Artifact $artifact, array $submitted_values);

    /**
     * Fetch the element for the artifact in read only
     *
     * @param Tracker_Artifact $artifact The artifact
     *
     * @return string html
     */
    abstract public function fetchArtifactReadOnly(Tracker_Artifact $artifact, array $submitted_values);

    /**
     * @return mixed
     */
    abstract public function fetchArtifactCopyMode(Tracker_Artifact $artifact, array $submitted_values);

    /**
     * Fetch mail rendering in a given format
     * @param string $format
     * @return string formatted output
     */
    public function fetchMail($format = 'text')
    {
        return '';
    }

    /**
     *
     * @return string
     */
    public function fetchMailArtifact($recipient, Tracker_Artifact $artifact, $format = 'text', $ignore_perms = false)
    {
        return '';
    }

    /**
     * Prepare the element to be displayed
     *
     * @return void
     */
    public function prepareForDisplay()
    {
        //do nothing per default
    }

    /**
     * Returns the value that will be displayed in a mail
     * @param bool $ignore_perms
     * @param String $format
     *
     * @return String
     */
    public function fetchMailArtifactValue(
        Tracker_Artifact $artifact,
        PFUser $user,
        $ignore_perms,
        ?Tracker_Artifact_ChangesetValue $value = null,
        $format = 'text'
    ) {
        return '';
    }

    /**
     * Get the label of a property by key
     *
     * @param string $key the key of the property
     *
     * @return string the label
     */
    public function getPropertyLabel($key)
    {
        switch ($key) {
            case 'hint':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'hint');
            case 'default_value_type':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'default_value_type');
            case 'size':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'size');
            case 'maxchars':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'maxchars');
            case 'rows':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'rows');
            case 'cols':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'cols');
            case 'static_value':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'static_value');
            case 'default_value_today':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'default_value_today');
            case 'target_field_name':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'target_field_name');
            case 'use_capacity':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'use_capacity');
            case 'include_weekends':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'include_weekends');
            case 'display_time':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'display_time');
            case 'use_cache':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'use_cache');
            case 'fast_compute':
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'fast_compute');
            case 'default_value':
            default:
                return $GLOBALS['Language']->getText('plugin_tracker_formelement_property', 'default_value');
        }
    }

    /**
     * Update the properties of the formElement
     *
     * @param array $properties all the properties of the element
     *
     * @return bool true if the update is successful
     */
    public function updateProperties($properties)
    {
        if (isset($properties['label']) && !trim($properties['label'])) {
            return false;
        }
        $this->parent_id     = isset($properties['parent_id'])     ? $properties['parent_id']               : $this->parent_id;
        $this->name          = isset($properties['name'])          ? $properties['name']                    : $this->name;
        $this->label         = isset($properties['label'])         ? $properties['label']                   : $this->label;
        $this->description   = isset($properties['description'])   ? $properties['description']             : $this->description;
        $this->use_it        = isset($properties['use_it'])        ? ($properties['use_it'] ? 1 : 0)        : $this->use_it;
        $this->scope         = isset($properties['scope'])         ? $properties['scope']                   : $this->scope;
        $this->required      = isset($properties['required'])      ? ($properties['required'] ? 1 : 0)      : $this->required;
        $this->notifications = isset($properties['notifications']) ? ($properties['notifications'] ? 1 : 0) : $this->notifications;
        $this->rank          = isset($properties['rank'])          ? $properties['rank']                    : $this->rank;
        return $this->updateSpecificProperties($properties);
    }

    /**
     * Update the specific properties of the formElement
     *
     * @param array $properties the specific properties
     *
     * @return bool true if the update is successful
     */
    public function updateSpecificProperties($properties)
    {
        //TODO make it abstract
        return true;
    }

    /**
     * Change the type of the formElement
     *
     * @param string $type the new type
     *
     * @return bool true if the change is allowed and successful
     */
    public function changeType($type)
    {
        // Default: type change is not allowed, so return false
        return false;
    }

    /**
     * Display the html f in the admin ui
     *
     * @return string html
     */
    abstract protected function fetchAdminFormElement();

    /**
     * Compute the url to edit the element
     *
     * @return string url to display in html (&amp; instead of &)
     */
    public function getAdminEditUrl()
    {
        return TRACKER_BASE_URL . '/?tracker=' . (int) $this->getTracker()->getId() . '&amp;func=admin-formElement-update&amp;formElement=' . $this->id;
    }

    /**
     * Transforms FormElement into a SimpleXMLElement
     */
    public function exportToXml(
        SimpleXMLElement $root,
        &$xmlMapping,
        $project_export_context,
        UserXMLExporter $user_xml_exporter
    ) {
        $cdata_section_factory = new XML_SimpleXMLCDATAFactory();

        $root->addAttribute('type', Tracker_FormElementFactory::instance()->getType($this));
        // this id is internal to XML
        $ID = $this->getXMLId();
        $xmlMapping[$ID] = $this->id;
        $root->addAttribute('ID', $ID);
        $root->addAttribute('rank', $this->rank);
        $root->addAttribute('id', $this->id);
        $root->addAttribute('tracker_id', $this->tracker_id);
        $root->addAttribute('parent_id', $this->parent_id);
        // ony add if values are different from default
        if (!$this->use_it) {
            $root->addAttribute('use_it', $this->use_it);
        }
        // TODO: decide which scope is default P or S
        if (($this->scope) && ($this->scope != 'P')) {
            $root->addAttribute('scope', $this->scope);
        }
        if ($this->required) {
            $root->addAttribute('required', $this->required);
        }
        if ($this->notifications) {
            $root->addAttribute('notifications', $this->notifications);
        }

        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($root, 'name', $this->name);
        $cdata_section_factory->insert($root, 'label', $this->label);
        // only add if not empty
        if ($this->description) {
            $cdata_section_factory->insert($root, 'description', $this->description);
        }
        if ($this->getProperties()) {
            $this->exportPropertiesToXML($root);
        }
    }

    public function getXMLId()
    {
        return self::XML_ID_PREFIX . $this->getId();
    }

    /**
     * Export form element properties into a SimpleXMLElement
     *
     * @param SimpleXMLElement &$root The root element of the form element
     *
     * @return void
     */
    public function exportPropertiesToXML(&$root)
    {
        $child = $root->addChild('properties');
        foreach ($this->getProperties() as $name => $property) {
            if (!empty($property['value'])) {
                $child->addAttribute($name, $property['value']);
            }
        }
    }

    public function exportPermissionsToXML(SimpleXMLElement $node_perms, array $ugroups, &$xmlMapping)
    {
        if ($permissions = $this->getPermissionsByUgroupId()) {
            foreach ($permissions as $ugroup_id => $permission_types) {
                if (($ugroup = array_search($ugroup_id, $ugroups)) !== false && $this->isUsed()) {
                    foreach ($permission_types as $permission_type) {
                        $node_perm = $node_perms->addChild('permission');
                        $node_perm->addAttribute('scope', 'field');
                        $node_perm->addAttribute('REF', array_search($this->getId(), $xmlMapping));
                        $node_perm->addAttribute('ugroup', $ugroup);
                        $node_perm->addAttribute('type', $permission_type);
                        unset($node_perm);
                    }
                }
            }
        }
    }

    /**
     * Continue the initialisation from an xml (FormElementFactory is not smart enough to do all stuff.
     * Polymorphism rulez!!!
     *
     * @param SimpleXMLElement $xml         containing the structure of the imported Tracker_FormElement
     * @param array            &$xmlMapping where the newly created formElements indexed by their XML IDs are stored (and values)
     *
     * @return void
     */
    public function continueGetInstanceFromXML(
        $xml,
        &$xmlMapping,
        User\XML\Import\IFindUserFromXMLReference $user_finder,
        TrackerXmlImportFeedbackCollector $feedback_collector
    ) {
        // add properties to specific fields
        if (isset($xml->properties)) {
            foreach ($xml->properties->attributes() as $name => $prop) {
                $this->default_properties[(string) $name] = (string) $prop;
            }
        }
    }

    /**
     * Callback called after factory::saveObject. Use this to do post-save actions
     *
     * @param Tracker $tracker The tracker
     *
     * @param bool $tracker_is_empty
     * @return void
     */
    public function afterSaveObject(Tracker $tracker, $tracker_is_empty, $force_absolute_ranking)
    {
        //do nothing per default
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

    /**
     *  Set the id
     *
     * @param int $id
     *
     * @return Tracker_FormElement
     */
    public function setId($id)
    {
        $this->id = $id;
        return $this;
    }

    /**
     *  Get the id
     *
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Hook called after a creation of a formelement
     *
     * @param array $form_element_data
     * @param bool $tracker_is_empty
     * @return void
     */
    public function afterCreate(array $form_element_data, $tracker_is_empty)
    {
    }

    /**
     * The element is permanently deleted from the db
     * This hooks is here to delete specific properties,
     *  specific values of the element... all its dependencies.
     * (The element itself will be deleted later)
     *
     * @return bool true if success
     */
    public function delete()
    {
        return true;
    }

    /**
     *  Get the label attribute value
     *
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     *  Get the name attribute value (internal field name)
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     *  Get the description attribute value
     *
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Say if the element has notifications
     *
     * @return bool
     */
    public function hasNotifications()
    {
        return $this->notifications;
    }

    public function getOriginalFieldId()
    {
        if ($this->original_field) {
            return $this->original_field->getId();
        }
        return 0;
    }

    public function getOriginalField()
    {
        return $this->original_field;
    }

    public function getOriginalTracker()
    {
        return $this->getOriginalField()->getTracker();
    }

    public function getOriginalProject()
    {
        return $this->getOriginalTracker()->getProject();
    }

    /**
     * Returns true if the field is a copy of another one
     *
     * @return bool
     */
    public function isTargetSharedField()
    {
        return $this->original_field !== null;
    }

    /**
     * Returns FormElements that are a copy of the current FormElement
     *
     * @return Array of FormElement
     */
    public function getSharedTargets()
    {
        return $this->getFormElementFactory()->getSharedTargets($this);
    }

    /**
     * Get a recipients list for notifications. This is filled by users fields for example.
     *
     * @param Tracker_Artifact_ChangesetValue $changeset_value The changeset
     *
     * @return string[]
     */
    public function getRecipients(Tracker_Artifact_ChangesetValue $changeset_value)
    {
        return array();
    }

    /**
     * Get the current user
     *
     * @return PFUser
     */
    protected function getCurrentUser()
    {
        return UserManager::instance()->getCurrentUser();
    }

    /**
     * Say if a user has permission. Checks super user status.
     * Do not call this directly. Use userCanRead, userCanUpdate or userCanSubmit instead.
     *
     * @param string $permission_type PLUGIN_TRACKER_FIELD_READ | PLUGIN_TRACKER_FIELD_UPDATE | PLUGIN_TRACKER_FIELD_SUBMIT
     * @param PFUser  $user             The user. if null given take the current user
     *
     * @return bool
     */
    protected function userHasPermission($permission_type, ?PFUser $user = null)
    {
        if ($user instanceof Tracker_Workflow_WorkflowUser) {
            return true;
        }

        if ($permission_type === self::PERMISSION_READ && $user instanceof Tracker_UserWithReadAllPermission) {
            return true;
        }

        if (! $user) {
            $user = $this->getCurrentUser();
        }
        return $user->isSuperUser() || PermissionsManager::instance()->userHasPermission(
            $this->id,
            $permission_type,
            $user->getUgroups(
                $this->getTracker()->getGroupId(),
                array(
                    'tracker' => $this->getTrackerId()
                )
            )
        );
    }

    private $user_can_read = array();

    /**
     * return true if user has Read or Update permission on this field
     *
     * @param PFUser $user The user. if not given or null take the current user
     *
     * @return bool
     */
    public function userCanRead(?PFUser $user = null)
    {
        if (! $user) {
            $user = $this->getCurrentUser();
        }

        if (! isset($this->user_can_read[$user->getId()])) {
            $this->user_can_read[$user->getId()] = $this->userHasPermission(self::PERMISSION_READ, $user)
                || $this->userHasPermission(self::PERMISSION_UPDATE, $user);
        }
        return $this->user_can_read[$user->getId()];
    }

    /**
     * return true if user has Update permission on this field
     *
     * @param PFUser $user The user. if not given or null take the current user
     *
     * @return bool
     */
    public function userCanUpdate(?PFUser $user = null)
    {
        return $this->isUpdateable() && $this->userHasPermission(self::PERMISSION_UPDATE, $user);
    }

    /**
     * return true if user has Submit permission on this field
     *
     * @param PFUser $user The user. if not given or null take the current user
     *
     * @return bool
     */
    public function userCanSubmit(?PFUser $user = null)
    {
        return $this->isSubmitable() && $this->userHasPermission(self::PERMISSION_SUBMIT, $user);
    }

    /**
     * return true if users in ugroups have Read permission on this field
     *
     * @param array $ugroups the ugroups users are part of
     *
     * @return bool
     */
    protected function ugroupsCanRead($ugroups)
    {
        $pm = PermissionsManager::instance();
        $ok = $pm->userHasPermission($this->id, self::PERMISSION_READ, $ugroups);
        return $ok;
    }

    /**
     * return true if users in ugroups have Update permission on this field
     *
     * @param array $ugroups the ugroups users are part of
     *
     * @return bool
     */
    protected function ugroupsCanUpdate($ugroups)
    {
        $pm = PermissionsManager::instance();
        $ok = $pm->userHasPermission($this->id, self::PERMISSION_UPDATE, $ugroups);
        return $ok;
    }

    /**
     * return true if users in ugroups have Submit permission on this field
     *
     * @param array $ugroups the ugroups users are part of
     *
     * @return bool
     */
    protected function ugroupsCanSubmit($ugroups)
    {
        $pm = PermissionsManager::instance();
        $ok = $pm->userHasPermission($this->id, self::PERMISSION_SUBMIT, $ugroups);
        return $ok;
    }

    /**
     * Retrieve users permissions (PLUGIN_TRACKER_FIELD_SUBMIT, -UPDATE, -READ)
     * on this field.
     *
     * @param array $ugroups the ugroups users are part of (called from Tracker_Html createMailForUsers)
     *
     * @return array of all associated permissions
     */
    protected function getPermissionForUgroups($ugroups)
    {
        $perms = array();
        if ($this->ugroupsCanRead($ugroups)) {
            $perms[] = self::PERMISSION_READ;
        }
        if ($this->ugroupsCanUpdate($ugroups)) {
            $perms[] = self::PERMISSION_UPDATE;
        }
        if ($this->ugroupsCanSubmit($ugroups)) {
            $perms[] = self::PERMISSION_SUBMIT;
        }
        return $perms;
    }

    /**
     * Say if the field is readable
     *
     * @return bool
     */
    public function isReadable()
    {
        return true;
    }

    /**
     * Say if the field is updateable
     *
     * @return bool
     */
    public function isUpdateable()
    {
        return !is_a($this, 'Tracker_FormElement_Field_ReadOnly');
    }

    /**
     * Say if the field is submitable
     *
     * @return bool
     */
    public function isSubmitable()
    {
        return !is_a($this, 'Tracker_FormElement_Field_ReadOnly');
    }

    /**
     * Generates a non-empty message string if the element cannot be
     * removed from usage; returns an empty string otherwise.
     *
     * @return string returns a message
     */
    abstract public function getCannotRemoveMessage();

    /**
     * Is the form element can be removed from usage?
     * This method is to prevent tracker inconsistency
     *
     * @return bool
     */
    abstract public function canBeRemovedFromUsage();

    protected $cache_permissions;
    /**
     * get the permissions for this field
     *
     * @return array
     */
    public function getPermissionsByUgroupId()
    {
        return array();
    }

    /**
     * Set the cache permission for the ugroup_id
     * Use during the two-step xml import
     *
     * @param int    $ugroup_id The ugroup id
     * @param string $permission_type The permission type
     *
     * @return void
     */
    public function setCachePermission($ugroup_id, $permission_type)
    {
        $this->cache_permissions[$ugroup_id][] = $permission_type;
    }

    /**
     * @return bool say if the field is a unique one
     */
    public static function getFactoryUniqueField()
    {
        return false;
    }

    /**
     * Format a timestamp into Y-m-d format
     */
    public function formatDate($date)
    {
        return format_date(Tracker_FormElement_DateFormatter::DATE_FORMAT, (float) $date, '');
    }

    public function exportCurrentUserPermissionsToREST(PFUser $user)
    {
        $permissions = array();

        if ($this->userCanRead($user)) {
            $permissions[] = self::REST_PERMISSION_READ;
        }

        if ($this->userCanUpdate($user)) {
            $permissions[] = self::REST_PERMISSION_UPDATE;
        }

        if ($this->userCanSubmit($user)) {
            $permissions[] = self::REST_PERMISSION_SUBMIT;
        }
        return $permissions;
    }

    public function setCriteriaValueFromREST(Tracker_Report_Criteria $criteria, array $rest_criteria_value)
    {
        return false;
    }


    /**
     * Return underlying content. Should be overwritten in container fields
     */
    public function getRESTContent()
    {
        return null;
    }

    abstract public function getRESTAvailableValues();

    /**
     * Get binding data for REST
     *
     * @return array the binding data
     */
    public function getRESTBindingProperties()
    {
        return array(
            'bind_type' => null,
            'bind_list' => array()
        );
    }

    abstract public function getDefaultRESTValue();

    public function getTagNameForXMLExport(): string
    {
        return self::XML_TAG;
    }
}
