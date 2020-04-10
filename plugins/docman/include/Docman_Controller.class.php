<?php
/**
 * Copyright (c) Enalean, 2015-Present. All rights reserved
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
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

use Tuleap\Docman\DestinationCloneItem;
use Tuleap\Docman\Log\LogEventAdder;
use Tuleap\Docman\Notifications\NotificationBuilders;
use Tuleap\Docman\Notifications\NotificationEventAdder;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO;
use Tuleap\Docman\Upload\Document\DocumentOngoingUploadRetriever;
use Tuleap\Docman\Upload\Version\DocumentOnGoingVersionToUploadDAO;
use Tuleap\Docman\Upload\Version\VersionOngoingUploadRetriever;
use Tuleap\User\InvalidEntryInAutocompleterCollection;
use Tuleap\User\RequestFromAutocompleter;

class Docman_Controller extends Controler
{
    // variables
    /**
     * @var HTTPRequest
     */
    public $request;
    public $user;
    public $groupId;
    public $themePath;
    public $plugin;
    public $logger;
    public $feedback;
    public $user_can_admin;
    public $reportId;
    public $hierarchy;

    /**
     * @var Docman_NotificationsManager
     */
    public $notificationsManager;
    /**
     * @var Docman_NotificationsManager_Add
     */
    public $notificationsManager_Add;
    /**
     * @var Docman_NotificationsManager_Delete
     */
    public $notificationsManager_Delete;
    /**
     * @var Docman_NotificationsManager_Move
     */
    public $notificationsManager_Move;
    /**
     * @var Docman_NotificationsManager_Subscribers
     */
    public $notificationsManager_Subscribers;
    /**
     * @var string
     */
    public $pluginPath;

    public function __construct($plugin, $pluginPath, $themePath, $request)
    {
        $this->request        = $request;
        $this->user           = null;
        $this->groupId        = null;
        $this->user_can_admin = null;
        $this->pluginPath     = $pluginPath;
        $this->themePath      = $themePath;
        $this->plugin         = $plugin;
        $this->view           = null;
        $this->reportId       = null;
        $this->hierarchy      = array();

        $this->feedback = new \Tuleap\Docman\ResponseFeedbackWrapper();

        $event_manager = $this->_getEventManager();

        $this->logger    = new Docman_Log();
        $log_event_adder = new LogEventAdder($event_manager, $this->logger);
        $log_event_adder->addLogEventManagement();

        $notifications_builders = new NotificationBuilders($this->feedback, $this->getProject());
        $this->notificationsManager = $notifications_builders->buildNotificationManager();
        $this->notificationsManager_Add  = $notifications_builders->buildNotificationManagerAdd();
        $this->notificationsManager_Delete = $notifications_builders->buildNotificationManagerDelete();
        $this->notificationsManager_Move = $notifications_builders->buildNotificationManagerMove();
        $this->notificationsManager_Subscribers = $notifications_builders->buildNotificationManagerSubsribers();

        $notification_event_adder = new NotificationEventAdder(
            $event_manager,
            $this->notificationsManager,
            $this->notificationsManager_Add,
            $this->notificationsManager_Delete,
            $this->notificationsManager_Move,
            $this->notificationsManager_Subscribers
        );
        $notification_event_adder->addNotificationManagement();
    }

    // Franlky, this is not at all the best place to do this.
    public function installDocman($ugroupsMapping, $group_id = false)
    {
        $_gid = $group_id ? $group_id : (int) $this->request->get('group_id');

        $item_factory = $this->getItemFactory();
        $root = $item_factory->getRoot($_gid);
        if ($root) {
            // Docman already install for this project.
            return false;
        } else {
            $pm = ProjectManager::instance();
            $project = $pm->getProject($_gid);
            $tmplGroupId = (int) $project->getTemplate();
            $this->_cloneDocman($tmplGroupId, $project, $ugroupsMapping);
        }
    }

    public function _cloneDocman($srcGroupId, Project $destination_project, $ugroupsMapping)
    {
        $user       = $this->getUser();
        $dstGroupId = $destination_project->getID();

        // Clone Docman permissions
        $dPm = $this->_getPermissionsManager();
        if ($ugroupsMapping === false) {
            $dPm->setDefaultDocmanPermissions($dstGroupId);
        } else {
            $dPm->cloneDocmanPermissions($srcGroupId, $dstGroupId);
        }

        // Clone Metadata definitions
        $metadataMapping = array();
        $mdFactory = new Docman_MetadataFactory($srcGroupId);
        $mdFactory->cloneMetadata($dstGroupId, $metadataMapping);

        // Clone Items, Item's permissions and metadata values
        $itemFactory     = $this->getItemFactory();
        $dataRoot        = $this->getProperty('docman_root');
        $src_root_folder = $itemFactory->getRoot($srcGroupId);
        if ($src_root_folder === null) {
            $itemFactory->createRoot($dstGroupId, 'roottitle_lbl_key');
            $itemMapping = [];
        } else {
            $itemMapping = $itemFactory->cloneItems(
                $user,
                $metadataMapping,
                $ugroupsMapping,
                $dataRoot,
                $src_root_folder,
                DestinationCloneItem::fromDestinationProject(
                    $itemFactory,
                    $destination_project,
                    ProjectManager::instance(),
                    new Docman_LinkVersionFactory()
                )
            );
        }

        // Clone reports
        $reportFactory = new Docman_ReportFactory($srcGroupId);
        $reportFactory->copy($dstGroupId, $metadataMapping, $user, false, $itemMapping);
    }

    public function getLogger()
    {
        return $this->logger;
    }
    public function logsDaily($params)
    {
        $this->logger->logsDaily($params);
    }

    public function _getEventManager()
    {
        return EventManager::instance();
    }

    /**
     * Obtain instance of Docman_PermissionsManager
     *
     * @return Docman_PermissionsManager
     */
    private function _getPermissionsManager()
    {
        return Docman_PermissionsManager::instance($this->getGroupId());
    }

    /**
     * @return PFUser
     */
    public function getUser()
    {
        if ($this->user === null) {
            $um = UserManager::instance();
            $this->user = $um->getCurrentUser();
        }
        return $this->user;
    }

    /***************** PERMISSIONS ************************/
    public function userCanRead($item_id)
    {
        $dPm  = $this->_getPermissionsManager();
        $user = $this->getUser();
        return $dPm->userCanRead($user, $item_id);
    }
    public function userCanWrite($item_id)
    {
        $dPm  = $this->_getPermissionsManager();
        $user = $this->getUser();
        return $dPm->userCanWrite($user, $item_id);
    }
    public function userCanManage($item_id)
    {
        $dPm  = $this->_getPermissionsManager();
        $user = $this->getUser();
        return $dPm->userCanManage($user, $item_id);
    }
    public function userCanAdmin()
    {
        $dPm  = $this->_getPermissionsManager();
        $user = $this->getUser();
        return $dPm->userCanAdmin($user);
    }

    public function setRequest($request)
    {
        $this->request = $request;
    }

    public function getGroupId()
    {
        if ($this->groupId === null) {
            $_gid = (int) $this->request->get('group_id');
            if ($_gid > 0) {
                $this->groupId = $_gid;
            }
        }
        return $this->groupId;
    }

    /**
     * @return string
     */
    public function getDefaultUrl()
    {
        $_gid = $this->getGroupId();
        return $this->pluginPath . '/?group_id=' . urlencode((string) $_gid);
    }

    public function getThemePath()
    {
        return $this->themePath;
    }

    public function setReportId($id)
    {
        $this->reportId = $id;
    }
    public function getReportId()
    {
        return $this->reportId;
    }

    public function _initReport($item)
    {
        $reportFactory = new Docman_ReportFactory($this->getGroupId());

        if ($this->reportId === null && $this->request->exist('report_id')) {
            $this->reportId = (int) $this->request->get('report_id');
        }
        $report = $reportFactory->get($this->reportId, $this->request, $item, $this->feedback);

        $this->_viewParams['filter'] = $report;
    }

    public function getValueInArrays($key, $array1, $array2)
    {
        $value = null;
        if (isset($array1[$key])) {
            $value = $array1[$key];
        } elseif (isset($array2[$key])) {
            $value = $array2[$key];
        }
        return $value;
    }

    public function setMetadataValuesFromUserInput(&$item, $itemArray, $metadataArray)
    {
        $mdvFactory = new Docman_MetadataValueFactory($this->groupId);
        $mdFactory = new Docman_MetadataFactory($this->groupId);

        $mdIter = $item->getMetadataIterator();
        $mdIter->rewind();
        while ($mdIter->valid()) {
            $md = $mdIter->current();

            $value = $this->getValueInArrays($md->getLabel(), $itemArray, $metadataArray);
            if ($value !== null) {
                $mdv = $mdvFactory->newMetadataValue($item->getId(), $md->getId(), $md->getType(), $value);
                $val = $mdv->getValue();
                $mdvFactory->validateInput($md, $val);
                $md->setValue($val);
                // Take care to update hardcoded values too.
                if ($mdFactory->isHardCodedMetadata($md->getLabel())) {
                    $item->updateHardCodedMetadata($md);
                }
            }
            $mdIter->next();
        }
    }

    public function createItemFromUserInput()
    {
        $new_item = null;
        if ($this->request->exist('item')) {
            $item_factory = $this->getItemFactory();
            $mdFactory    = new Docman_MetadataFactory($this->_viewParams['group_id']);

            $i = $this->request->get('item');
            $new_item = $item_factory->getItemFromRow($i);
            $new_item->setGroupId($this->_viewParams['group_id']);
            // Build metadata list (from db) ...
            $mdFactory->appendItemMetadataList($new_item);
            // ... and set the value (from user input)
            $this->setMetadataValuesFromUserInput(
                $new_item,
                $i,
                $this->request->get('metadata')
            );
            if ($i['item_type'] == PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE) {
                $tmp_path = tempnam($GLOBALS['tmp_dir'], 'embedded_file');
                $f = fopen($tmp_path, 'w');
                fwrite($f, $this->request->get('content'));
                fclose($f);
                $v = new Docman_Version();
                $v->setPath($tmp_path);
                $new_item->setCurrentVersion($v);
            }
        }
        return $new_item;
    }

    public function updateMetadataFromUserInput(&$item)
    {
        $this->setMetadataValuesFromUserInput(
            $item,
            $this->request->get('item'),
            $this->request->get('metadata')
        );
    }

    public function updateItemFromUserInput(&$item)
    {
        if ($this->request->exist('item')) {
            $i           = $this->request->get('item');
            $itemFactory = $this->getItemFactory();
            switch ($itemFactory->getItemTypeForItem($item)) {
                case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                    $item->setPagename($i['wiki_page']);
                    break;
                case PLUGIN_DOCMAN_ITEM_TYPE_LINK:
                    $item->setUrl($i['link_url']);
                    break;
            }
        }
    }

    public function request()
    {
        if (
            $this->request->exist('action')
            && ($this->request->get('action') == 'plugin_docman_approval_reviewer'
                || $this->request->get('action') == 'plugin_docman_approval_requester'
                )
        ) {
            if ($this->request->get('hide')) {
                user_set_preference('hide_' . $this->request->get('action'), 1);
            } else {
                user_del_preference('hide_' . $this->request->get('action'));
            }
            exit;
        }
        if (!$this->request->exist('group_id')) {
            $this->feedback->log('error', 'Project is missing.');
            $this->_setView('Error');
        } else {
            $_groupId = (int) $this->request->get('group_id');
            $pm = ProjectManager::instance();
            $project = $pm->getProject($_groupId);
            if ($project == false) {
                $this->feedback->log('error', 'Project is missing.');
                $this->_setView('Error');
                return;
            }

            //token for redirection
            $tok = new Docman_Token();

            $this->_viewParams['docman']      = $this;
            $this->_viewParams['user']        = $this->getUser();
            $this->_viewParams['token']       = $tok->getToken();
            $this->_viewParams['default_url'] = $this->getDefaultUrl();
            $this->_viewParams['theme_path']  = $this->getThemePath();
            $this->_viewParams['group_id']    = (int) $this->request->get('group_id');
            if ($this->request->exist('version_number')) {
                $this->_viewParams['version_number'] = (int) $this->request->get('version_number');
            }

            if ($this->request->exist('section')) {
                $this->_viewParams['section'] = $this->request->get('section');
            } elseif ($this->request->get('action') == 'permissions') {
                $this->_viewParams['section'] = 'permissions';
            }
            $view = $this->request->exist('action') ? $this->request->get('action') : 'show';
            $this->_viewParams['action'] = $view;

            // Start is used by Table view (like LIMIT start,offset)
            if ($this->request->exist('start')) {
                $this->_viewParams['start']       = (int) $this->request->get('start');
            }

            if ($this->request->exist('pv')) {
                $this->_viewParams['pv']       = (int) $this->request->get('pv');
            }

            if ($this->request->exist('report')) {
                $this->_viewParams['report'] = $this->request->get('report');
                $views                       = Docman_View_Browse::getDefaultViews();
                $validator                   = new Valid_WhiteList('report', $views);
                $views_keys                  = array_keys($views);
                $default_view                = $views[$views_keys[0]];
                $this->_viewParams['report'] = $this->request->getValidated('report', $validator, $default_view);
            }

            $item_factory = $this->getItemFactory();
            $root         = $item_factory->getRoot($this->request->get('group_id'));
            if (!$root) {
                // Install
                $_gid = (int) $this->request->get('group_id');

                $pm = ProjectManager::instance();
                $project = $pm->getProject($_gid);
                $tmplGroupId = (int) $project->getTemplate();
                $this->_cloneDocman($tmplGroupId, $project, false);
                if (!$item_factory->getRoot($_gid)) {
                    $item_factory->createRoot($_gid, 'roottitle_lbl_key');
                }
                $this->_viewParams['redirect_to'] = $_SERVER['REQUEST_URI'];
                $this->view = 'Redirect';
            } else {
                $id = $this->request->get('id');
                if (!$id && $this->request->exist('item')) {
                    $i = $this->request->get('item');
                    if (isset($i['id'])) {
                        $id = $i['id'];
                    }
                }
                if ($id) {
                    $item = $item_factory->getItemFromDb($id);

                    if (!$item) {
                        $this->feedback->log('error', dgettext('tuleap-docman', 'Unable to retrieve item. Perhaps it was removed.'));
                        $this->_setView('DocmanError');
                    }
                } else {
                    $item = $root;
                }
                if ($item) {
                    // Load report
                    // If the item (folder) defined in the report is not the
                    // same than the current one, replace it.
                    $this->_initReport($item);
                    if (
                        $this->_viewParams['filter'] !== null
                        && $this->_viewParams['filter']->getItemId() !== null
                        && $this->_viewParams['filter']->getItemId() != $item->getId()
                    ) {
                        $reportItem = $item_factory->getItemFromDb($this->_viewParams['filter']->getItemId());
                        // If item defined in the report exists, use it
                        // otherwise raise an error
                        if (!$reportItem) {
                            $this->feedback->log('warning', dgettext('tuleap-docman', 'The folder associated to this report no longer exists.'));
                        } else {
                            unset($item);
                            $item = $reportItem;
                        }
                    }

                    if ($this->request->get('action') == 'ajax_reference_tooltip') {
                        $this->groupId = $item->getGroupId();
                    }
                    if ($item->getGroupId() != $this->getGroupId()) {
                        $pm = ProjectManager::instance();
                        $g = $pm->getProject($this->getGroupId());
                        $this->_set_doesnot_belong_to_project_error($item, $g);
                    } else {
                        $user = $this->getUser();
                        $dpm = $this->_getPermissionsManager();
                        $can_read = $dpm->userCanAccess($user, $item->getId());
                        $folder_or_document = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                        if (!$can_read) {
                            if ($this->request->get('action') == 'ajax_reference_tooltip') {
                                $this->_setView('AjaxReferenceTooltipError');
                            } else {
                                $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to view this item.'));
                                $this->_setView('PermissionDeniedError');
                            }
                        } else {
                            $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                            $mdFactory->appendItemMetadataList($item);

                            $get_show_view             = new Docman_View_GetShowViewVisitor();
                            $this->_viewParams['item'] = $item;
                            if (strpos($view, 'admin') === 0 && !$this->userCanAdmin()) {
                                $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to administrate the document manager.'));
                                $this->view = $item->accept($get_show_view, $this->request->get('report'));
                            } else {
                                if ($item->isObsolete()) {
                                    $this->feedback->log('warning', dgettext('tuleap-docman', 'The file is obsolete and no longer available in standard views (Tree, Table, ...)'));
                                }
                                $this->_dispatch($view, $item, $root, $get_show_view);
                            }
                        }
                    }
                }
            }
        }
    }

    public function _dispatch($view, $item, $root, $get_show_view)
    {
        \Tuleap\Project\ServiceInstrumentation::increment('docman');
        $item_factory = $this->getItemFactory();
        $user         = $this->getUser();
        $dpm          = $this->_getPermissionsManager();

        switch ($view) {
            case 'show':
                if ($item->isObsolete()) {
                    if (!$this->userCanAdmin($item->getId())) {
                        // redirect to details view
                        $this->view = 'Details';
                        break;
                    }
                }
                $this->view = $item->accept($get_show_view, $this->request->get('report'));
                break;
            case 'expandFolder':
                $this->action = 'expandFolder';
                if ($this->request->get('view') == 'ulsubfolder') {
                    $this->view = 'RawTree';
                } else {
                    $this->_viewParams['item'] = $root;
                    $this->view                = 'Tree';
                }
                break;
            case 'getRootFolder':
                $this->_viewParams['action_result'] = $root->getId();
                $this->_setView('getRootFolder');
                break;
            case 'collapseFolder':
                $this->action              = 'collapseFolder';
                $this->_viewParams['item'] = $root;
                $this->view                = 'Tree';
                break;
            case 'admin_set_permissions':
                $this->action = $view;
                $this->view   = 'Admin_Permissions';
                break;
            case 'admin_change_view':
                $this->action = $view;
                $this->_viewParams['default_url_params'] = array('action'  => 'admin_view',
                                                             'id'      => $item->getParentId());
                $this->view = 'RedirectAfterCrud';
                break;
            case 'admin':
            case 'details':
                $this->view = ucfirst($view);
                break;
            case 'admin_view':
                $this->view = 'Admin_View';
                break;
            case 'admin_permissions':
                $this->view = 'Admin_Permissions';
                break;
            case 'admin_metadata':
                $this->view = 'Admin_Metadata';
                $mdFactory  = new Docman_MetadataFactory($this->_viewParams['group_id']);
                $mdIter     = $mdFactory->getMetadataForGroup();
                $this->_viewParams['mdIter'] = $mdIter;
                break;
            case 'admin_md_details':
                // Sanitize
                $_mdLabel = $this->request->get('md');

                $md = null;
                $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                $valid = $this->validateMetadata($_mdLabel, $md);

                if (!$valid) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'Invalid property'));
                    $this->view = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
                } else {
                    $this->view = 'Admin_MetadataDetails';
                    $mdFactory->appendMetadataValueList($md, false);
                    $this->_viewParams['md'] = $md;
                }
                break;
            case 'admin_md_details_update':
                $_name = trim($this->request->get('name'));
                $_label = $this->request->get('label');

                $mdFactory = $this->_getMetadataFactory($this->_viewParams['group_id']);
                if ($mdFactory->isValidLabel($_label)) {
                    $this->_viewParams['default_url_params'] = array('action'  => 'admin_md_details', 'md' => $_label);
                    if ($mdFactory->isHardCodedMetadata($_label) || $this->validateUpdateMetadata($_name, $_label)) {
                        $this->action = $view;
                    }
                } else {
                    $this->_viewParams['default_url_params'] = array('action'  => 'admin_metadata');
                }
                $this->view = 'RedirectAfterCrud';
                break;
            case 'admin_create_metadata':
                $_name = trim($this->request->get('name'));
                $valid = $this->validateNewMetadata($_name);

                if ($valid) {
                    $this->action = $view;
                }

                $this->_viewParams['default_url_params'] = array('action'  => 'admin_metadata');
                $this->view = 'RedirectAfterCrud';
                break;
            case 'admin_delete_metadata':
                $valid = false;
                // md
                // Sanitize
                $_mdLabel = $this->request->get('md');

                // Valid
                $logmsg = '';
                $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                $md = null;
                $vld = $this->validateMetadata($_mdLabel, $md);
                if ($vld) {
                    if (!$mdFactory->isHardCodedMetadata($md->getLabel())) {
                        $valid = true;
                    } else {
                        $logmsg = dgettext('tuleap-docman', 'You are not allowed to delete system properties.');
                    }
                } else {
                    $logmsg = dgettext('tuleap-docman', 'Invalid property');
                }

                if (!$valid) {
                    if ($logmsg != '') {
                        $this->feedback->log('error', $logmsg);
                    }
                    $this->view = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
                } else {
                    $this->action = $view;
                    $this->_actionParams['md'] = $md;
                }

                break;
            case 'admin_create_love':
                $mdFactory = $this->_getMetadataFactory($this->_viewParams['group_id']);
                if ($mdFactory->isValidLabel($this->request->get('md'))) {
                    $this->action = $view;
                    $this->_viewParams['default_url_params'] = array('action'  => 'admin_md_details',
                                                                 'md' => $this->request->get('md'));
                } else {
                    $this->_viewParams['default_url_params'] = array('action'  => 'admin_metadata');
                }
                $this->view = 'RedirectAfterCrud';
                break;
            case 'admin_delete_love':
                $mdFactory = $this->_getMetadataFactory($this->_viewParams['group_id']);
                if ($mdFactory->isValidLabel($this->request->get('md'))) {
                    $this->action = $view;
                    $this->_viewParams['default_url_params'] = array('action'  => 'admin_md_details',
                                                                 'md' => $this->request->get('md'));
                } else {
                    $this->_viewParams['default_url_params'] = array('action'  => 'admin_metadata');
                }
                $this->view = 'RedirectAfterCrud';
                break;
            case 'admin_display_love':
                $valid = false;
                // Required params:
                // md (string [a-z_]+)
                // loveid (int)

                // Sanitize
                $_mdLabel = $this->request->get('md');
                $_loveId = (int) $this->request->get('loveid');

                // Valid
                $md = null;
                $love = null;
                $this->validateMetadata($_mdLabel, $md);
                if ($md !== null && $md->getLabel() !== 'status') {
                    $valid = $this->validateLove($_loveId, $md, $love);
                }

                if (!$valid) {
                    $this->view = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
                } else {
                    $mdFactory = new Docman_MetadataFactory($this->groupId);
                    $mdFactory->appendMetadataValueList($md, false);

                    $this->view = 'Admin_MetadataDetailsUpdateLove';
                    $this->_viewParams['md'] = $md;
                    $this->_viewParams['love'] = $love;
                }
                break;
            case 'admin_update_love':
                $valid = false;
                // Required params:
                // md (string [a-z_]+)
                // loveid (int)
                //
                // rank (beg, end, [0-9]+)
                // name
                // descr

                // Sanitize
                /// @todo sanitize md, rank, name, descr
                $_mdLabel = $this->request->get('md');
                $_loveId = (int) $this->request->get('loveid');
                $_rank = $this->request->get('rank');
                $_name = $this->request->get('name');
                $_descr = $this->request->get('descr');

                // Valid
                $md = null;
                $love = null;
                $this->validateMetadata($_mdLabel, $md);
                if ($md !== null && $md->getLabel() !== 'status') {
                    $valid = $this->validateLove($_loveId, $md, $love);
                }

                if (!$valid) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'There is an error in parameters. Back to previous screen.'));
                    $this->view = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
                } else {
                    // Set parameters
                    $love->setRank($_rank);
                    $love->setName($_name);
                    $love->setDescription($_descr);

                    // define action
                    $this->action = $view;
                    $this->_actionParams['md'] = $md;
                    $this->_actionParams['love'] = $love;
                }
                break;

            case 'admin_import_metadata_check':
                $ok = false;
                if ($this->request->existAndNonEmpty('plugin_docman_metadata_import_group')) {
                    $pm = ProjectManager::instance();
                    $srcGroup = $pm->getProjectFromAutocompleter($this->request->get('plugin_docman_metadata_import_group'));
                    if ($srcGroup && !$srcGroup->isError()) {
                        $this->_viewParams['sSrcGroupId'] = $srcGroup->getGroupId();
                        $this->view = 'Admin_MetadataImport';
                        $ok = true;
                    }
                }
                if (!$ok) {
                    $this->view = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
                }
                break;

            case 'admin_import_metadata':
                if ($this->request->existAndNonEmpty('confirm')) {
                    if ($this->request->existAndNonEmpty('plugin_docman_metadata_import_group')) {
                        $pm = ProjectManager::instance();
                        $srcGroup = $pm->getProjectFromAutocompleter($this->request->get('plugin_docman_metadata_import_group'));
                        $srcGroupId = $srcGroup->getGroupId();
                        $this->_actionParams['sSrcGroupId'] = $srcGroupId;
                        $this->_actionParams['sGroupId'] = $this->_viewParams['group_id'];

                        $this->action = $view;
                    } else {
                        $this->feedback->log('error', dgettext('tuleap-docman', 'Parameter is missing'));
                        $this->feedback->log('info', dgettext('tuleap-docman', 'Operation Canceled'));
                    }
                } else {
                    $this->feedback->log('info', dgettext('tuleap-docman', 'Operation Canceled'));
                }
                $this->view = 'RedirectAfterCrud';
                $this->_viewParams['default_url_params'] = array('action' => 'admin_metadata');
                break;

            case 'admin_obsolete':
                $this->view = 'Admin_Obsolete';
                break;

            case 'admin_lock_infos':
                $this->view = 'Admin_LockInfos';
                break;

            case 'move':
                if (!$this->userCanWrite($item->getId()) || !$this->userCanWrite($item->getParentId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to move this item.'));
                    $this->view = 'Details';
                } else {
                    if ($this->request->exist('quick_move')) {
                        $this->action = 'move';
                        $this->view = null;
                    } else {
                        $this->_viewParams['hierarchy'] = $this->getItemHierarchy($root);
                        $this->view                     = ucfirst($view);
                    }
                }
                break;
            case 'newGlobalDocument':
                if ($dpm->oneFolderIsWritable($user)) {
                    $this->_viewParams['hierarchy'] = $this->getItemHierarchy($root);
                    $this->view                     = 'New_FolderSelection';
                } else {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to create something in this folder.'));
                    $this->view = $item->accept($get_show_view, $this->request->get('report'));
                }
                break;
            case 'newDocument':
            case 'newFolder':
                if ($this->request->exist('cancel')) {
                    $this->_set_redirectView();
                } else {
                    if (!$this->userCanWrite($item->getId())) {
                        $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to create something in this folder.'));
                        $this->view = 'Details';
                    } else {
                        $this->_viewParams['ordering'] = $this->request->get('ordering');
                        if ($this->request->get('item_type') == PLUGIN_DOCMAN_ITEM_TYPE_FOLDER) {
                            $view = 'newFolder';
                        }
                        $this->view = ucfirst($view);
                    }
                }
                break;
            case 'monitor':
                if ($this->request->exist('monitor')) {
                    $this->_actionParams['monitor'] =  $this->request->get('monitor');
                    if ($this->request->exist('cascade')) {
                        $this->_actionParams['cascade'] = $this->request->get('cascade');
                    }
                    $this->_actionParams['item'] = $item;
                    $this->action                = 'monitor';
                }
                $this->_setView('Details');
                break;
            case 'update_monitoring':
                $users_to_delete   = $this->request->get('listeners_users_to_delete');
                $ugroups_to_delete = $this->request->get('listeners_ugroups_to_delete');
                $listeners_to_add  = $this->request->get('listeners_to_add');

                if (! $users_to_delete && ! $ugroups_to_delete && ! $listeners_to_add) {
                    $this->feedback->log(
                        'error',
                        dgettext('tuleap-docman', 'No element selected')
                    );
                } else {
                    if ($users_to_delete || $ugroups_to_delete) {
                        $this->removeMonitoring($item, $users_to_delete, $ugroups_to_delete);
                    }
                    if ($listeners_to_add) {
                        $this->addMonitoring($item, $listeners_to_add);
                    }
                    $this->_actionParams['item'] = $item;
                    $this->action                = 'update_monitoring';
                }
                $this->view                       = 'RedirectAfterCrud';
                $this->_viewParams['redirect_to'] = DOCMAN_BASE_URL . '/index.php?group_id='
                . $item->groupId . '&id=' . $item->id . '&action=details&section=notifications';
                break;
            case 'move_here':
                if (!$this->request->exist('item_to_move')) {
                    $this->feedback->log('error', 'Missing parameter.');
                    $this->view = 'DocmanError';
                } else {
                    $item_to_move = $item_factory->getItemFromDb($this->request->get('item_to_move'));
                    $this->view   = null;
                    if ($this->request->exist('confirm')) {
                        if (!$item_to_move || !($this->userCanWrite($item->getId()) && $this->userCanWrite($item_to_move->getId()) && $this->userCanWrite($item_to_move->getParentId()))) {
                            $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to move this item.'));
                            $this->_set_moveView_errorPerms();
                        } else {
                            $this->action = 'move';
                        }
                    }
                    if (!$this->view) {
                        $this->_set_redirectView();
                    }
                }
                break;
            case 'permissions':
                if (!$this->userCanManage($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to set permissions for this item.'));
                    $this->view = 'Details';
                } else {
                    $this->action = 'permissions';
                    $this->view   = 'Details';
                }
                break;
            case 'confirmDelete':
                if ($this->userCannotDelete($user, $item)) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to delete this item.'));
                    $this->view = 'Details';
                } else {
                    $this->view   = 'Delete';
                }
                break;
            case 'action_new_version':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $dPm = $this->_getPermissionsManager();
                    if ($dPm->getLockFactory()->itemIsLocked($item)) {
                        $this->feedback->log('warning', dgettext('tuleap-docman', 'Locked document'));
                    }
                    $this->view   = 'NewVersion';
                }
                break;
            case 'action_update':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $this->view   = 'Update';
                }
                break;

            case 'action_copy':
                //@XSS: validate action against a regexp.
                $_action = $this->request->get('orig_action');
                $_id     = (int) $this->request->get('orig_id');
                $this->_actionParams['item'] = $item;

                $this->action = $view;
                if (!$this->request->exist('ajax_copy')) {
                    $this->_viewParams['default_url_params'] = array('action'  => $_action,
                                                                 'id'      => $_id);
                    $this->view = 'RedirectAfterCrud';
                }
                break;

            case 'action_cut':
                $_action = $this->request->get('orig_action');
                $_id = (int) $this->request->get('orig_id');
                $this->_actionParams['item'] = $item;

                $this->action = $view;
                if (!$this->request->exist('ajax_cut')) {
                    $this->_viewParams['default_url_params'] = array('action'  => $_action,
                                                                 'id'      => $_id);
                    $this->view = 'RedirectAfterCrud';
                }
                break;

            case 'action_paste':
                $itemToPaste = null;
                $mode        = null;
                $allowed = $this->checkPasteIsAllowed($item, $itemToPaste, $mode);
                if (!$allowed) {
                    $this->view = 'Details';
                } else {
                    $this->_viewParams['itemToPaste'] = $itemToPaste;
                    $this->_viewParams['srcMode']     = $mode;
                    $this->view = 'Paste';
                }
                break;

            case 'paste_cancel':
                // intend to be only called through ajax call
                $item_factory->delCopyPreference();
                $item_factory->delCutPreference();
                break;

            case 'paste':
                if ($this->request->exist('cancel')) {
                    $this->_viewParams['default_url_params'] = array('action'  => 'show');
                    $this->view = 'RedirectAfterCrud';
                } else {
                    $itemToPaste = null;
                    $mode        = null;
                    $allowed = $this->checkPasteIsAllowed($item, $itemToPaste, $mode);
                    if (!$allowed) {
                        $this->view = 'Details';
                    } else {
                        $this->_viewParams['importMd'] = false;
                        if ($this->userCanAdmin()) {
                            if (
                                $this->request->exist('import_md') &&
                                $this->request->get('import_md') == '1'
                            ) {
                                $this->_viewParams['importMd'] = true;
                            }
                        }
                        $this->_viewParams['item'] = $item;
                        $this->_viewParams['rank'] = $this->request->get('rank');
                        $this->_viewParams['itemToPaste'] = $itemToPaste;
                        $this->_viewParams['srcMode']     = $mode;
                        /*$this->action = $view;

                        $this->_viewParams['default_url_params'] = array('action'  => 'show',
                                                                     'id'      => $item->getId());
                        $this->view = 'RedirectAfterCrud';*/
                        $this->_viewParams['item']        = $item;
                        $this->_viewParams['rank']        = $this->request->get('rank');
                        $this->_viewParams['itemToPaste'] = $itemToPaste;
                        $this->_viewParams['srcMode']     = $mode;
                        $this->view                       = 'PasteInProgress';
                    }
                }
                break;

            case 'approval_create':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $this->view = 'ApprovalCreate';
                }
                break;

            case 'approval_delete':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    if ($this->request->exist('confirm')) {
                        $this->action = $view;
                        $this->_actionParams['item']   = $item;

                        // Version
                        $vVersion = new Valid_UInt('version');
                        $vVersion->required();
                        if ($this->request->valid($vVersion)) {
                            $this->_actionParams['version'] = $this->request->get('version');
                        } else {
                            $this->_actionParams['version'] = null;
                        }
                    }

                    $this->_viewParams['default_url_params'] = array('action'  => 'details',
                                                                 'section' => 'approval',
                                                                 'id'      => $item->getId());
                    $this->view = 'RedirectAfterCrud';
                }
                break;

            case 'approval_update':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $this->_actionParams['item']   = $item;

                    // Settings
                    $this->_actionParams['status']       = (int) $this->request->get('status');
                    $this->_actionParams['description']  = $this->request->get('description');
                    $this->_actionParams['notification'] = (int) $this->request->get('notification');
                    $this->_actionParams['reminder']     = $this->request->get('reminder');
                    $this->_actionParams['occurence']    = (int) $this->request->get('occurence');
                    $this->_actionParams['period']       = (int) $this->request->get('period');

                    // Users
                    $this->_actionParams['user_list'] = $this->request->get('user_list');
                    $this->_actionParams['ugroup_list'] = null;
                    if (is_array($this->request->get('ugroup_list'))) {
                        $this->_actionParams['ugroup_list'] = array_map('intval', $this->request->get('ugroup_list'));
                    }

                    // Selected users
                    $this->_actionParams['sel_user'] = null;
                    if (is_array($this->request->get('sel_user'))) {
                        $this->_actionParams['sel_user'] = array_map('intval', $this->request->get('sel_user'));
                    }
                    $allowedAct = array('100', 'mail', 'del');
                    $this->_actionParams['sel_user_act'] = null;
                    if (in_array($this->request->get('sel_user_act'), $allowedAct)) {
                        $this->_actionParams['sel_user_act'] = $this->request->get('sel_user_act');
                    }

                    // Resend
                    $this->_actionParams['resend_notif'] = false;
                    if ($this->request->get('resend_notif') == 'yes') {
                        $this->_actionParams['resend_notif'] = true;
                    }

                    // Version
                    $vVersion = new Valid_UInt('version');
                    $vVersion->required();
                    if ($this->request->valid($vVersion)) {
                        $this->_actionParams['version'] = $this->request->get('version');
                    } else {
                        $this->_actionParams['version'] = null;
                    }

                    // Import
                    $vImport = new Valid_WhiteList('app_table_import', array('copy', 'reset', 'empty'));
                    $vImport->required();
                    $this->_actionParams['import'] = $this->request->getValidated('app_table_import', $vImport, false);

                    // Owner
                    $vOwner = new Valid_String('table_owner');
                    $vOwner->required();
                    $this->_actionParams['table_owner'] = $this->request->getValidated('table_owner', $vOwner, false);

                    // Special handeling of table deletion
                    if ($this->_actionParams['status'] == PLUGIN_DOCMAN_APPROVAL_TABLE_DELETED) {
                        $this->_viewParams['default_url_params'] = array('action' => 'approval_create',
                                                                     'delete' => 'confirm',
                                                                     'id'     => $item->getId());
                    } else {
                        // Action!
                        $this->action = $view;
                        $this->_viewParams['default_url_params'] = array('action'  => 'approval_create',
                                                                     'id'      => $item->getId());
                    }
                    if ($this->_actionParams['version'] !== null) {
                        $this->_viewParams['default_url_params']['version'] = $this->_actionParams['version'];
                    }
                    $this->view = 'RedirectAfterCrud';
                }
                break;

            case 'approval_upd_user':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $this->_actionParams['item'] = $item;
                    $this->_actionParams['user_id'] = (int) $this->request->get('user_id');
                    $this->_actionParams['rank']    = $this->request->get('rank');
                    $this->action = $view;

                    $this->_viewParams['default_url_params'] = array('action'  => 'approval_create',
                                                                 'id'      => $item->getId());
                    $this->view = 'RedirectAfterCrud';
                }
                break;

            case 'approval_del_user':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $this->_actionParams['item'] = $item;
                    $this->_actionParams['user_id'] = (int) $this->request->get('user_id');
                    $this->action = $view;

                    $this->_viewParams['default_url_params'] = array('action'  => 'approval_create',
                                                                 'id'      => $item->getId());
                    $this->view = 'RedirectAfterCrud';
                }
                break;

            case 'approval_user_commit':
                $atf   = Docman_ApprovalTableFactoriesFactory::getFromItem($item);
                $table = $atf->getTable();
                $atrf  = new Docman_ApprovalTableReviewerFactory($table, $item);
                if (
                    !$this->userCanRead($item->getId())
                    || !$atrf->isReviewer($user->getId())
                    || !$table->isEnabled()
                ) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $this->_actionParams['item'] = $item;

                    $svState = 0;
                    $sState = (int) $this->request->get('state');
                    if ($sState >= 0 && $sState < 5) {
                        $svState = $sState;
                    }
                    $this->_actionParams['svState'] = $svState;

                    $this->_actionParams['sVersion'] = null;
                    if ($this->request->exist('version')) {
                        $sVersion = (int) $this->request->get('version');
                        switch ($item_factory->getItemTypeForItem($item)) {
                            case PLUGIN_DOCMAN_ITEM_TYPE_WIKI:
                                if ($sVersion <= 0) {
                                    $sVersion = null;
                                }
                                break;
                            case PLUGIN_DOCMAN_ITEM_TYPE_FILE:
                            case PLUGIN_DOCMAN_ITEM_TYPE_EMBEDDEDFILE:
                                // assume ok: do nothing.
                                break;
                            default:
                                $sVersion = null;
                        }
                        $this->_actionParams['sVersion'] = $sVersion;
                    }
                    $this->_actionParams['usComment'] = $this->request->get('comment');
                    $this->_actionParams['monitor'] = (int) $this->request->get('monitor');

                    $this->action = $view;

                    $this->_viewParams['default_url_params'] = array('action'  => 'details',
                                                                 'section' => 'approval',
                                                                 'id'      => $item->getId());
                    $this->view = 'RedirectAfterCrud';
                }
                break;

            case 'approval_notif_resend':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $this->action = $view;
                    $this->_actionParams['item'] = $item;

                    $this->_viewParams['default_url_params'] = array('action'  => 'approval_create',
                                                                 'id'      => $item->getId());
                    $this->view = 'RedirectAfterCrud';
                }
                break;

            case 'edit':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                    $mdFactory->appendAllListOfValuesToItem($item);
                    $this->view   = 'Edit';
                }
                break;
            case 'delete':
                if ($this->userCannotDelete($user, $item)) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to delete this item.'));
                    $this->_set_deleteView_errorPerms();
                } elseif ($this->request->exist('confirm')) {
                    $this->action = $view;
                    $this->_set_redirectView();
                } else {
                    $this->view = 'Details';
                }
                break;

            case 'deleteVersion':
                if ($this->userCannotDelete($user, $item)) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to delete this item.'));
                    $this->_set_deleteView_errorPerms();
                } elseif ($this->request->exist('confirm')) {
                    $this->action = $view;
                    $this->_set_redirectView();
                } else {
                    $this->view = 'Details';
                }
                break;

            case 'createFolder':
            case 'createDocument':
            case 'createItem':
                if ($this->request->exist('cancel')) {
                    $this->_set_redirectView();
                } else {
                    $i = $this->request->get('item');
                    if (!$i || !isset($i['parent_id'])) {
                        $this->feedback->log('error', 'Missing parameter.');
                        $this->view = 'DocmanError';
                    } else {
                        $parent = $item_factory->getItemFromDb($i['parent_id']);
                        if (!$parent || $parent->getGroupId() != $this->getGroupId() || !$this->userCanWrite($parent->getId())) {
                            $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to create something in this folder.'));
                            $this->_set_createItemView_errorParentDoesNotExist($item, $get_show_view);
                        } else {
                            //Validations
                            $new_item = $this->createItemFromUserInput();

                            $fields = array_merge(
                                $new_item->accept(new Docman_View_GetFieldsVisitor()),
                                $new_item->accept(
                                    new Docman_View_GetSpecificFieldsVisitor(),
                                    ['request' => $this->request]
                                )
                            );
                            $valid = $this->_validateRequest($fields);
                            if ($user->isMember($this->getGroupId(), 'A') || $user->isMember($this->getGroupId(), 'N1') || $user->isMember($this->getGroupId(), 'N2')) {
                                $news = $this->request->get('news');
                                if ($news) {
                                    $is_news_details = isset($news['details']) && trim($news['details']);
                                    $is_news_summary = isset($news['summary']) && trim($news['summary']);
                                    if ($is_news_details && !$is_news_summary) {
                                        $this->feedback->log('error', dgettext('tuleap-docman', 'Error while creating news. Check that subject field is not empty.'));
                                        $valid = false;
                                    }
                                    if (!$is_news_details && $is_news_summary) {
                                        $this->feedback->log('error', dgettext('tuleap-docman', 'Error while creating news. Check that details field is not empty.'));
                                        $valid = false;
                                    }
                                }
                            }

                            if ($valid && $new_item !== null) {
                                $document_retriever         = new DocumentOngoingUploadRetriever(new DocumentOngoingUploadDAO());
                                $is_document_being_uploaded = $document_retriever->isThereAlreadyAnUploadOngoing(
                                    $parent,
                                    $new_item->getTitle(),
                                    new DateTimeImmutable()
                                );
                                if ($is_document_being_uploaded) {
                                    $valid = false;
                                    $this->feedback->log(
                                        Feedback::ERROR,
                                        dgettext('tuleap-docman', 'There is already a document being uploaded for this item')
                                    );
                                }
                            }

                            if ($valid) {
                                $this->action = $view;
                                $this->_set_redirectView();
                            } else {
                                // Propagate return page
                                $this->_viewParams['token']               = $this->request->get('token');

                                $this->_viewParams['force_item']          = $new_item;
                                $this->_viewParams['force_news']          = $this->request->get('news');
                                $this->_viewParams['force_permissions']   = $this->request->get('permissions');
                                $this->_viewParams['force_ordering']      = $this->request->get('ordering');
                                $this->_viewParams['display_permissions'] = $this->request->exist('user_has_displayed_permissions');
                                $this->_viewParams['display_news']        = $this->request->exist('user_has_displayed_news');
                                $this->_viewParams['hierarchy']           = $this->getItemHierarchy($root);
                                $this->_set_createItemView_afterCreate($view);
                            }
                        }
                    }
                }
                break;
            case 'update':
                $this->_viewParams['recurseOnDocs'] = false;
                $this->_actionParams['recurseOnDocs'] = false;
                if ($this->request->get('recurse_on_doc') == 1) {
                    $this->_viewParams['recurseOnDocs'] = true;
                    $this->_actionParams['recurseOnDocs'] = true;
                }
                // Fall-through code dealing with new versions creation and update is the same
            case 'update_wl':
            case 'new_version':
                if (!$this->userCanWrite($item->getId())) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
                    $this->view = 'Details';
                } else {
                    // For properties update ('update' action), we need to confirm
                    // the recursive application of metadata update.
                    if (
                        $view == 'update' &&
                        $this->request->exist('recurse') &&
                        !$this->request->exist('cancel')
                    ) {
                        $this->_viewParams['recurse'] = $this->request->get('recurse');
                        if (!$this->request->exist('validate_recurse')) {
                            $updateConfirmed = false;
                        } elseif ($this->request->get('validate_recurse') != 'true') {
                            $updateConfirmed = false;
                        } else {
                            $updateConfirmed = true;
                        }
                    } else {
                        $updateConfirmed = true;
                    }

                    $valid = true;
                    if ($this->request->exist('confirm')) {
                        $retriever = new VersionOngoingUploadRetriever(new DocumentOnGoingVersionToUploadDAO());
                        if ($retriever->isThereAlreadyAnUploadOngoing($item, new DateTimeImmutable())) {
                            $valid = false;
                        }
                        //Validations
                        if ($valid && $view == 'update') {
                            $this->updateMetadataFromUserInput($item);
                            $valid = $this->_validateRequest($item->accept(new Docman_View_GetFieldsVisitor()));
                        } elseif ($valid) {
                            $this->updateItemFromUserInput($item);
                            $valid = (($this->_validateApprovalTable($this->request, $item)) && ($this->_validateRequest($item->accept(new Docman_View_GetSpecificFieldsVisitor(), array('request' => &$this->request)))));
                        }
                        //Actions
                        if ($valid && $updateConfirmed) {
                            if ($view == 'update_wl') {
                                $this->action = 'update';
                            } else {
                                $this->action = $view;
                            }
                        }
                    }
                    //Views
                    if ($valid && $updateConfirmed) {
                        if ($redirect_to = Docman_Token::retrieveUrl($this->request->get('token'))) {
                            $this->_viewParams['redirect_to'] = $redirect_to;
                        }
                        $this->view = 'RedirectAfterCrud';
                    } else {
                        if ($view == 'update_wl') {
                            $this->view = 'Update';
                        } elseif ($view == 'new_version') {
                            // Keep fields values
                            $v = $this->request->get('version');
                            $this->_viewParams['label']     = $v['label'];
                            $this->_viewParams['changelog'] = $v['changelog'];
                            if ($item instanceof Docman_EmbeddedFile) {
                                $v = $item->getCurrentVersion();
                                $v->setContent($this->request->get('content'));
                            }
                            $this->view = 'NewVersion';
                        } else {
                            $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                            $mdFactory->appendAllListOfValuesToItem($item);
                            if ($this->request->existAndNonEmpty('token')) {
                                // propagate the token so the user will be
                                // redirected to the original page even after
                                // several properties update errors or
                                // confirmations.
                                $this->_viewParams['token'] = $this->request->get('token');
                            }
                            $this->_viewParams['updateConfirmed'] = $updateConfirmed;
                            // The item may have changed (new user input)
                            unset($this->_viewParams['item']);
                            $this->_viewParams['item'] = $item;

                            $this->view = 'Edit';
                        }
                    }
                }
                break;
            case 'change_view':
                $this->action = $view;
                break;
            case 'install':
                $this->feedback->log('error', dgettext('tuleap-docman', 'Document Manager already installed.'));
                $this->view = 'DocmanError';
                break;
            case 'search':
                $this->view = 'Table';
                break;
            case 'positionWithinFolder':
                $this->_viewParams['force_ordering'] = $this->request->get('default_position');
                $this->_viewParams['exclude']        = $this->request->get('exclude');
                $this->_viewParams['hierarchy']      = $this->getItemHierarchy($root);
                $this->view = ucfirst($view);
                break;
            case 'permissionsForItem':
                $this->_viewParams['user_can_manage'] = $this->userCanManage($item->getId());
                $this->view = ucfirst($view);
                break;
            case 'report_settings':
                $this->view = 'ReportSettings';
                break;
            case 'report_del':
                if ($this->request->exist('report_id')) {
                    $this->_actionParams['sReportId'] = (int) $this->request->get('report_id');
                    $this->_actionParams['sGroupId']  = $this->_viewParams['group_id'];

                    $this->action = $view;
                }
                $this->_viewParams['default_url_params'] = array('action'  => 'report_settings');
                $this->view = 'RedirectAfterCrud';

                break;
            case 'report_upd':
                if ($this->request->exist('report_id')) {
                    $this->_actionParams['sReportId'] = (int) $this->request->get('report_id');
                    $this->_actionParams['sGroupId']  = $this->_viewParams['group_id'];
                    $usScope = $this->request->get('scope');
                    if ($usScope === 'I' || $usScope === 'P') {
                        $this->_actionParams['sScope'] = $usScope;
                    }
                    $this->_actionParams['description'] = $this->request->get('description');
                    $this->_actionParams['title']       = $this->request->get('title');
                    $this->_actionParams['sImage'] = (int) $this->request->get('image');

                    $this->action = $view;
                }
                $this->_viewParams['default_url_params'] = array('action'  => 'report_settings');
                $this->view = 'RedirectAfterCrud';
                break;

            case 'report_import':
                if ($this->request->exist('import_search_report_from_group')) {
                    $pm = ProjectManager::instance();
                    $srcGroup = $pm->getProjectFromAutocompleter($this->request->get('import_search_report_from_group'));
                    if ($srcGroup && !$srcGroup->isError()) {
                        $this->_actionParams['sGroupId']       = $this->_viewParams['group_id'];
                        $this->_actionParams['sImportGroupId'] = $srcGroup->getGroupId();
                        $this->_actionParams['sImportReportId'] = null;
                        if ($this->request->exist('import_report_id') && trim($this->request->get('import_report_id')) != '') {
                            $this->_actionParams['sImportReportId'] = (int) $this->request->get('import_report_id');
                        }
                        $this->action = $view;
                    }
                }

                $this->_viewParams['default_url_params'] = array('action'  => 'report_settings');
                $this->view = 'RedirectAfterCrud';
                break;

            case 'action_lock_add':
                $this->_actionParams['item'] = $item;
                $this->action = 'action_lock_add';
                break;

            case 'action_lock_del':
                $this->_actionParams['item'] = $item;
                $this->action = 'action_lock_del';
                break;

            case 'ajax_reference_tooltip':
                $this->view = 'AjaxReferenceTooltip';
                break;

            default:
                $purifier = Codendi_HTMLPurifier::instance();
                die($purifier->purify($view) . ' is not supported');
            break;
        }
    }

    public function getProperty($name)
    {
        $info = $this->plugin->getPluginInfo();
        return $info->getPropertyValueForName($name);
    }
    public $item_factory;
    public function getItemFactory()
    {
        if (!$this->item_factory) {
            $this->item_factory = new Docman_ItemFactory();
        }
        return $this->item_factory;
    }

    public $metadataFactory;
    private function _getMetadataFactory($groupId)
    {
        if (!isset($metadataFactory[$groupId])) {
            $metadataFactory[$groupId] = new Docman_MetadataFactory($groupId);
        }
        return $metadataFactory[$groupId];
    }

    public function forceView($view)
    {
        $this->view = $view;
    }

    public function _validateApprovalTable($request, $item)
    {
        $atf = Docman_ApprovalTableFactoriesFactory::getFromItem($item);
        if ($atf && $atf->tableExistsForItem()) {
            $vAppTable = new Valid_WhiteList('app_table_import', array('copy', 'reset', 'empty'));
            $vAppTable->required();
            if (!$request->valid($vAppTable)) {
                $this->feedback->log('error', dgettext('tuleap-docman', 'Please choose option for creating approval table'));
                return false;
            }
        }
        return true;
    }

    public function _validateRequest($fields)
    {
        $valid = true;
        foreach ($fields as $field) {
            $validatorList = null;
            if (is_a($field, 'Docman_MetadataHtml')) {
                $fv = $field->getValidator($this->request);
                if ($fv !== null) {
                    if (!is_array($fv)) {
                        $validatorList = array($fv);
                    } else {
                        $validatorList = $fv;
                    }
                }
            } else {
                if (isset($field['validator'])) {
                    if (!is_array($field['validator'])) {
                        $validatorList = array($field['validator']);
                    } else {
                        $validatorList = $field['validator'];
                    }
                }
            }

            if ($validatorList !== null) {
                foreach ($validatorList as $v) {
                    if (!$v->isValid()) {
                        $valid = false;
                        foreach ($v->getErrors() as $error) {
                            $this->feedback->log('error', $error);
                        }
                    }
                }
            }
        }
        return $valid;
    }

    public function validateMetadata($label, &$md)
    {
        $valid = false;

        $mdFactory = new Docman_MetadataFactory($this->groupId);
        if ($mdFactory->isValidLabel($label)) {
            $_md = $mdFactory->getFromLabel($label);
            if (
                $_md !== null
                && $_md->getGroupId() == $this->groupId
            ) {
                $valid = true;
                $md = $_md;
            }
        }

        return $valid;
    }

    /**
    * Checks that the new property have a non-empty name,
    * and also checks that the same name is not already taken by
    * another property
    */
    private function validateNewMetadata($name)
    {
        $name = trim($name);
        if ($name == '') {
            $valid = false;
            $this->feedback->log('error', dgettext('tuleap-docman', 'Property name is required, please fill this field.'));
        } else {
            $mdFactory = new Docman_MetadataFactory($this->groupId);

            if ($mdFactory->findByName($name)->count() == 0) {
                $valid = true;
            } else {
                $valid = false;
                $this->feedback->log('error', sprintf(dgettext('tuleap-docman', 'There is already a property with the name \'%1$s\'.'), $name));
            }
        }

        return $valid;
    }

    /**
    * Checks that the updating property have a non-empty name,
    * and if the name have been changed, also checks that the same
    * name is not already taken by another property
    */
    private function validateUpdateMetadata($name, $label)
    {
        $name = trim($name);
        if ($name == '') {
            $valid = false;
            $this->feedback->log('error', dgettext('tuleap-docman', 'Property name is required, please fill this field.'));
        } else {
            $mdFactory = new Docman_MetadataFactory($this->groupId);

            $md = $mdFactory->getFromLabel($label);
            // name has changed
            if ($md !== null && $md->getName() != $name) {
                if ($mdFactory->findByName($name)->count() == 0) {
                    $valid = true;
                } else {
                    $valid = false;
                    $this->feedback->log('error', sprintf(dgettext('tuleap-docman', 'There is already a property with the name \'%1$s\'.'), $name));
                }
            } else {
                $valid = true;
            }
        }

        return $valid;
    }

    public function validateLove($loveId, $md, &$love)
    {
        $valid = false;

        $loveFactory = new Docman_MetadataListOfValuesElementFactory($md->getId());
        $_love = $loveFactory->getByElementId($loveId, $md->getLabel());
        if ($_love !== null) {
            // Still Need to verify that $love belong to $md
            $valid = true;
            $love = $_love;
        }

        return $valid;
    }

    public function checkPasteIsAllowed($item, &$itemToPaste, &$mode)
    {
        $isAllowed = false;

        $itemFactory = $this->getItemFactory();
        $user        = $this->getUser();

        $type = $itemFactory->getItemTypeForItem($item);
        if (PLUGIN_DOCMAN_ITEM_TYPE_FOLDER != $type) {
            $this->feedback->log('error', dgettext('tuleap-docman', 'You cannot paste something into a document.'));
        } elseif (!$this->userCanWrite($item->getId())) {
            $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to edit this item.'));
        } else {
            $copiedItemId = $itemFactory->getCopyPreference($user);
            $cutItemId    = $itemFactory->getCutPreference($user, $item->getGroupId());
            $itemToPaste  = null;

            if ($copiedItemId !== false && $cutItemId === false) {
                $itemToPaste = $itemFactory->getItemFromDb($copiedItemId);
                $mode        = 'copy';
            } elseif ($item->getId() == $cutItemId) {
                $this->feedback->log('error', dgettext('tuleap-docman', 'You can not paste an item into itself.'));
                return false;
            } elseif ($copiedItemId === false && $cutItemId !== false) {
                if ($itemFactory->isInSubTree($item->getId(), $cutItemId)) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'You cannot cut something and then paste it into its child.'));
                    return false;
                }
                $itemToPaste = $itemFactory->getItemFromDb($cutItemId);
                $mode        = 'cut';
            } else {
                $this->feedback->log('error', dgettext('tuleap-docman', 'No valid item to paste. Either no item was copied or item no longer exist.'));
                return false;
            }

            if ($itemToPaste == null) {
                $this->feedback->log('error', dgettext('tuleap-docman', 'No valid item to paste. Either no item was copied or item no longer exist.'));
            } else {
                $isAllowed = true;
            }
        }

        return $isAllowed;
    }

    public function actionsManagement()
    {
        // Redefine actions classes names building.
        $className = static::class;
        $class = substr($className, 0, -(strlen("Controller"))) . 'Actions';
        require_once($class . '.class.php');
        if (! class_exists($class)) {
            throw new LogicException("$class does not exist");
        }
        $wa = new $class($this, $this->gid);
        $wa->process($this->action, $this->_actionParams);
    }

    public function viewsManagement()
    {
        if ($this->view !== null) {
            $className = $this->_includeView();
            if (class_exists($className)) {
                $wv = new $className($this);
                return $wv->display($this->_viewParams);
            } else {
                die($className . ' does not exist.');
            }
        }
    }
    public function _count(&$item, &$hierarchy, $go = false)
    {
        $nb = $go ? 1 : 0;
        if (is_a($hierarchy, 'Docman_Folder')) {
            $list = $hierarchy->getAllItems();
            $iter = $list->iterator();
            while ($iter->valid()) {
                $o = $iter->current();
                $n = $this->_count($item, $o, $go ? $go : $o->getId() == $item->getId());
                if ($n) {
                    $nb += $n;
                }
                $iter->next();
            }
        }
        return $nb;
    }

    private function getItemHierarchy($rootItem)
    {
        if (!isset($this->hierarchy[$rootItem->getId()])) {
            $itemFactory = new Docman_ItemFactory($rootItem->getGroupId());
            $user = $this->getUser();
            $this->hierarchy[$rootItem->getId()] = $itemFactory->getItemTree($rootItem, $user, false, true);
        }
        return $this->hierarchy[$rootItem->getId()];
    }

    /**
     * @return Project
     */
    private function getProject()
    {
        return ProjectManager::instance()->getProject($this->getGroupId());
    }

    public function userCannotDelete(PFUser $user, Docman_Item $item)
    {
        return ! $this->_getPermissionsManager()->userCanDelete($user, $item);
    }

    /**
     * @param $item
     */
    private function removeMonitoring($item, $users_to_delete_ids, $ugroups_to_delete_ids)
    {
        $this->_actionParams['listeners_users_to_delete']   = array();
        $this->_actionParams['listeners_ugroups_to_delete'] = array();
        if ($this->userCanManage($item->getId())) {
            $user_manager  = UserManager::instance();
            $valid_user_id = new Valid_UInt('listeners_users_to_delete');
            if ($this->request->validArray($valid_user_id)) {
                $users = array();
                foreach ($users_to_delete_ids as $user_id) {
                    $users[] = $user_manager->getUserById($user_id);
                }
                $this->_actionParams['listeners_users_to_delete'] = $users;
                $this->_actionParams['item']                      = $item;
            }
            $ugroup_manager  = new UGroupManager();
            $valid_ugroup_id = new Valid_UInt('listeners_ugroups_to_delete');
            if ($this->request->validArray($valid_ugroup_id)) {
                $ugroups = array();
                foreach ($ugroups_to_delete_ids as $ugroup_id) {
                    $ugroups[] = $ugroup_manager->getById($ugroup_id);
                }
                $this->_actionParams['listeners_ugroups_to_delete'] = $ugroups;
            }
        } else {
            $this->feedback->log(
                'error',
                dgettext('tuleap-docman', 'You don\'t have enough permissions to perform this action')
            );
        }
    }

    /**
     * @param $item
     */
    private function addMonitoring($item, $listeners_to_add)
    {
        $this->_actionParams['listeners_to_add'] = array();
        if ($this->userCanManage($item->getId())) {
            $invalid_entries = new InvalidEntryInAutocompleterCollection();
            $autocompleter   = $this->getAutocompleter($listeners_to_add, $invalid_entries);
            $invalid_entries->generateWarningMessageForInvalidEntries();
            $emails  = $autocompleter->getEmails();
            $users   = $autocompleter->getUsers();
            $ugroups = $autocompleter->getUgroups();

            if (! empty($users)) {
                $this->notificationAddUsers($users);
            }

            if (! empty($ugroups)) {
                $this->notificationAddUgroups($ugroups);
            }

            if (! empty($emails)) {
                $this->feedback->log(
                    'warning',
                    dgettext('tuleap-docman', 'You cannot add emails')
                );
            }
        } else {
            $this->feedback->log(
                'error',
                dgettext('tuleap-docman', 'You don\'t have enough permissions to perform this action')
            );
        }
    }

    private function notificationAddUsers($users)
    {
        if ($this->request->exist('monitor_cascade')) {
            $this->_actionParams['monitor_cascade'] = $this->request->get('monitor_cascade');
        }
        $this->_actionParams['listeners_users_to_add'] = $users;
    }

    private function notificationAddUgroups($ugroups)
    {
        if ($this->request->exist('monitor_cascade')) {
            $this->_actionParams['monitor_cascade'] = $this->request->get('monitor_cascade');
        }
        $this->_actionParams['listeners_ugroups_to_add'] = $ugroups;
    }

    /**
     * @return RequestFromAutocompleter
     */
    private function getAutocompleter($addresses, InvalidEntryInAutocompleterCollection $invalid_entries)
    {
        $autocompleter = new RequestFromAutocompleter(
            $invalid_entries,
            new Rule_Email(),
            UserManager::instance(),
            new UGroupManager(),
            $this->getUser(),
            $this->getProject(),
            $addresses
        );
        return $autocompleter;
    }
}
