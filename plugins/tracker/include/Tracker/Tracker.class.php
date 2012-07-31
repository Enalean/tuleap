<?php
/**
 * Copyright (c) Xerox Corporation, Codendi Team, 2001-2009. All rights reserved
 *
 * This file is a part of Codendi.
 *
 * Codendi is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Codendi is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Codendi. If not, see <http://www.gnu.org/licenses/>.
 */

require_once('dao/TrackerDao.class.php');
require_once('dao/Tracker_PermDao.class.php');
require_once('Semantic/Tracker_SemanticManager.class.php');
require_once('Tooltip/Tracker_Tooltip.class.php');
require_once('Tracker_NotificationsManager.class.php');
require_once('CannedResponse/Tracker_CannedResponseManager.class.php');
require_once('DateReminder/Tracker_DateReminderManager.class.php');
require_once('Rule/Tracker_RulesManager.class.php');
require_once(dirname(__FILE__).'/../workflow/WorkflowManager.class.php');
require_once('common/date/DateHelper.class.php');
require_once('common/widget/Widget_Static.class.php');
require_once(dirname(__FILE__).'/../tracker_permissions.php');
require_once('Tracker_Dispatchable_Interface.class.php');
require_once('FormElement/Tracker_SharedFormElementFactory.class.php');
require_once('Hierarchy/Controller.class.php');
require_once('Hierarchy/HierarchyFactory.class.php');
require_once 'IFetchTrackerSwitcher.class.php';

require_once('json.php');

class Tracker implements Tracker_Dispatchable_Interface {

    public $id;
    public $group_id;
    public $name;
    public $description;
    public $item_name;
    public $allow_copy;
    public $submit_instructions;
    public $browse_instructions;
    public $status;
    public $deletion_date;
    public $instantiate_for_new_projects;
    public $stop_notification;
    private $formElementFactory;
    private $sharedFormElementFactory;
    private $project;

    // attributes necessary to to create an intermediate Tracker Object
    // (before Database import) during XML import
    // they are not used after the import
    public $tooltip;
    public $cannedResponses = array();
    public $formElements = array();
    public $reports = array();
    public $workflow;

    public function __construct($id,
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
            $stop_notification) {
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
        $this->stop_notification            = $stop_notification;
        $this->formElementFactory           = Tracker_FormElementFactory::instance();
        $this->sharedFormElementFactory     = new Tracker_SharedFormElementFactory($this->formElementFactory, new Tracker_FormElement_Field_List_BindFactory());
    }
    
    public function __toString() {
        return "Tracker #".$this->id;
    }

