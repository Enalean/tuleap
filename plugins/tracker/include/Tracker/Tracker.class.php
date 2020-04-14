<?php
/**
 * Copyright (c) Enalean, 2012-Present. All Rights Reserved.
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

use Tracker\Artifact\XMLArtifactSourcePlatformExtractor;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumb;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkCollection;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbLinkWithIcon;
use Tuleap\Layout\BreadCrumbDropdown\SubItemsSection;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Project\XML\Import\ExternalFieldsExtractor;
use Tuleap\Tracker\Admin\ArtifactLinksUsageDao;
use Tuleap\Tracker\Admin\ArtifactLinksUsageUpdater;
use Tuleap\Tracker\Admin\HeaderPresenter;
use Tuleap\Tracker\Admin\NewLayoutDao;
use Tuleap\Tracker\Admin\TrackerGeneralSettingsChecker;
use Tuleap\Tracker\Artifact\ArtifactsDeletion\ArtifactDeletorBuilder;
use Tuleap\Tracker\Artifact\Changeset\FieldsToBeSavedInSpecificOrderRetriever;
use Tuleap\Tracker\Artifact\ExistingArtifactSourceIdFromTrackerExtractor;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfig;
use Tuleap\Tracker\Artifact\MailGateway\MailGatewayConfigDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\RecentlyVisitedDao;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRetriever;
use Tuleap\Tracker\DAO\TrackerArtifactSourceIdDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationCollectionBuilder;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SourceOfAssociationDetector;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\SubmittedValueConvertor;
use Tuleap\Tracker\FormElement\Field\Date\CSVFormatter;
use Tuleap\Tracker\FormElement\View\Admin\DisplayAdminFormElementsWarningsEvent;
use Tuleap\Tracker\Hierarchy\HierarchyController;
use Tuleap\Tracker\Hierarchy\HierarchyDAO;
use Tuleap\Tracker\Masschange\TrackerMasschangeGetExternalActionsEvent;
use Tuleap\Tracker\Notifications\CollectionOfUgroupToBeNotifiedPresenterBuilder;
use Tuleap\Tracker\Notifications\CollectionOfUserInvolvedInNotificationPresenterBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationsAddressesBuilder;
use Tuleap\Tracker\Notifications\GlobalNotificationsEmailRetriever;
use Tuleap\Tracker\Notifications\GlobalNotificationSubscribersFilter;
use Tuleap\Tracker\Notifications\InvolvedNotificationDao;
use Tuleap\Tracker\Notifications\NotificationLevelExtractor;
use Tuleap\Tracker\Notifications\NotificationListBuilder;
use Tuleap\Tracker\Notifications\NotificationsForceUsageUpdater;
use Tuleap\Tracker\Notifications\RecipientsManager;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsDAO;
use Tuleap\Tracker\Notifications\Settings\UserNotificationSettingsRetriever;
use Tuleap\Tracker\Notifications\UgroupsToNotifyDao;
use Tuleap\Tracker\Notifications\UnsubscribersNotificationDAO;
use Tuleap\Tracker\Notifications\UserNotificationOnlyStatusChangeDAO;
use Tuleap\Tracker\Notifications\UsersToNotifyDao;
use Tuleap\Tracker\TrackerColor;
use Tuleap\Tracker\TrackerCrumbInContext;
use Tuleap\Tracker\TrackerIsInvalidException;
use Tuleap\Tracker\Webhook\Actions\AdminWebhooks;
use Tuleap\Tracker\Webhook\WebhookDao;
use Tuleap\Tracker\Webhook\WebhookFactory;
use Tuleap\Tracker\Webhook\WebhookLogsRetriever;
use Tuleap\Tracker\Webhook\WebhookXMLExporter;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldDetector;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsDao;
use Tuleap\Tracker\Workflow\PostAction\FrozenFields\FrozenFieldsRetriever;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowDao;
use Tuleap\Tracker\Workflow\SimpleMode\SimpleWorkflowXMLExporter;
use Tuleap\Tracker\Workflow\SimpleMode\State\StateFactory;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionExtractor;
use Tuleap\Tracker\Workflow\SimpleMode\State\TransitionRetriever;
use Tuleap\Tracker\Workflow\WorkflowUpdateChecker;
use Tuleap\Tracker\XML\Updater\FieldChange\FieldChangeComputedXMLUpdater;

class Tracker implements Tracker_Dispatchable_Interface
{
    public const PERMISSION_ADMIN               = 'PLUGIN_TRACKER_ADMIN';
    public const PERMISSION_FULL                = 'PLUGIN_TRACKER_ACCESS_FULL';
    public const PERMISSION_ASSIGNEE            = 'PLUGIN_TRACKER_ACCESS_ASSIGNEE';
    public const PERMISSION_SUBMITTER           = 'PLUGIN_TRACKER_ACCESS_SUBMITTER';
    public const PERMISSION_NONE                = 'PLUGIN_TRACKER_NONE';
    public const PERMISSION_SUBMITTER_ONLY      = 'PLUGIN_TRACKER_ACCESS_SUBMITTER_ONLY';

    public const NOTIFICATIONS_LEVEL_DEFAULT       = 0;
    public const NOTIFICATIONS_LEVEL_DISABLED      = 1;
    public const NOTIFICATIONS_LEVEL_STATUS_CHANGE = 2;

    public const NOTIFICATIONS_LEVEL_DEFAULT_LABEL       = 'notifications_level_default';
    public const NOTIFICATIONS_LEVEL_DISABLED_LABEL      = 'notifications_level_disabled';
    public const NOTIFICATIONS_LEVEL_STATUS_CHANGE_LABEL = 'notifications_level_status_change';

    public const REMAINING_EFFORT_FIELD_NAME = "remaining_effort";
    public const ASSIGNED_TO_FIELD_NAME      = "assigned_to";
    public const IMPEDIMENT_FIELD_NAME       = "impediment";
    public const TYPE_FIELD_NAME             = "type";
    public const NO_PARENT                   = null;

    // The limit to 25 char is due to cross references
    // extraction fails if length is more than 25
    public const MAX_TRACKER_SHORTNAME_LENGTH = 25;

    public const XML_ID_PREFIX = 'T';

    public const MAXIMUM_RECENT_ARTIFACTS_TO_DISPLAY = 6;

    public $id;
    public $group_id;
    public $name;
    public $description;
    /**
     * @var TrackerColor
     */
    private $color;
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
    public $webhooks = [];

    private $is_project_allowed_to_use_nature;

    /**
     * @var TemplateRenderer
     */
    private $renderer;

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
        TrackerColor $color,
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
        $this->color                        = $color;
    }

    public function __toString()
    {
        return "Tracker #" . $this->id;
    }

    /**
     * @return string the url of the form to submit a new artifact
     */
    public function getSubmitUrl()
    {
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
     * getGroupId - get this Tracker Group ID.
     *
     * @return int The group_id
     */
    public function getGroupId()
    {
        return $this->group_id;
    }

    /**
     * Get the project of this tracker.
     *
     * @return Project
     */
    public function getProject()
    {
        if (!$this->project) {
            $this->project = ProjectManager::instance()->getProject($this->group_id);
        }
        return $this->project;
    }

    public function setProject(Project $project)
    {
        $this->project  = $project;
        $this->group_id = $project->getID();
    }

    public function getId(): int
    {
        return (int) $this->id;
    }

    /**
     * set this Tracker Id.
     *
     * @param int $id the id of the tracker
     *
     * @return int The id
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * getName - get this Tracker name.
     *
     * @return string the tracker name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * getDescription - get this Tracker description.
     *
     * @return string the tracker description
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * getItemName - get this Tracker item name (short name).
     *
     * @return string the tracker item name (shortname)
     */
    public function getItemName()
    {
        return $this->item_name;
    }

    /**
     * Returns the brwose instructions
     *
     * @return string the browse instructions of the tracker
     */
    public function getBrowseInstructions()
    {
        return $this->browse_instructions;
    }

    /**
     * Returns true is this tracker must be instantiated for new project
     *
     * @return bool true is this tracker must be instantiated for new project
     */
    public function mustBeInstantiatedForNewProjects()
    {
        return $this->instantiate_for_new_projects == 1;
    }

    public function arePriorityChangesShown()
    {
        return $this->log_priority_changes == 1;
    }

    /**
     * Returns true is notifications are stopped for this tracker
     *
     * @return bool true is notifications are stopped for this tracker, false otherwise
     */
    public function isNotificationStopped()
    {
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
     * @return Tracker_FormElement[] array of formElements used by this tracker
     */
    public function getFormElements()
    {
        return Tracker_FormElementFactory::instance()->getUsedFormElementForTracker($this);
    }

    /**
     * @return Tracker_FormElement_Field[]
     */
    public function getFormElementFields()
    {
        return Tracker_FormElementFactory::instance()->getUsedFields($this);
    }

    /**
     * @param string $name
     * @param mixed  $type A field type name, or an array of field type names, e.g. 'float', or array('float', 'int').
     *
     * @return bool true if the tracker contains an element of the given name and type
     */
    public function hasFormElementWithNameAndType($name, $type)
    {
        $form_element_factory = Tracker_FormElementFactory::instance();
        $element              = $form_element_factory->getUsedFieldByName($this->getId(), $name);

        return $element !== null && in_array($form_element_factory->getType($element), (array) $type);
    }

    /**
     * Should probably be mobified for better efficiency
     *
     * @return array of all the formElements
     */
    public function getAllFormElements()
    {
        return array_merge(
            Tracker_FormElementFactory::instance()->getUsedFormElementForTracker($this),
            Tracker_FormElementFactory::instance()->getUnusedFormElementForTracker($this)
        );
    }

    /**
     * fetch FormElements
     * @param Tracker_Artifact $artifact
     * @param array $submitted_values the values already submitted
     *
     * @return string
     */
    public function fetchFormElements($artifact, array $submitted_values)
    {
        $html = '';
        foreach ($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchArtifact($artifact, $submitted_values, []);
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
    public function fetchFormElementsForCopy($artifact, array $submitted_values)
    {
        $html = '';
        foreach ($this->getFormElements() as $formElement) {
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
    public function fetchFormElementsNoColumns($artifact, array $submitted_values)
    {
        $html = '';
        foreach ($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchArtifactForOverlay($artifact, $submitted_values);
        }
        return $html;
    }

    /**
     * Fetch Tracker submit form in HTML without the container and column rendering
     *
     * @return String
     */
    public function fetchSubmitNoColumns(?Tracker_Artifact $artifact_to_link, array $submitted_values)
    {
        $html = '';

        if ($artifact_to_link) {
            $html .= '<input type="hidden" name="link-artifact-id" value="' . $artifact_to_link->getId() . '" />';
        }

        foreach ($this->getFormElements() as $form_element) {
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
    public function fetchFormElementsReadOnly($artifact, array $submitted_values)
    {
        $html = '';
        foreach ($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchArtifactReadOnly($artifact, $submitted_values);
        }
        return $html;
    }

    /**
     * fetch FormElements
     * @return string
     */
    public function fetchAdminFormElements()
    {
        $html = '';
        $html .= '<div id="tracker-admin-fields" class="tracker-admin-group">';
        foreach ($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchAdmin($this);
        }
        $html .= '</div>';
        return $html;
    }

    public function fetchFormElementsMasschange()
    {
        $html = '';
        $html .= '<table width="20%"><tr><td>';
        foreach ($this->getFormElements() as $formElement) {
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
    public function getTrackerFactory()
    {
        return TrackerFactory::instance();
    }

    /**
     * Return self
     *
     * @see plugins/tracker/include/Tracker/Tracker_Dispatchable_Interface::getTracker()
     *
     * @return Tracker
     */
    public function getTracker()
    {
        return $this;
    }

    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        //TODO: log the admin actions (add a formElement, ...) ?
        $hp = Codendi_HTMLPurifier::instance();
        $func = (string) $request->get('func');
        switch ($func) {
            case 'new-artifact':
                if ($this->userCanSubmitArtifact($current_user)) {
                    $this->displaySubmit($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;

            case 'get-create-in-place':
                if ($this->userCanSubmitArtifact($current_user)) {
                    $artifact_link_id       = $request->get('artifact-link-id');
                    $render_with_javascript = ($request->get('fetch-js') == 'false') ? false : true;

                    $renderer = new Tracker_Artifact_Renderer_CreateInPlaceRenderer(
                        $this,
                        TemplateRendererFactory::build()->getRenderer(dirname(TRACKER_BASE_DIR) . '/templates')
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
                    $service_usage_for_tracker = $this->getInformationsFromOtherServicesAboutUsage();
                    if ($service_usage_for_tracker['can_be_deleted'] === false) {
                        $GLOBALS['Response']->addFeedback(
                            Feedback::ERROR,
                            $GLOBALS['Language']->getText(
                                'plugin_tracker',
                                'cannot_delete_tracker',
                                [$service_usage_for_tracker['message']]
                            )
                        );
                    } elseif ($this->getTrackerFactory()->markAsDeleted($this->id)) {
                        $event_manager = EventManager::instance();
                        $event_manager->processEvent(TRACKER_EVENT_DELETE_TRACKER, array(
                                          'tracker_id' => $this->getId()));
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
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?group_id=' . $this->group_id);
                break;
            case 'admin-editoptions':
                if ($this->userIsAdmin($current_user)) {
                    if ($request->get('update')) {
                        $this->editOptions($request);
                    }
                    $this->displayAdminOptions($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-perms':
            case 'admin-perms-tracker':
                if ($this->userIsAdmin($current_user)) {
                    $this->getPermissionController()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin':
            case 'admin-formElements':
                if ($this->userIsAdmin($current_user)) {
                    if (is_array($request->get('add-formElement'))) {
                        $formElement_id = key($request->get('add-formElement'));
                        if (Tracker_FormElementFactory::instance()->addFormElement($formElement_id)) {
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_admin_index', 'field_added'));
                            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . (int) $this->getId() . '&func=admin-formElements');
                        }
                    } elseif (is_array($request->get('create-formElement'))) {
                        $type = key($request->get('create-formElement'));
                        if ($request->get('docreate-formElement') && is_array($request->get('formElement_data'))) {
                            try {
                                $this->createFormElement($type, $request->get('formElement_data'), $current_user);
                            } catch (Exception $e) {
                                $GLOBALS['Response']->addFeedback('error', $e->getMessage());
                            }
                            $GLOBALS['Response']->redirect(
                                TRACKER_BASE_URL . '/?' . http_build_query(
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
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-formElement-update':
            case 'admin-formElement-remove':
            case 'admin-formElement-delete':
                if ($this->userIsAdmin($current_user)) {
                    if ($formElement = Tracker_FormElementFactory::instance()->getFormElementById((int) $request->get('formElement'))) {
                        $formElement->process($layout, $request, $current_user);
                    } else {
                        $this->displayAdminFormElements($layout, $request, $current_user);
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-semantic':
                if ($this->userIsAdmin($current_user)) {
                    $this->getTrackerSemanticManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-canned':
            // TODO : project members can access this part ?
                if ($this->userIsAdmin($current_user)) {
                    $this->getCannedResponseManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
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
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case AdminWebhooks::FUNC_ADMIN_WEBHOOKS:
                if ($this->userIsAdmin($current_user)) {
                    $admin_webhook = new AdminWebhooks($this, $this->getWebhookFactory(), $this->getWebhookLogsRetriever());
                    $admin_webhook->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
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
                            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'import_failed'));
                            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                        }
                    }
                    $this->displayAdminCSVImport($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-export':
                if ($this->userIsAdmin($current_user)) {
                    // TODO: change directory
                    $xml_element = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><tracker />');
                    $this->sendXML($this->exportToXML($xml_element));
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-dependencies':
                if ($this->userIsAdmin($current_user)) {
                    $this->getGlobalRulesManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'submit-artifact':
                header('X-Frame-Options: SAMEORIGIN');
                $action = new Tracker_Action_CreateArtifact(
                    $this,
                    $this->getTrackerArtifactFactory(),
                    $this->getFormElementFactory()
                );
                $action->process($layout, $request, $current_user);
                break;
            case 'submit-copy-artifact':
                $logger                    = new Tracker_XML_Importer_CopyArtifactInformationsAggregator(BackendLogger::getDefaultLogger());
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
                    $this->displayAdminHeader($layout, 'hierarchy', $GLOBALS['Language']->getText('plugin_tracker_admin', 'hierarchy'));
                    $this->getHierarchyController($request)->edit();
                    $this->displayAdminFooter($layout);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-hierarchy-update':
                if ($this->userIsAdmin($current_user)) {
                    $this->getHierarchyController($request)->update();
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-clean':
                if ($this->userIsAdmin($current_user)) {
                    $this->displayAdminClean($layout);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-delete-artifact-confirm':
                if ($this->userIsAdmin($current_user)) {
                    $token = new CSRFSynchronizerToken(TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-delete-artifact-confirm');
                    $token->check();
                    $artifact_id = $request->getValidated('id', 'uint', 0);
                    $artifact    = $this->getTrackerArtifactFactory()->getArtifactById($artifact_id);
                    if ($artifact && $artifact->getTrackerId() == $this->id) {
                        $this->displayAdminConfirmDelete($layout, $artifact);
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_error_noart', array($request->get('id'))));
                        $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId() . '&func=admin-clean');
                    }
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'admin-delete-artifact':
                if ($this->userIsAdmin($current_user)) {
                    $token = new CSRFSynchronizerToken(TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-delete-artifact');
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
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }
                break;
            case 'create_new_public_report':
                if (! $this->userIsAdmin($current_user)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }

                $name      = $request->get('new_report_name');
                $validator = new Valid_String('new_report_name');
                $validator->required();

                if (! $request->valid($validator)) {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker', 'create_new_report_invalid'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                }

                $hp = Codendi_HTMLPurifier::instance();
                $hp->purify($name);

                $report = new Tracker_Report(0, $name, 'Public rapport', 0, 0, null, false, $this->getId(), 1, false, '', null, 0);
                $report->criteria = array();

                $this->getReportFactory()->saveObject($this->id, $report);
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . $this->getId());
                break;

            default:
                if ($this->userCanView($current_user)) {
                    $this->displayAReport($layout, $request, $current_user);
                }
                break;
        }
        return;
    }

    /**
     * @return bool
     */
    public function isProjectAllowedToUseNature()
    {
        if ($this->is_project_allowed_to_use_nature === null) {
            $artifact_links_usage_updater = new ArtifactLinksUsageUpdater(new ArtifactLinksUsageDao());

            $this->is_project_allowed_to_use_nature = $artifact_links_usage_updater->isProjectAllowedToUseArtifactLinkTypes($this->getProject());
        }
        return $this->is_project_allowed_to_use_nature;
    }

    private function getHierarchyController(Codendi_Request $request): HierarchyController
    {
        $dao                  = new HierarchyDAO();
        $tracker_factory      = $this->getTrackerFactory();
        $factory              = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $hierarchical_tracker = $factory->getWithChildren($this);
        $controller           = new HierarchyController(
            $request,
            $hierarchical_tracker,
            $factory,
            $dao,
            new ArtifactLinksUsageDao(),
            new Tracker_Workflow_Trigger_RulesDao()
        );

        return $controller;
    }

    public function createFormElement($type, $formElement_data, $user)
    {
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
    public function displayAReport(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        $report = null;

        //Does the user wants to change its report?
        if ($request->get('select_report')) {
            //Is the report id valid
            if ($report = $this->getReportFactory()->getReportById($request->get('select_report'), $current_user->getid())) {
                $current_user->setPreference('tracker_' . $this->id . '_last_report', $report->id);
            }
        }

        //If no valid report found. Search the last viewed report for the user
        if (! $report) {
            if ($report_id = $current_user->getPreference('tracker_' . $this->id . '_last_report')) {
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

        $link_artifact_id = (int) $request->get('link-artifact-id');
        if ($link_artifact_id && !$request->get('report-only')) {
            $linked_artifact = Tracker_ArtifactFactory::instance()->getArtifactById($link_artifact_id);

            if (!$linked_artifact) {
                $err = "Linked artifact not found or doesn't exist";
                if (!$request->isAjax()) {
                    $GLOBALS['Response']->addFeedback('error', $err);
                    $GLOBALS['Response']->redirect('/');
                }
                die($err);
            }
            if (!$request->isAjax()) {
                //screwed up
                $GLOBALS['Response']->addFeedback('error', 'Something is wrong with your request');
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?aid=' . $linked_artifact->getId());
            }

            echo $linked_artifact->fetchTitleWithoutUnsubscribeButton(
                $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'title_prefix')
            );

            echo '<input type="hidden" id="link-artifact-id" value="' . (int) $link_artifact_id . '" />';

            echo '<table id="tracker-link-artifact-different-ways" cellpadding="0" cellspacing="0" border="0"><tbody><tr>';

            //the fast ways
            echo '<td id="tracker-link-artifact-fast-ways">';

            //Manual
            echo '<div id="tracker-link-artifact-manual-way">';
            echo '<div class="boxtitle">';
            echo $GLOBALS['HTML']->getImage('ic/lightning-white.png', array('style' => 'vertical-align:middle')) . '&nbsp;';
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
            echo $GLOBALS['HTML']->getImage('ic/star-white.png', array('style' => 'vertical-align:middle')) . '&nbsp;';
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
                                     value="' . (int) $artifact->getId() . '" /> ';
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
                echo $GLOBALS['HTML']->getImage('ic/magnifier-white.png', array('style' => 'vertical-align:middle')) . '&nbsp;';
                echo $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'search_panel_title');
                echo '</div>';
                echo '<div id="tracker-link-artifact-slow-way-content">';
            }
        }

        if ($report) {
            $report->process($layout, $request, $current_user);
        } elseif (!$link_artifact_id) {
            $this->displayHeader($layout, $this->name, array(), $this->getDefaultToolbar());
            echo $GLOBALS['Language']->getText('plugin_tracker', 'no_reports_available');

            if ($this->userIsAdmin($current_user)) {
                $action = '?tracker=' . (int) $this->getID() . '&func=create_new_public_report';

                echo '<form class="form-inline" action="' . $action . '" method="POST">'
                    . '<fieldset>'
                        . '<legend>' . $GLOBALS['Language']->getText('plugin_tracker', 'create_new_report') . '</legend>'
                        . '<input required type="text" name="new_report_name" placeholder="' . $GLOBALS['Language']->getText('plugin_tracker', 'create_new_report_name') . '" />'
                        . '<button type="submit" class="btn">' . $GLOBALS['Language']->getText('plugin_tracker', 'create_new_report_submit') . '</button>'
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
            echo '<a href="#cancel" onclick="myLightWindow.deactivate(); return false;">&laquo;&nbsp;' . $GLOBALS['Language']->getText('global', 'btn_cancel') . '</a>';
            echo ' ';
            echo '<button name="link-artifact-submit">' . $GLOBALS['Language']->getText('global', 'btn_submit') . '</button>';
            echo '</div>';
        }
    }

    /**
     * Display the submit form
     */
    public function displaySubmit(Tracker_IFetchTrackerSwitcher $layout, $request, $current_user, $link = null)
    {
        if ($link) {
            $source_artifact = $this->getTrackerArtifactFactory()->getArtifactByid($link);
            $submit_renderer = new Tracker_Artifact_SubmitOverlayRenderer(
                $this,
                $source_artifact,
                EventManager::instance(),
                $layout
            );
        } else {
            $submit_renderer = new Tracker_Artifact_SubmitRenderer(
                $this,
                EventManager::instance(),
                $layout
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
    public function displaySearch(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
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
                        'url'   => TRACKER_BASE_URL . '/?tracker=' . $this->getId(),
                ),
        );
        $this->displayHeader($layout, $this->name, $breadcrumbs, $this->getDefaultToolbar());
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

        if ($dar->rowCount() < 1 || $rows_returned < 1) {
            $no_rows = true;
            $html .= '<h2>' . $GLOBALS['Language']->getText('search_index', 'no_match_found', $hp->purify($words, CODENDI_PURIFIER_CONVERT_HTML)) . '</h2>';
        } else {
            $html .= '<h3>' . $GLOBALS['Language']->getText('search_index', 'search_res', array($hp->purify($words, CODENDI_PURIFIER_CONVERT_HTML), $rows_returned)) . '</h3>';

            $title_arr = array();

            $art_field_fact = Tracker_FormElementFactory::instance();
            $artifact_factory = Tracker_ArtifactFactory::instance();
            $user_helper = UserHelper::instance();

            $summary_field = $this->getTitleField();
            if ($summary_field && $summary_field->userCanRead()) {
                $title_arr[] = $GLOBALS['Language']->getText('plugin_tracker_search_index', 'artifact_title');
            }
            $submitted_field = $art_field_fact->getFormElementByName($this->getId(), 'submitted_by');
            if ($submitted_field && $submitted_field->userCanRead()) {
                $title_arr[] = $GLOBALS['Language']->getText('search_index', 'submitted_by');
            }
            $date_field = $art_field_fact->getFormElementByName($this->getId(), 'open_date');
            if ($date_field && $date_field->userCanRead()) {
                $title_arr[] = $GLOBALS['Language']->getText('search_index', 'date');
            }
            $status_field = $this->getStatusField();
            if ($status_field && $status_field->userCanRead()) {
                $title_arr[] = $GLOBALS['Language']->getText('global', 'status');
            }

            $html .= html_build_list_table_top($title_arr);
            $nb_artifacts = 0;
            while ($row = $dar->getRow()) {
                $nb_artifacts++;
                $artifact_id = $row['artifact_id'];
                $artifact    = $artifact_factory->getArtifactById($artifact_id);
                if ($artifact->userCanView()) {
                    $html .= '<tr class="' . html_get_alt_row_color($nb_artifacts) . '">';
                    if ($summary_field->userCanRead()) {
                        $html .= '<td><a href="' . TRACKER_BASE_URL . '/?aid=' . $artifact_id . '"><img src="' . util_get_image_theme('msg.png') . '" border="0" height="12" width="10"> '
                        . $artifact->getTitle() . '</a></td>';
                    }
                    if ($submitted_field->userCanRead()) {
                        $html .= '<td>' . $hp->purify($user_helper->getDisplayNameFromUserId($artifact->getSubmittedBy())) . '</td>';
                    }
                    if ($date_field->userCanRead()) {
                        $html .=  '<td>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $artifact->getSubmittedOn()) . '</td>';
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
        if (!$no_rows && ( ($rows_returned > $nb_artifacts) || ($offset != 0) )) {
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
                $html .= '<a href="/search/?' . http_build_query($url_params);
                $html .= '">' . "<b><img src=\"" . util_get_image_theme('t2.png') . "\" height=15 width=15 border=0 align=middle> " . $GLOBALS['Language']->getText('search_index', 'prev_res') . " </a></b></span>";
            } else {
                $html .= '&nbsp;';
            }
            $html .= '</td><td align="right">';
            if ($rows_returned > $nb_artifacts && $rows_returned > $offset + $limit) {
                $url_params['offset'] = ($offset + $limit);
                $html .= '<span class="normal"><b>';
                $html .= '<a href="/search/?' . http_build_query($url_params);
                $html .= '"><b>' . $GLOBALS['Language']->getText('search_index', 'next_res') . ' <img src="' . util_get_image_theme('t.png') . '" height="15" width="15" border="0" align="middle"></a></b></span>';
            } else {
                $html .= '&nbsp;';
            }
            $html .= '</td></tr>';
            $html .= '</table>';
        }

        echo $html;
        $this->displayFooter($layout);
    }

    public function displayHeader(
        Tracker_IDisplayTrackerLayout $layout,
        $title,
        $breadcrumbs,
        array $toolbar,
        array $params = array()
    ) {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            $hp = Codendi_HTMLPurifier::instance();

            $breadcrumbs = array_merge(
                array(
                    $this->getCrumb()
                ),
                $breadcrumbs
            );

            $title = ($title ? $title . ' - ' : '') . $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML);
            $layout->displayHeader($project, $title, $breadcrumbs, $toolbar, $params);
        }
    }

    private function getCrumb(): BreadCrumb
    {
        $user          = UserManager::instance()->getCurrentUser();
        $tracker_crumb = EventManager::instance()->dispatch(new TrackerCrumbInContext($this, $user));
        assert($tracker_crumb instanceof TrackerCrumbInContext);

        $crumb = $tracker_crumb->getCrumb(TrackerCrumbInContext::TRACKER_CRUMB_IDENTIFIER);
        $sub_items = $crumb->getSubItems();
        $existing_sections = $sub_items->getSections();


        $links_collection = new BreadCrumbLinkCollection([
            new BreadCrumbLinkWithIcon(
                sprintf(dgettext('tuleap-tracker', 'New %s'), $this->getName()),
                $this->getSubmitUrl(),
                'fa-plus'
            )
        ]);
        if ($this->userIsAdmin($user)) {
            $links_collection->add(
                new BreadCrumbLinkWithIcon(
                    dgettext('tuleap-tracker', 'Administration'),
                    $this->getAdministrationUrl(),
                    'fa-cog'
                )
            );
        }
        if ($user->isLoggedIn()) {
            $links_collection->add(
                new BreadCrumbLinkWithIcon(
                    dgettext('tuleap-tracker', 'My notifications'),
                    TRACKER_BASE_URL . '/notifications/my/' . urlencode($this->id) . '/',
                    'fa-bell'
                )
            );
        }
        $sub_items->setSections(
            array_merge(
                [new SubItemsSection('', $links_collection)],
                $existing_sections
            )
        );
        return $crumb;
    }

    public function getDefaultToolbar()
    {
        $toolbar = array();

        $html_purifier = Codendi_HTMLPurifier::instance();

        $toolbar[] = [
                'title'      => $html_purifier->purify(
                    sprintf(dgettext('tuleap-tracker', 'Create a %s'), $this->getItemName())
                ),
                'url'        => $this->getSubmitUrl(),
                'class'      => 'tracker-submit-new',
                'submit-new' => 1,
                'data-test' => "new-artifact"
        ];

        $artifact_by_email_status = $this->getArtifactByMailStatus();
        if ($artifact_by_email_status->canCreateArtifact($this)) {
            $email_domain = ForgeConfig::get('sys_default_mail_domain');

            if (! $email_domain) {
                $email_domain = ForgeConfig::get('sys_default_domain');
            }

            $email = trackerPlugin::EMAILGATEWAY_INSECURE_ARTIFACT_CREATION . '+' . $this->id . '@' . $email_domain;
            $email = $html_purifier->purify($email);
            $toolbar[] = array(
                    'title'      => '<span class="email-tracker" data-email="' . $email . '"><i class="fa fa-envelope"></i></span>',
                    'url'        => 'javascript:;',
                    'submit-new' => 1
            );
        }

        if (UserManager::instance()->getCurrentUser()->isLoggedIn()) {
            $toolbar[] = array(
                    'title' => $GLOBALS['Language']->getText('plugin_tracker', 'notifications'),
                    'url'   => TRACKER_BASE_URL . '/notifications/my/' . urlencode($this->id) . '/',
            );
        }
        if ($this->userIsAdmin()) {
            $toolbar[] = array(
                    'title' => $GLOBALS['Language']->getText('plugin_tracker', 'administration'),
                    'url'   => $this->getAdministrationUrl(),
                    'data-test' => "tracker-administration"
            );
        }
        $toolbar[] = array(
                'title' => $GLOBALS['Language']->getText('plugin_tracker', 'help'),
                'url'   => 'javascript:help_window(\'/doc/' . UserManager::instance()->getCurrentUser()->getShortLocale() . '/user-guide/tracker.html\');',
        );

        return $toolbar;
    }

    public function displayFooter(Tracker_IDisplayTrackerLayout $layout)
    {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            $layout->displayFooter($project);
        }
    }

    public function displayAdminFooter(Tracker_IDisplayTrackerLayout $layout)
    {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            echo '</div>';
            $layout->displayFooter($project);
        }
    }

    public function displayAdminHeader(
        Tracker_IDisplayTrackerLayout $layout,
        string $current_item,
        $title,
        array $params = array()
    ) {
        $this->buildAndDisplayAdministrationHeader($layout, $title, [], $params);

        $items = [];
        $event_parameters = array("items" => &$items, "tracker_id" => $this->id);
        EventManager::instance()->processEvent(TRACKER_EVENT_FETCH_ADMIN_BUTTONS, $event_parameters);

        $presenter = new HeaderPresenter($this, $current_item, array_values($items));
        $this->renderer->renderToPage('admin-header', $presenter);
    }

    public function displayAdminHeaderBurningParrot(
        Tracker_IDisplayTrackerLayout $layout,
        string $current_item,
        $title,
        $breadcrumbs,
        array $params = array()
    ) {
        $this->buildAndDisplayAdministrationHeader($layout, $title, $breadcrumbs, $params);

        $items = [];
        $event_parameters = array("items" => &$items, "tracker_id" => $this->id);
        EventManager::instance()->processEvent(TRACKER_EVENT_FETCH_ADMIN_BUTTONS, $event_parameters);

        $presenter = new HeaderPresenter($this, $current_item, array_values($items));
        $this->renderer->renderToPage('admin-header-bp', $presenter);
    }

    private function buildAndDisplayAdministrationHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs, array $params): void
    {
        $title = ($title ? $title . ' - ' : '') . $GLOBALS['Language']->getText('plugin_tracker_include_type', 'administration');
        if ($this->userIsAdmin()) {
            $breadcrumbs = array_merge(
                array(
                    array(
                        'title' => $GLOBALS['Language']->getText('plugin_tracker_include_type', 'administration'),
                        'url' => $this->getAdministrationUrl(),
                    ),
                ),
                $breadcrumbs
            );
        }

        $params['body_class'][] = 'tracker-administration';
        $this->displayHeader($layout, $title, $breadcrumbs, [], $params);
    }

    public function displayAdminItemHeader(Tracker_IDisplayTrackerLayout $layout, $item, string $title, array $params = array())
    {
        $this->displayAdminHeader($layout, $item, $title, $params);
    }

    public function displayAdminItemHeaderBurningParrot(
        Tracker_IDisplayTrackerLayout $layout,
        string $item,
        string $header_title,
        array $params = array()
    ) {
        $this->displayAdminHeaderBurningParrot($layout, $item, $header_title, [], $params);
    }

    /**
     * @return TrackerColor
     */
    public function getColor()
    {
        return $this->color;
    }

    public function isEmailgatewayEnabled()
    {
        return $this->enable_emailgateway;
    }

    protected function displayAdminOptions(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        $this->displayWarningGeneralsettings();
        $this->displayAdminItemHeader($layout, 'editoptions', dgettext('tuleap-tracker', 'General settings'));
        $cannot_configure_instantiate_for_new_projects = false;
        $params = array('cannot_configure_instantiate_for_new_projects' => &$cannot_configure_instantiate_for_new_projects, 'tracker' => $this);
        EventManager::instance()->processEvent(TRACKER_EVENT_GENERAL_SETTINGS, $params);
        $this->renderer->renderToPage(
            'tracker-general-settings',
            new Tracker_GeneralSettings_Presenter(
                $this,
                TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&func=admin-editoptions',
                new Tracker_ColorPresenterCollection($this),
                $this->getMailGatewayConfig(),
                $this->getArtifactByMailStatus(),
                $cannot_configure_instantiate_for_new_projects
            )
        );

        $this->displayAdminFooter($layout);
    }

    public function displayAdminPermsHeader(Tracker_IDisplayTrackerLayout $layout, $title)
    {
        $this->displayAdminHeader($layout, 'editperms', $title);
    }

    public function displayAdminFormElementsHeader(Tracker_IDisplayTrackerLayout $layout, $title)
    {
        $assets = new IncludeAssets(
            __DIR__ . '/../../../../src/www/assets/trackers',
            '/assets/trackers'
        );

        $GLOBALS['HTML']->addStylesheet($assets->getFileURL('colorpicker.css'));
        $GLOBALS['HTML']->includeFooterJavascriptFile($assets->getFileURL('TrackerAdminFields.js'));

        $this->displayAdminHeader($layout, 'editformElements', $title);
    }

    public function displayAdminFormElements(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        $this->displayAdminFormElementsWarnings();
        $title = $GLOBALS['Language']->getText('plugin_tracker_include_type', 'mng_field_usage');
        $this->displayAdminFormElementsHeader($layout, $title);

        $this->displayAdminFormElementsNewLayoutModal($current_user);

        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
        echo '<form name="form1" method="POST" action="' . TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-formElements">';

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
        $this->displayAdminFooter($layout);
    }

    private function displayAdminFormElementsNewLayoutModal(PFUser $current_user): void
    {
        $layout_dao = new NewLayoutDao();
        $user_id    = (int) $current_user->getId();

        $user_must_see_the_modal = $layout_dao->mustUserSeeTheModal($user_id);
        if ($user_must_see_the_modal === false) {
            return;
        }

        $layout_dao->removeUserFromList($user_id);

        $this->renderer->renderToPage(
            'admin-new-layout-modal',
            []
        );
    }

    private function fetchAdminPalette()
    {
        echo '<div class="tracker-admin-palette">';

        $this->formElementFactory->displayFactories($this);

        $w = new Widget_Static($GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'unused_elements'));
        $unused_elements_content = '';
        $unused_elements_content = $GLOBALS['Language']->getText('plugin_tracker_formelement_admin', 'unused_elements_desc');
        $unused_elements_content .= '<div class="tracker-admin-palette-content"><table>';
        foreach (Tracker_FormElementFactory::instance()->getUnusedFormElementForTracker($this) as $f) {
            $unused_elements_content .= $f->fetchAdminAdd();
        }
        $unused_elements_content .= '</table></div>';
        $w->setContent($unused_elements_content);
        $w->display();

        echo '</div>';
    }

    public function displayAdminCSVImportHeader(Tracker_IDisplayTrackerLayout $layout, $title)
    {
        $this->displayAdminHeader($layout, 'csvimport', $title);
    }

    public function displayAdminCSVImport(Tracker_IDisplayTrackerLayout $layout, $request, $current_user)
    {
        $title = $GLOBALS['Language']->getText('plugin_tracker_admin', 'csv_import');
        $this->displayAdminCSVImportHeader($layout, $title);

        echo '<h2 class="almost-tlp-title">' . $title . ' ' . help_button('tracker.html#tracker-artifact-import') . '</h2>';
        echo '<form name="form1" method="POST" enctype="multipart/form-data" action="' . TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-csvimport">';
        echo '<input type="file" name="csv_filename" size="50">';
        echo '<br>';
        echo '<span class="smaller"><em>';
        echo $GLOBALS['Language']->getText('plugin_tracker_import', 'file_upload_instructions', formatByteToMb($GLOBALS['sys_max_size_upload']));
        echo '</em></span>';
        echo '<br>';
        echo $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'send_notifications');
        echo '<input type="checkbox" name="notify" value="ok" />';
        echo '<br>';
        echo '<input type="hidden" name="action" value="import_preview">';
        echo '<input type="submit" value="' . $GLOBALS['Language']->getText('plugin_tracker_import', 'submit_info') . '">';
        echo '</form>';
        $this->displayAdminFooter($layout);
    }

    public function displayAdminClean(Tracker_IDisplayTrackerLayout $layout)
    {
        $token = new CSRFSynchronizerToken(TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-delete-artifact-confirm');
        $title = dgettext('tuleap-tracker', 'Delete artifacts');
        $this->displayAdminItemHeader($layout, 'clean', $title);
        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
        echo '<p>' . $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_info') . '</p>';
        echo '<form name="delete_artifact" method="post" action="' . TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-delete-artifact-confirm">';
        echo $token->fetchHTMLInput();
        echo '<label>' . $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_id') . ' <input type="text" name="id" value=""></label>';
        echo '<br>';
        echo '<input type="submit" value="' . $GLOBALS['Language']->getText('global', 'btn_submit') . '">';
        echo '</form>';
        $this->displayAdminFooter($layout);
    }

    public function displayAdminConfirmDelete(Tracker_IDisplayTrackerLayout $layout, Tracker_Artifact $artifact)
    {
        $token = new CSRFSynchronizerToken(TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-delete-artifact');
        $this->displayAdminItemHeader($layout, 'clean', dgettext('tuleap-tracker', 'Delete artifacts'));
        echo '<div class="tracker_confirm_delete">';
        echo $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_confirm_text', array($artifact->getXRefAndTitle()));
        echo '<div class="tracker_confirm_delete_preview">';
        echo $this->fetchFormElementsReadOnly($artifact, []);
        echo '</div>';
        echo '<form name="delete_artifact" method="post" action="' . TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-delete-artifact">';
        echo $token->fetchHTMLInput();
        echo '<div class="tracker_confirm_delete_buttons">';
        echo '<input type="submit" tabindex="2" name="confirm" value="' . $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_confirm') . '" />';
        echo '<input type="submit" tabindex="1" name="cancel" value="' . $GLOBALS['Language']->getText('plugin_tracker_admin', 'clean_cancel') . '" />';
        echo '</div>';
        echo '<input type="hidden" name="id" value="' . $artifact->getId() . '" />';
        echo '</form>';
        echo '</div>';
        $this->displayAdminFooter($layout);
    }

    public function displayMasschangeForm(Tracker_IDisplayTrackerLayout $layout, PFUser $user, $masschange_aids)
    {
        $breadcrumbs = array(
            array(
                'title' => $GLOBALS['Language']->getText('plugin_tracker_index', 'mass_change'),
                'url'   => '#' //TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=display-masschange-form',
            ),
        );
        $this->displayHeader($layout, $this->name, $breadcrumbs, $this->getDefaultToolbar());

        $event = new TrackerMasschangeGetExternalActionsEvent($this, $user);
        EventManager::instance()->processEvent($event);

        $this->renderer->renderToPage(
            'masschange',
            new Tracker_Masschange_Presenter(
                $masschange_aids,
                $this->fetchFormElementsMasschange(),
                $this->displayRulesAsJavascript(),
                $event->getExternalActions()
            )
        );

        $this->displayFooter($layout);
    }

    protected function editOptions(HTTPRequest $request): void
    {
        $previous_shortname = $this->getItemName();
        $previous_public_name      = $this->getName();

        $reference_manager                = ReferenceManager::instance();
        $tracker_general_settings_checker = new TrackerGeneralSettingsChecker(
            TrackerFactory::instance(),
            $reference_manager
        );

        $validated_tracker_color = $request->get('tracker_color');
        $validated_public_name = trim($request->getValidated('name', 'string', ''));
        $validated_short_name  = trim($request->getValidated('item_name', 'string', ''));

        try {
            $tracker_general_settings_checker->check(
                $this->group_id,
                $previous_shortname,
                $previous_public_name,
                $validated_public_name,
                $validated_tracker_color,
                $validated_short_name
            );
        } catch (TrackerIsInvalidException $exception) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                $exception->getTranslatedMessage()
            );

            return;
        }

        $cannot_configure_instantiate_for_new_projects = false;
        $params = [
            'cannot_configure_instantiate_for_new_projects' => &$cannot_configure_instantiate_for_new_projects,
            'tracker'                                       => $this
        ];
        EventManager::instance()->processEvent(TRACKER_EVENT_GENERAL_SETTINGS, $params);

        $this->name            = $validated_public_name;
        $request_tracker_color = $validated_tracker_color;
        $this->item_name                    = $validated_short_name;

        $this->description     = trim($request->getValidated('description', 'text', ''));
        $this->allow_copy                   = $request->getValidated('allow_copy') ? 1 : 0;
        $this->enable_emailgateway          = $request->getValidated('enable_emailgateway') ? 1 : 0;
        $this->submit_instructions          = $request->getValidated('submit_instructions', 'text', '');
        $this->browse_instructions          = $request->getValidated('browse_instructions', 'text', '');
        $this->instantiate_for_new_projects = $request->getValidated('instantiate_for_new_projects') || $cannot_configure_instantiate_for_new_projects ? 1 : 0;
        $this->log_priority_changes         = $request->getValidated('log_priority_changes') ? 1 : 0;

        try {
            $this->color = TrackerColor::fromName((string) $request_tracker_color);

            //Update reference and cross references
            //WARNING this replace existing reference(s) so that all old_item_name reference won't be extracted anymore
            $reference_manager->updateProjectReferenceShortName($this->group_id, $previous_shortname, $this->item_name);

            $artifact_link_value_dao = new Tracker_FormElement_Field_Value_ArtifactLinkDao();
            $artifact_link_value_dao->updateItemName($this->group_id, $previous_shortname, $this->item_name);

            $dao = new TrackerDao();
            if ($dao->save($this)) {
                $GLOBALS['Response']->addFeedback(Feedback::INFO, $GLOBALS['Language']->getText('plugin_tracker_admin', 'successfully_updated'));
            } else {
                $GLOBALS['Response']->addFeedback(Feedback::ERROR, $GLOBALS['Language']->getText('global', 'error'));
            }
        } catch (InvalidArgumentException $ex) {
            $GLOBALS['Response']->addFeedback(
                Feedback::ERROR,
                dgettext('tuleap-tracker', 'Invalid color tracker name')
            );
        }
    }

    /**
     * Test if the tracker is active
     * @return bool
     */
    public function isActive()
    {
        return !$this->isDeleted();
    }

    /**
     * Test if tracker is deleted
     *
     * @return bool
     */
    public function isDeleted()
    {
        return ($this->deletion_date != '');
    }

    /**
     * @return Tracker_SemanticManager
     */
    public function getTrackerSemanticManager()
    {
        return new Tracker_SemanticManager($this);
    }

    /**
     * @return Tracker_Tooltip
     */
    public function getTooltip()
    {
        return new Tracker_Tooltip($this);
    }

    /**
     * @return Tracker_NotificationsManager
     */
    public function getNotificationsManager()
    {
        $user_to_notify_dao             = new UsersToNotifyDao();
        $ugroup_to_notify_dao           = new UgroupsToNotifyDao();
        $unsubscribers_notification_dao = new UnsubscribersNotificationDAO();
        $user_manager                   = UserManager::instance();
        $notification_list_builder      = new NotificationListBuilder(
            new UGroupDao(),
            new CollectionOfUserInvolvedInNotificationPresenterBuilder(
                $user_to_notify_dao,
                $unsubscribers_notification_dao,
                $user_manager
            ),
            new CollectionOfUgroupToBeNotifiedPresenterBuilder($ugroup_to_notify_dao)
        );
        return new Tracker_NotificationsManager(
            $this,
            $notification_list_builder,
            $user_to_notify_dao,
            $ugroup_to_notify_dao,
            new UserNotificationSettingsDAO(),
            new GlobalNotificationsAddressesBuilder(),
            $user_manager,
            new UGroupManager(),
            new GlobalNotificationSubscribersFilter($unsubscribers_notification_dao),
            new NotificationLevelExtractor(),
            new \TrackerDao(),
            new \ProjectHistoryDao(),
            new NotificationsForceUsageUpdater(
                new RecipientsManager(
                    \Tracker_FormElementFactory::instance(),
                    $user_manager,
                    $unsubscribers_notification_dao,
                    new UserNotificationSettingsRetriever(
                        new Tracker_GlobalNotificationDao(),
                        new UnsubscribersNotificationDAO(),
                        new UserNotificationOnlyStatusChangeDAO(),
                        new InvolvedNotificationDao()
                    ),
                    new UserNotificationOnlyStatusChangeDAO()
                ),
                new UserNotificationSettingsDAO()
            )
        );
    }

    /**
     * @return Tracker_CannedResponseManager
     */
    public function getCannedResponseManager()
    {
        return new Tracker_CannedResponseManager($this);
    }

    public function getUGroupRetrieverWithLegacy()
    {
        return new UGroupRetrieverWithLegacy(new UGroupManager());
    }

    /**
     * @return Tracker_CannedResponseFactory
     */
    public function getCannedResponseFactory()
    {
        return Tracker_CannedResponseFactory::instance();
    }

    /**
     * @return WorkflowManager
     */
    public function getWorkflowManager()
    {
        return new WorkflowManager($this);
    }

    /**
     * @return Tracker_Permission_PermissionController
     */
    protected function getPermissionController()
    {
        return new Tracker_Permission_PermissionController($this);
    }

    /**
     * @return Tracker_RulesManager
     */
    private function getGlobalRulesManager()
    {
        return $this->getWorkflowFactory()->getGlobalRulesManager($this);
    }

    /**
     * @return MailGatewayConfig
     */
    private function getMailGatewayConfig()
    {
        return new MailGatewayConfig(
            new MailGatewayConfigDao()
        );
    }

    /**
     * @return Tracker_ArtifactByEmailStatus
     */
    private function getArtifactByMailStatus()
    {
        return new Tracker_ArtifactByEmailStatus($this->getMailGatewayConfig());
    }

    /**
     * @return string
     */
    public function displayRulesAsJavascript()
    {
        return $this->getGlobalRulesManager()->displayRulesAsJavascript();
    }

    /**
     * Determine if the user can view this tracker.
     * Note that if there is no group explicitely auhtorized, access is denied (don't check default values)
     *
     * @param PFUser|int $user if not specified, use the current user id. The params accept also User object
     *
     * @return bool true if the user can view the tracker.
     */
    public function userCanView($user = 0)
    {
        $user_manager = $this->getUserManager();

        if (! $user instanceof PFUser) {
            if (!$user) {
                $user = $user_manager->getCurrentUser();
            } else {
                $user = $user_manager->getUserById((int) $user);
            }
        }

        $permission_checker = new Tracker_Permission_PermissionChecker(
            $user_manager,
            new ProjectAccessChecker(
                PermissionsOverrider_PermissionsOverriderManager::instance(),
                new RestrictedUserCanAccessProjectVerifier(),
                EventManager::instance()
            )
        );

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
    public function getPermissionsByUgroupId()
    {
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
    public function setCachePermission($ugroup_id, $permission_type)
    {
        $this->cache_permissions[$ugroup_id][] = $permission_type;
    }

    /**
     * Empty cache permissions
     *
     * @return void
     */
    public function permissionsAreCached()
    {
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
    public function getAuthorizedUgroupsByPermissionType()
    {
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
    public function getFieldsAuthorizedUgroupsByPermissionType()
    {
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
     * @param PFUser|int|false $user Either the user ID or the User object to test, or current user if false
     *
     * @return bool True if the user is tracker admin, false otherwise
     */
    public function userIsAdmin($user = 0)
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
            foreach ($permission_types as $permission_type) {
                if ($permission_type == self::PERMISSION_ADMIN) {
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
    protected function getTrackerManager()
    {
        return new TrackerManager();
    }


    /**
     * Check if user has permission to submit artifact or not
     *
     * @param PFUser $user The user to test (current user if not defined)
     *
     * @return bool true if user has persission to submit artifacts, false otherwise
     */
    public function userCanSubmitArtifact($user = false)
    {
        if (! $user instanceof PFUser) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }

        if ($user->isAnonymous() || ! $this->userCanView($user)) {
            return false;
        }

        $can_submit = false;
        foreach ($this->getFormElementFactory()->getUsedFields($this) as $form_element) {
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
     * @return bool true if user has persission to delete trackers, false otherwise
     */
    public function userCanDeleteTracker($user = false)
    {
        if (!($user instanceof PFUser)) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        return $user->isSuperUser() || $user->isMember($this->getGroupId(), 'A');
    }

    public function getInformationsFromOtherServicesAboutUsage()
    {
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
     * @return bool true if user has full access to tracker, false otherwise
     */
    public function userHasFullAccess($user = false)
    {
        if (!($user instanceof PFUser)) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        if ($user->isSuperUser() || $user->isMember($this->getGroupId(), 'A')) {
            return true;
        } else {
            $permissions = $this->getPermissionsByUgroupId();
            foreach ($permissions as $ugroup_id => $permission_types) {
                foreach ($permission_types as $permission_type) {
                    if ($permission_type == self::PERMISSION_FULL || $permission_type == self::PERMISSION_ADMIN) {
                        if ($user->isMemberOfUGroup($ugroup_id, $this->getGroupId())) {
                                return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    public function exportToXML(SimpleXMLElement $xmlElem, array &$xmlMapping = array())
    {
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

    public function getXMLId()
    {
        return self::XML_ID_PREFIX . $this->getId();
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
            $parent_id = "T" . $parent_id;
        } else {
            $parent_id = "0";
        }

        $xmlElem->addAttribute('parent_id', (string) $parent_id);

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
        $cdata = new XML_SimpleXMLCDATAFactory();
        $cdata->insert($xmlElem, 'item_name', $this->getItemName());
        $cdata_section_factory->insert($xmlElem, 'description', $this->getDescription());
        $cdata->insert($xmlElem, 'color', $this->getColor()->getName());

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
            $grandchild = $child->addChild($formElement->getTagNameForXMLExport());
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
        $workflow = $this->getWorkflowFactory()->getWorkflowByTrackerId($this->id);
        if (! empty($workflow)) {
            if (! $workflow->isAdvanced()) {
                $child    = $xmlElem->addChild('simple_workflow');
                $exporter = new SimpleWorkflowXMLExporter(
                    new SimpleWorkflowDao(),
                    new StateFactory(
                        TransitionFactory::instance(),
                        new SimpleWorkflowDao()
                    ),
                    new TransitionExtractor()
                );
                $exporter->exportToXML($workflow, $child, $xmlMapping);
            } else {
                $child = $xmlElem->addChild('workflow');
                $workflow->exportToXML($child, $xmlMapping);
            }
        }

        $webhook_xml_exporter = $this->getWebhookXMLExporter();
        $webhook_xml_exporter->exportTrackerWebhooksInXML($xmlElem, $this);

        // permissions
        $node_perms       = $xmlElem->addChild('permissions');
        $project_ugroups = $this->getUGroupRetrieverWithLegacy()->getProjectUgroupIds($this->getProject());
        // tracker permissions
        if ($permissions = $this->getPermissionsByUgroupId()) {
            foreach ($permissions as $ugroup_id => $permission_types) {
                if (($ugroup = array_search($ugroup_id, $project_ugroups)) !== false) {
                    foreach ($permission_types as $permission_type) {
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
     * Send the xml to the client
     *
     * @param SimpleXMLElement $xmlElem The xml
     */
    protected function sendXML(SimpleXMLElement $xmlElem)
    {
        $dom = dom_import_simplexml($xmlElem)->ownerDocument;
        if ($dom === null) {
            return;
        }
        $dom->formatOutput = true;

        $output_filename = 'Tracker_' . $this->item_name . '.xml';
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
        foreach ($header as $field_name) {
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

    private function _getCSVSeparator($current_user)
    {
        if (! $current_user || ! ($current_user instanceof PFUser)) {
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

    private function _getCSVDateformat($current_user)
    {
        if (! $current_user || ! ($current_user instanceof PFUser)) {
            $current_user = UserManager::instance()->getCurrentUser();
        }
        $dateformat_csv_export_pref = $current_user->getPreference('user_csv_dateformat');
        if ($dateformat_csv_export_pref === false) {
            $dateformat_csv_export_pref = "month_day_year"; // by default, mm/dd/yyyy
        }
        return $dateformat_csv_export_pref;
    }

    protected function displayImportPreview(Tracker_IDisplayTrackerLayout $layout, $request, $current_user, $session)
    {
        $purifier = Codendi_HTMLPurifier::instance();

        if ($_FILES['csv_filename']) {
            if (mb_detect_encoding(file_get_contents($_FILES['csv_filename']['tmp_name']), 'UTF-8', true) === false) {
                $GLOBALS['Response']->addFeedback(
                    'error',
                    dgettext('tuleap-tracker', 'Your file is not encoded in UTF-8, we are not able to parse it safely. Please save it with UTF-8 encoding before uploading it.')
                );
                $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . (int) $this->getId() . '&func=admin-csvimport');
            }
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

                        $title = $GLOBALS['Language']->getText('plugin_tracker_admin', 'csv_import');
                        $this->displayAdminCSVImportHeader($layout, $title);

                        echo '<h2 class="almost-tlp-title">' . $title . '</h2>';
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
                                $html_table .= '<tr class="' . $tr_class . '">';
                                $html_table .= '<td style="color:gray;">' . ($line_number + 1) . '</td>';
                                $mode = 'creation';
                                foreach ($data_line as $idx => $data_cell) {
                                    if ($fields[$idx] && is_a($fields[$idx], 'Tracker_FormElement_Field')) {
                                        $field  = $fields[$idx];
                                        $displayed_data = $field->getFieldDataForCSVPreview($data_cell);
                                    } else {
                                        // else: this cell is an 'aid' cell
                                        if ($fields[$idx] === 'aid' && $data_cell) {
                                            $mode = 'update';
                                        }
                                        $displayed_data = $purifier->purify($data_cell);
                                    }
                                    $html_table .=  '<td class="tracker_report_table_column">' . $displayed_data . '</td>';
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

                            $expected_format = 'MM/DD/YYYY';
                            if ($this->_getCSVDateformat($current_user) === CSVFormatter::DAY_MONTH_YEAR) {
                                $expected_format = 'DD/MM/YYYY';
                            }

                            echo $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'date_format_help', [$expected_format]);
                            echo '</p>';

                            if ($is_valid) {
                                echo '<form name="form1" method="POST" enctype="multipart/form-data" action="' . TRACKER_BASE_URL . '/?tracker=' . (int) $this->id . '&amp;func=admin-csvimport">';
                                echo '<p>' . $GLOBALS['Language']->getText('plugin_tracker_import', 'ready', array($nb_lines, $nb_artifact_creation, $nb_artifact_update)) . '</p>';
                                echo '<input type="hidden" name="action" value="import">';
                                if ($request->exist('notify') && $request->get('notify') == 'ok') {
                                    echo '<input type="hidden" name="notify" value="ok">';
                                }
                                echo '<input type="submit" class="csv-preview-import-button" value="' . $GLOBALS['Language']->getText('plugin_tracker_import', 'import_new_hdr') . '">';
                            }
                            echo $html_table;
                            if ($is_valid) {
                                echo '</form>';

                                $session->set('csv_header', $header);
                                $session->set('csv_body', $lines);
                            }
                        }

                        $this->displayAdminFooter($layout);
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
            $GLOBALS['Response']->redirect(TRACKER_BASE_URL . '/?tracker=' . (int) $this->getId() . '&func=admin-csvimport');
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

    public function displayWarningArtifactByEmailSemantic()
    {
        $artifactbyemail_status = $this->getArtifactByMailStatus();

        if (! $artifactbyemail_status->isSemanticConfigured($this)) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'semantic_missing')
            );
        }
    }

    private function displayAdminFormElementsWarnings()
    {
        $this->displayWarningArtifactByEmailRequiredFields();
        $event = new DisplayAdminFormElementsWarningsEvent($this, $GLOBALS['Response']);
        EventManager::instance()->processEvent($event);
    }

    private function displayWarningArtifactByEmailRequiredFields()
    {
        $artifactbyemail_status = $this->getArtifactByMailStatus();

        if (! $artifactbyemail_status->isRequiredFieldsConfigured($this)) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'invalid_required_fields')
            );
        }
    }

    public function displayWarningGeneralsettings()
    {
        $artifactbyemail_status = $this->getArtifactByMailStatus();

        if (
            ! $artifactbyemail_status->isRequiredFieldsConfigured($this)
            || ! $artifactbyemail_status->isSemanticConfigured($this)
        ) {
            $GLOBALS['Response']->addFeedback(
                'warning',
                $GLOBALS['Language']->getText('plugin_tracker_emailgateway', 'invalid_configuration')
            );
        }
    }

    /**
     * Validation of CSV file datas in this tracker
     *
     * @return bool true if CSV file is valid, false otherwise
     */
    public function isValidCSV($lines, $separator)
    {
        $is_valid = true;
        $header_line = array_shift($lines);

        if (count($header_line) == 1) {
            // not sure it is an error, so don't set is_valid to false.
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'separator_not_found', array($separator)), CODENDI_PURIFIER_FULL);
        }

        if ($this->hasBlockingError($header_line, $lines)) {
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
    public function hasUnknownAid($header_line, $lines)
    {
        $has_unknown = false;
        $aid_key = array_search('aid', $header_line);
        //Update mode
        if ($aid_key !== false) {
            foreach ($lines as $line) {
                if ($line[$aid_key] != '') {
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
    public function hasBlockingError($header_line, $lines)
    {
        $has_blocking_error = false;
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
                        $has_blocking_error = true;
                    } elseif ($field && !is_array($field_name)) {
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
                        $data[$field->getId()] = $field->getFieldDataFromCSVValue($line[$idx], $artifact);

                        if ($data[$field->getId()] === null) {
                            $GLOBALS['Response']->addFeedback(
                                'error',
                                $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_value', array($line[$idx], $field_name))
                            );
                            $has_blocking_error = true;
                        }
                    } else {
                        $error_nature[$column_name] = $column_name;
                    }
                } else {
                    //Field is aid : we check if the artifact id exists
                    if ($this->hasUnknownAid($header_line, $lines)) {
                        $has_blocking_error = true;
                    }
                }
            }
            if ($artifact) {
                $is_new_artifact = $artifact->getId() == 0;
                if ($is_new_artifact) {
                    $fields_validator = new Tracker_Artifact_Changeset_InitialChangesetFieldsValidator($this->getFormElementFactory());
                } else {
                    $fields_validator = $this->getNewChangesetFieldsValidator();
                }
                if (! $fields_validator->validate($artifact, $this->getUserManager()->getCurrentuser(), $data)) {
                     $has_blocking_error = true;
                }
                try {
                    $this->getWorkflow()->checkGlobalRules($data);
                } catch (Tracker_Workflow_GlobalRulesViolationException $e) {
                    $GLOBALS['Response']->addFeedback(
                        Feedback::ERROR,
                        sprintf(
                            dgettext('tuleap-tracker', "This artifact doesn't respect tracker's rules. It will not be imported : CSV's line %s."),
                            intval($cpt_line) + 2
                        )
                    );
                }
            }
        }
        if (count($unknown_fields) > 0) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_field', array(implode(',', $unknown_fields))));
        }
        if (count($error_nature) > 0) {
            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'importing_nature', array(implode(',', $error_nature))));
        }

        return $has_blocking_error;
    }


    /**
     * Check if CSV contains all the required fields and values associated
     *
     * @param Array $lines, the CSV file lines
     *
     * @return bool true if missing required fields, false otherwise
     */
    public function isMissingRequiredFields($header_line, $lines)
    {
        $is_missing = false;
        $fields = array();
        $fef = $this->getFormElementFactory();
        $fields = $fef->getUsedFields($this);
        foreach ($fields as $field) {
            if ($field->isRequired()) {
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
    protected function aidExists($aid)
    {
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
     * @return bool true if import succeed, false otherwise
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
                $mode        = 'creation';
                $fields_data = array();
                $artifact    = null;
                foreach ($data_line as $idx => $data_cell) {
                    if (($fields[$idx]) === null) {
                        continue;
                    } elseif ($fields[$idx] === 'aid') {
                        if ($data_cell) {
                            $mode = 'update';

                            $artifact_id = (int) $data_cell;
                            $artifact    = $af->getArtifactById($artifact_id);
                            if (! $artifact) {
                                $GLOBALS['Response']->addFeedback(
                                    Feedback::ERROR,
                                    $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_artifact', array($artifact_id))
                                );
                                $is_error = true;
                            }
                        }
                    } elseif (is_a($fields[$idx], 'Tracker_FormElement')) {
                        $field = $fields[$idx];
                        if ($field->isCSVImportable()) {
                            $fields_data[$field->getId()] = $field->getFieldDataFromCSVValue($data_cell, $artifact);
                        } else {
                            $GLOBALS['Response']->addFeedback('warning', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'field_not_taken_account', $field->getName()));
                        }
                    }
                }
                $nb_lines++;
                if ($mode == 'creation') {
                    $fields_data = $this->getFormElementFactory()->getUsedFieldsWithDefaultValue($this, $fields_data, $current_user);
                    if ($artifact = $af->createArtifact($this, $fields_data, $current_user, null, $send_notifications)) {
                        $nb_artifact_creation++;
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unable_to_create_artifact'));
                        $is_error = true;
                    }
                } else {
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
        return ! $is_error;
    }

     /**
     * Get UserManager instance
     *
     * @return UserManager
     */
    protected function getUserManager()
    {
        return UserManager::instance();
    }

    /**
     * Get Tracker_ArtifactFactory instance
     *
     * @return Tracker_ArtifactFactory
     */
    protected function getTrackerArtifactFactory()
    {
        return Tracker_ArtifactFactory::instance();
    }

    /**
     * Get FormElementFactory instance
     *
     * @return Tracker_FormElementFactory
     */
    protected function getFormElementFactory()
    {
        return $this->formElementFactory;
    }

    /**
     * Get WorkflowFactory instance
     *
     * @return WorkflowFactory
     */
    protected function getWorkflowFactory()
    {
        return WorkflowFactory::instance();
    }

    /**
     * Get ReportFactory instance
     *
     * @return Tracker_ReportFactory
     */
    protected function getReportFactory()
    {
        return Tracker_ReportFactory::instance();
    }

    /**
     * Verifies the consistency of the imported Tracker
     *
     * @return bool true if Tracker is ok
     */
    public function testImport()
    {
        foreach ($this->formElements as $form) {
            if (! $form || !$form->testImport()) {
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
    public function augmentDataFromRequest(&$fields_data)
    {
        foreach (Tracker_FormElementFactory::instance()->getUsedFields($this) as $field) {
            $field->augmentDataFromRequest($fields_data);
        }
    }

    /**
     * Get a recipients list for (global) notifications.
     *
     * @return array
     */
    public function getRecipients()
    {
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
            $recipients[$id] = array( 'recipients' => $notified_emails, 'on_updates' => $notification->isAllUpdates(), 'check_permissions' => $notification->isCheckPermissions()  );
        }
        return $recipients;
    }

    protected $cache_stats;

    /**
     * get stats for this tracker
     *
     * @return array
     */
    public function getStats()
    {
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
    public function fetchStats()
    {
        $html = '';
        if ($row = $this->getStats()) {
            $html .= '<div class="tracker_statistics" style="font-size:0.9em; color:#666;">';
            $html .= '<div style="text-align:right;font-size:0.825em;">#' . $this->id . '</div>';
            if ($row['nb_total'] && $this->hasSemanticsStatus()) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_stat', 'number_open_artifacts') . ' ' . $row['nb_open'] . '<br />';
            }

            $html .= $GLOBALS['Language']->getText('plugin_tracker_stat', 'total_number_artifacts') . ' ' . $row['nb_total'] . '<br />';
            if ($row['last_creation'] && $row['last_update']) {
                $html .= $GLOBALS['Language']->getText('plugin_tracker_stat', 'recent_activity');
                $html .= '<ul>';
                if ($row['last_update']) {
                    $html .= '<li>' . $GLOBALS['Language']->getText('plugin_tracker_stat', 'last_update') . ' ';
                    $html .= DateHelper::timeAgoInWords($row['last_update'], true, true);
                    $html .= '</li>';
                }
                if ($row['last_creation']) {
                    $html .= '<li>' . $GLOBALS['Language']->getText('plugin_tracker_stat', 'last_artifact_created') . ' ';
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
    public function hasSemanticsTitle()
    {
        return Tracker_Semantic_Title::load($this)->getFieldId() ? true : false;
    }

    /**
     * Return the title field, or null if no title field defined
     *
     * @return Tracker_FormElement_Field_Text the title field, or null if not defined
     */
    public function getTitleField()
    {
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
    public function hasSemanticsDescription()
    {
        return Tracker_Semantic_Description::load($this)->getFieldId() ? true : false;
    }

    /**
     * Return the description field, or null if no title field defined
     *
     * @return Tracker_FormElement_Field_Text the title field, or null if not defined
     */
    public function getDescriptionField()
    {
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
    public function hasSemanticsStatus()
    {
        return Tracker_Semantic_Status::load($this)->getFieldId() ? true : false;
    }

    /**
     * Return the status field, or null if no status field defined
     *
     * @return Tracker_FormElement_Field_List the status field, or null if not defined
     */
    public function getStatusField()
    {
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
    public function getContributorField()
    {
        $contributor_field = Tracker_Semantic_Contributor::load($this)->getField();
        if ($contributor_field) {
            return $contributor_field;
        } else {
            return null;
        }
    }

    public function setFormElementFactory(Tracker_FormElementFactory $factory)
    {
        $this->formElementFactory = $factory;
    }

    public function setSharedFormElementFactory(Tracker_SharedFormElementFactory $factory)
    {
        $this->sharedFormElementFactory = $factory;
    }

    /**
     * Set children trackers
     *
     * @param Tracker[] $trackers
     */
    public function setChildren(array $trackers)
    {
        $this->children = $trackers;
    }

    /**
     * Return the children of the tracker
     *
     * @return Tracker[]
     */
    public function getChildren()
    {
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
    public function getHierarchy()
    {
        return $this->getHierarchyFactory()->getHierarchy(array($this->getId()));
    }

    /**
     * @return Tracker_HierarchyFactory
     */
    protected function getHierarchyFactory()
    {
        return new Tracker_HierarchyFactory(
            new HierarchyDAO(),
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
     */
    public function setParent(?Tracker $tracker = null)
    {
        $this->parent = $tracker;
    }

    /**
     * Return parent tracker of current tracker (if any)
     *
     * @return Tracker|null
     */
    public function getParent()
    {
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

    protected function getParentId()
    {
        return $this->getHierarchy()->getParent($this->getId());
    }

    /**
     * Return workflow of the current tracker (there is always a workflow).
     *
     * @return Workflow
     */
    public function getWorkflow()
    {
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
    public function getUri()
    {
        return TRACKER_BASE_URL . '/?tracker=' . $this->getId();
    }

    private function getArtifactXMLImporterForArtifactCopy(Tracker_XML_Importer_CopyArtifactInformationsAggregator $logger)
    {
        $fields_validator      = new Tracker_Artifact_Changeset_AtGivenDateFieldsValidator(
            $this->getFormElementFactory()
        );

        $changeset_dao         = new Tracker_Artifact_ChangesetDao();
        $changeset_comment_dao = new Tracker_Artifact_Changeset_CommentDao();
        $send_notifications    = true;

        $artifact_creator = new Tracker_ArtifactCreator(
            $this->getTrackerArtifactFactory(),
            $fields_validator,
            new Tracker_Artifact_Changeset_InitialChangesetAtGivenDateCreator(
                $fields_validator,
                new FieldsToBeSavedInSpecificOrderRetriever($this->getFormElementFactory()),
                $changeset_dao,
                $this->getTrackerArtifactFactory(),
                EventManager::instance(),
                new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->getFormElementFactory()),
                $logger
            ),
            $this->getVisitRecorder(),
            $logger,
            new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
        );

        $new_changeset_creator = new Tracker_Artifact_Changeset_NewChangesetAtGivenDateCreator(
            $fields_validator,
            new FieldsToBeSavedInSpecificOrderRetriever($this->getFormElementFactory()),
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
            new Tracker_Artifact_Changeset_ChangesetDataInitializator($this->getFormElementFactory()),
            new \Tuleap\DB\DBTransactionExecutorWithConnection(\Tuleap\DB\DBFactory::getMainTuleapDBConnection()),
        );

        $artifact_source_id_dao = new TrackerArtifactSourceIdDao();
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
            new NatureDao(),
            new XMLArtifactSourcePlatformExtractor(new Valid_HTTPURI(), $logger),
            new ExistingArtifactSourceIdFromTrackerExtractor($artifact_source_id_dao),
            $artifact_source_id_dao,
            new ExternalFieldsExtractor(EventManager::instance())
        );
    }

    private function getChildrenCollector(Codendi_Request $request)
    {
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

    private function getUserXMLExporter()
    {
        return new UserXMLExporter(
            $this->getUserManager(),
            new UserXMLExportedCollection(new XML_RNGValidator(), new XML_SimpleXMLCDATAFactory())
        );
    }

    private function getChangesetXMLUpdater()
    {
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

    private function getFileXMLUpdater()
    {
        return new Tracker_XML_Updater_TemporaryFileXMLUpdater(
            new Tracker_XML_Updater_TemporaryFileCreator()
        );
    }

    public function hasFieldBindedToUserGroupsViewableByUser(PFUser $user)
    {
        $form_elements = $this->formElementFactory->getUsedFieldsBindedToUserGroups($this);

        foreach ($form_elements as $field) {
            if ($field->userCanRead($user)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return WebhookXMLExporter
     */
    protected function getWebhookXMLExporter()
    {
        return new WebhookXMLExporter(
            $this->getWebhookFactory()
        );
    }

    /**
     * @return WebhookFactory
     */
    private function getWebhookFactory()
    {
        return new WebhookFactory(
            new WebhookDao()
        );
    }

    /**
     * @return WebhookLogsRetriever
     */
    private function getWebhookLogsRetriever()
    {
        return new WebhookLogsRetriever(
            new WebhookDao()
        );
    }

    /**
     * @return Tracker_Artifact_Changeset_NewChangesetFieldsValidator
     */
    private function getNewChangesetFieldsValidator()
    {
        $frozen_field_detector = new FrozenFieldDetector(
            new TransitionRetriever(
                new StateFactory(
                    new TransitionFactory(
                        Workflow_Transition_ConditionFactory::build(),
                        EventManager::instance(),
                        new DBTransactionExecutorWithConnection(
                            DBFactory::getMainTuleapDBConnection()
                        )
                    ),
                    new SimpleWorkflowDao()
                ),
                new TransitionExtractor()
            ),
            new FrozenFieldsRetriever(
                new FrozenFieldsDao(),
                $this->getFormElementFactory()
            )
        );
        $workflow_update_checker = new WorkflowUpdateChecker($frozen_field_detector);
        return new Tracker_Artifact_Changeset_NewChangesetFieldsValidator(
            $this->getFormElementFactory(),
            $workflow_update_checker
        );
    }
}
