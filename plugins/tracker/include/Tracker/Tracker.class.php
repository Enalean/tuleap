<?php
/**
 * Copyright (c) Enalean, 2012 - 2018. All Rights Reserved.
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

use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletorBuilder;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueConvertor;
use Tuleap\Tracker\FormElement\View\Admin\DisplayAdminFormElementsWarningsEvent;
use Tuleap\Tracker\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\CollectionOfUserInvolvedInNotificationPresenterBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationsAddressesBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationsEmailRetriever;
use Tuleap\Tracker\Notifications\GlobalNotificationSubscribersFilter;
use Tuleap\Tracker\Notifications\NotificationLevelExtractor;
use Tuleap\Tracker\Notifications\NotificationListBuilder;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsDAO;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;
use Tuleap\Tracker\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\RecentlyVisited\VisitRetriever;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookRetriever;
use Tuleap\Tracker\Webhook\WebhookStatusLogger;
use Tuleap\Tracker\XML\Updater\FieldChange\FieldChangeComputedXMLUpdater;
use Tuleap\Webhook\Emitter;

require_once('common/date/DateHelper.class.php');
require_once('common/widget/Widget_Static.class.php');

require_once('json.php');

class Tracker implements Tracker_Dispatchable_Interface
{
    const PERMISSION_ADMIN               = 'PLUGIN_TRACKER_ADMIN';
    const PERMISSION_FULL                = 'PLUGIN_TRACKER_ACCESS_FULL';
    const PERMISSION_ASSIGNEE            = 'PLUGIN_TRACKER_ACCESS_ASSIGNEE';
    const PERMISSION_SUBMITTER           = 'PLUGIN_TRACKER_ACCESS_SUBMITTER';
    const PERMISSION_NONE                = 'PLUGIN_TRACKER_NONE';
    const PERMISSION_SUBMITTER_ONLY      = 'PLUGIN_TRACKER_ACCESS_SUBMITTER_ONLY';

    const NOTIFICATIONS_LEVEL_DEFAULT       = 0;
    const NOTIFICATIONS_LEVEL_DISABLED      = 1;
    const NOTIFICATIONS_LEVEL_STATUS_CHANGE = 2;

    const REMAINING_EFFORT_FIELD_NAME = "remaining_effort";
    const ASSIGNED_TO_FIELD_NAME      = "assigned_to";
    const IMPEDIMENT_FIELD_NAME       = "impediment";
    const TYPE_FIELD_NAME             = "type";
    const NO_PARENT                   = -1;
    const DEFAULT_COLOR               = 'inca_silver';

    const XML_ID_PREFIX = 'T';

    const MAXIMUM_RECENT_ARTIFACTS_TO_DISPLAY = 6;

    public $id;
    public $group_id;
    public $name;
    public $description;
    public $color;
    public $item_name;
    public $allow_copy;
    public $submit_instructions;
    public $browse_instructions;
    public $status;
    public $deletion_date;
    public $instantiate_for_new_projects;
    public $log_priority_changes;
    private $notifications_level;
    private $formElementFactory;
    private $sharedFormElementFactory;
    private $project;
    private $children;
    private $parent = false;
    private $enable_emailgateway;

    // attributes necessary to to create an intermediate Tracker Object
    // (before Database import) during XML import
    // they are not used after the import
    public $tooltip;
    public $cannedResponses = array();
    public $formElements = array();
    public $reports = array();
    public $workflow;

    public function __construct(
        $id,
        $group_id,
        $name,
        $description,
        $item_name,
        $allow_copy,
        $submit_instructions,
        $browse_instructions,
        $status,
        $deletion_date,
        $instantiate_for_new_projects,
        $log_priority_changes,
        $notifications_level,
        $color,
        $enable_emailgateway
    ) {
        $this->id                           = $id;
        $this->group_id                     = $group_id;
        $this->name                         = $name;
        $this->description                  = $description;
        $this->item_name                    = $item_name;
        $this->allow_copy                   = $allow_copy;
        $this->submit_instructions          = $submit_instructions;
        $this->browse_instructions          = $browse_instructions;
        $this->status                       = $status;
        $this->deletion_date                = $deletion_date;
        $this->instantiate_for_new_projects = $instantiate_for_new_projects;
        $this->log_priority_changes         = $log_priority_changes;
        $this->notifications_level          = (int) $notifications_level;
        $this->enable_emailgateway          = $enable_emailgateway;
        $this->formElementFactory           = Tracker_FormElementFactory::instance();
        $this->sharedFormElementFactory     = new Tracker_SharedFormElementFactory($this->formElementFactory, new Tracker_FormElement_Field_List_BindFactory());
        $this->renderer                     = TemplateRendererFactory::build()->getRenderer(TRACKER_TEMPLATE_DIR);

        $this->setColor($color);
    }

    private function setColor($color) {
        if (! $color) {
            $color = self::DEFAULT_COLOR;
        }

        $this->color = $color;
    }

    public function __toString() {
        return "Tracker #".$this->id;
    }

    /**
     * @return string the url of the form to submit a new artifact
     */
    public function getSubmitUrl() {
        return TRACKER_BASE_URL . '/?' . http_build_query(array(
            'tracker' => $this->getId(),
            'func'    => 'new-artifact'
        ));
    }

    /**
     * @return string
     */
    public function getAdministrationUrl()
    {
        return TRACKER_BASE_URL . '/?' . http_build_query(array(
            'tracker' => $this->getId(),
            'func'    => 'admin'
        ));
    }

    /**
     * @return string ~ 'Add new bug'
     */
    public function getAddNewLabel() {
        return $GLOBALS['Language']->getText('plugin_tracker', 'add_a', $this->getItemName());
    }

    /**
     * getGroupId - get this Tracker Group ID.
     *
     * @return int The group_id
     */
    function getGroupId() {
        return $this->group_id;
    }

    /**
     * Get the project of this tracker.
     *
     * @return Project
     */
    function getProject() {
        if (!$this->project) {
            $this->project = ProjectManager::instance()->getProject($this->group_id);
        }
        return $this->project;
    }

    function setProject(Project $project) {
        $this->project  = $project;
        $this->group_id = $project->getID();
    }

    /**
     * getId - get this Tracker Id.
     *
     * @return int The id
     */
    function getId() {
        return $this->id;
    }

    /**
     * set this Tracker Id.
     *
     * @param int $id the id of the tracker
     *
     * @return int The id
     */
    function setId($id) {
        $this->id = $id;
    }

    /**
     * getName - get this Tracker name.
     *
     * @return string the tracker name
     */
    function getName() {
        return $this->name;
    }

    /**
     * getDescription - get this Tracker description.
     *
     * @return string the tracker description
     */
    function getDescription() {
        return $this->description;
    }

    /**
     * getItemName - get this Tracker item name (short name).
     *
     * @return string the tracker item name (shortname)
     */
    function getItemName() {
        return $this->item_name;
    }

    /**
     * Returns the brwose instructions
     *
     * @return string the browse instructions of the tracker
     */
    function getBrowseInstructions() {
        return $this->browse_instructions;
    }

    /**
     * Returns true is this tracker must be instantiated for new project
     *
     * @return boolean true is this tracker must be instantiated for new project
     */
    function mustBeInstantiatedForNewProjects() {
        return $this->instantiate_for_new_projects == 1;
    }

    public function arePriorityChangesShown() {
        return $this->log_priority_changes == 1;
    }

    /**
     * Returns true is notifications are stopped for this tracker
     *
     * @return boolean true is notifications are stopped for this tracker, false otherwise
     */
    public function isNotificationStopped() {
        return (int) $this->notifications_level === self::NOTIFICATIONS_LEVEL_DISABLED;
    }

    /**
     * @return int
     */
    public function getNotificationsLevel()
    {
        return (int) $this->notifications_level;
    }

    public function setNotificationsLevel($notifications_level)
    {
        $this->notifications_level = (int) $notifications_level;
    }

    /**
     * @return array of formElements used by this tracker
     */
    public function getFormElements() {
        return Tracker_FormElementFactory::instance()->getUsedFormElementForTracker($this);
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFormElementFields() {
        return Tracker_FormElementFactory::instance()->getUsedFields($this);
    }

    /**
     * @param string $name
     * @param mixed  $type A field type name, or an array of field type names, e.g. 'float', or array('float', 'int').
     *
     * @return bool true if the tracker contains an element of the given name and type
     */
    public function hasFormElementWithNameAndType($name, $type) {
        $form_element_factory = Tracker_FormElementFactory::instance();
        $element              = $form_element_factory->getUsedFieldByName($this->getId(), $name);

        return $element !== null && in_array($form_element_factory->getType($element), (array)$type);
    }

    /**
     * Should probably be mobified for better efficiency
     *
     * @return array of all the formElements
     */
    public function getAllFormElements() {
        return array_merge(Tracker_FormElementFactory::instance()->getUsedFormElementForTracker($this),
                Tracker_FormElementFactory::instance()->getUnusedFormElementForTracker($this));
    }

    /**
     * fetch FormElements
     * @param Tracker_Artifact $artifact
     * @param array $submitted_values the values already submitted
     *
     * @return string
     */
    public function fetchFormElements($artifact, $submitted_values = array()) {
        $html = '';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchArtifact($artifact, $submitted_values);
        }
        return $html;
    }

    /**
     * fetch FormElements
     * @param Tracker_Artifact $artifact
     * @param array $submitted_values the values already submitted
     *
     * @return string
     */
    public function fetchFormElementsForCopy($artifact, $submitted_values = array()) {
        $html = '';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchArtifactCopyMode($artifact, $submitted_values);
        }
        return $html;
    }

    /**
     * Fetch FormElements in HTML without the container and column rendering
     *
     * @param Tracker_Artifact $artifact
     * @param array $submitted_values the values already submitted
     *
     * @return string
     */
    public function fetchFormElementsNoColumns($artifact, $submitted_values = array()) {
        $html = '';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchArtifactForOverlay($artifact, $submitted_values);
        }
        return $html;
    }

    /**
     * Fetch Tracker submit form in HTML without the container and column rendering
     *
     * @param Tracker_Artifact | null  $artifact_to_link  The artifact wich will be linked to the new artifact
     *
     * @return String
     */
    public function fetchSubmitNoColumns($artifact_to_link, $submitted_values) {
        $html='';

        if ($artifact_to_link) {
            $html .= '<input type="hidden" name="link-artifact-id" value="'. $artifact_to_link->getId() .'" />';
        }

        foreach($this->getFormElements() as $form_element) {
            $html .= $form_element->fetchSubmitForOverlay($submitted_values);
        }

        return $html;
    }

    /**
     * fetch FormElements in read only mode
     *
     * @param Tracker_Artifact $artifact
     *
     * @return string
     */
    public function fetchFormElementsReadOnly($artifact, $submitted_values = array()) {
        $html = '';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchArtifactReadOnly($artifact, $submitted_values);
        }
        return $html;
    }

    /**
     * fetch FormElements
     * @return string
     */
    public function fetchAdminFormElements() {
        $html = '';
        $html .= '<div id="tracker-admin-fields" class="tracker-admin-group">';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchAdmin($this);
        }
        $html .= '</div>';
        return $html;
    }

    public function fetchFormElementsMasschange() {
        $html = '';
        $html .= '<table width="20%"><tr><td>';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchSubmitMasschange();
        }
        $html .= '</td></tr></table>';
        return $html;
    }

    /**
     * Return an instance of TrackerFactory
     *
     * @return TrackerFactory an instance of tracker factory
     */
    public function getTrackerFactory() {
        return TrackerFactory::instance();
    }

    /**
     * Return self
     *
     * @see plugins/tracker/include/Tracker/Tracker_Dispatchable_Interface::getTracker()
     *
     * @return Tracker
     */
    public function getTracker() {
        return $this;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        //TODO: log the admin actions (add a formElement, ...) ?
        $hp = Codendi_HTMLPurifier::instance();
        $func = (string)$request->get('func');
        switch ($func) {
            case 'new-artifact':
                if ($this->userCanSubmitArtifact($current_user)) {
                    $this->displaySubmit($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;

            case 'get-create-in-place':
                if ($this->userCanSubmitArtifact($current_user)) {
                    $artifact_link_id       = $request->get('artifact-link-id');
                    $render_with_javascript = ($request->get('fetch-js') == 'false') ? false : true;

                    $renderer = new Tracker_Artifact_Renderer_CreateInPlaceRenderer(
                        $this,
                        TemplateRendererFactory::build()->getRenderer(dirname(TRACKER_BASE_DIR).'/templates')
                    );

                    $renderer->display($artifact_link_id, $render_with_javascript);
                } else {
                    $GLOBALS['Response']->send400JSONErrors();
                }
                break;
            case 'new-artifact-link':
                header('X-Frame-Options: SAMEORIGIN');
                $link = $request->get('id');
                if ($this->userCanSubmitArtifact($current_user)) {
                    $this->displaySubmit($layout, $request, $current_user, $link);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                }
                break;
            case 'delete':
                if ($this->userCanDeleteTracker($current_user)) {
                    if ($this->getTrackerFactory()->markAsDeleted($this->id)) {
                        $event_manager = EventManager::instance();
                        $event_manager->processEvent(TRACKER_EVENT_DELETE_TRACKER, array(
                                          'tracker_id' => $this->getId())
                                         );
                        $GLOBALS['Response']->addFeedback(
                            'info',
                            $GLOBALS['Language']->getText(
                                'plugin_tracker_admin_index',
                                'delete_success',
                                $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML)
                            )
                        );
                        $GLOBALS['Response']->addFeedback(
                            'info',
                            $GLOBALS['Language']->getText(
                                'plugin_tracker_admin_index',
                                'tracker_deleted',
                                $GLOBALS['sys_email_admin']
                            ),
                            CODENDI_PURIFIER_FULL
                        );
                        $reference_manager =  ReferenceManager::instance();
                        $ref =  $reference_manager->loadReferenceFromKeywordAndNumArgs(strtolower($this->getItemName()), $this->getGroupId(), 1);
                        if ($ref) {
                            if ($reference_manager->deleteReference($ref)) {
                                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_reference', 't_r_deleted'));
                            }
                        }

                        EventManager::instance()->processEvent(
                            TRACKER_EVENT_TRACKER_DELETE,
                            array(
                                'tracker' => $this,
                            )
                        );
                    } else {
                        $GLOBALS['Response']->addFeedback(
                            'error',
                            $GLOBALS['Language']->getText(
                                'plugin_tracker_admin_index',
                                'deletion_failed',
                                $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML)
                            )
                        );
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                }
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?group_id='. $this->group_id);
                break;
            case 'admin':
                if ($this->userIsAdmin($current_user)) {
                    $this->displayAdmin($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-editoptions':
                if ($this->userIsAdmin($current_user)) {
                    if ($request->get('update')) {
                        $this->editOptions($request);
                    }
                    $this->displayAdminOptions($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-perms':
                if ($this->userIsAdmin($current_user)) {
                    $this->displayAdminPerms($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-perms-tracker':
                if ($this->userIsAdmin($current_user)) {
                    $this->getPermissionController()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-perms-fields':
                if ($this->userIsAdmin($current_user)) {
                    if ($request->exist('update')) {
                        if ($request->exist('permissions') && is_array($request->get('permissions'))) {
                            plugin_tracker_permission_process_update_fields_permissions(
                                $this->getGroupId(),
                                $this->getId(),
                                Tracker_FormElementFactory::instance()->getUsedFields($this),
                                $request->get('permissions')
                            );
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('project_admin_userperms', 'perm_upd'));
                        }
                    }
                    $this->displayAdminPermsFields($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-formElements':
                if ($this->userIsAdmin($current_user)) {
                    if (is_array($request->get('add-formElement'))) {
                        list($formElement_id,) = each($request->get('add-formElement'));
                        if (Tracker_FormElementFactory::instance()->addFormElement($formElement_id)) {
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'field_added'));
                            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. (int)$this->getId() .'&func=admin-formElements');
                        }
                    } else if (is_array($request->get('create-formElement'))) {
                        list($type,) = each($request->get('create-formElement'));
                        if ($request->get('docreate-formElement') && is_array($request->get('formElement_data'))) {
                            try {
                                $this->createFormElement($type, $request->get('formElement_data'), $current_user);
                            } catch (Exception $e) {
                                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                            }
                            $GLOBALS['Response']->redirect(
                                TRACKER_BASE_URL.'/?'. http_build_query(
                                    array(
                                    'tracker' => $this->getId(),
                                    'func'    => $func,
                                    )
                                )
                            );
                        } else {
                            Tracker_FormElementFactory::instance()->displayAdminCreateFormElement($layout, $request, $current_user, $type, $this);
                            exit;
                        }
                    }
                    $this->displayAdminFormElements($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-formElement-update':
            case 'admin-formElement-remove':
            case 'admin-formElement-delete':
                if ($this->userIsAdmin($current_user)) {
                    if ($formElement = Tracker_FormElementFactory::instance()->getFormElementById((int)$request->get('formElement'))) {
                        $formElement->process($layout, $request, $current_user);
                    } else {
                        $this->displayAdminFormElements($layout, $request, $current_user);
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-semantic':
                if ($this->userIsAdmin($current_user)) {
                    $this->getTrackerSemanticManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-canned':
            // TODO : project members can access this part ?
                if ($this->userIsAdmin($current_user)) {
                    $this->getCannedResponseManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case Workflow::FUNC_ADMIN_RULES:
            case Workflow::FUNC_ADMIN_CROSS_TRACKER_TRIGGERS:
            case Workflow::FUNC_ADMIN_TRANSITIONS:
            case Workflow::FUNC_ADMIN_GET_TRIGGERS_RULES_BUILDER_DATA:
            case Workflow::FUNC_ADMIN_ADD_TRIGGER:
            case Workflow::FUNC_ADMIN_DELETE_TRIGGER:
                if ($this->userIsAdmin($current_user)) {
                    $this->getWorkflowManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-csvimport':
                $session = new Codendi_Session();
                if ($this->userIsAdmin($current_user)) {
                    if ($request->exist('action') && $request->get('action') == 'import_preview' && array_key_exists('csv_filename', $_FILES)) {
                        // display preview before importing artifacts
                        $this->displayImportPreview($layout, $request, $current_user, $session);
                    } elseif ($request->exist('action') && $request->get('action') == 'import') {
                        $csv_header = $session->get('csv_header');
                        $csv_body   = $session->get('csv_body');

                        if ($this->importFromCSV($request, $current_user, $csv_header, $csv_body)) {
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'import_succeed'));
                            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'import_failed'));
                            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                        }
                    }
                    $this->displayAdminCSVImport($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-export':
                if ($this->userIsAdmin($current_user)) {
                    // TODO: change directory
                    $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
                    $this->sendXML($this->exportToXML($xml_element));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-dependencies':
                if ($this->userIsAdmin($current_user)) {
                    $this->getGlobalRulesManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'submit-artifact':
                header('X-Frame-Options: SAMEORIGIN');
                $action = new Tracker_Action_CreateArtifact(
                    $this,
                    $this->getTrackerArtifactFactory(),
                    $this->getTrackerFactory(),
                    $this->getFormElementFactory()
                );
                $action->process($layout, $request, $current_user);
                break;
            case 'submit-copy-artifact':
                $logger                    = new Tracker_XML_Importer_CopyArtifactInformationsAggregator(new BackendLogger());
                $xml_importer              = $this->getArtifactXMLImporterForArtifactCopy($logger);
                $artifact_factory          = $this->getTrackerArtifactFactory();
                $file_xml_updater          = $this->getFileXMLUpdater();
                $export_children_collector = $this->getChildrenCollector($request);
                $file_path_xml_exporter    = new Tracker_XML_Exporter_LocalAbsoluteFilePathXMLExporter();
                $artifact_xml_exporter     = $this->getArtifactXMLExporter(
                    $export_children_collector,
                    $file_path_xml_exporter,
                    $current_user
                );

                $action = new Tracker_Action_CopyArtifact(
                    $this,
                    $artifact_factory,
                    $artifact_xml_exporter,
                    $xml_importer,
                    $this->getChangesetXMLUpdater(),
                    $file_xml_updater,
                    new Tracker_XML_Exporter_ChildrenXMLExporter(
                        $artifact_xml_exporter,
                        $file_xml_updater,
                        $artifact_factory,
                        $export_children_collector
                    ),
                    new Tracker_XML_Importer_ArtifactImportedMapping(),
                    $logger,
                    TrackerFactory::instance()
                );
                $action->process($layout, $request, $current_user);
                break;
            case 'submit-artifact-in-place':
                $action = new Tracker_Action_CreateArtifactFromModal($request, $this, $this->getTrackerArtifactFactory());
                $action->process($current_user);
                break;
            case 'admin-hierarchy':
                if ($this->userIsAdmin($current_user)) {
                    $this->displayAdminItemHeaderWithoutTitle($layout, 'hierarchy');
                    $this->getHierarchyController($request)->edit();
                    $this->displayFooter($layout);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-hierarchy-update':
                if ($this->userIsAdmin($current_user)) {
                    $this->getHierarchyController($request)->update();
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-clean':
                if ($this->userIsAdmin($current_user)) {
                    $this->displayAdminClean($layout);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-delete-artifact-confirm':
                if ($this->userIsAdmin($current_user)) {
                    $token = new CSRFSynchronizerToken(TRACKER_BASE_URL.'/?tracker='. (int)$this->id.'&amp;func=admin-delete-artifact-confirm');
                    $token->check();
                    $artifact_id = $request->getValidated('id', 'uint', 0);
                    $artifact    = $this->getTrackerArtifactFactory()->getArtifactById($artifact_id);
                    if ($artifact && $artifact->getTrackerId() == $this->id) {
                        $this->displayAdminConfirmDelete($layout, $artifact);
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_error_noart', array($request->get('id'))));
                        $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId().'&func=admin-clean');
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-delete-artifact':
                if ($this->userIsAdmin($current_user)) {
                    $token = new CSRFSynchronizerToken(TRACKER_BASE_URL.'/?tracker='. (int)$this->id.'&amp;func=admin-delete-artifact');
                    $token->check();
                    if ($request->exist('confirm')) {
                        $artifact = $this->getTrackerArtifactFactory()->getArtifactById($request->get('id'));
                        if ($artifact && $artifact->getTrackerId() == $this->getId()) {
                            $artifact_deletor = ArtifactDeletorBuilder::build();
                            $artifact_deletor->delete($artifact, $current_user);
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_info_deleted', array($request->get('id'))));
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_error_noart', array($request->get('id'))));
                        }
                    } else {
                        $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_cancel_deleted'));
                    }
                    $GLOBALS['Response']->redirect($this->getAdministrationUrl());
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'create_new_public_report':
                if (! $this->userIsAdmin($current_user)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }

                $name      = $request->get('new_report_name');
                $validator = new Valid_String('new_report_name');
                $validator->required();

                if (! $request->valid($validator)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker', 'create_new_report_invalid'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }

                $hp = Codendi_HTMLPurifier::instance();
                $hp->purify($name);

                $report = new Tracker_Report(0, $name, 'Public rapport', 0, 0, null, 0, $this->getId(), 1, false, '', null, 0);
                $report->criterias = array();

                $this->getReportFactory()->saveObject($this->id, $report);
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                break;

            default:
                if ($this->userCanView($current_user)) {
                    $this->displayAReport($layout, $request, $current_user);
                }
                break;
        }
        return false;
    }

    /**
     * @return boolean
     */
    public function isProjectAllowedToUseNature() {
        $artifact_links_usage_updater = new ArtifactLinksUsageUpdater(new ArtifactLinksUsageDao());

        return $artifact_links_usage_updater->isProjectAllowedToUseArtifactLinkTypes($this->getProject());
    }

    /**
     * @return Tracker_Hierarchy_Controller
     */
    private function getHierarchyController($request)
    {
        $dao                  = new Tracker_Hierarchy_Dao();
        $tracker_factory      = $this->getTrackerFactory();
        $factory              = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $hierarchical_tracker = $factory->getWithChildren($this);
        $controller           = new Tracker_Hierarchy_Controller(
            $request,
            $hierarchical_tracker,
            $factory,
            $dao,
            new ArtifactLinksUsageDao()
        );

        return $controller;
    }

    public function createFormElement($type, $formElement_data, $user) {
        if ($type == 'shared') {
            $this->sharedFormElementFactory->createFormElement($this, $formElement_data, $user, false, false);
        } else {
            $this->formElementFactory->createFormElement($this, $type, $formElement_data, false, false);
        }
    }

    /**
     * Display a report. Choose the report among
     *  - the requested 'select_report'
     *  - the last viewed report (stored in preferences)
     *  - the default report of this tracker
     *
     * If the user request a 'link-artifact-id' then display also manual and recent
     * panels to ease the selection of artifacts to link
     *
     * @param Tracker_IDisplayTrackerLayout  $layout          Displays the page header and footer
     * @param Codendi_Request                $request         The request
     * @param PFUser                           $current_user    The user who made the request
     *
     * @return void
     */
    public function displayAReport(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $report = null;

        //Does the user wants to change its report?
        if ($request->get('select_report')) {
            //Is the report id valid
            if ($report = $this->getReportFactory()->getReportById($request->get('select_report'), $current_user->getid())) {
                $current_user->setPreference('tracker_'. $this->id .'_last_report', $report->id);
            }
        }

        //If no valid report found. Search the last viewed report for the user
        if (! $report) {
            if ($report_id = $current_user->getPreference('tracker_'. $this->id .'_last_report')) {
                $report = $this->getReportFactory()->getReportById($report_id, $current_user->getid());
            }
        }

        //If no valid report found. Take the default one
        if (! $report) {
            $report = $this->getReportFactory()->getDefaultReportsByTrackerId($this->id);
        }

        //If no default one, take the first private one
        if (! $report) {
            $report_for_user = $this->getReportFactory()->getReportsByTrackerId($this->id, $current_user->getid());
            $report = array_shift($report_for_user);
        }

        $link_artifact_id = (int)$request->get('link-artifact-id');
        if ($link_artifact_id && !$request->get('report-only')) {

            $linked_artifact = Tracker_ArtifactFactory::instance()->getArtifactById($link_artifact_id);

            if (!$linked_artifact) {
                $err = "Linked artifact not found or doesn't exist";
                if (!$request->isAjax()) {
                    $GLOBALS['Response']->addFeedback('error', $err);
                    $GLOBALS['Response']->redirect('/');
                }
                die ($err);
            }
            if (!$request->isAjax()) {
                //screwed up
                $GLOBALS['Response']->addFeedback('error', 'Something is wrong with your request');
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?aid='. $linked_artifact->getId());
            }

            echo $linked_artifact->fetchTitleWithoutUnsubscribeButton(
                $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'title_prefix')
            );

            echo '<input type="hidden" id="link-artifact-id" value="'. (int)$link_artifact_id .'" />';

            echo '<table id="tracker-link-artifact-different-ways" cellpadding="0" cellspacing="0" border="0"><tbody><tr>';

            //the fast ways
            echo '<td id="tracker-link-artifact-fast-ways">';

            //Manual
            echo '<div id="tracker-link-artifact-manual-way">';
            echo '<div class="boxtitle">';
            echo $GLOBALS['HTML']->getImage('ic/lightning-white.png', array('style' => 'vertical-align:middle')). '&nbsp;';
            echo $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'manual_panel_title');
            echo '</div>';
            echo '<div class="tracker-link-artifact-manual-way-content">';
            echo $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'manual_panel_desc');
            echo '<p><label for="link-artifact-manual-field">';
            echo $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'manual_panel_label');
            echo '</label><br />';
            echo '<input type="text" name="link-artifact[manual]" value="" id="link-artifact-manual-field" />';
            echo '</p>';
            echo '</div>';
            echo '</div>';

            //History
            echo '<div id="tracker-link-artifact-recentitems-way">';
            echo '<div class="boxtitle">';
            echo $GLOBALS['HTML']->getImage('ic/star-white.png', array('style' => 'vertical-align:middle')). '&nbsp;';
            echo $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'recent_panel_title');
            echo '</div>';
            echo '<div class="tracker-link-artifact-recentitems-way-content">';
            $visit_retriever            = new VisitRetriever(
                new RecentlyVisitedDao(),
                $this->getTrackerArtifactFactory(),
                new \Tuleap\Glyph\GlyphFinder(EventManager::instance())
            );
            $recently_visited_artifacts = $visit_retriever->getMostRecentlySeenArtifacts(
                $current_user,
                self::MAXIMUM_RECENT_ARTIFACTS_TO_DISPLAY
            );
            if (! empty($recently_visited_artifacts)) {
                echo $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'recent_panel_desc');
                echo '<ul>';
                foreach ($recently_visited_artifacts as $artifact) {
                    if ((int) $artifact->getId() !== $link_artifact_id) {
                        echo '<li>';
                        echo '<input type="checkbox"
                                     name="link-artifact[recent][]"
                                     value="'. (int) $artifact->getId() .'" /> ';
                        echo $artifact->fetchXRefLink();
                        echo '</li>';
                    }
                }
                echo '</ul>';
            }
            echo '</div>';
            echo '</div>';

            //end of fast ways
            echo '</td>';

            //And the slow way (aka need to search)
            if ($report) {
                echo '<td><div id="tracker-link-artifact-slow-way">';
                echo '<div class="boxtitle">';
                echo $GLOBALS['HTML']->getImage('ic/magnifier-white.png', array('style' => 'vertical-align:middle')). '&nbsp;';
                echo $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'search_panel_title');
                echo '</div>';
                echo '<div id="tracker-link-artifact-slow-way-content">';
            }
        }

        if ($report) {
            $report->process($layout, $request, $current_user);
        } elseif (!$link_artifact_id) {
            $this->displayHeader($layout, $this->name, array());
            echo $GLOBALS['Language']->getText('plugin_tracker', 'no_reports_available');

            if ($this->userIsAdmin($current_user)) {
                $action = '?tracker='. (int)$this->getID() .'&func=create_new_public_report';

                echo '<form class="form-inline" action="'.$action.'" method="POST">'
                    . '<fieldset>'
                        . '<legend>'.$GLOBALS['Language']->getText('plugin_tracker', 'create_new_report').'</legend>'
                        . '<input required type="text" name="new_report_name" placeholder="'.$GLOBALS['Language']->getText('plugin_tracker', 'create_new_report_name').'" />'
                        . '<button type="submit" class="btn">'.$GLOBALS['Language']->getText('plugin_tracker', 'create_new_report_submit').'</button>'
                    . '</fieldset></form>';
            }

            $this->displayFooter($layout);
        }

        if ($link_artifact_id && !$request->get('report-only')) {
            if ($report) {
                echo '</div></div></td>'; //end of slow
            }
            echo '</tr></tbody></table>'; //end of ways

            echo '<div class="tracker-link-artifact-controls">';
            echo '<a href="#cancel" onclick="myLightWindow.deactivate(); return false;">&laquo;&nbsp;'. $GLOBALS['Language']->getText('global', 'btn_cancel') .'</a>';
            echo ' ';
            echo '<button name="link-artifact-submit">'. $GLOBALS['Language']->getText('global', 'btn_submit') .'</button>';
            echo '</div>';
        }
    }

    /**
     * Display the submit form
     */
    public function displaySubmit(Tracker_IFetchTrackerSwitcher $layout, $request, $current_user, $link = null)
    {
        $visit_recorder = $this->getVisitRecorder();
        if ($link) {
            $source_artifact = $this->getTrackerArtifactFactory()->getArtifactByid($link);
            $submit_renderer = new Tracker_Artifact_SubmitOverlayRenderer(
                $this,
                $source_artifact,
                EventManager::instance(),
                $layout, $visit_recorder
            );
        } else {
            $submit_renderer = new Tracker_Artifact_SubmitRenderer(
                $this,
                EventManager::instance(),
                $layout,
                $visit_recorder
            );
        }
        $submit_renderer->display($request, $current_user);
    }

    /**
     * @return VisitRecorder
     */
    private function getVisitRecorder()
    {
        return new VisitRecorder(new RecentlyVisitedDao());
    }

    /**
     * Display the submit form
     */
    public function displaySearch(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $hp = Codendi_HTMLPurifier::instance();

        $pm = ProjectManager::instance();
        $group_id = $request->get('group_id');
        $group = $pm->getProject($group_id);
        if (!$group || !is_object($group) || $group->isError()) {
            exit_no_group();
        }

        $breadcrumbs = array(
                array(
                        'title' => $GLOBALS['Language']->getText('plugin_tracker_browse', 'search_result'),
                        'url'   => TRACKER_BASE_URL.'/?tracker='. $this->getId(),
                ),
        );
        $this->displayHeader($layout, $this->name, $breadcrumbs);
        $html = '';

        $words    = $request->get('words');

        $criteria = 'OR';
        if ($request->exist('exact') && $request->get('exact') == '1') {
            $criteria = 'AND';
        }
        $offset = 0;
        if ($request->exist('offset')) {
            $offset = $request->get('offset');
        }
        $limit = 25;

        $tracker_artifact_dao = new Tracker_ArtifactDao();
        $dar = $tracker_artifact_dao->searchByKeywords($this->getId(), $words, $criteria, $offset, $limit);
        $rows_returned = $tracker_artifact_dao->foundRows();

        $no_rows = false;

        if ( $dar->rowCount() < 1 || $rows_returned < 1) {
            $no_rows = true;
            $html .= '<h2>'.$GLOBALS['Language']->getText('search_index','no_match_found', $hp->purify($words, CODENDI_PURIFIER_CONVERT_HTML)) .'</h2>';
        } else {
            $html .= '<h3>'.$GLOBALS['Language']->getText('search_index','search_res', array($hp->purify($words, CODENDI_PURIFIER_CONVERT_HTML), $rows_returned)).'</h3>';

            $title_arr = array();

            $art_field_fact = Tracker_FormElementFactory::instance();
            $artifact_factory = Tracker_ArtifactFactory::instance();
            $user_helper = UserHelper::instance();

            $summary_field = $this->getTitleField();
            if ($summary_field && $summary_field->userCanRead()) {
                $title_arr[] = $GLOBALS['Language']->getText('plugin_tracker_search_index','artifact_title');
            }
            $submitted_field = $art_field_fact->getFormElementByName($this->getId(), 'submitted_by');
            if ($submitted_field && $submitted_field->userCanRead()) {
                $title_arr[] = $GLOBALS['Language']->getText('search_index','submitted_by');
            }
            $date_field = $art_field_fact->getFormElementByName($this->getId(), 'open_date');
            if ($date_field && $date_field->userCanRead()) {
                $title_arr[] = $GLOBALS['Language']->getText('search_index','date');
            }
            $status_field = $this->getStatusField();
            if ($status_field && $status_field->userCanRead()) {
                $title_arr[] = $GLOBALS['Language']->getText('global','status');
            }

            $html .= html_build_list_table_top ($title_arr);
            $nb_artifacts = 0;
            while ($row = $dar->getRow()) {
                $nb_artifacts++;
                $artifact_id = $row['artifact_id'];
                $artifact    = $artifact_factory->getArtifactById($artifact_id);
                if ($artifact->userCanView()) {
                    $html .= '<tr class="' . html_get_alt_row_color($nb_artifacts) . '">';
                    if ($summary_field->userCanRead()) {
                        $html .= '<td><a href="'.TRACKER_BASE_URL.'/?aid=' . $artifact_id . '"><img src="' . util_get_image_theme('msg.png') . '" border="0" height="12" width="10"> '
                        . $artifact->getTitle() . '</a></td>';
                    }
                    if ($submitted_field->userCanRead()) {
                        $html .= '<td>' . $hp->purify($user_helper->getDisplayNameFromUserId($artifact->getSubmittedBy())) . '</td>';
                    }
                    if ($date_field->userCanRead()) {
                        $html .=  '<td>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'),$artifact->getSubmittedOn()) . '</td>';
                    }
                    if ($status_field->userCanRead()) {
                        $html .=  '<td>' . $artifact->getStatus() . '</td>';
                    }
                    $html .=  '</tr>';
                }
            }
            $html .= '</table>';
        }


        // Search result pagination
        if ( !$no_rows && ( ($rows_returned > $nb_artifacts) || ($offset != 0) ) ) {
            $html .= '<br />';
            $url_params = array(
                'exact' => $request->get('exact') === '1' ? 1 : 0,
                'group_id' => $this->getGroupId(),
                'tracker' => $this->getId(),
                'type_of_search' => 'tracker',
                'words' => urlencode($words),
                'offset' => ($offset - $limit)
            );
            $html .= '<table class="boxitem" width="100%" cellpadding="5" cellspacing="0">';
            $html .= '<tr>';
            $html .= '<td align="left">';
            if ($offset != 0) {
                $html .= '<span class="normal"><b>';
                $html .= '<a href="/search/?'. http_build_query($url_params);
                $html .= '">' . "<b><img src=\"".util_get_image_theme('t2.png')."\" height=15 width=15 border=0 align=middle> ".$GLOBALS['Language']->getText('search_index','prev_res')." </a></b></span>";
            } else {
                $html .= '&nbsp;';
            }
            $html .= '</td><td align="right">';
            if ( $rows_returned > $nb_artifacts && $rows_returned > $offset + $limit) {
                $url_params['offset'] = ($offset + $limit);
                $html .= '<span class="normal"><b>';
                $html .= '<a href="/search/?'. http_build_query($url_params);
                $html .= '"><b>' . $GLOBALS['Language']->getText('search_index','next_res').' <img src="' . util_get_image_theme('t.png') . '" height="15" width="15" border="0" align="middle"></a></b></span>';
            } else {
                $html .= '&nbsp;';
            }
            $html .= '</td></tr>';
            $html .= '</table>';
        }

        echo $html;
        $this->displayFooter($layout);
    }

    public function displayHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs, $toolbar = null, array $params = array())
    {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            $hp = Codendi_HTMLPurifier::instance();
            $breadcrumbs = array_merge(
                array(
                    array(
                        'title' => $this->name,
                        'url'   => TRACKER_BASE_URL.'/?tracker='. $this->id
                    )
                ),
                $breadcrumbs
            );
            if (!$toolbar) {
                $toolbar = $this->getDefaultToolbar();
            }
            $title = ($title ? $title .' - ' : ''). $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML);
            $layout->displayHeader($project, $title, $breadcrumbs, $toolbar, $params);
        }
    }

    public function getDefaultToolbar() {
        $toolbar = array();

        $toolbar[] = array(
                'title'      => $GLOBALS['Language']->getText('plugin_tracker', 'submit_new_artifact'),
                'url'        => $this->getSubmitUrl(),
                'class'      => 'tracker-submit-new',
                'submit-new' => 1
        );

        $artifact_by_email_status = $this->getArtifactByMailStatus();
        if ($artifact_by_email_status->canCreateArtifact($this)) {
            $email_domain = ForgeConfig::get('sys_default_mail_domain');

            if (! $email_domain) {
                $email_domain = ForgeConfig::get('sys_default_domain');
            }

            $email = trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION .'+'. $this->id .'@'. $email_domain;
            $email = Codendi_HTMLPurifier::instance()->purify($email);
            $toolbar[] = array(
                    'title'      => '<span class="email-tracker" data-email="'. $email .'"><i class="icon-envelope"></i></span>',
                    'url'        => 'javascript:;',
                    'submit-new' => 1
            );
        }

        if (UserManager::instance()->getCurrentUser()->isLoggedIn()) {
            $toolbar[] = array(
                    'title' => $GLOBALS['Language']->getText('plugin_tracker', 'notifications'),
                    'url'   => TRACKER_BASE_URL.'/notifications/my/' . urlencode($this->id) . '/',
            );
        }
        if ($this->userIsAdmin()) {
            $toolbar[] = array(
                    'title' => $GLOBALS['Language']->getText('plugin_tracker', 'administration'),
                    'url'   => $this->getAdministrationUrl()
            );
        }
        $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('plugin_tracker', 'help'),
                'url'   => 'javascript:help_window(\''.get_server_url().'/doc/'.UserManager::instance()->getCurrentUser()->getShortLocale().'/user-guide/tracker.html\');',
        );

        return $toolbar;
    }

    public function displayFooter(Tracker_IDisplayTrackerLayout $layout) {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            $layout->displayFooter($project);
        }
    }

    protected function getAdminItems() {
        $items = array(
                'editoptions' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-editoptions',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','settings'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','settings'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_title'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-general.png'),
                ),
                'editperms' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-perms',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','permissions'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','manage_permissions'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_manage_permissions'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-perms.png'),
                ),
                'editformElements' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-formElements',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','field_usage'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','mng_field_usage'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_use'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-form.png'),
                ),
                'dependencies' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-dependencies',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_dependencies'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_dependencies'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_dependencies_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-fdependencies.png'),
                ),
                'editsemantic' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-semantic',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','semantic'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_semantic'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_semantic_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-semantic.png'),
                ),
                'editworkflow' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func='. Workflow::FUNC_ADMIN_RULES,
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','workflow'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_workflow'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_workflow_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-workflow.png'),
                ),
                'editcanned' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-canned',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','canned_resp'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','mng_response'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','add_del_resp'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-canned.png'),
                ),
                'editnotifications' => array(
                        'url'         => TRACKER_BASE_URL.'/notifications/' . urlencode($this->id) . '/',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','mail_notif'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','mail_notif'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_notif'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-notifs.png'),
                ),
                'csvimport' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-csvimport',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','csv_import'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','csv_import'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','csv_import_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-import.png'),
                ),
                'export' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-export',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','export'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','export'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','export_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-export.png'),
                ),
                'hierarchy' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-hierarchy',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','hierarchy'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','hierarchy'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','hierarchy_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-hierarchy.png'),
                ),
                'clean' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&func=admin-clean',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','clean'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','clean'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','clean_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-delete.png'),
                ),
        );
        $params = array("items" => &$items, "tracker_id" => $this->id);
        EventManager::instance()->processEvent(TRACKER_EVENT_FETCH_ADMIN_BUTTONS, $params);

        return $items;
    }

    public function displayAdminHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs)
    {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            $hp = Codendi_HTMLPurifier::instance();
            $title = ($title ? $title .' - ' : ''). $GLOBALS['Language']->getText('plugin_tracker_include_type', 'administration');
            $toolbar = null;
            if ($this->userIsAdmin()) {
                $breadcrumbs = array_merge(
                    array(
                        array(
                                'title' => $GLOBALS['Language']->getText('plugin_tracker_include_type', 'administration'),
                                'url'   => $this->getAdministrationUrl(),
                        ),
                    ),
                    $breadcrumbs
                );
                $toolbar = $this->getAdminItems();
            }
            $this->displayHeader($layout, $title, $breadcrumbs, $toolbar);
        }
    }
    public function displayAdmin(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $title = '';
        $breadcrumbs = array();
        $this->displayAdminHeader($layout, $title, $breadcrumbs);
        echo $this->fetchAdminMenu($this->getAdminItems());
        $this->displayFooter($layout);
    }

    /**
     * Display the items of the menu and their description
     *
     * @param array $items the items, each item is ['url', 'title', 'description'].
     *                     Only name is mandatory (else the item is not displayed.
     *
     * @return string html
     */
    protected function fetchAdminMenu($items)
    {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $cleaned_items = $items;
        foreach ($cleaned_items as $key => $item) {
            if (!isset($item['title'])) {
                unset($cleaned_items[$key]);
            }
        }

        if ($cleaned_items) {
            $html .= '<table id="tracker_admin_menu">';
            $chunks = array_chunk($cleaned_items, 2);
            foreach ($chunks as $row) {
                $html .= '<tr valign="top">';
                foreach ($row as $item) {
                    $html .= '<td width="450">';
                    $html .= '<H3>';
                    $title =  $hp->purify($item['title'], CODENDI_PURIFIER_CONVERT_HTML) ;
                    if (isset($item['url'])) {
                        $html .= '<a href="'.$item['url'].'">';
                        if (isset($item['img']) && $item['img']) {
                            $html .= '<img src="'. $item['img'] .'" style="float: left;" />';
                        }
                        $html .= $title;
                        $html .= '</a>';
                    } else {
                        $html .= $title;
                    }
                    $html .= '</h3>';
                    if (isset($item['description'])) {
                        $html .= '<div>'. $hp->purify($item['description'], CODENDI_PURIFIER_BASIC, $this->getGroupId()) .'</div>';
                    }
                    $html .= '</td>';
                }
                $html .= '</tr>';
            }
            $html .= '</table>';
        }
        return $html;
    }

    public function displayAdminItemHeader(Tracker_IDisplayTrackerLayout $layout, $item, $breadcrumbs = array(), $title = null) {
        $this->displayAdminItemHeaderWithoutTitle($layout, $item, $breadcrumbs, $title);

        echo '<h1>'. $title .'</h1>';
    }

    private function displayAdminItemHeaderWithoutTitle(
        Tracker_IDisplayTrackerLayout $layout,
        $item,
        $breadcrumbs = array(),
        $title = null
    ) {
        $items = $this->getAdminItems();
        $title = $title ? $title : $items[$item]['title'];
        $breadcrumbs = array_merge(
            array(
                $items[$item]
            ),
            $breadcrumbs
        );
        $this->displayAdminHeader($layout, $title, $breadcrumbs);
    }

    public function getColor() {
        return $this->color;
    }

    public function getNormalizedColor()
    {
        return str_replace('_', '-', $this->color);
    }

    public function isEmailgatewayEnabled() {
        return $this->enable_emailgateway;
    }

    protected function displayAdminOptions(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $this->displayWarningGeneralsettings();
        $this->displayAdminItemHeader($layout, 'editoptions');
        $cannot_configure_instantiate_for_new_projects = false;
        $params = array('cannot_configure_instantiate_for_new_projects' => &$cannot_configure_instantiate_for_new_projects, 'tracker'=>$this);
        EventManager::instance()->processEvent(TRACKER_EVENT_GENERAL_SETTINGS, $params);
        $this->renderer->renderToPage(
            'tracker-general-settings',
            new Tracker_GeneralSettings_Presenter(
                $this,
                TRACKER_BASE_URL.'/?tracker='. (int)$this->id .'&func=admin-editoptions',
                new Tracker_ColorPresenterCollection($this),
                $this->getMailGatewayConfig(),
                $this->getArtifactByMailStatus(),
                $cannot_configure_instantiate_for_new_projects
            )
        );

        $this->displayFooter($layout);
    }

    public function displayAdminPermsHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs) {
        $items = $this->getAdminItems();
        $breadcrumbs = array_merge(array(
                $items['editperms']
                ), $breadcrumbs);
        $this->displayAdminHeader($layout, $title, $breadcrumbs);
    }

    public function getPermsItems() {
        return array(
                'tracker' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='.(int)$this->getId().'&func=admin-perms-tracker',
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','manage_tracker_permissions'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_manage_tracker_permissions')
                ),
                'fields' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='.(int)$this->getId().'&func=admin-perms-fields',
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','manage_fields_tracker_permissions'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_manage_fields_tracker_permissions')
                )
        );
    }

    public function displayAdminPerms(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $items = $this->getAdminItems();
        $title = $items['editperms']['title'];
        $breadcrumbs = array();
        $this->displayAdminPermsHeader($layout, $title, $breadcrumbs);
        echo '<h2>'. $title .'</h2>';
        echo $this->fetchAdminMenu($this->getPermsItems());
        $this->displayFooter($layout);
    }

    public function displayAdminPermsFields(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $items = $this->getPermsItems();
        $title = $items['fields']['title'];
        $breadcrumbs = array(
                $items['fields']
        );
        $this->displayAdminPermsHeader($layout, $title, $breadcrumbs);
        echo '<h2>'. $title .'</h2>';

        $hp = Codendi_HTMLPurifier::instance();

        $group_first = $request->get('group_first') ? 1 : 0;
        $selected_id = $request->get('selected_id');
        $selected_id = $selected_id ? $selected_id : false;
        $ugroups_permissions = plugin_tracker_permission_get_field_tracker_ugroups_permissions(
                $this->getGroupId(),
                $this->getId(),
                Tracker_FormElementFactory::instance()->getUsedFields($this),
                false
        );

        $submit_permission = 'PLUGIN_TRACKER_FIELD_SUBMIT';
        $read_permission   = 'PLUGIN_TRACKER_FIELD_READ';
        $update_permission = 'PLUGIN_TRACKER_FIELD_UPDATE';
        $none              = 'PLUGIN_TRACKER_NONE';
        $attributes_for_selected = 'selected="selected" style="background:#EEE;"'; //TODO: put style in stylesheet

        $html = '';

        //form
        $url_action_without_group_first = '?tracker='. (int)$this->getID() .'&amp;func=admin-perms-fields';
        $url_action_with_group_first    = $url_action_without_group_first .'&amp;group_first='. $group_first;

        //The change form
        $group_first_value = $group_first;
        $group_id          = (int)$this->getGroupID();
        $atid              = (int)$this->getID();

        $url_action_with_group_first_for_js = str_replace('&amp;', '&', $url_action_with_group_first) .'&selected_id=';

        $html .= <<<EOS
            <script type="text/javascript">
            <!--
            function changeFirstPartId(wanted) {
                location.href = '$url_action_with_group_first_for_js' + wanted;
            }
            //-->
            </script>
EOS;


        if ($group_first) {
            //We reorganize the associative array
            $tablo = $ugroups_permissions;
            $ugroups_permissions = array();
            foreach($tablo as $key_field => $value_field) {
                foreach($value_field['ugroups'] as $key_ugroup => $value_ugroup) {
                    if (!isset($ugroups_permissions[$key_ugroup])) {
                        $ugroups_permissions[$key_ugroup] = array(
                                'values'              => $value_ugroup['ugroup'],
                                'related_parts'       => array(),
                                'tracker_permissions' => $value_ugroup['tracker_permissions']
                        );

                    }
                    $ugroups_permissions[$key_ugroup]['related_parts'][$key_field] = array(
                            'values'       => $value_field['field'],
                            'permissions' => $value_ugroup['permissions']
                    );
                }
            }
            ksort($ugroups_permissions);
            $header = array(
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'ugroup'),
                    $GLOBALS['Language']->getText('plugin_tracker_include_report', 'field_label'),
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $submit_permission),
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'permissions')) ;
        } else {
            foreach($ugroups_permissions as $key_field => $value_field) {
                $ugroups_permissions[$key_field]['values']        =& $ugroups_permissions[$key_field]['field'];
                $ugroups_permissions[$key_field]['related_parts'] =& $ugroups_permissions[$key_field]['ugroups'];
                foreach($value_field['ugroups'] as $key_ugroup => $value_ugroup) {
                    $ugroups_permissions[$key_field]['related_parts'][$key_ugroup]['values'] =& $ugroups_permissions[$key_field]['related_parts'][$key_ugroup]['ugroup'];
                }
                ksort($ugroups_permissions[$key_field]['related_parts']);
                reset($ugroups_permissions[$key_field]['related_parts']);
            }
            $header = array(
                    $GLOBALS['Language']->getText('plugin_tracker_include_report', 'field_label'),
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'ugroup'),
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $submit_permission),
                    $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'permissions')) ;
        }
        reset($ugroups_permissions);
        list($key, $value) = each($ugroups_permissions);

        //header
        if (($group_first && count($ugroups_permissions) < 1) || (!$group_first && count($ugroups_permissions[$key]['related_parts']) < 1)) {
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'fields_no_ugroups');
        } else {

            //The permission form
            $html .= '<form name="form_tracker_permissions" action="'. $url_action_with_group_first .'" method="post">';
            $html .= '<div>';
            $html .= '<input type="hidden" name="selected_id" value="'. (int)$selected_id .'" />';

            //intro
            $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'fields_tracker_intro');

            //We display 'group_first' or 'field_first'
            if ($group_first) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions',
                        'fields_tracker_toggle_field',
                        $url_action_without_group_first.'&amp;group_first=0');
            } else {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions',
                        'fields_tracker_toggle_group',
                        $url_action_without_group_first.'&amp;group_first=1');
            }

            $html .= html_build_list_table_top($header);

            //body
            $i = 0;
            $a_star_is_displayed = false;
            $related_parts = array();
            //The select box for the ugroups or fields (depending $group_first)
            $html .= '<tr class="'. util_get_alt_row_color($i++) .'">';
            $html .= '<td rowspan="'. (count($ugroups_permissions[$key]['related_parts'])+1) .'" style="vertical-align:top;">';
            $html .= '<select onchange="changeFirstPartId(this.options[this.selectedIndex].value);">';

            foreach($ugroups_permissions as $part_permissions) {
                if ($selected_id === false) {
                    $selected_id = $part_permissions['values']['id'];
                }
                $html .= '<option value="'. (int)$part_permissions['values']['id'] .'" ';
                if ($part_permissions['values']['id'] === $selected_id) {
                    $first_part    = $part_permissions['values'];
                    $related_parts = $part_permissions['related_parts'];
                    $html .= $attributes_for_selected;
                }
                $html .= ' >';
                $html .= $hp->purify($part_permissions['values']['name']);
                if ($group_first) {
                    if (isset($part_permissions['tracker_permissions'])
                            && count($part_permissions['tracker_permissions']) === 0) {
                        $html .= ' *';
                        $a_star_is_displayed = true;
                    }
                }
                $html .= '</option>';
            }
            $html .= '</select>';
            $html .= '</td>';
            $is_first = true;

            //The permissions for the current item (field or ugroup, depending $group_id)
            foreach($related_parts as $ugroup_permissions) {
                $second_part = $ugroup_permissions['values'];
                $permissions = $ugroup_permissions['permissions'];


                //The group
                if (!$is_first) {
                    $html .= '<tr class="'. util_get_alt_row_color($i++) .'">';
                } else {
                    $is_first = false;
                }
                $html .= '<td>';

                $name = '<a href="'. $url_action_without_group_first .'&amp;selected_id='. (int)$second_part['id'] .'&amp;group_first='. ($group_first?0:1) .'">';
                $name .=  $hp->purify($second_part['name']) ;
                $name .= '</a>';
                if (!$group_first && isset($ugroup_permissions['tracker_permissions']) && count($ugroup_permissions['tracker_permissions']) === 0) {
                    $name = '<span >'. $name .' *</span>'; //TODO css
                    $a_star_is_displayed = true;
                }
                $html .= $name;

                $html .= '</td>';

                //The permissions
                {
                    //Submit permission
                    $html .= '<td style="text-align:center;">';
                    if ($group_first) {
                        $name_of_variable = "permissions[".(int)$second_part['id']."][".(int)$first_part['id']."]";
                    } else {
                        $name_of_variable = "permissions[".(int)$first_part['id']."][".(int)$second_part['id']."]";
                    }
                    $html .= '<input type="hidden" name="'. $name_of_variable .'[submit]" value="off"/>';

                    $can_submit = ($group_first && $second_part['field']->isSubmitable())
                            || (!$group_first && $first_part['field']->isSubmitable());

                    $can_update = ($group_first && $second_part['field']->isUpdateable())
                            || (!$group_first && $first_part['field']->isUpdateable());

                    $html .= "<input type='checkbox' name=\"".$name_of_variable.'[submit]"  '.
                            (isset($permissions[$submit_permission])?"checked='checked'":"")." ".($can_submit?"":"disabled='disabled'")." /> ";
                    $html .= "</td><td>";


                    //Other permissions (R/W)
                    $html .= "<select name='".$name_of_variable."[others]' >";
                    $html .= "<option value='100' ".(!isset($permissions[$read_permission]) && !isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $none)."</option>";
                    $html .= "<option value='0' ".(isset($permissions[$read_permission]) && !isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $read_permission)."</option>";

                    if ($can_update) {
                        $html .= "<option value='1' ".(isset($permissions[$update_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $update_permission)."</option>";
                    }
                    $html .= "</select>";

                }
                $html .= "</td>";
                $html .= "</tr>\n";
            }

            //end of table
            $html .= "</table>";
            if ($a_star_is_displayed) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'ug_may_have_no_access', TRACKER_BASE_URL."/?tracker=".(int)$this->getID()."&func=admin-perms-tracker");
            }
            $html .= "<input type='submit' name='update' value=\"".$GLOBALS['Language']->getText('project_admin_permissions','submit_perm')."\" />";
            //{{{20050602 NTY: removed. what is default permissions ???
            //$html .= "<input type='submit' name='reset' value=\"".$GLOBALS['Language']->getText('project_admin_permissions','reset_to_def')."\" />";
            //}}}
        }
        $html .= "</div></form>";
        $html .= "<p>";
        $html .= $GLOBALS['Language']->getText('project_admin_permissions',
                'admins_create_modify_ug',
                array(
                "/project/admin/ugroup.php?group_id=".(int)$this->getGroupID()
                )
        );
        $html .= "</p>";
        print $html;

        $this->displayFooter($layout);
    }

    public function displayAdminFormElementsHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs) {
        $items = $this->getAdminItems();
        $breadcrumbs = array_merge(array(
                $items['editformElements']
                ), $breadcrumbs);

        $include_assets = new \Tuleap\Layout\IncludeAssets(
            __DIR__ . '/../../www/assets',
            TRACKER_BASE_URL . '/assets'
        );

        $include_assets_css = new \Tuleap\Layout\IncludeAssets(
            __DIR__ . '/../../www/themes/FlamingParrot/assets',
            TRACKER_BASE_URL . '/themes/FlamingParrot/assets'
        );

        $GLOBALS['HTML']->addStylesheet(
            $include_assets_css->getFileURL('colorpicker.css')
        );

        $GLOBALS['HTML']->includeFooterJavascriptFile($include_assets->getFileURL('TrackerAdminFields.js'));

        $this->displayAdminHeader($layout, $title, $breadcrumbs);
    }

    public function displayAdminFormElements(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $this->displayAdminFormElementsWarnings();
        $items = $this->getAdminItems();
        $title = $items['editformElements']['title'];
        $this->displayAdminFormElementsHeader($layout, $title, array());

        echo '<h2>'. $title .'</h2>';
        echo '<form name="form1" method="POST" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->id .'&amp;func=admin-formElements">';

        echo '  <div class="container-fluid">
                  <div class="row-fluid">
                    <div class="span3">';
        $this->fetchAdminPalette();
        echo '      </div>
                    <div class="span9">';
        echo $this->fetchAdminFormElements();
        echo '      </div>
                  </div>
                </div>
              </form>';
        $this->displayFooter($layout);
    }

    private function fetchAdminPalette() {
        echo '<div class="tracker-admin-palette">';

        $this->formElementFactory->displayFactories($this);

        $w = new Widget_Static($GLOBALS['Language']->getText('plugin_tracker_formelement_admin','unused_elements'));
        $unused_elements_content = '';
        $unused_elements_content = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin','unused_elements_desc');
        $unused_elements_content .= '<div class="tracker-admin-palette-content"><table>';
        foreach(Tracker_FormElementFactory::instance()->getUnusedFormElementForTracker($this) as $f) {
            $unused_elements_content .= $f->fetchAdminAdd();
        }
        $unused_elements_content .= '</table></div>';
        $w->setContent($unused_elements_content);
        $w->display();

        echo '</div>';
    }

    public function displayAdminCSVImportHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs) {
        $items = $this->getAdminItems();
        $breadcrumbs = array_merge(array(
                $items['csvimport']
                ), $breadcrumbs);
        $this->displayAdminHeader($layout, $title, $breadcrumbs);
    }

    public function displayAdminCSVImport(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $items = $this->getAdminItems();
        $title = $items['csvimport']['title'];
        $this->displayAdminCSVImportHeader($layout, $title, array());

        echo '<h2>'. $title . ' ' . help_button('tracker.html#tracker-artifact-import') . '</h2>';
        echo '<form name="form1" method="POST" enctype="multipart/form-data" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->id .'&amp;func=admin-csvimport">';
        echo '<input type="file" name="csv_filename" size="50">';
        echo '<br>';
        echo '<span class="smaller"><em>';
        echo $GLOBALS['Language']->getText('plugin_tracker_import', 'file_upload_instructions', formatByteToMb($GLOBALS['sys_max_size_upload']));
        echo '</em></span>';
        echo '<br>';
        echo $GLOBALS['Language']->getText('plugin_tracker_admin_import','send_notifications');
        echo '<input type="checkbox" name="notify" value="ok" />';
        echo '<br>';
        echo '<input type="hidden" name="action" value="import_preview">';
        echo '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_import','submit_info').'">';
        echo '</form>';
        $this->displayFooter($layout);
    }

    public function displayAdminClean(Tracker_IDisplayTrackerLayout $layout) {
        $token = new CSRFSynchronizerToken(TRACKER_BASE_URL.'/?tracker='. (int)$this->id.'&amp;func=admin-delete-artifact-confirm');
        $this->displayAdminItemHeader($layout, 'clean');
        echo '<p>'.$GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_info').'</p>';
        echo '<form name="delete_artifact" method="post" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->id.'&amp;func=admin-delete-artifact-confirm">';
        echo $token->fetchHTMLInput();
        echo '<label>'.$GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_id').' <input type="text" name="id" value=""></label>';
        echo '<br>';
        echo '<input type="submit" value="'.$GLOBALS['Language']->getText('global','btn_submit').'">';
        echo '</form>';
        $this->displayFooter($layout);
    }

    public function displayAdminConfirmDelete(Tracker_IDisplayTrackerLayout $layout, Tracker_Artifact $artifact) {
        $token = new CSRFSynchronizerToken(TRACKER_BASE_URL.'/?tracker='. (int)$this->id.'&amp;func=admin-delete-artifact');
        $this->displayAdminItemHeader($layout, 'clean');
        echo '<div class="tracker_confirm_delete">';
        echo $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_confirm_text', array($artifact->getXRefAndTitle()));
        echo '<div class="tracker_confirm_delete_preview">';
        echo $this->fetchFormElementsReadOnly($artifact);
        echo '</div>';
        echo '<form name="delete_artifact" method="post" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->id.'&amp;func=admin-delete-artifact">';
        echo $token->fetchHTMLInput();
        echo '<div class="tracker_confirm_delete_buttons">';
        echo '<input type="submit" tabindex="2" name="confirm" value="'. $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_confirm') .'" />';
        echo '<input type="submit" tabindex="1" name="cancel" value="'. $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_cancel') .'" />';
        echo '</div>';
        echo '<input type="hidden" name="id" value="'.$artifact->getId().'" />';
        echo '</form>';
        echo '</div>';
        $this->displayFooter($layout);
    }

    public function displayMasschangeForm(Tracker_IDisplayTrackerLayout $layout, $masschange_aids) {
        $breadcrumbs = array(
            array(
                'title' => $GLOBALS['Language']->getText('plugin_tracker_index', 'mass_change'),
                'url'   => '#' //TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=display-masschange-form',
            ),
        );
        $this->displayHeader($layout, $this->name, $breadcrumbs);

        $this->renderer->renderToPage(
            'masschange',
            new Tracker_Masschange_Presenter(
                $masschange_aids,
                $this->fetchFormElementsMasschange(),
                $this->displayRulesAsJavascript()
            )
        );

        $this->displayFooter($layout);
    }

    public function updateArtifactsMasschange(
        $submitter,
        $masschange_aids,
        $fields_data,
        $comment,
        $send_notifications,
        $comment_format
    ) {
        $fields_data['request_method_called'] = 'artifact-masschange';

        $this->augmentDataFromRequest($fields_data);

        unset($fields_data['request_method_called']);

        $not_updated_aids = array();
        foreach ( $masschange_aids as $aid ) {
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($aid);
            if ( !$artifact ) {
                $not_updated_aids[] = $aid;
                continue;
            }

            try {
                $artifact->createNewChangeset($fields_data, $comment, $submitter, $send_notifications, $comment_format);
            } catch (Tracker_NoChangeException $e) {
                $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
                $not_updated_aids[] = $aid;
                continue;
            } catch (Tracker_Exception $e) {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unable_to_update_artifact', array($aid)));
                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                $not_updated_aids[] = $aid;
                continue;
            }
        }
        if ( !empty($not_updated_aids) ) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_index', 'mass_update_failed', implode(', ', $not_updated_aids)));
            return false;
        } else {
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_index', 'mass_update_success'));
            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_index', 'updated_aid', implode(', ', $masschange_aids)));
            return true;
        }
    }

    protected function editOptions($request) {
        $old_item_name = $this->getItemName();
        $old_name      = $this->getName();
        $cannot_configure_instantiate_for_new_projects = false;
        $params = array('cannot_configure_instantiate_for_new_projects' => &$cannot_configure_instantiate_for_new_projects, 'tracker'=>$this);
        EventManager::instance()->processEvent(TRACKER_EVENT_GENERAL_SETTINGS, $params);
        $this->name                         = trim($request->getValidated('name', 'string', ''));
        $this->description                  = trim($request->getValidated('description', 'text', ''));
        $this->color                        = trim($request->getValidated('tracker_color', 'string', ''));
        $this->item_name                    = trim($request->getValidated('item_name', 'string', ''));
        $this->allow_copy                   = $request->getValidated('allow_copy') ? 1 : 0;
        $this->enable_emailgateway          = $request->getValidated('enable_emailgateway') ? 1 : 0;
        $this->submit_instructions          = $request->getValidated('submit_instructions', 'text', '');
        $this->browse_instructions          = $request->getValidated('browse_instructions', 'text', '');
        $this->instantiate_for_new_projects = $request->getValidated('instantiate_for_new_projects') || $cannot_configure_instantiate_for_new_projects ? 1 : 0;
        $this->log_priority_changes         = $request->getValidated('log_priority_changes') ? 1 : 0;

        if (!$this->name || !$this->description || !$this->color || !$this->item_name) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','name_requ'));
        } else {
            if ($old_name != $this->name) {
                if(TrackerFactory::instance()->isNameExists($this->name, $this->group_id)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','name_already_exists', $this->name));
                    return false;
                }
            }
            if ($old_item_name != $this->item_name) {
                if (!$this->itemNameIsValid($this->item_name)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','invalid_shortname', $this->item_name, CODENDI_PURIFIER_CONVERT_HTML));
                    return false;
                }

                if(TrackerFactory::instance()->isShortNameExists($this->item_name, $this->group_id)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','shortname_already_exists', $this->item_name));
                    return false;
                }

                $reference_manager = ReferenceManager::instance();
                if (!$reference_manager->checkKeyword($this->item_name) ) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type','invalid_shortname', $this->item_name, CODENDI_PURIFIER_CONVERT_HTML));
                    return false;
                }

                if ($reference_manager->_isKeywordExists($this->item_name, $this->group_id)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_type', 'shortname_already_exists', $this->item_name, CODENDI_PURIFIER_CONVERT_HTML));
                    return false;
                }

                //Update reference and cross references
                //WARNING this replace existing reference(s) so that all old_item_name reference won't be extracted anymore
                $reference_manager->updateProjectReferenceShortName($this->group_id, $old_item_name, $this->item_name);

                $artifact_link_value_dao = new Tracker_FormElement_Field_Value_ArtifactLinkDao();
                $artifact_link_value_dao->updateItemName($this->group_id, $old_item_name, $this->item_name);
            }

            $dao = new TrackerDao();
            if ($dao->save($this)) {
                $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin', 'successfully_updated'));
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('global', 'error'));
            }
        }
    }

    /**
     * Validate the format of the item name
     * @param string $itemname
     * @return boolean
     */
    public function itemNameIsValid($item_name)
    {
        return preg_match("/^[a-zA-Z0-9_]+$/i",$item_name);
    }

    /**
     * Test if the tracker is active
     * @return boolean
     */
    public function isActive() {
        return !$this->isDeleted();
    }

    /**
     * Test if tracker is deleted
     *
     * @return Boolean
     */
    public function isDeleted() {
        return ($this->deletion_date != '');
    }

    /**
     * @return Tracker_SemanticManager
     */
    public function getTrackerSemanticManager() {
        return new Tracker_SemanticManager($this);
    }

    /**
     * @return Tracker_Tooltip
     */
    public function getTooltip() {
        return new Tracker_Tooltip($this);
    }

    /**
     * @return Tracker_NotificationsManager
     */
    public function getNotificationsManager()
    {
        $user_to_notify_dao             = new UsersToNotifyDao();
        $ugroup_to_notify_dao           = new UgroupsToNotifyDao();
        $unsubscribers_notification_dao = new UnsubscribersNotificationDAO;
        $notification_list_builder      = new NotificationListBuilder(
            new UGroupDao(),
            new CollectionOfUserInvolvedInNotificationPresenterBuilder($user_to_notify_dao, $unsubscribers_notification_dao),
            new CollectionOfUgroupToBeNotifiedPresenterBuilder($ugroup_to_notify_dao)
        );
        return new Tracker_NotificationsManager(
            $this,
            $notification_list_builder,
            $user_to_notify_dao,
            $ugroup_to_notify_dao,
            new UserNotificationSettingsDAO,
            new GlobalNotificationsAddressesBuilder(),
            UserManager::instance(),
            new UGroupManager(),
            new GlobalNotificationSubscribersFilter($unsubscribers_notification_dao),
            new NotificationLevelExtractor(),
            new \TrackerDao(),
            new \ProjectHistoryDao()
        );
    }

    /**
     * @return Tracker_CannedResponseManager
     */
    public function getCannedResponseManager() {
        return new Tracker_CannedResponseManager($this);
    }

    /**
     * @return Tracker_CannedResponseFactory
     */
    public function getCannedResponseFactory() {
        return Tracker_CannedResponseFactory::instance();
    }

    /**
     * @return WorkflowManager
     */
    public function getWorkflowManager() {
        return new WorkflowManager($this);
    }

    /**
     * @return Tracker_Permission_PermissionController
     */
    protected function getPermissionController() {
        return new Tracker_Permission_PermissionController($this);
    }

    /**
     * @return Tracker_RulesManager
     */
    private function getGlobalRulesManager() {
        return $this->getWorkflowFactory()->getGlobalRulesManager($this);
    }

    /**
     * @return MailGatewayConfig
     */
    private function getMailGatewayConfig() {
        return new MailGatewayConfig(
            new MailGatewayConfigDao()
        );
    }

    /**
     * @return Tracker_ArtifactByEmailStatus
     */
    private function getArtifactByMailStatus() {
        return new Tracker_ArtifactByEmailStatus($this->getMailGatewayConfig());
    }

    /**
     * @return string
     */
    public function displayRulesAsJavascript() {
        return $this->getGlobalRulesManager()->displayRulesAsJavascript();
    }

    /**
     * Determine if the user can view this tracker.
     * Note that if there is no group explicitely auhtorized, access is denied (don't check default values)
     *
     * @param int $user if not specified, use the current user id. The params accept also User object
     *
     * @return boolean true if the user can view the tracker.
     */
    public function userCanView($user = 0) {

        $user_manager = $this->getUserManager();

        if (! $user instanceof PFUser) {
            if (!$user) {
                $user = $user_manager->getCurrentUser();
            } else {
                $user = $user_manager->getUserById((int)$user);
            }
        }

        $project_manager    = ProjectManager::instance();
        $permission_checker = new Tracker_Permission_PermissionChecker($user_manager, $project_manager);

        return $permission_checker->userCanViewTracker($user, $this);
    }

    protected $cache_permissions = null;

    /**
     * get the permissions for this tracker
     * E.g.
     * array(
     *     $ugroup_id_1 => array('PLUGIN_TRACKER_ADMIN'),
     *     $ugroup_id_2 => array('PLUGIN_TRACKER_ACCESS')
     * );
     *
     * @return array
     */
    public function getPermissionsByUgroupId() {
        if (! $this->cache_permissions) {
            $this->cache_permissions = array();
            $perm_dao = new Tracker_PermDao();
            if ($dar = $perm_dao->searchAccessPermissionsByTrackerId($this->getId())) {
                while ($row = $dar->getRow()) {
                    $this->cache_permissions[$row['ugroup_id']][] = $row['permission_type'];
                }
            }
        }
        return $this->cache_permissions;
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
    public function setCachePermission($ugroup_id, $permission_type) {
        $this->cache_permissions[$ugroup_id][] = $permission_type;
    }

    /**
     * Empty cache permissions
     *
     * @return void
     */
    public function permissionsAreCached() {
        return is_array($this->cache_permissions);
    }

    /**
     * @var array
     */
    private $cached_permission_authorized_ugroups;

    /**
     * Retreives the permissions set on a given tracker
     * E.g.
     * array(
     *     Tracker::PERMISSION_ADMIN => array($ugroup_id_1, $ugroup_id_2)
     *     Tracker::PERMISSION_NONE  => array($ugroup_id_1, $ugroup_id_2)
     * );
     *
     * @return array
     */
    public function getAuthorizedUgroupsByPermissionType() {
        if (! $this->cached_permission_authorized_ugroups || empty($this->cached_permission_authorized_ugroups)) {

            $this->cached_permission_authorized_ugroups = array();
            $perm_dao = new Tracker_PermDao();

            if ($dar = $perm_dao->searchAccessPermissionsByTrackerId($this->getId())) {
                while ($row = $dar->getRow()) {
                    $this->cached_permission_authorized_ugroups[$row['permission_type']][] = $row['ugroup_id'];
                }
            }
        }
        return $this->cached_permission_authorized_ugroups;
    }

    /**
     * Retreives the permissions set on a given tracker fields
     *
     * @return array
     */
    public function getFieldsAuthorizedUgroupsByPermissionType() {
        $fields             = Tracker_FormElementFactory::instance()->getUsedFields($this);
        $perm_dao           = new Tracker_PermDao();
        $authorized_ugroups = array();

        foreach ($fields as $field) {
            $field_id = $field->getId();
            if ($dar = $perm_dao->searchAccessPermissionsByFieldId($field_id)) {
                while ($row = $dar->getRow()) {
                    $authorized_ugroups[$field_id][$row['permission_type']][] = $row['ugroup_id'];
                }
            }
        }
        return $authorized_ugroups;
    }

    /**
     * See if the user's perms are >= 2 or project admin.
     *
     * @param int $user Either the user ID or the User object to test, or current user if false
     *
     * @return boolean True if the user is tracker admin, false otherwise
     */
    public function userIsAdmin($user = false)
    {
        if (! $user instanceof PFUser) {
            $user_manager = UserManager::instance();
            if (! $user) {
                $user = $user_manager->getCurrentUser();
            } else {
                $user = $user_manager->getUserById((int) $user);
            }
        }

        static $cache_is_admin = array();

        if (isset($cache_is_admin[$this->getId()][$user->getId()])) {
            return $cache_is_admin[$this->getId()][$user->getId()];
        }

        if ($user->isSuperUser() || $user->isMember($this->getGroupId(), 'A')) {
            $cache_is_admin[$this->getId()][$user->getId()] = true;
            return true;
        }

        if ($this->getTrackerManager()->userCanAdminAllProjectTrackers($user)) {
            $cache_is_admin[$this->getId()][$user->getId()] = true;
            return true;
        }

        $permissions = $this->getPermissionsByUgroupId();

        foreach ($permissions as $ugroup_id => $permission_types) {

            foreach ( $permission_types as $permission_type ) {

                if($permission_type == self::PERMISSION_ADMIN) {

                    if ($user->isMemberOfUGroup($ugroup_id, $this->getGroupId())) {
                        $cache_is_admin[$this->getId()][$user->getId()] = true;
                        return true;
                    }
                }
            }
        }
        $cache_is_admin[$this->getId()][$user->getId()] = false;
        return false;
    }

    /**
     * @return TrackerManager
     */
    protected function getTrackerManager() {
        return new TrackerManager();
    }


    /**
     * Check if user has permission to submit artifact or not
     *
     * @param PFUser $user The user to test (current user if not defined)
     *
     * @return boolean true if user has persission to submit artifacts, false otherwise
     */
    function userCanSubmitArtifact($user = false) {
        if (! $user instanceof PFUser) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }

        if ($user->isAnonymous() || ! $this->userCanView($user)) {
            return false;
        }

        $can_submit = false;
        foreach($this->getFormElementFactory()->getUsedFields($this) as $form_element) {
            if ($form_element->userCanSubmit($user)) {
                $can_submit = true;
            }
        }

        return $can_submit;
    }

    /**
     * Check if user has permission to delete a tracker or not
     *
     * @param PFUser $user The user to test (current user if not defined)
     *
     * @return boolean true if user has persission to delete trackers, false otherwise
     */
    function userCanDeleteTracker($user = false) {
        if (!($user instanceof PFUser)) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        return $user->isSuperUser() || $user->isMember($this->getGroupId(), 'A');
    }

    public function getInformationsFromOtherServicesAboutUsage() {
        $result                   = array();
        $result['can_be_deleted'] = true;

        EventManager::instance()->processEvent(
            TRACKER_USAGE,
            array(
                'tracker'   => $this,
                'result'    => &$result
            )
        );

        return $result;
    }

    /**
     * Check if user has full access to a tracker or not
     *
     * @param PFUser $user The user to test (current user if not defined)
     *
     * @return boolean true if user has full access to tracker, false otherwise
     */
    function userHasFullAccess($user = false) {
        if (!($user instanceof PFUser)) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        if ($user->isSuperUser() || $user->isMember($this->getGroupId(), 'A')) {
            return true;
        }  else {
            $permissions = $this->getPermissionsByUgroupId();
            foreach ($permissions as $ugroup_id => $permission_types) {
                foreach ( $permission_types as $permission_type ) {
                    if($permission_type == self::PERMISSION_FULL || $permission_type == self::PERMISSION_ADMIN) {
                        if ($user->isMemberOfUGroup($ugroup_id, $this->getGroupId())) {
                                return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function exportToXML(SimpleXMLElement $xmlElem, array &$xmlMapping = array()) {
        $user_xml_exporter = $this->getUserXMLExporter();

        return $this->exportTrackerToXML($xmlElem, $user_xml_exporter, $xmlMapping, false);
    }

    public function exportToXMLInProjectExportContext(
        SimpleXMLElement $xmlElem,
        UserXMLExporter $user_xml_exporter,
        array &$xmlMapping = array()
    ) {
        return $this->exportTrackerToXML($xmlElem, $user_xml_exporter, $xmlMapping, true);
    }

    public function getXMLId() {
        return self::XML_ID_PREFIX. $this->getId();
    }

    /**
     * Exports the tracker to an XML file.
     *
     * @return SimpleXMLElement
     */
    private function exportTrackerToXML(
        SimpleXMLElement $xmlElem,
        UserXMLExporter $user_xml_exporter,
        array &$xmlMapping,
        $project_export_context
    ) {
        $xmlElem->addAttribute('id', $this->getXMLId());

        $cdata_section_factory = new XML_SimpleXMLCDATAFactory();

        $parent_id = $this->getParentId();
        if ($parent_id && ! $project_export_context) {
            $parent_id = "T". $parent_id;
        } else {
            $parent_id = "0";
        }

        $xmlElem->addAttribute('parent_id', (string)$parent_id);

        // only add attributes which are different from the default value
        if ($this->enable_emailgateway) {
            $xmlElem->addAttribute('enable_emailgateway', $this->enable_emailgateway);
        }
        if ($this->allow_copy) {
            $xmlElem->addAttribute('allow_copy', $this->allow_copy);
        }
        if ($this->instantiate_for_new_projects) {
            $xmlElem->addAttribute('instantiate_for_new_projects', $this->instantiate_for_new_projects);
        }
        if ($this->log_priority_changes) {
            $xmlElem->addAttribute('log_priority_changes', $this->log_priority_changes);
        }
        if ($this->notifications_level) {
            $xmlElem->addAttribute('notifications_level', $this->notifications_level);
        }

        // these will not be used at the import
        $cdata_section_factory->insert($xmlElem, 'name', $this->getName());
        $xmlElem->addChild('item_name', $this->getItemName());
        $cdata_section_factory->insert($xmlElem, 'description', $this->getDescription());
        $xmlElem->addChild('color', $this->getColor());

        // add only if not empty
        if ($this->submit_instructions) {
            $cdata_section_factory->insert($xmlElem, 'submit_instructions', $this->submit_instructions);
        }
        if ($this->browse_instructions) {
            $cdata_section_factory->insert($xmlElem, 'browse_instructions', $this->browse_instructions);
        }

        $child = $xmlElem->addChild('cannedResponses');
        if ($responses = $this->getCannedResponseFactory()->getCannedResponses($this)) {
            foreach ($responses as $response) {
                $grandchild = $child->addChild('cannedResponse');
                $response->exportToXML($grandchild);
            }
        }

        $child = $xmlElem->addChild('formElements');
        // association between ids in database and ids in xml


        foreach ($this->getFormElementFactory()->getUsedFormElementForTracker($this) as $formElement) {
            $grandchild = $child->addChild('formElement');
            $formElement->exportToXML($grandchild, $xmlMapping, $project_export_context, $user_xml_exporter);
        }

        // semantic
        $tsm = $this->getTrackerSemanticManager();
        $child = $xmlElem->addChild('semantics');
        $tsm->exportToXML($child, $xmlMapping);

        // rules
        $child = $xmlElem->addChild('rules');
        $this->getGlobalRulesManager()->exportToXML($child, $xmlMapping);

        // only the reports with project scope are exported
        $reports = $this->getReportFactory()->getReportsByTrackerId($this->id, null);
        if ($reports) {
            $child = $xmlElem->addChild('reports');
            foreach ($this->getReportFactory()->getReportsByTrackerId($this->id, null) as $report) {
                $report->exportToXML($child, $xmlMapping);
            }
        }

        // workflow
        $child = $xmlElem->addChild('workflow');
        $workflow = $this->getWorkflowFactory()->getWorkflowByTrackerId($this->id);
        if(!empty($workflow)) {
            $workflow->exportToXML($child, $xmlMapping);
        }

        // permissions
        $node_perms      = $xmlElem->addChild('permissions');
        $project_ugroups = $this->getProjectUgroups();
        // tracker permissions
        if ($permissions = $this->getPermissionsByUgroupId()) {
            foreach ($permissions as $ugroup_id => $permission_types) {
                if (($ugroup = array_search($ugroup_id, $project_ugroups)) !== false) {
                    foreach ( $permission_types as $permission_type) {
                        $node_perm = $node_perms->addChild('permission');
                        $node_perm->addAttribute('scope', 'tracker');
                        $node_perm->addAttribute('ugroup', $ugroup);
                        $node_perm->addAttribute('type', $permission_type);
                        unset($node_perm);
                    }
                }
            }
        }
        // fields permission
        if ($formelements = $this->getFormElementFactory()->getUsedFormElementForTracker($this)) {
            foreach ($formelements as $formelement) {
                $formelement->exportPermissionsToXML($node_perms, $project_ugroups, $xmlMapping);
            }
        }

        return $xmlElem;
    }

    /**
     * @return array
     */
    protected function getProjectUgroups()
    {
        $ugroup_manager = new UGroupManager();
        $ugroups        = $GLOBALS['UGROUPS'];
        $static_groups  = $ugroup_manager->getStaticUGroups($this->getProject());
        foreach ($static_groups as $ugroup) {
            $ugroups[$ugroup->getName()] = $ugroup->getId();
        }

        return $ugroups;
    }

    /**
     * Send the xml to the client
     *
     * @param SimpleXMLElement $xmlElem The xml
     */
    protected function sendXML(SimpleXMLElement $xmlElem) {
        $dom = dom_import_simplexml($xmlElem)->ownerDocument;
        $dom->formatOutput = true;

        $output_filename = 'Tracker_'.$this->item_name.'.xml';
        $xml             = $dom->saveXML();

        $GLOBALS['Response']->sendXMLAttachementFile($xml, $output_filename);
    }

    /**
     * Returns the array of fields corresponding with the CSV header field_names $header
     * Returns false if there is an unknown field
     * field_name 'aid' is kept like this
     *
     * @param array $header the array of field names (string), in the same order than the CSV file
     *
     * @return array of Tracker_FormElementField the fields, in the same order than the header, or false if there is an unknown field
     */
    private function getCSVFields(array $header)
    {
        $fef = $this->getFormElementFactory();
        $fields = array();
        foreach($header as $field_name) {
            if ($field_name !== 'aid') {
                $field = $fef->getUsedFieldByName($this->getId(), $field_name);
                if ($field) {
                    $fields[] = $field;
                } else {
                    $fields[] = null;
                }
            } else {
                $fields[] = 'aid';
            }
        }
        return $fields;
    }

    private function _getCSVSeparator($current_user) {
        if ( ! $current_user || ! ($current_user instanceof PFUser)) {
            $current_user = UserManager::instance()->getCurrentUser();
        }

        $separator = ",";   // by default, comma.
        $separator_csv_export_pref = $current_user->getPreference('user_csv_separator');
        switch ($separator_csv_export_pref) {
        case "comma":
            $separator = ',';
            break;
        case "semicolon":
            $separator = ';';
            break;
        case "tab":
            $separator = chr(9);
            break;
        }
        return $separator;
    }

    private function _getCSVDateformat($current_user) {
        if ( ! $current_user || ! ($current_user instanceof PFUser)) {
            $current_user = UserManager::instance()->getCurrentUser();
        }
        $dateformat_csv_export_pref = $current_user->getPreference('user_csv_dateformat');
        if ($dateformat_csv_export_pref === false) {
            $dateformat_csv_export_pref = "month_day_year"; // by default, mm/dd/yyyy
        }
        return $dateformat_csv_export_pref;
    }

    protected function displayImportPreview(Tracker_IDisplayTrackerLayout $layout, $request, $current_user, $session) {
        $purifier = Codendi_HTMLPurifier::instance();

        if ($_FILES['csv_filename']) {
            $f = fopen($_FILES['csv_filename']['tmp_name'], 'r');
            if ($f) {
                // get the csv separator (defined in user preferences)
                $separator = $this->_getCSVSeparator($current_user);

                $is_valid = true;
                $i = 0;
                $lines = array();
                while ($line = fgetcsv($f, 0, $separator)) {
                    if ($line === false) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'error_in_csv_file', array($i)));
                        $is_valid = false;
                    } else {
                        $lines[] = $this->getCSVLine($line, $i);
                    }
                    $i++;
                }
                fclose($f);

                if ($is_valid) {
                    if (count($lines) >= 2) {
                        $is_valid = $this->isValidCSV($lines, $separator);

                        //header
                        $items = $this->getAdminItems();
                        $title = $items['csvimport']['title'];
                        $this->displayAdminCSVImportHeader($layout, $title, array());

                        echo '<h2>'. $title .'</h2>';
                        //body
                        if (count($lines) > 1) {
                            $html_table = '';
                            $html_table .= '<table class="table csv-import-preview">';
                            $html_table .=  '<thead>';
                            $header = array_shift($lines);
                            $html_table .=  '<tr class="boxtable">';
                            $html_table .=  '<th class="boxtitle"></th>';
                            $fields = $this->getCSVFields($header);

                            foreach ($header as $field_name) {
                                $html_table .=  '<th class="boxtitle tracker_report_table_column">';
                                $html_table .=  $purifier->purify($field_name);
                                $html_table .=  '</th>';
                            }
                            $html_table .=  '</tr>';
                            $html_table .=  '</thead>';
                            $html_table .=  '<tbody>';
                            $nb_lines = 0;
                            $nb_artifact_creation = 0;
                            $nb_artifact_update = 0;
                            foreach ($lines as $line_number => $data_line) {
                                if ($nb_lines % 2 == 0) {
                                    $tr_class = 'boxitem';
                                } else {
                                    $tr_class = 'boxitemalt';
                                }
                                $html_table .= '<tr class="'. $tr_class .'">';
                                $html_table .= '<td style="color:gray;">'. ($line_number + 1) .'</td>';
                                $mode = 'creation';
                                foreach ($data_line as $idx => $data_cell) {
                                    if ($fields[$idx] && is_a($fields[$idx], 'Tracker_FormElement_Field')) {
                                        $field  = $fields[$idx];
                                        $displayed_data = $field->getFieldDataForCSVPreview($data_cell);
                                    } else  {
                                        // else: this cell is an 'aid' cell
                                        if ($fields[$idx] === 'aid' && $data_cell) {
                                            $mode = 'update';
                                        }
                                        $displayed_data = $purifier->purify($data_cell);
                                    }
                                    $html_table .=  '<td class="tracker_report_table_column">' . $displayed_data .'</td>';
                                }
                                $html_table .=  '</tr>';
                                $nb_lines++;
                                if ($mode == 'creation') {
                                    $nb_artifact_creation++;
                                } else {
                                    $nb_artifact_update++;
                                }
                            }
                            $html_table .=  '</tbody>';
                            $html_table .=  '</table>';

                            echo '<p>';
                            echo $GLOBALS['Language']->getText('plugin_tracker_import', 'check_data') . '<br />';
                            echo $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'date_format_help', array($GLOBALS['Language']->getText('account_preferences', $this->_getCSVDateformat($current_user))));
                            echo '</p>';

                            if ($is_valid) {
                                echo '<form name="form1" method="POST" enctype="multipart/form-data" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->id .'&amp;func=admin-csvimport">';
                                echo '<p>' . $GLOBALS['Language']->getText('plugin_tracker_import','ready', array($nb_lines, $nb_artifact_creation, $nb_artifact_update)) . '</p>';
                                echo '<input type="hidden" name="action" value="import">';
                                if ($request->exist('notify') && $request->get('notify') == 'ok') {
                                    echo '<input type="hidden" name="notify" value="ok">';
                                }
                                echo '<input type="submit" class="csv-preview-import-button" value="'.$GLOBALS['Language']->getText('plugin_tracker_import','import_new_hdr').'">';
                            }
                            echo $html_table;
                            if ($is_valid) {
                                echo '</form>';

                                $session->set('csv_header', $header);
                                $session->set('csv_body', $lines);

                            }
                        }

                        //footer
                        $this->displayFooter($layout);
                        exit();
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'no_data'));
                    }
                }
            } else {
                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unable_to_open_file', array($_FILES['csv_filename']['tmp_name'])));
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'file_not_found'));
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. (int)$this->getId() .'&func=admin-csvimport');
        }
    }

    /**
     * @return array
     */
    private function getCSVLine(array $line, $index)
    {
        $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF));

        if ($index === 0 && strpos($line[0], $bom) === 0) {
            $line[0] = substr($line[0], strlen($bom));
        }

        return $line;
    }

    public function displayWarningArtifactByEmailSemantic() {
        $artifactbyemail_status = $this->getArtifactByMailStatus();

        if (! $artifactbyemail_status->isSemanticConfigured($this)) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('plugin_tracker_emailgateway','semantic_missing')
            );
        }
    }

    private function displayAdminFormElementsWarnings()
    {
        $this->displayWarningArtifactByEmailRequiredFields();
        $event = new DisplayAdminFormElementsWarningsEvent($this, $GLOBALS['Response']);
        EventManager::instance()->processEvent($event);
    }

    private function displayWarningArtifactByEmailRequiredFields() {
        $artifactbyemail_status = $this->getArtifactByMailStatus();

        if (! $artifactbyemail_status->isRequiredFieldsConfigured($this)) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('plugin_tracker_emailgateway','invalid_required_fields')
            );
        }
    }

    public function displayWarningGeneralsettings() {
        $artifactbyemail_status = $this->getArtifactByMailStatus();

        if (! $artifactbyemail_status->isRequiredFieldsConfigured($this)
            || ! $artifactbyemail_status->isSemanticConfigured($this)
        ) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('plugin_tracker_emailgateway','invalid_configuration')
            );
        }
    }

    /**
     * Validation of CSV file datas in this tracker
     *
     * @return bool, true if CSV file is valid, false otherwise
     */
    public function isValidCSV($lines, $separator) {
        $is_valid = true;
        $header_line = array_shift($lines);

        if (count($header_line) == 1) {
            // not sure it is an error, so don't set is_valid to false.
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'separator_not_found', array($separator)), CODENDI_PURIFIER_FULL);
        }

        if ($this->hasError($header_line, $lines)) {
            $is_valid = false;
        }
        return $is_valid;
    }

    /**
     * Check if CSV file contains unknown aid
     *
     * @param Array $header_line, the CSV file header line
     * @param Array $lines, the CSV file lines
     *
     * @return bool true if has unknown fields, false otherwise
     */
    public function hasUnknownAid($header_line, $lines) {
        $has_unknown = false;
        $aid_key = array_search('aid', $header_line);
        //Update mode
        if ($aid_key !== false) {
            foreach ($lines as $line) {
                if($line[$aid_key] != '') {
                    if (!$this->aidExists($line[$aid_key])) {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'aid_does_not_exist', array($line[$aid_key])));
                        $has_unknown = true;
                    }
                }
            }
        }
        return $has_unknown;
    }

    /**
     * Check if CSV file contains unknown fields
     *
     * @param Array $lines, the CSV file lines
     *
     * @return bool true if has unknown fields, false otherwise
     */
    public function hasError($header_line, $lines)
    {
        $has_error = false;
        $fef = $this->getFormElementFactory();
        $aid_key = array_search('aid', $header_line);
        $af = $this->getTrackerArtifactFactory();
        $artifact = null;
        $hp       = Codendi_HTMLPurifier::instance();

        $unknown_fields   = array();
        $error_nature     = array();
        foreach ($lines as $cpt_line => $line) {
            $data = array();
            foreach ($header_line as $idx => $field_name) {
                //Fields other than aid
                if ($field_name != 'aid') {
                    $field = $fef->getUsedFieldByName($this->getId(), $field_name);

                    if (! $field) {
                        $column_name = $field_name;
                        $field_name  = explode(" ", $field_name);
                        $field       = $fef->getUsedFieldByName($this->getId(), $field_name[0]);
                    }

                    if ($field && ! $field->isCSVImportable()) {
                        $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'field_not_taken_account', $field_name));
                        continue;
                    }

                    if (! $field) {
                        if (is_array($field_name)) {
                            $unknown_fields[implode('.', $field_name)] = implode(' ', $field_name);
                        } else {
                            $unknown_fields[$field_name] = $field_name;
                        }
                        $has_error = true;
                    } else if ($field && !is_array($field_name)) {
                        // check if value is ok
                        if ($aid_key !== false && $this->aidExists($line[$aid_key])) {
                            $artifact_id = $line[$aid_key];
                        } else {
                            $artifact_id = 0;
                        }

                        $artifact = $af->getInstanceFromRow(
                            array(
                                'id'                       => $artifact_id,
                                'tracker_id'               => $this->id,
                                'submitted_by'             => $this->getUserManager()->getCurrentuser()->getId(),
                                'submitted_on'             => $_SERVER['REQUEST_TIME'],
                                'use_artifact_permissions' => 0,
                            )
                        );
                        if ($line[$idx]!='') {
                            $data[$field->getId()] = $field->getFieldDataFromCSVValue($line[$idx]);

                            if ($data[$field->getId()] === null) {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_value', array($line[$idx], $field_name)));
                                $has_error = true;
                            }
                        }
                    } else {
                        $error_nature[$column_name] = $column_name;
                    }
                } else {
                    //Field is aid : we check if the artifact id exists
                    if ($this->hasUnknownAid($header_line, $lines)) {
                        $has_error = true;
                    }
                }
            }
            if ($artifact) {
                $is_new_artifact = $artifact->getId() == 0;
                if ($is_new_artifact) {
                    $fields_validator = new Tracker_Artifact_Changeset_InitialChangesetFieldsValidator($this->getFormElementFactory());
                } else {
                    $fields_validator = new Tracker_Artifact_Changeset_NewChangesetFieldsValidator($this->getFormElementFactory());
                }
                if (! $fields_validator->validate($artifact, $data)) {
                     $has_error = true;
                }
            }
        }
        if (count($unknown_fields) > 0) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_field', array(implode(',', $unknown_fields))));
        }
        if (count($error_nature) >0) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'importing_nature', array(implode(',', $error_nature))));
        }

        return $has_error;
    }


    /**
     * Check if CSV contains all the required fields and values associated
     *
     * @param Array $lines, the CSV file lines
     *
     * @return bool true if missing required fields, false otherwise
     */
    public function isMissingRequiredFields($header_line, $lines) {
        $is_missing = false;
        $fields = array();
        $fef = $this->getFormElementFactory();
        $fields = $fef->getUsedFields($this);
        foreach ($fields as $field) {
            if($field->isRequired()) {
                $key = array_search($field->getName(), $header_line);
                if ($key === false) {
                    //search if field  is in the CSV file header line
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'missing_required_field', array($field->getName())));
                    $is_missing = true;
                } else {
                    //search if there is a value at each line for that field
                    foreach ($lines as $line) {
                        if (! isset($line[$key]) || $line[$key] == '') {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'missing_required_field_value', array($field->getName())));
                            $is_missing = true;
                        }
                    }
                }
            }
        }
        return $is_missing;
    }

    /**
     * Check if aid exists in update mode in CSV import
     *
     * @param Int $aid, the artifact id
     *
     * @return String $error_message
     */
    protected function aidExists($aid) {
        $af = $this->getTrackerArtifactFactory();
        $artifact = $af->getArtifactById($aid);
        if ($artifact) {
            if ($artifact->getTrackerId() == $this->getId()) {
                return true;
            } else {
                return false;
            }
        }
        return false;
    }


    /**
     * Import artifacts from CSV file ($_FILES['csv_filename']['tmp_name'])  in this tracker
     *
     * @return boolean true if import succeed, false otherwise
     */
    private function importFromCSV(Codendi_Request $request, PFUser $current_user, array $header, array $lines)
    {
        $is_error = false;
        if (count($lines) >= 1) {
            if ($request->exist('notify') && $request->get('notify') == 'ok') {
                $send_notifications = true;
            } else {
                $send_notifications = false;
            }
            $fields = $this->getCSVFields($header);
            $af = Tracker_ArtifactFactory::instance();
            $nb_lines = 0;
            $nb_artifact_creation = 0;
            $nb_artifact_update = 0;
            foreach ($lines as $line_number => $data_line) {
                $mode = 'creation';
                $fields_data = array();
                foreach ($data_line as $idx => $data_cell) {

                    if (($fields[$idx]) === null) {
                        continue;
                    } else if ($fields[$idx] === 'aid') {
                        if ($data_cell) {
                            $mode = 'update';
                            $artifact_id = (int) $data_cell;
                        } else {
                            $artifact_id = 0;
                        }
                    } else if (is_a($fields[$idx], 'Tracker_FormElement')) {
                        $field = $fields[$idx];
                        if ($field->isCSVImportable()) {
                            $fields_data[$field->getId()] = $field->getFieldDataFromCSVValue($data_cell);
                        } else {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'field_not_taken_account', $field->getName()));
                        }
                    }
                }
                $nb_lines++;
                if ($mode == 'creation') {
                    if ($artifact = $af->createArtifact($this, $fields_data, $current_user, null, $send_notifications)) {
                        $nb_artifact_creation++;
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unable_to_create_artifact'));
                        $is_error = true;
                    }
                } else {
                    $artifact = $af->getArtifactById($artifact_id);
                    if ($artifact) {

                        if ($artifact->getTracker()->getId() !== $this->getId()) {
                            $GLOBALS['Response']->addFeedback(
                                Feedback::ERROR,
                                sprintf(
                                    dgettext('tuleap-tracker', "Artifact (%s) does not belong to this tracker."),
                                    $artifact->getId()
                                )
                            );

                            $is_error = true;
                        }
                        $followup_comment = '';
                        try {
                            $artifact->createNewChangeset($fields_data, $followup_comment, $current_user, $send_notifications);
                            $nb_artifact_update++;
                        } catch (Tracker_NoChangeException $e) {
                            $GLOBALS['Response']->addFeedback('info', $e->getMessage(), CODENDI_PURIFIER_LIGHT);
                            $is_error = true;
                        } catch (Tracker_Exception $e) {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unable_to_update_artifact', array($artifact_id)));
                            $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                            $is_error = true;
                        }
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_artifact', array($artifact_id)));
                        $is_error = true;
                    }
                }
            }
            if (! $is_error) {
                if ($nb_artifact_creation > 0) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'nb_created_import', array($nb_artifact_creation)));
                }
                if ($nb_artifact_update > 0) {
                    $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'nb_updated_import', array($nb_artifact_update)));
                }
            }
        } else {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'no_data'));
            $is_error = true;
        }
        return  ! $is_error;
    }

     /**
     * Get UserManager instance
     *
     * @return UserManager
     */
    protected function getUserManager() {
        return UserManager::instance();
    }

    /**
     * Get Tracker_ArtifactFactory instance
     *
     * @return Tracker_ArtifactFactory
     */
    protected function getTrackerArtifactFactory() {
        return Tracker_ArtifactFactory::instance();
    }

    /**
     * Get FormElementFactory instance
     *
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory() {
        return $this->formElementFactory;
    }

    /**
     * Get WorkflowFactory instance
     *
     * @return WorkflowFactory
     */
    protected function getWorkflowFactory() {
        return WorkflowFactory::instance();
    }

    /**
     * Get ReportFactory instance
     *
     * @return Tracker_ReportFactory
     */
    protected function getReportFactory() {
        return Tracker_ReportFactory::instance();
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return true if Tracker is ok
     */
    public function testImport() {
        foreach ($this->formElements as $form) {
            if (!$form->testImport()) {
                return false;
            }
        }
        return true;
    }

    /**
     * Ask to fields to augment the fields_data
     *
     * @param array &$fields_data The user submitted data
     *
     * @return void
     */
    public function augmentDataFromRequest(&$fields_data) {
        foreach(Tracker_FormElementFactory::instance()->getUsedFields($this) as $field) {
            $field->augmentDataFromRequest($fields_data);
        }
    }

    /**
     * Get a recipients list for (global) notifications.
     *
     * @return array
     */
    public function getRecipients() {
        $recipients            = array();
        $notifications_manager = $this->getNotificationsManager();
        $notifications         = $notifications_manager->getGlobalNotifications();
        $email_retriever       = new GlobalNotificationsEmailRetriever(
            new UsersToNotifyDao(),
            new UgroupsToNotifyDao(),
            new UGroupManager(),
            TrackerFactory::instance(),
            new GlobalNotificationsAddressesBuilder()
        );
        foreach ($notifications as $id => $notification) {
            $notified_emails = $email_retriever->getNotifiedEmails($notification);
            $recipients[$id] = array( 'recipients'=>$notified_emails, 'on_updates'=> $notification->isAllUpdates(), 'check_permissions'=> $notification->isCheckPermissions()  );
        }
        return $recipients;
    }

    protected $cache_stats;

    /**
     * get stats for this tracker
     *
     * @return array
     */
    public function getStats() {
        if (!isset($this->cache_stats)) {
            $dao = new Tracker_ArtifactDao();
            $this->cache_stats = $dao->searchStatsForTracker($this->id)->getRow();
        }
        return $this->cache_stats;
    }

    /**
     * Fetch some statistics about this tracker to display on trackers home page
     *
     * @return string html
     */
    public function fetchStats() {
        $html = '';
        if ($row = $this->getStats()) {
            $html .= '<div class="tracker_statistics" style="font-size:0.9em; color:#666;">';
            $html .= '<div style="text-align:right;font-size:0.825em;">#'. $this->id .'</div>';
            if ($row['nb_total'] && $this->hasSemanticsStatus()) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_stat','number_open_artifacts').' '. $row['nb_open'] .'<br />';
            }

            $html .= $GLOBALS['Language']->getText('plugin_tracker_stat','total_number_artifacts'). ' '.$row['nb_total'] .'<br />';
            if ($row['last_creation'] && $row['last_update']) {

                $html .= $GLOBALS['Language']->getText('plugin_tracker_stat','recent_activity');
                $html .= '<ul>';
                if ($row['last_update']) {
                    $html .= '<li>'. $GLOBALS['Language']->getText('plugin_tracker_stat','last_update').' ';
                    $html .= DateHelper::timeAgoInWords($row['last_update'], true, true);
                    $html .= '</li>';
                }
                if ($row['last_creation']) {
                    $html .= '<li>'. $GLOBALS['Language']->getText('plugin_tracker_stat','last_artifact_created').' ';
                    $html .= DateHelper::timeAgoInWords($row['last_creation'], true, true);
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }
            $html .= '</div>';
        }
        return $html;
    }

    /**
     * Say if the tracker as "title" defined
     *
     * @return bool
     */
    public function hasSemanticsTitle() {
        return Tracker_Semantic_Title::load($this)->getFieldId() ? true : false;
    }

    /**
     * Return the title field, or null if no title field defined
     *
     * @return Tracker_FormElement_Field_Text the title field, or null if not defined
     */
    public function getTitleField() {
        $title_field = Tracker_Semantic_Title::load($this)->getField();
        if ($title_field) {
            return $title_field;
        } else {
            return null;
        }
    }

    /**
     * Say if the tracker as "description" defined
     *
     * @return bool
     */
    public function hasSemanticsDescription() {
        return Tracker_Semantic_Description::load($this)->getFieldId() ? true : false;
    }

    /**
     * Return the description field, or null if no title field defined
     *
     * @return Tracker_FormElement_Field_Text the title field, or null if not defined
     */
    public function getDescriptionField() {
        $title_field = Tracker_Semantic_Description::load($this)->getField();
        if ($title_field) {
            return $title_field;
        } else {
            return null;
        }
    }

    /**
     * Say if the tracker as "status" defined
     *
     * @return bool
     */
    public function hasSemanticsStatus() {
        return Tracker_Semantic_Status::load($this)->getFieldId() ? true : false;
    }

    /**
     * Return the status field, or null if no status field defined
     *
     * @return Tracker_FormElement_Field_List the status field, or null if not defined
     */
    public function getStatusField() {
        $status_field = Tracker_Semantic_Status::load($this)->getField();
        if ($status_field) {
            return $status_field;
        } else {
            return null;
        }
    }

    /**
     * Return the contributor field, or null if no contributor field defined
     *
     * @return Tracker_FormElement_Field_List the contributor field, or null if not defined
     */
    public function getContributorField() {
        $contributor_field = Tracker_Semantic_Contributor::load($this)->getField();
        if ($contributor_field) {
            return $contributor_field;
        } else {
            return null;
        }
    }

    public function setFormElementFactory(Tracker_FormElementFactory $factory) {
        $this->formElementFactory = $factory;
    }

    public function setSharedFormElementFactory(Tracker_SharedFormElementFactory $factory) {
        $this->sharedFormElementFactory = $factory;
    }

    /**
     * Set children trackers
     *
     * @param Tracker[] $trackers
     */
    public function setChildren(array $trackers) {
        $this->children = $trackers;
    }

    /**
     * Return the children of the tracker
     *
     * @return Tracker[]
     */
    public function getChildren() {
        if ($this->children === null) {
            $this->children = $this->getHierarchyFactory()->getChildren($this->getId());
        }
        return $this->children;
    }

    /**
     * Return the hierarchy the tracker belongs to
     *
     * @return Tracker_Hierarchy
     */
    public function getHierarchy() {
        return $this->getHierarchyFactory()->getHierarchy(array($this->getId()));
    }

    /**
     * @return Tracker_HierarchyFactory
     */
    protected function getHierarchyFactory()
    {
        return new Tracker_HierarchyFactory(
            new Tracker_Hierarchy_Dao(),
            $this->getTrackerFactory(),
            $this->getTrackerArtifactFactory(),
            new NatureIsChildLinkRetriever(
                $this->getTrackerArtifactFactory(),
                new Tracker_FormElement_Field_Value_ArtifactLinkDao()
            )
        );
    }

    /**
     * Set parent
     *
     * @param Tracker $tracker
     */
    public function setParent(Tracker $tracker = null) {
        $this->parent = $tracker;
    }

    /**
     * Return parent tracker of current tracker (if any)
     *
     * @return Tracker
     */
    public function getParent() {
        if ($this->parent === false) {
            $parent_tracker_id = $this->getParentId();
            if ($parent_tracker_id) {
                $this->parent = $this->getTrackerFactory()->getTrackerById($parent_tracker_id);
            } else {
                $this->parent = self::NO_PARENT;
            }
        }
        if ($this->parent === self::NO_PARENT) {
            return null;
        }
        return $this->parent;
    }

    protected function getParentId() {
        return $this->getHierarchy()->getParent($this->getId());
    }

    /**
     * Return workflow of the current tracker (there is always a workflow).
     *
     * @return Workflow
     */
    public function getWorkflow() {
        if (! $this->workflow) {
            $this->workflow = $this->getWorkflowFactory()->getWorkflowByTrackerId($this->getId());
            if (! $this->workflow) {
                $this->workflow = $this->getWorkflowFactory()->getWorkflowWithoutTransition($this);
            }
        }
        return $this->workflow;
    }

    /**
     * @return string
     */
    public function getUri() {
        return TRACKER_BASE_URL . '/?tracker=' . $this->getId();
    }

    private function getArtifactXMLImporterForArtifactCopy(Tracker_XML_Importer_CopyArtifactInformationsAggregator $logger) {
        $fields_validator      = new Tracker_Artifact_Changeset_AtGivenDateFieldsValidator(
            $this->getFormElementFactory()
        );

        $changeset_dao         = new Tracker_Artifact_ChangesetDao();
        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();
        $send_notifications    = true;
        $emitter               = new Emitter(
            new Http_Client(),
            new WebhookStatusLogger()
        );

        $webhook_retriever = new WebhookRetriever(new WebhookDao());

        $artifact_creator = new Tracker_ArtifactCreator(
            $this->getTrackerArtifactFactory(),
            $fields_validator,
            new Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator(
                $fields_validator,
                $this->getFormElementFactory(),
                $changeset_dao,
                $this->getTrackerArtifactFactory(),
                EventManager::instance(),
                $emitter,
                $webhook_retriever
            ),
            $this->getVisitRecorder()
        );

        $new_changeset_creator = new Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator(
            $fields_validator,
            $this->getFormElementFactory(),
            $changeset_dao,
            $changeset_comment_dao,
            $this->getTrackerArtifactFactory(),
            EventManager::instance(),
            ReferenceManager::instance(),
            new SourceOfAssociationCollectionBuilder(
                new SubmittedValueConvertor(
                    Tracker_ArtifactFactory::instance(),
                    new SourceOfAssociationDetector(
                        Tracker_HierarchyFactory::instance()
                    )
                ),
                Tracker_FormElementFactory::instance()
            ),
            $emitter,
            $webhook_retriever
        );

        return new Tracker_Artifact_XMLImport(
            new XML_RNGValidator(),
            $artifact_creator,
            $new_changeset_creator,
            Tracker_FormElementFactory::instance(),
            new XMLImportHelper($this->getUserManager()),
            new Tracker_FormElement_Field_List_Bind_Static_ValueDao(),
            $logger,
            $send_notifications,
            Tracker_ArtifactFactory::instance(),
            new NatureDao()
        );
    }

    private function getChildrenCollector(Codendi_Request $request) {
        if ($request->get('copy_children')) {
            return new Tracker_XML_ChildrenCollector();
        }

        return new Tracker_XML_Exporter_NullChildrenCollector();
    }

    private function getArtifactXMLExporter(
        Tracker_XML_ChildrenCollector $children_collector,
        Tracker_XML_Exporter_FilePathXMLExporter $file_path_xml_exporter,
        PFUser $current_user
    ) {
        $builder               = new Tracker_XML_Exporter_ArtifactXMLExporterBuilder();
        $user_xml_exporter     = $this->getUserXMLExporter();
        $is_in_archive_context = false;

        return $builder->build(
            $children_collector,
            $file_path_xml_exporter,
            $current_user,
            $user_xml_exporter,
            $is_in_archive_context
        );
    }

    private function getUserXMLExporter() {
        return new UserXMLExporter(
            $this->getUserManager(),
            new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
        );
    }

    private function getChangesetXMLUpdater() {
        $visitor = new Tracker_XML_Updater_FieldChangeXMLUpdaterVisitor(
            new Tracker_XML_Updater_FieldChange_FieldChangeDateXMLUpdater(),
            new Tracker_XML_Updater_FieldChange_FieldChangeFloatXMLUpdater(),
            new Tracker_XML_Updater_FieldChange_FieldChangeIntegerXMLUpdater(),
            new Tracker_XML_Updater_FieldChange_FieldChangeTextXMLUpdater(),
            new Tracker_XML_Updater_FieldChange_FieldChangeStringXMLUpdater(),
            new Tracker_XML_Updater_FieldChange_FieldChangePermissionsOnArtifactXMLUpdater(),
            new Tracker_XML_Updater_FieldChange_FieldChangeListXMLUpdater(),
            new Tracker_XML_Updater_FieldChange_FieldChangeOpenListXMLUpdater(),
            new FieldChangeComputedXMLUpdater(),
            new Tracker_XML_Updater_FieldChange_FieldChangeUnknownXMLUpdater()
        );

        return new Tracker_XML_Updater_ChangesetXMLUpdater(
            $visitor,
            $this->getFormElementFactory()
        );

    }

    private function getFileXMLUpdater() {
        return new Tracker_XML_Updater_TemporaryFileXMLUpdater(
            new Tracker_XML_Updater_TemporaryFileCreator()
        );
    }

    public function hasFieldBindedToUserGroupsViewableByUser(PFUser $user) {
        $form_elements = $this->formElementFactory->getUsedFieldsBindedToUserGroups($this);

        foreach ($form_elements as $field) {
            if ($field->userCanRead($user)) {
                return true;
            }
        }
        return false;
    }
}