    /**
     * @return string the url of the form to submit a new artifact
     */
    public function getSubmitUrl() {
        return TRACKER_BASE_URL .'/?tracker='. $this->getId() .'&func=new-artifact';
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
    
    /**
     * Returns true is notifications are stopped for this tracker
     *
     * @return boolean true is notifications are stopped for this tracker, false otherwise
     */
    function isNotificationStopped() {
        return $this->stop_notification == 1;
    }

    /**
     * @return array of formElements used by this tracker
     */
    public function getFormElements() {
        return Tracker_FormElementFactory::instance()->getUsedFormElementForTracker($this);
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
    public function fetchFormElements($artifact, $submitted_values=array()) {
        $html = '';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchArtifact($artifact, $submitted_values);
        }
        return $html;
    }

    /**
     * fetch FormElements
     * @return string
     */
    public function fetchAdminFormElements() {
        $html = '';
        $html .= '<table width="20%"><tr><td id="tracker-admin-fields" class="tracker-admin-group">';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchAdmin($this);
        }
        $html .= '</td></tr></table>';
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
    
    public function process(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
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
            case 'new-artifact-link':
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
                        $GLOBALS['Response']->addFeedback(
                                'info',
                                $GLOBALS['Language']->getText(
                                'plugin_tracker_admin_index',
                                'delete_success',
                                $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML)));
                        $GLOBALS['Response']->addFeedback(
                                'info',
                                $GLOBALS['Language']->getText(
                                'plugin_tracker_admin_index',
                                'tracker_deleted',
                                $GLOBALS['sys_email_admin']),
                                CODENDI_PURIFIER_FULL
                        );
                    } else {
                        $GLOBALS['Response']->addFeedback(
                                'error',
                                $GLOBALS['Language']->getText(
                                'plugin_tracker_admin_index',
                                'deletion_failed',
                                $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML)));
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
                    if ($request->get('update')) {
                        //TODO : really bad! _REQUEST must be processed before using it, or refactor: use request object
                        plugin_tracker_permission_process_update_tracker_permissions($this->getGroupId(), $this->getId(), $_REQUEST);
                    }
                    $this->displayAdminPermsTracker($layout, $request, $current_user);
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
            case 'admin-notifications':
                if ($this->userIsAdmin($current_user)) {
                    $this->getDateReminderManager()->processReminder($layout, $request, $current_user);
                    $this->getNotificationsManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'notifications':
            // you just need to be registered to have access to this part
                if ($current_user->isLoggedIn()) {
                    $this->getDateReminderManager()->processReminder($layout, $request, $current_user);
                    $this->getNotificationsManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'display_reminder_form':
                print $this->getDateReminderManager()->getDateReminderRenderer()->getNewDateReminderForm();
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
            case 'admin-workflow':
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
                        
                        if ($this->importFromCSV($layout, $request, $current_user, $csv_header, $csv_body)) {
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
                    $this->sendXML($this->exportToXML());
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-dependencies':
                if ($this->userIsAdmin($current_user)) {
                    $this->getRulesManager()->process($layout, $request, $current_user);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'submit-artifact':
                if ($this->userCanSubmitArtifact($current_user)) {
                    $link = (int)$request->get('link-artifact-id');
                    if ($artifact = $this->createArtifact($layout, $request, $current_user)) {
                        $this->associateImmediatelyIfNeeded($artifact, $link, $request->get('immediate'), $current_user);
                        
                        $artifact->summonArtifactRedirectors($request);
                        
                        if ($request->isAjax()) {
                            header(json_header(array('aid' => $artifact->getId())));
                            exit;
                        } else if ($link) {
                            echo '<script>window.parent.codendi.tracker.artifact.artifactLink.newArtifact('. (int)$artifact->getId() .');</script>';
                            exit;
                        } else {
                            $art_link = '<a href="'.TRACKER_BASE_URL.'/?aid=' . $artifact->getId() . '">' . $this->getItemName() . ' #' . $artifact->getId() . '</a>';
                            $GLOBALS['Response']->addFeedback('info', $GLOBALS['Language']->getText('plugin_tracker_index', 'create_success', array($art_link)), CODENDI_PURIFIER_LIGHT);
                            
                            $url_redirection = $this->redirectUrlAfterArtifactSubmission($request, $this->getId(), $artifact->getId());
                            $GLOBALS['Response']->redirect($url_redirection);
                        }
                    }
                    $this->displaySubmit($layout, $request, $current_user, $link);
                } else {
                    $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin', 'access_denied'));
                    $GLOBALS['Response']->redirect(TRACKER_BASE_URL.'/?tracker='. $this->getId());
                }
                break;
            case 'admin-hierarchy':
                if ($this->userIsAdmin($current_user)) {
                    
                    $this->displayAdminItemHeader($layout, 'hierarchy');
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
            default:
                $nothing_has_been_done = true;
                EventManager::instance()->processEvent(
                    TRACKER_EVENT_PROCESS,
                    array(
                        'func'                  => $func,
                        'tracker'               => $this,
                        'layout'                => $layout,
                        'request'               => $request,
                        'user'                  => $current_user,
                        'nothing_has_been_done' => &$nothing_has_been_done,
                    )
                );
                if ($nothing_has_been_done) {
                    //If there is nothing to do, display a report
                    if ($this->userCanView($current_user)) {
                        $this->displayAReport($layout, $request, $current_user);
                    }
                }
                break;
        }
        return false;
    }
    
    private function associateImmediatelyIfNeeded(Tracker_Artifact $new_artifact, $link_artifact_id, $doitnow, User $current_user) {
        if ($link_artifact_id && $doitnow) {
            $source_artifact = Tracker_ArtifactFactory::instance()->getArtifactById($link_artifact_id);
            if ($source_artifact) {
                $source_artifact->linkArtifact($new_artifact->getId(), $current_user);
            }
        }
    }
    
    private function getHierarchyController($request) {
        $dao                  = new Tracker_Hierarchy_Dao();
        $tracker_factory      = $this->getTrackerFactory();
        $factory              = new Tracker_Hierarchy_HierarchicalTrackerFactory($tracker_factory, $dao);
        $hierarchical_tracker = $factory->getWithChildren($this);
        $controller           = new Tracker_Hierarchy_Controller($request, $hierarchical_tracker, $factory, $dao);
        return $controller;
    }

    
    public function createFormElement($type, $formElement_data, $user) {
        if ($type == 'shared') {
            $this->sharedFormElementFactory->createFormElement($this, $formElement_data, $user);
        } else {
            $this->formElementFactory->createFormElement($this, $type, $formElement_data);
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
     * @param User                           $current_user    The user who made the request
     *
     * @return void
     */
    public function displayAReport(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $report = null;

        //Does the user wants to change its report?
        if ($request->get('select_report') && $request->isPost()) {
            //Is the report id valid
            if ($report = $this->getReportFactory()->getReportById($request->get('select_report'), $current_user->getid())) {
                $current_user->setPreference('tracker_'. $this->id .'_last_report', $report->id);
            }
        }

        //If no valid report found. Search the last viewed report for the user
        if (!$report) {
            if ($report_id = $current_user->getPreference('tracker_'. $this->id .'_last_report')) {
                $report = $this->getReportFactory()->getReportById($report_id, $current_user->getid());
            }
        }

        //If no valid report found. Take the default one
        if (!$report) {
            $report = $this->getReportFactory()->getDefaultReportsByTrackerId($this->id);
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
            echo $linked_artifact->fetchTitle($GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'title_prefix'));
            
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
            if ($recent_items = $current_user->getRecentElements()) {
                echo $GLOBALS['Language']->getText('plugin_tracker_artifactlink', 'recent_panel_desc');
                echo '<ul>';
                foreach ($recent_items as $item) {
                    if ($item['id'] != $link_artifact_id) {
                        echo '<li>';
                        echo '<input type="checkbox" 
                                     name="link-artifact[recent][]" 
                                     value="'. (int)$item['id'] .'" /> ';
                        echo $item['link'];
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
    public function displaySubmit(Tracker_IFetchTrackerSwitcher $layout, $request, $current_user, $link = null) {
        $hp = Codendi_HTMLPurifier::instance();
        $breadcrumbs = array(
                array(
                        'title' => 'New artifact',
                        'url'   => $this->getSubmitUrl(),
                ),
        );
        
        if (!$link) {
            $this->displayHeader($layout, $this->name, $breadcrumbs);
        }
        
        if ($link) {
            echo '<html>';
            echo '<head>';
            echo '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
            $GLOBALS['HTML']->displayStylesheetElements(array());
            $GLOBALS['HTML']->includeCalendarScripts();
            $GLOBALS['HTML']->displayJavascriptElements(array());
            echo '</head>';
            
            echo '<body>';
            echo '<div class="main_body_row">';
            echo '<div class="contenttable">';

            $project = null;
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactByid($link);
            if ($artifact) {
                $project = $artifact->getTracker()->getProject();
                $GLOBALS['Response']->addFeedback(
                    'warning', 
                    $GLOBALS['Language']->getText(
                        'plugin_tracker', 
                        'linked_to', 
                        array(
                            $artifact->fetchDirectLinkToArtifact(),
                            $layout->fetchTrackerSwitcher($current_user, ' ', $project, $this),
                        )
                    ),
                    CODENDI_PURIFIER_DISABLED
                );
            } else {
                $GLOBALS['Response']->addFeedback('error', 'Error the artifact to link doesn\'t exist');
            }
            $GLOBALS['Response']->displayFeedback();
        }
        $html = '';
        if ($this->submit_instructions) {
            $html .= '<p class="submit_instructions">' . $hp->purify($this->submit_instructions, CODENDI_PURIFIER_FULL) . '</p>';
        }
        
        $query_parameters = array(
            'tracker'  => $this->id,
            'func'     => 'submit-artifact',
        );
        EventManager::instance()->processEvent(
            TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION,
            array(
                'request'          => $request,
                'query_parameters' => &$query_parameters,
            )
        );
        
        $html .= '<form action="'. TRACKER_BASE_URL .'/?'. http_build_query($query_parameters) .'" method="POST" enctype="multipart/form-data">';
        if ($link) {
            $html .= '<input type="hidden" name="link-artifact-id" value="'. (int)$link .'" />';
            if ($request->get('immediate')) {
                $html .= '<input type="hidden" name="immediate" value="1" />';
            }
        }
        $html .= '<input type="hidden" value="67108864" name="max_file_size" />';
        $html .= '<table><tr><td>';
        foreach($this->getFormElements() as $formElement) {
            $html .= $formElement->fetchSubmit($request->get('artifact'));
        }
        $html .= '</td></tr></table>';
        
        if ($current_user->isAnonymous()) {
            $html .= $this->fetchAnonymousEmailForm();
        }
        
        if (!$link) {
            $html .= '<input type="submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
            $html .= ' ';
            $html .= '<input type="submit" name="submit_and_continue" value="'. $GLOBALS['Language']->getText('global', 'btn_submit_and_continue') .'" />';
            $html .= '<input type="submit" name="submit_and_stay" value="'. $GLOBALS['Language']->getText('global', 'btn_submit_and_stay') .'" />';
        } else {
            $html .= '<input type="submit" id="tracker_artifact_submit" value="'. $GLOBALS['Language']->getText('global', 'btn_submit') .'" />';
        }
        
        $html .= '</form>';
        
        $trm = new Tracker_RulesManager($this);
        $html .= $trm->displayRulesAsJavascript();
        
        $html .= '</div></div>';
        
        echo $html;
        if (!$link) {
            $this->displayFooter($layout);
        }
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
    
    protected function fetchAnonymousEmailForm() {
        $html = '<p>';
        $html .= $GLOBALS['Language']->getText('plugin_tracker_artifact', 'not_logged_in', array('/account/login.php?return_to='.urlencode($_SERVER['REQUEST_URI'])));
        $html .= '<br />';
        $html .= '<input type="text" name="email" id="email" size="50" maxsize="100" />';
        $html .= '</p>';
        return $html;
    }
    
    public function displayHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs, $toolbar = null) {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            $hp = Codendi_HTMLPurifier::instance();
            $breadcrumbs = array_merge(array(array('title' => $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML),
                            'url'   => TRACKER_BASE_URL.'/?tracker='. $this->id)
                    ),
                    $breadcrumbs);
            if (!$toolbar) {
                $toolbar = array();
                $toolbar[] = array(
                        'title' => $GLOBALS['Language']->getText('plugin_tracker', 'submit_new_artifact'),
                        'url'   => $this->getSubmitUrl(),
                        'class' => 'tracker-submit-new',
                );
                if (UserManager::instance()->getCurrentUser()->isLoggedIn()) {
                    $toolbar[] = array(
                            'title' => $GLOBALS['Language']->getText('plugin_tracker', 'notifications'),
                            'url'   => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=notifications',
                    );
                }
                if ($this->userIsAdmin()) {
                    $toolbar[] = array(
                            'title' => $GLOBALS['Language']->getText('plugin_tracker', 'administration'),
                            'url'   => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin'
                    );
                }
                $toolbar[] = array(
                        'title' => $GLOBALS['Language']->getText('plugin_tracker', 'help'),
                        'url'   => 'javascript:help_window(\''.get_server_url().'/documentation/user_guide/html/'.UserManager::instance()->getCurrentUser()->getLocale().'/TrackerV5Service.html\');',
                );
            }
            $title = ($title ? $title .' - ' : ''). $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML);
            $layout->displayHeader($project, $title, $breadcrumbs, $toolbar);
        }
    }
    public function displayFooter(Tracker_IDisplayTrackerLayout $layout) {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            $layout->displayFooter($project);
        }
    }

    protected function getAdminItems() {
        $items = array(
                'editoptions' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-editoptions',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','settings'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','settings'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_title'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-general.png'),
                ),
                'editperms' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-perms',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','permissions'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','manage_permissions'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_manage_permissions'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-perms.png'),
                ),
                'editformElements' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-formElements',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','field_usage'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','mng_field_usage'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_use'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-form.png'),
                ),
                'dependencies' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-dependencies',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_dependencies'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_dependencies'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_dependencies_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-fdependencies.png'),
                ),
                'editsemantic' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-semantic',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','semantic'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_semantic'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_semantic_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-semantic.png'),
                ),
                'editworkflow' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-workflow',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','workflow'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_workflow'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','manage_workflow_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-workflow.png'),
                ),
                'editcanned' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-canned',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','canned_resp'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','mng_response'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','add_del_resp'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-canned.png'),
                ),
                'editnotifications' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=notifications',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','mail_notif'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','mail_notif'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_notif'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-notifs.png'),
                ),
                'csvimport' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-csvimport',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','csv_import'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','csv_import'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','csv_import_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-import.png'),
                ),
                'export' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-export',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','export'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','export'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','export_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-export.png'),
                ),
                'hierarchy' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin-hierarchy',
                        'short_title' => $GLOBALS['Language']->getText('plugin_tracker_admin','hierarchy'),
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_admin','hierarchy'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_admin','hierarchy_desc'),
                        'img'         => $GLOBALS['HTML']->getImagePath('ic/48/tracker-hierarchy.png'),
                ),
        );
        EventManager::instance()->processEvent(
            TRACKER_EVENT_ADMIN_ITEMS, 
            array(
                'tracker' => $this,
                'items'   => &$items
            )
        );
        return $items;
    }

    public function displayAdminHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs) {
        if ($project = ProjectManager::instance()->getProject($this->group_id)) {
            $hp = Codendi_HTMLPurifier::instance();
            $title = ($title ? $title .' - ' : ''). $GLOBALS['Language']->getText('plugin_tracker_include_type','administration');
            $toolbar = null;
            if ($this->userIsAdmin()) {
                $breadcrumbs = array_merge(
                        array(
                        array(
                                'title' => $GLOBALS['Language']->getText('plugin_tracker_include_type','administration'),
                                'url'   => TRACKER_BASE_URL.'/?tracker='. $this->id .'&amp;func=admin',
                        ),
                        ),
                        $breadcrumbs
                );
                $toolbar = $this->getAdminItems();
            }
            $this->displayHeader($layout, $title, $breadcrumbs, $toolbar);
        }
    }
    public function displayAdmin(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
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
    protected function fetchAdminMenu($items) {
        $hp = Codendi_HTMLPurifier::instance();
        $html = '';
        $cleaned_items = $items;
        foreach($cleaned_items as $key => $item) {
            if (!isset($item['title'])) {
                unset($cleaned_items[$key]);
            }
        }
        if ($cleaned_items) {
            $html .= '<table id="tracker_admin_menu">';
            $chunks = array_chunk($cleaned_items, 2);
            foreach($chunks as $row) {
                $html .= '<tr valign="top">';
                foreach($row as $item) {
                    $html .= '<td width="450">';
                    $html .= '<H3>';
                    $title =  $hp->purify($item['title'], CODENDI_PURIFIER_CONVERT_HTML) ;
                    if (isset($item['url'])) {
                        $html .= '<a href="'.$item['url'].'">';
                        if (isset($item['img']) && $item['img']) {
                            $html .= $GLOBALS['HTML']->getAbsoluteImage($item['img'], array(
                                    'style' => 'float:left;',
                            ));
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
        $items = $this->getAdminItems();
        $title = $title ? $title : $items[$item]['title'];
        $breadcrumbs = array_merge(
                array(
                $items[$item]
                ),
                $breadcrumbs
        );
        $this->displayAdminHeader($layout, $title, $breadcrumbs);
        echo '<h2>'. $title .'</h2>';
    }
    protected function displayAdminOptions(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $this->displayAdminItemHeader($layout, 'editoptions');
        $project = ProjectManager::instance()->getProject($this->group_id);

        echo '<form name="form1" method="POST" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->id .'&amp;func=admin-editoptions">
          <input type="hidden" name="update" value="1">
          <input type="hidden" name="instantiate_for_new_projects" value="0">
          <table width="100%" border="0" cellpadding="5">
            <tr> 
              <td width="15%"><b>'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','name').'</b> <font color="red">*</font>:</td>
              <td> 
              <input type="text" name="name" value="'. $hp->purify($this->name, CODENDI_PURIFIER_CONVERT_HTML) .'">
              </td>
            </tr>
            <tr> 
              <td width="15%"><b>'.$GLOBALS['Language']->getText('plugin_tracker_include_artifact','desc').'</b>: <font color="red">*</font></td>
              <td> 
                <textarea name="description" rows="3" cols="50">'. $hp->purify($this->description, CODENDI_PURIFIER_CONVERT_HTML) .'</textarea>
              </td>
            </tr>
            <tr> 
              <td width="15%"><b>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','short_name').'</b>: <font color="red">*</font></td>
              <td> 
                <input type="text" name="item_name" value="'. $hp->purify($this->item_name, CODENDI_PURIFIER_CONVERT_HTML) .'">
              </td>
            </tr>';
        //<tr>
        //  <td width="15%"><b>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','allow_copy').'</b></td>
        //  <td>
        //    <input type="checkbox" name="allow_copy" value="1" '. ($this->allow_copy ? 'checked="checked"' : '') . '>
        //  </td>
        //</tr>';

        echo '
            <tr> 
              <td width="15%"><b>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','instantiate').':</b></td>
              <td>
                <input type="checkbox" name="instantiate_for_new_projects" value="1" '. ($this->instantiate_for_new_projects ? 'checked="checked"' : '') . '>
              </td>
            </tr>
            <tr> 
              <td width="15%">'.$GLOBALS['Language']->getText('plugin_tracker_include_type','submit_instr').'</td>
              <td> 
                <textarea name="submit_instructions" rows="3" cols="50">'. $hp->purify($this->submit_instructions, CODENDI_PURIFIER_CONVERT_HTML) .'</textarea>
              </td>
            </tr>
            <tr> 
              <td>'.$GLOBALS['Language']->getText('plugin_tracker_include_type','browse_instr').'</td>
              <td> 
                <textarea name="browse_instructions" rows="3" cols="50">'. $hp->purify($this->browse_instructions, CODENDI_PURIFIER_CONVERT_HTML) .'</textarea>
              </td>
            </tr>
          </table>
          <p align="center"><input type="submit" value="'.$GLOBALS['Language']->getText('global','btn_submit').'"></p>
        </form>';

        $this->displayFooter($layout);
    }

    protected function displayAdminPermsHeader(Tracker_IDisplayTrackerLayout $layout, $title, $breadcrumbs) {
        $items = $this->getAdminItems();
        $breadcrumbs = array_merge(array(
                $items['editperms']
                ), $breadcrumbs);
        $this->displayAdminHeader($layout, $title, $breadcrumbs);
    }

    protected function getPermsItems() {
        return array(
                'tracker' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='.(int)$this->getId().'&amp;func=admin-perms-tracker',
                        'title'       => $GLOBALS['Language']->getText('plugin_tracker_include_type','manage_tracker_permissions'),
                        'description' => $GLOBALS['Language']->getText('plugin_tracker_include_type','define_manage_tracker_permissions')
                ),
                'fields' => array(
                        'url'         => TRACKER_BASE_URL.'/?tracker='.(int)$this->getId().'&amp;func=admin-perms-fields',
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

    public function displayAdminPermsTracker(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $items = $this->getPermsItems();
        $title = $items['tracker']['title'];
        $breadcrumbs = array(
                $items['tracker']
        );
        $this->displayAdminPermsHeader($layout, $title, $breadcrumbs);
        echo '<h2>'. $title .'</h2>';
        $hp = Codendi_HTMLPurifier::instance();

        $admin_permission     = 'PLUGIN_TRACKER_ADMIN';
        $full_permission      = 'PLUGIN_TRACKER_ACCESS_FULL';
        $assignee_permission  = 'PLUGIN_TRACKER_ACCESS_ASSIGNEE';
        $submitter_permission = 'PLUGIN_TRACKER_ACCESS_SUBMITTER';
        $none                 = 'PLUGIN_TRACKER_NONE';

        $html = '';

        //form
        $html .= '<form name="form_tracker_permissions" action="?tracker='.(int)$this->getId().'&amp;func=admin-perms-tracker" method="post">';
        $html .= '<div>';

        //intro
        $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'tracker_intro');

        //header
        $html .= html_build_list_table_top(array(
                $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'ugroup'),
                $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'permissions')));

        //body
        $ugroups_permissions = plugin_tracker_permission_get_tracker_ugroups_permissions($this->getGroupId(), $this->getId());
        ksort($ugroups_permissions);
        reset($ugroups_permissions);
        $i = 0;
        foreach($ugroups_permissions as $ugroup_permissions) {
            $ugroup      = $ugroup_permissions['ugroup'];
            $permissions = $ugroup_permissions['permissions'];
            
            $html .= '<tr class="'. util_get_alt_row_color($i++).'">';
            $html .= '<td>';
            $name  =  $hp->purify($ugroup['name'], CODENDI_PURIFIER_CONVERT_HTML) ;
            if (isset($ugroup['link'])) {
                $html .= '<a href="'.$ugroup['link'].'">';
                $html .= $name;
                $html .= '</a>';
            } else {
                $html .= $name;
            }
            $html .= '</td>';
            $html .= '<td>';

            $html .= '<select name="permissions_'. $ugroup['id'] .'">';
            $attributes_for_selected = 'selected="selected" style="background:#EEE;"'; //TODO: put style in stylesheet
            $html .= '<option value="100" '.(count($permissions) == 0 ? $attributes_for_selected : "").' >'. $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $none) .'</option>';
            $html .= '<option value="0" '.(isset($permissions[$full_permission]) ? $attributes_for_selected : "") .' >'. $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $full_permission) .'</option>';

            //We don't show specific access permissions for anonymous users and registered
            if ($ugroup['id'] != $GLOBALS['UGROUP_ANONYMOUS'] && $ugroup['id'] != $GLOBALS['UGROUP_REGISTERED']) {
                $html .= '<option value="1" '.(isset($permissions[$assignee_permission]) && !isset($permissions[$submitter_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $assignee_permission) .'</option>';
                $html .= '<option value="2" '.(!isset($permissions[$assignee_permission]) && isset($permissions[$submitter_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $submitter_permission) .'</option>';
                $html .= '<option value="3" '.(isset($permissions[$assignee_permission]) && isset($permissions[$submitter_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $assignee_permission .'_AND_'. $submitter_permission) .'</option>';
                $html .= '<option value="4" '.(isset($permissions[$admin_permission]) && isset($permissions[$admin_permission])?$attributes_for_selected:"")." >".$GLOBALS['Language']->getText('plugin_tracker_admin_permissions', $admin_permission) .'</option>';
            }
            $html .= '</select></td>';
            $html .= '</tr>';
        }
        //end of table
        $html .= '</table>';
        $html .= '<input type="submit" name="update" value="'. $GLOBALS['Language']->getText('project_admin_permissions','submit_perm') .'" />';

        $html .= '</div></form>';
        $html .= '<p>';
        $html .= $GLOBALS['Language']->getText('project_admin_permissions',
                'admins_create_modify_ug',
                array(
                '/project/admin/editugroup.php?func=create&group_id='.(int)$this->getGroupID(),
                '/project/admin/ugroup.php?group_id='.(int)$this->getGroupID()
                )
        );
        $html .= '</p>';
        echo $html;
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
                $html .= $part_permissions['values']['name'];
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
                $name .=  $hp->purify($second_part['name'], $group_first ? CODENDI_PURIFIER_DISABLED : CODENDI_PURIFIER_BASIC ) ;
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
                $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_permissions', 'ug_may_have_no_access', TRACKER_BASE_URL."/admin/?group_id=".(int)$this->getGroupID()."&atid=".(int)$this->getID()."&func=permissions&perm_type=tracker");
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
                "/project/admin/editugroup.php?func=create&group_id=".(int)$this->getGroupID(),
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
        $this->displayAdminHeader($layout, $title, $breadcrumbs);
    }

    public function displayAdminFormElements(Tracker_IDisplayTrackerLayout $layout, $request, $current_user) {
        $hp = Codendi_HTMLPurifier::instance();
        $items = $this->getAdminItems();
        $title = $items['editformElements']['title'];
        $this->displayAdminFormElementsHeader($layout, $title, array());

        echo '<h2>'. $title .'</h2>';
        echo '<form name="form1" method="POST" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->id .'&amp;func=admin-formElements">';
        

        echo '<table cellspacing="4" cellpadding="0"><tr valign="top"><td>';
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
        echo '</td><td>';

        echo $this->fetchAdminFormElements();

        echo '</td></tr></table>';

        echo '</form>';
        $this->displayFooter($layout);
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

        echo '<h2>'. $title . ' ' . help_button('TrackerV5ArtifactImport') . '</h2>';
        echo '<form name="form1" method="POST" enctype="multipart/form-data" action="'.TRACKER_BASE_URL.'/?tracker='. (int)$this->id .'&amp;func=admin-csvimport">';
        echo '<input type="file" name="csv_filename" size="50">';
        echo '<br>';
        echo '<span class="smaller"><em>'.$GLOBALS['Language']->getText('plugin_tracker_import','max_upload_size',formatByteToMb($GLOBALS['sys_max_size_upload'])).'</em></span>';
        echo '<br>';
        echo $GLOBALS['Language']->getText('plugin_tracker_admin_import','send_notifications');
        echo '<input type="checkbox" name="notify" value="ok" />';
        echo '<br>';
        echo '<input type="hidden" name="action" value="import_preview">';
        echo '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_import','submit_info').'">';
        echo '</form>';
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
        
        $html = '';
        
        $html .= '<strong>' . $GLOBALS['Language']->getText('plugin_tracker_artifact_masschange', 'changing_items', array(count($masschange_aids))) . '</strong>';
        $html .= '<p class="masschange_artifact_ids">';
        foreach ($masschange_aids as $art_id) {
            $html .= '<a href="'.TRACKER_BASE_URL.'/?aid='.$art_id.'">#' . $art_id . '</a> ';
        }
        $html .= '</p>';
        
        $html .= '<form id="masschange_form" enctype="multipart/form-data" action="" method="POST">';
        $html .= '<input type="hidden" name="func" value="update-masschange-aids">';
        foreach ( $masschange_aids as $aid ) {
            $html .= '<input type="hidden" name="masschange_aids[]" value="'.(int)$aid.'" />';
        }       
        $html .= $this->fetchFormElementsMasschange();
        
        // Follow-up comment
        $html .= '<b>'. $GLOBALS['Language']->getText('plugin_tracker_include_artifact', 'add_comment') .'</b><br />';
        $html .= '<textarea wrap="soft" rows="12" cols="80" name="artifact_masschange_followup_comment" id="artifact_masschange_followup_comment">'.$GLOBALS['Language']->getText('plugin_tracker_index', 'mass_change').'</textarea>';
        $html .= '<br />';
        
        // Send notification checkbox
        $html .= $GLOBALS['Language']->getText('plugin_tracker_admin_import','send_notifications');
        $html .= '<input type="checkbox" name="notify" value="ok" />';
        $html .= '<br />';
        
        $html .= '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_include_type', 'submit_mass_change').'"/>';
        $html .= '</form>';
        echo $html;

        $this->displayFooter($layout);
    }

    public function updateArtifactsMasschange($submitter , $masschange_aids, $masschange_data, $comment, $send_notifications) {
        //building data for update
        $fields_data  = array();
        foreach ( $masschange_data as $field_id => $data ) {
            //skip unchanged value
            if ( $data === $GLOBALS['Language']->getText('global','unchanged') ||
                    (is_array($data) && in_array($GLOBALS['Language']->getText('global','unchanged'), $data)) ) {
                continue;
            }
            $fields_data[$field_id] = $data;
        }
        $this->augmentDataFromRequest($fields_data);
        
        $not_updated_aids = array();
        foreach ( $masschange_aids as $aid ) {
            $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($aid);
            if ( !$artifact ) {
                $not_updated_aids[] = $aid;
                continue;
            }
            
            if ( !$artifact->createNewChangeset($fields_data, $comment, $submitter, $email='', $send_notifications) ) {
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
        $project = ProjectManager::instance()->getProject($this->group_id);
        $old_item_name = $this->getItemName();
        $old_name = $this->getName();
        $this->name                         = trim($request->getValidated('name', 'string', ''));
        $this->description                  = trim($request->getValidated('description', 'text', ''));
        $this->item_name                    = trim($request->getValidated('item_name', 'string', ''));
        $this->allow_copy                   = $request->getValidated('allow_copy') ? 1 : 0;
        $this->submit_instructions          = $request->getValidated('submit_instructions', 'text', '');
        $this->browse_instructions          = $request->getValidated('browse_instructions', 'text', '');
        $this->instantiate_for_new_projects = $request->getValidated('instantiate_for_new_projects') ? 1 : 0;

        if (!$this->name || !$this->description || !$this->item_name) {
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
     * Add an artefact in the tracker
     *
     * @param Tracker_IDisplayTrackerLayout  $layout
     * @param Codendi_Request                $request
     * @param User                           $user
     *
     * @return Tracker_Artifact the new artifact
     */
    public function createArtifact(Tracker_IDisplayTrackerLayout $layout, $request, $user) {
        $email = null;
        if ($user->isAnonymous()) {
            $email = $request->get('email');
        }

        $fields_data = $request->get('artifact');
        $this->augmentDataFromRequest($fields_data);

        return Tracker_ArtifactFactory::instance()->createArtifact($this, $fields_data, $user, $email);
    }

    /**
     * Validate the format of the item name
     * @param string $itemname
     * @return boolean
     */
    public function itemNameIsValid($item_name) {
        return eregi("^[a-zA-Z0-9_]+$",$item_name);
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
    public function getNotificationsManager() {
        return new Tracker_NotificationsManager($this);
    }

    /**
     * @return Tracker_DateReminderManager
     */
    public function getDateReminderManager() {
        return new Tracker_DateReminderManager($this);
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
     * @return Tracker_RulesManager
     */
    public function getRulesManager() {
        return new Tracker_RulesManager($this);
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
        if (!is_a($user, 'User')) {
            $um = UserManager::instance();
            if (!$user) {
                $user = $um->getCurrentUser();
            } else {
                $user = $um->getUserById((int)$user);
            }
        }

        // Super-user has all rights...
        if ($user->isSuperUser()) {
            return true;
        } else {
            //... so has tracker admin
            if ($this->userIsAdmin($user)) {
                return true;
            } else {
                foreach ($this->getPermissions() as $ugroup_id => $permission_types) {
                    foreach ($permission_types as $permission_type) {
                        if ($user->isMemberOfUGroup($ugroup_id, $this->getGroupId())) {
                            return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    protected $cache_permissions = null;
    /**
     * get the permissions for this tracker
     *
     * @return array
     */
    public function getPermissions() {
        if (!$this->cache_permissions) {
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

    public function permission_db_authorized_ugroups( $permission_type ) {
        if ( ! isset($this->cached_permission_authorized_ugroups)) {
            $this->cached_permission_authorized_ugroups = array();
            $res = permission_db_authorized_ugroups($permission_type, $this->getId());
            if ( db_numrows($res) > 0 ) { 
                while ( $row = db_fetch_array($res) ) {
                    $this->cached_permission_authorized_ugroups[] = $row;
                }
            }
        }
        return $this->cached_permission_authorized_ugroups;
    }
    
    /**
     * See if the user's perms are >= 2 or project admin.
     *
     * @param int $user Either the user ID or the User object to test, or current user if false
     *
     * @return boolean True if the user is tracker admin, false otherwise
     */
    function userIsAdmin($user = false) {
        if (!is_a($user, 'User')) {
            $um = UserManager::instance();
            if (!$user) {
                $user = $um->getCurrentUser();
            } else {
                $user = $um->getUserById((int)$user);
            }
        }
        if ($user->isSuperUser() || $user->isMember($this->getGroupId(), 'A')) {
            return true;
        }
        $permissions = $this->getPermissions();
        foreach ($permissions as $ugroup_id => $permission_types) {
            foreach ( $permission_types as $permission_type ) {
                if($permission_type == 'PLUGIN_TRACKER_ADMIN') {
                    if ($user->isMemberOfUGroup($ugroup_id, $this->getGroupId())) {
                        return true;
                    }
                }
            }
        }
        return false;
    }

    /**
     * Check if user has permission to submit artifact or not
     *
     * @param User $user The user to test (current user if not defined)
     *
     * @return boolean true if user has persission to submit artifacts, false otherwise
     */
    function userCanSubmitArtifact($user = false) {
        if (!is_a($user, 'User')) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }


        // TODO : return the real value


        return true;
    }

    /**
     * Check if user has permission to delete a tracker or not
     *
     * @param User $user The user to test (current user if not defined)
     *
     * @return boolean true if user has persission to delete trackers, false otherwise
     */
    function userCanDeleteTracker($user = false) {
        if (!is_a($user, 'User')) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        return $user->isSuperUser() || $user->isMember($this->getGroupId(), 'A');
    }
    
    /**
     * Check if user has full access to a tracker or not
     *
     * @param User $user The user to test (current user if not defined)
     *
     * @return boolean true if user has full access to tracker, false otherwise
     */
    function userHasFullAccess($user = false) {
        if (!is_a($user, 'User')) {
            $um = UserManager::instance();
            $user = $um->getCurrentUser();
        }
        if ($user->isSuperUser() || $user->isMember($this->getGroupId(), 'A')) {
            return true;
        }  else {
            $permissions = $this->getPermissions();
            foreach ($permissions as $ugroup_id => $permission_types) {
                foreach ( $permission_types as $permission_type ) {
                    if($permission_type == 'PLUGIN_TRACKER_ACCESS_FULL' || $permission_type == 'PLUGIN_TRACKER_ADMIN') {
                        if ($user->isMemberOfUGroup($ugroup_id, $this->getGroupId())) {
                                return true;
                        }
                    }
                }
            }
        }
        return false;
    }

    /**
     * Exports the tracker to an XML file.
     *
     * @return void
     */
    public function exportToXML() {
        // create a SimpleXMLElement corresponding to this tracker
        // sets encoding to UTF-8
        $xmlElem = new SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?>
                                         <tracker xmlns="http://codendi.org/tracker" />');
        // if old ids are important, modify code here
        if (false) {
            $xmlElem->addAttribute('id', $this->id);
            $xmlElem->addAttribute('group_id', $this->group_id);
        }

        // only add attributes which are different from the default value
        if ($this->allow_copy) {
            $xmlElem->addAttribute('allow_copy', $this->allow_copy);
        }
        if ($this->instantiate_for_new_projects) {
            $xmlElem->addAttribute('instantiate_for_new_projects', $this->instantiate_for_new_projects);
        }
        if ($this->stop_notification) {
            $xmlElem->addAttribute('stop_notification', $this->stop_notification);
        }

        // these will not be used at the import
        $xmlElem->addChild('name', $this->name);
        $xmlElem->addChild('item_name', $this->item_name);
        $xmlElem->addChild('description', $this->description);

        // add only if not empty
        if ($this->submit_instructions) {
            $n = $xmlElem->addChild('submit_instructions');
            $node = dom_import_simplexml($n);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($this->submit_instructions));
        }
        if ($this->browse_instructions) {
            $n = $xmlElem->addChild('browse_instructions');
            $node = dom_import_simplexml($n);
            $no = $node->ownerDocument;
            $node->appendChild($no->createCDATASection($this->browse_instructions));
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
        $xmlMapping = array();
        $i = 0;        
        if ($formelements = $this->getAllFormElements()) {
            foreach ($formelements as $formElement) {
                $grandchild = $child->addChild('formElement');
                $i++;
                $formElement->exportToXML($grandchild, $xmlMapping, $i);
            }
        }

        // semantic
        $tsm = $this->getTrackerSemanticManager();
        $child = $xmlElem->addChild('semantics');
        $tsm->exportToXML($child, $xmlMapping);

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
        $node_perms = $xmlElem->addChild('permissions');
        // tracker permissions
        if ($permissions = $this->getPermissions()) {
            foreach ($permissions as $ugroup_id => $permission_types) {
                if (($ugroup = array_search($ugroup_id, $GLOBALS['UGROUPS'])) !== false && $ugroup_id < 100) {
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
        if ($formelements = $this->getFormElementFactory()->getAllFormElementsForTracker($this)) {
            foreach ($formelements as $formelement) {
                if ($permissions = $formelement->getPermissions()) {
                    foreach ($permissions as $ugroup_id => $permission_types) {
                        if (($ugroup = array_search($ugroup_id, $GLOBALS['UGROUPS'])) !== false && $ugroup_id < 100 && $formelement->isUsed()) {
                            foreach ($permission_types as $permission_type) {
                                $node_perm = $node_perms->addChild('permission');
                                $node_perm->addAttribute('scope', 'field');
                                $node_perm->addAttribute('REF', array_search($formelement->getId(), $xmlMapping));
                                $node_perm->addAttribute('ugroup', $ugroup);
                                $node_perm->addAttribute('type', $permission_type);
                                unset($node_perm);
                            }
                        }
                    }
                }
            }
        }
        return $xmlElem;
    }

    /**
     * Send the xml to the client
     *
     * @param SimpleXMLElement $xmlElem The xml
     */
    protected function sendXML(SimpleXMLElement $xmlElem) {
        //force file transfer
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="Tracker_'.$this->item_name.'.xml"');
        header('Content-Type: text/xml');
        $dom = dom_import_simplexml($xmlElem)->ownerDocument;
        $dom->formatOutput = true;
        echo $dom->saveXML();
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
    private function _getCSVFields($header) {
        $fef = $this->getFormElementFactory();
        $fields = array();
        foreach($header as $field_name) {
            if ($field_name != 'aid') {
                $field = $fef->getUsedFieldByName($this->getId(), $field_name);
                if ($field) {
                    $fields[] = $field;
                } else {
                    return false;
                }
            } else {
                $fields[] = 'aid';
            }
        }
        return $fields;
    }
    
    private function _getCSVSeparator($current_user) {
        if ( ! $current_user || ! is_a($current_user, 'User')) {
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
        if ( ! $current_user || ! is_a($current_user, 'User')) {
            $current_user = UserManager::instance()->getCurrentUser();
        }
        $dateformat_csv_export_pref = $current_user->getPreference('user_csv_dateformat');
        if ($dateformat_csv_export_pref === false) {
            $dateformat_csv_export_pref = "month_day_year"; // by default, mm/dd/yyyy
        }
        return $dateformat_csv_export_pref;
    }
    
    protected function displayImportPreview(Tracker_IDisplayTrackerLayout $layout, $request, $current_user, $session) {
        $hp = Codendi_HTMLPurifier::instance();
        
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
                        $lines[] = $line;
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
                            $html_table .= '<table>';
                            $html_table .=  '<thead>';
                            $header = array_shift($lines);
                            $html_table .=  '<tr class="boxtable">';
                            $html_table .=  '<th class="boxtitle"></th>';
                            $fields = $this->_getCSVFields($header);
                            
                            foreach ($header as $field_name) {
                                $html_table .=  '<th class="boxtitle tracker_report_table_column">';
                                $html_table .=  $field_name;
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
                                        if ($data_cell) {
                                            $mode = 'update';
                                        }
                                        $displayed_data = $data_cell;
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
                                echo '<input type="submit" value="'.$GLOBALS['Language']->getText('plugin_tracker_import','import_new_hdr').'">';
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
    public function hasError($header_line, $lines) {
        $has_error = false;
        $fef = $this->getFormElementFactory();
        $aid_key = array_search('aid', $header_line);
        $af = $this->getTrackerArtifactFactory();
        $artifact = null;
        
        foreach ($lines as $cpt_line => $line) {
            $data = array();
            foreach ($header_line as $idx => $field_name) {
                //Fields other than aid
                if ($field_name != 'aid') {
                    $field = $fef->getUsedFieldByName($this->getId(), $field_name);
                    if (! $field) {
                        // a field is unknown
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_field', array($field_name)));
                        $has_error = true;
                    } else {
                        //check if value is ok
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
                        if ($line[$idx]!=''){
                            
                            $data[$field->getId()] = $field->getFieldData($line[$idx]);
                            
                            if ($data[$field->getId()] === null) {
                                $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_value', array($line[$idx], $field_name)));
                                $has_error = true;
                            }
                        }
                    }
                } else {
                    //Field is aid : we check if the artifact id exists
                    if ($this->hasUnknownAid($header_line, $lines)) {
                        $has_error = true;
                    }
                }
            }
            if ($artifact) {
                if (! $ok = $artifact->validateFields($data)) {
                     $has_error = true;
                }
            }
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
    protected function importFromCSV(Tracker_IDisplayTrackerLayout $layout, $request, $current_user, $header, $lines) {
        $is_error = false;
        if (count($lines) >= 1) {
            if ($request->exist('notify') && $request->get('notify') == 'ok') {
                $send_notifications = true;
            } else {
                $send_notifications = false;
            }
            $fields = $this->_getCSVFields($header);
            $af = Tracker_ArtifactFactory::instance();
            $nb_lines = 0;
            $nb_artifact_creation = 0;
            $nb_artifact_update = 0;
            foreach ($lines as $line_number => $data_line) {
                $mode = 'creation';
                $fields_data = array();
                foreach ($data_line as $idx => $data_cell) {
                    if ($fields[$idx] && is_a($fields[$idx], 'Tracker_FormElement_Field')) {
                        $field = $fields[$idx];
                        $fields_data[$field->getId()] = $field->getFieldData($data_cell);
                    } else  {
                        // else: this cell is an 'aid' cell
                        if ($data_cell) {
                            $mode = 'update';
                            $artifact_id = (int) $data_cell;
                        } else {
                            $artifact_id = 0;
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
                    // $idx is the artifact id
                    $artifact = $af->getArtifactById($artifact_id);
                    if ($artifact) {
                        $followup_comment = '';
                        if ($artifact->createNewChangeset($fields_data, $followup_comment, $current_user, null, $send_notifications)) {
                            $nb_artifact_update++;
                        } else {
                            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unable_to_update_artifact', array($artifact_id)));
                            $is_error = true;
                        }
                    } else {
                        $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_admin_import', 'unknown_artifact', array($artifact_id)));
                        $is_error = true;
                    }
                }
            }
            if ( ! $is_error) {
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
     * @return Tracker_ArtifactFactory
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
        $recipients = array();
        $nm = new Tracker_NotificationsManager($this);
        $notifs = $nm->getGlobalNotifications();
        foreach ( $notifs as $id=>$notif ) {
            $recipients[$id] = array( 'recipients'=>$notif->getAddresses(true), 'on_updates'=> $notif->isAllUpdates(), 'check_permissions'=> $notif->isCheckPermissions()  );
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
     * @return Tracker_FormElement_Text the title field, or null if not defined
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
     * @return Tracker_FormElement_List the status field, or null if not defined
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
     * @return Tracker_FormElement_List the contributor field, or null if not defined
     */
    public function getContributorField() {
        $contributor_field = Tracker_Semantic_Contributor::load($this)->getField();
        if ($contributor_field) {
            return $contributor_field;
        } else {
            return null;
        }
    }
    
    /**
     *	existUser - check if a user is already in the project permissions
     *
     *	@param	int		user_id of the new user.
     *	@return boolean	success.
     */
    function existUser($id) {
        if (!$id) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_canned','missing_param'));
            return false;
        }
        $perm_dao = new Tracker_PermDao();
        if ($perm_dao->searchByUserIdAndTrackerId($id, $this->getId())) {
            return true;
        } else {
            return false;
        }
    }


    /**
     *	updateUser - update a user's permissions.
     *
     *	@param	int		user_id of the user to update.
     *	@param	int		(1) tech only, (2) admin & tech (3) admin only.
     *	@return boolean	success.
     */
    function updateUser($id, $perm_level) {
        if (!$this->userIsAdmin()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_canned','perm_denied'));
            return false;
        }
        if (!$id) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_canned','missing_param'));
            return false;
        }

        $perm_dao = new Tracker_PermDao();

        $row = $perm_dao->searchByUserIdAndTrackerId($id, $this->getId())->getRow();
        
        if ($row) {
		    if ($perm_dao->updateUser($id, $perm_level, $this->getId())) {
                return true;
            } else {
                return false;
            }
        } else if ($perm_dao->createUser($id, $perm_level, $this->getId())) {

            return true;
                
        } else {
            return false;
        }
    }

    /**
     *	addUser - add a user to this ArtifactType - depends on UNIQUE INDEX preventing duplicates.
     *
     *	@param	int		user_id of the new user.
     *  @param  value: the value permission
     *
     *	@return boolean	success.
     */
    function addUser($id, $value) {
        global $Language;

        if (!$this->userIsAdmin()) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_canned','perm_denied'));
            return false;
        }
        if (!$id) {
            $GLOBALS['Response']->addFeedback('error', $GLOBALS['Language']->getText('plugin_tracker_common_canned','missing_param'));
            return false;
        }
        $perm_dao = new Tracker_PermDao();
        if ($perm_dao->createUser($id, $value, $this->getId())) {
            return true;
        } else {
            return false;
        }
    }

    /**
     *	deleteUser - delete a user's permissions.
     *
     *	@param	int		user_id of the user who's permissions to delete.
     *	@return boolean	success.
     */
    function deleteUser($id) {
        global $Language;

        if (!$this->userIsAdmin()) {
            $this->setError($Language->getText('plugin_tracker_common_canned','perm_denied'));
            return false;
        }
        if (!$id) {
            $this->setError($Language->getText('plugin_tracker_common_canned','missing_param'));
            return false;
        }
        $perm_dao = new Tracker_PermDao();
        if ($perm_dao->deleteUser($id, $this->getId())) {
            return true;
        } else {
            return false;
        }      
    }
    
    public function setFormElementFactory(Tracker_FormElementFactory $factory) {
        $this->formElementFactory = $factory;
    }
    
    public function setSharedFormElementFactory(Tracker_SharedFormElementFactory $factory) {
        $this->sharedFormElementFactory = $factory;
    }

    public function redirectUrlAfterArtifactSubmission($request, $tracker_id, $artifact_id) {
        $stay      = $request->get('submit_and_stay');
        $continue  = $request->get('submit_and_continue');
        
        $redirect_params = $this->calculateRedirectParams($tracker_id, $artifact_id, $stay, $continue);
        EventManager::instance()->processEvent(
            TRACKER_EVENT_BUILD_ARTIFACT_FORM_ACTION,
            array(
                'request'          => $request,
                'query_parameters' => &$redirect_params,
            )
        );
        return TRACKER_BASE_URL.'/?'.  http_build_query($redirect_params);
    }

    private function calculateRedirectParams($tracker_id, $artifact_id, $stay, $continue) {
        $redirect_params = array();
        $redirect_params['tracker']       = $tracker_id;
        if ($continue) {
            $redirect_params['func']      = 'new-artifact';
        }
        if ($stay) {
            $redirect_params['aid']       = $artifact_id;
        }
        return array_filter($redirect_params);
    }

    /**
     * Return the hierarchy the tracker belongs to
     *
     * @return Tracker_Hierarchy
     */
    public function getHierarchy() {
        $hierarchy_factory = new Tracker_HierarchyFactory(new Tracker_Hierarchy_Dao(), $this->getTrackerFactory(), $this->getTrackerArtifactFactory());
        return $hierarchy_factory->getHierarchy(array($this->getId()));
    }
}

?>
