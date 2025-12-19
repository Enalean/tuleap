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
use Tuleap\Docman\Metadata\CreationMetadataValidator;
use Tuleap\Docman\Notifications\NotificationBuilders;
use Tuleap\Docman\Notifications\NotificationEventAdder;
use Tuleap\Docman\ResponseFeedbackWrapper;
use Tuleap\Document\Tree\DocumentItemUrlBuilder;
use Tuleap\Project\MappingRegistry;

class Docman_Controller extends Controler // phpcs:ignoreFile
{
    // variables
    /**
     * @var \Tuleap\HTTPRequest
     */
    public $request;
    private ?PFUser $user;
    public $groupId;
    public $themePath;
    public DocmanPlugin $plugin;
    public $logger;
    public ResponseFeedbackWrapper $feedback;
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
    private DocumentItemUrlBuilder $document_url_builder;

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
        $this->hierarchy      = [];

        $this->feedback = new ResponseFeedbackWrapper();

        $event_manager = $this->getEventManager();

        $this->logger    = new Docman_Log();
        $log_event_adder = new LogEventAdder($event_manager, $this->logger);
        $log_event_adder->addLogEventManagement();

        $notifications_builders                 = new NotificationBuilders($this->feedback, $this->getProject());
        $this->notificationsManager             = $notifications_builders->buildNotificationManager();
        $this->notificationsManager_Add         = $notifications_builders->buildNotificationManagerAdd();
        $this->notificationsManager_Delete      = $notifications_builders->buildNotificationManagerDelete();
        $this->notificationsManager_Move        = $notifications_builders->buildNotificationManagerMove();
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

        $this->document_url_builder = new DocumentItemUrlBuilder(ProjectManager::instance());
    }

    // Franlky, this is not at all the best place to do this.
    public function installDocman(?MappingRegistry $mapping_registry, $group_id = false)
    {
        $_gid = $group_id ? $group_id : (int) $this->request->get('group_id');

        $item_factory = $this->getItemFactory();
        $root         = $item_factory->getRoot($_gid);
        if ($root) {
            // Docman already install for this project.
            return false;
        } else {
            $pm                = ProjectManager::instance();
            $project           = $pm->getProject($_gid);
            $source_project_id = (int) $project->getTemplate();
            $this->cloneDocman($source_project_id, $project, $mapping_registry);
        }
    }

    private function cloneDocman(
        int $source_project_id,
        Project $destination_project,
        ?MappingRegistry $mapping_registry,
    ): void {
        $user                   = $this->getUser();
        $destination_project_id = (int) $destination_project->getID();

        // Clone Docman permissions
        $dPm = $this->_getPermissionsManager();
        if ($mapping_registry === null) {
            $dPm->setDefaultDocmanPermissions($destination_project_id);
        } else {
            $dPm->cloneDocmanPermissions($source_project_id, $destination_project_id);
        }

        // Clone Metadata definitions
        $metadataMapping = [];
        $mdFactory       = new Docman_MetadataFactory($source_project_id);
        $mdFactory->cloneMetadata($destination_project_id, $metadataMapping);

        // Clone Items, Item's permissions and metadata values
        $itemFactory     = $this->getItemFactory();
        $dataRoot        = \ForgeConfig::get(\DocmanPlugin::CONFIG_ROOT_DIRECTORY);
        $src_root_folder = $itemFactory->getRoot($source_project_id);
        if ($src_root_folder === null) {
            $itemFactory->createRoot($destination_project_id, 'roottitle_lbl_key');
            $item_mapping = [];
        } else {
            $item_mapping = $itemFactory->cloneItems(
                $user,
                $metadataMapping,
                $mapping_registry ? $mapping_registry->getUgroupMapping() : false,
                $dataRoot,
                $src_root_folder,
                DestinationCloneItem::fromDestinationProject(
                    $itemFactory,
                    $destination_project,
                    ProjectManager::instance(),
                    new Docman_LinkVersionFactory(),
                    $this->getEventManager(),
                )
            );
        }

        if ($mapping_registry) {
            $mapping_registry->setCustomMapping(\DocmanPlugin::ITEM_MAPPING_KEY, $item_mapping);
        }
    }

    public function getLogger()
    {
        return $this->logger;
    }

    public function logsDaily($params)
    {
        $this->logger->logsDaily($params);
    }

    public function getEventManager(): EventManager
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

    public function getUser(): PFUser
    {
        if ($this->user === null) {
            $um         = UserManager::instance();
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
                if (Docman_MetadataFactory::isHardCodedMetadata($md->getLabel())) {
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

            $i        = $this->request->get('item');
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
                $tmp_path = tempnam(ForgeConfig::get('tmp_dir'), 'embedded_file');
                $f        = fopen($tmp_path, 'w');
                fwrite($f, $this->request->get('content'));
                fclose($f);
                $v = new Docman_Version();
                $v->setPath($tmp_path);
                $new_item->setCurrentVersion($v);
            }
        }
        return $new_item;
    }

    #[\Override]
    public function request()
    {
        if (! $this->request->exist('group_id')) {
            $this->feedback->log('error', 'Project is missing.');
            $this->_setView('Error');
        } else {
            $_groupId = (int) $this->request->get('group_id');
            $pm       = ProjectManager::instance();
            $project  = $pm->getProject($_groupId);
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
            $view                        = $this->request->exist('action') ? $this->request->get('action') : 'show';
            $this->_viewParams['action'] = $view;



            $item_factory = $this->getItemFactory();
            $root         = $item_factory->getRoot($this->request->get('group_id'));
            if (! $root) {
                // Install
                $_gid = (int) $this->request->get('group_id');

                $pm          = ProjectManager::instance();
                $project     = $pm->getProject($_gid);
                $tmplGroupId = (int) $project->getTemplate();
                $this->cloneDocman($tmplGroupId, $project, null);
                if (! $item_factory->getRoot($_gid)) {
                    $item_factory->createRoot($_gid, 'roottitle_lbl_key');
                }
                $this->_viewParams['redirect_to'] = $_SERVER['REQUEST_URI'];
                $this->view                       = 'Redirect';
            } else {
                $id = $this->request->get('id');
                if (! $id && $this->request->exist('item')) {
                    $i = $this->request->get('item');
                    if (isset($i['id'])) {
                        $id = $i['id'];
                    }
                }
                if ($id) {
                    $item = $item_factory->getItemFromDb($id);

                    if (! $item) {
                        if ($this->request->get('action') === 'ajax_reference_tooltip') {
                            $this->_setView('AjaxReferenceTooltipNoContent');
                        } else {
                            $this->feedback->log('error', dgettext('tuleap-docman', 'Unable to retrieve item. Perhaps it was removed.'));
                            $this->_setView('DocmanError');
                        }
                    }
                } else {
                    $item = $root;
                }
                if ($item) {
                    // Load report
                    // If the item (folder) defined in the report is not the
                    // same than the current one, replace it.

                    if ($this->request->get('action') == 'ajax_reference_tooltip') {
                        $this->groupId = $item->getGroupId();
                    }
                    if ($item->getGroupId() != $this->getGroupId()) {
                        $pm = ProjectManager::instance();
                        $g  = $pm->getProject($this->getGroupId());
                        $this->_set_doesnot_belong_to_project_error($item, $g);
                    } else {
                        $user     = $this->getUser();
                        $dpm      = $this->_getPermissionsManager();
                        $can_read = $dpm->userCanAccess($user, $item->getId());
                        if (! $can_read) {
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
                            if (strpos($view, 'admin') === 0 && ! $this->userCanAdmin()) {
                                $this->feedback->log('error', dgettext('tuleap-docman', 'You do not have sufficient access rights to administrate the document manager.'));
                                $this->view = 'RedirectAfterCrud';
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

        switch ($view) {
            case 'show':
                $report = [];
                $this->view = $item->accept($get_show_view, $report);
                break;
            case 'getRootFolder':
                $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing element via the old interface is not supported.'));
                $GLOBALS['Response']->redirect($this->document_url_builder->getUrl($item). "/");
                break;
            case 'admin_set_permissions':
                \Docman_View_Admin_Permissions::getCSRFToken($this->getGroupId())->check();
                $this->action = $view;
                $this->view   = 'Admin_Permissions';
                break;
            case 'details':
                $section = $this->request->get('section');
                if ($section === 'properties') {
                    $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing properties via the old interface is not supported.'));
                    $GLOBALS['Response']->redirect($this->document_url_builder->getUrl($item));
                } elseif ($section === 'history') {
                    $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing the history via the old interface is not supported.'));
                    $project      = ProjectManager::instance()->getProject((int) $item->getGroupId());
                    $redirect_url = '/plugins/document/' . urlencode($project->getUnixNameLowerCase()) . '/versions/' . urlencode($item->getId());
                    $GLOBALS['Response']->redirect($redirect_url);
                } else if ($section === 'references') {
                    $GLOBALS['Response']->addFeedback(\Feedback::WARN, dgettext('tuleap-docman', 'Your link is not anymore valid: accessing the references via the old interface is not supported.'));
                    $project      = ProjectManager::instance()->getProject((int) $item->getGroupId());
                    $redirect_url = '/plugins/document/' . urlencode($project->getUnixNameLowerCase()) . '/references/' . urlencode($item->getId());
                    $GLOBALS['Response']->redirect($redirect_url);
                } else if ($section === 'notifications') {
                    $project = ProjectManager::instance()->getProject((int)$item->getGroupId());
                    $redirect_url = '/plugins/document/' . urlencode($project->getUnixNameLowerCase()) . '/notifications/' . urlencode($item->getId());
                    $GLOBALS['Response']->redirect($redirect_url);
                } else if ($section === 'statistics') {
                    $project      = ProjectManager::instance()->getProject((int) $item->getGroupId());
                    $redirect_url = '/plugins/document/' . urlencode($project->getUnixNameLowerCase()) . '/statistics/' . urlencode($item->getId());
                    $GLOBALS['Response']->redirect($redirect_url);
                } else if ($section === 'approval') {
                    $project      = ProjectManager::instance()->getProject((int) $item->getGroupId());
                    $redirect_url = '/plugins/document/' . urlencode($project->getUnixNameLowerCase()) . '/approval-table/' . urlencode($item->getId());
                    $GLOBALS['Response']->redirect($redirect_url);
                }
            $this->view = ucfirst($view);
            case 'admin':
                $GLOBALS['Response']->redirect(
                    \Tuleap\Document\Config\Project\SearchView::getUrl(
                        ProjectManager::instance()->getProject((int) $root->getGroupId())
                    )
                );
                break;
            case \Docman_View_Admin_Permissions::IDENTIFIER:
                $this->view = 'Admin_Permissions';
                break;
            case \Docman_View_Admin_Metadata::IDENTIFIER:
                $this->view                  = 'Admin_Metadata';
                $mdFactory                   = new Docman_MetadataFactory($this->_viewParams['group_id']);
                $mdIter                      = $mdFactory->getMetadataForGroup();
                $this->_viewParams['mdIter'] = $mdIter;
                break;
            case \Docman_View_Admin_MetadataDetails::IDENTIFIER:
                // Sanitize
                $_mdLabel = $this->request->get('md');

                $md        = null;
                $mdFactory = new Docman_MetadataFactory($this->_viewParams['group_id']);
                $valid     = $this->validateMetadata($_mdLabel, $md);

                if (! $valid) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'Invalid property'));
                    $this->view                              = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = ['action' => \Docman_View_Admin_Metadata::IDENTIFIER];
                } else {
                    $this->view = 'Admin_MetadataDetails';
                    $mdFactory->appendMetadataValueList($md, false);
                    $this->_viewParams['md'] = $md;
                }
                break;
            case \Docman_View_Admin_FilenamePattern::IDENTIFIER:
                $this->view = 'Admin_FilenamePattern';
                break;
            case 'admin_change_filename_pattern':
                Docman_View_Admin_FilenamePattern::getCSRFToken($this->getGroupId())->check();
                $this->action                            = $view;
                $this->_viewParams['default_url_params'] = ['action'  => \Docman_View_Admin_FilenamePattern::IDENTIFIER];
                $this->view                              = 'RedirectAfterCrud';
                break;
            case 'admin_md_details_update':
                \Docman_View_Admin_Metadata::getCSRFToken($this->getGroupId())->check();
                $_name  = trim($this->request->get('name'));
                $_label = $this->request->get('label');

                $mdFactory = $this->_getMetadataFactory($this->_viewParams['group_id']);
                if ($mdFactory->isValidLabel($_label)) {
                    $this->_viewParams['default_url_params'] = ['action'  => \Docman_View_Admin_MetadataDetails::IDENTIFIER, 'md' => $_label];
                    if (Docman_MetadataFactory::isHardCodedMetadata($_label) || $this->validateUpdateMetadata($_name, $_label)) {
                        $this->action = $view;
                    }
                } else {
                    $this->_viewParams['default_url_params'] = ['action'  => \Docman_View_Admin_Metadata::IDENTIFIER];
                }
                $this->view = 'RedirectAfterCrud';
                break;
            case 'admin_create_metadata':
                \Docman_View_Admin_Metadata::getCSRFToken($this->getGroupId())->check();
                $_name     = trim($this->request->get('name'));
                $validator = new CreationMetadataValidator(new Docman_MetadataFactory($this->groupId));
                $valid     = $validator->validateNewMetadata($_name, $this->feedback);

                if ($valid) {
                    $this->action = $view;
                }

                $this->_viewParams['default_url_params'] = ['action' => \Docman_View_Admin_Metadata::IDENTIFIER];
                $this->view                              = 'RedirectAfterCrud';
                break;
            case 'admin_delete_metadata':
                \Docman_View_Admin_Metadata::getCSRFToken($this->getGroupId())->check();
                $valid = false;
                // md
                // Sanitize
                $_mdLabel = $this->request->get('md');

                // Valid
                $logmsg = '';
                $md     = null;
                $vld    = $this->validateMetadata($_mdLabel, $md);
                if ($vld) {
                    if (! Docman_MetadataFactory::isHardCodedMetadata($md->getLabel())) {
                        $valid = true;
                    } else {
                        $logmsg = dgettext('tuleap-docman', 'You are not allowed to delete system properties.');
                    }
                } else {
                    $logmsg = dgettext('tuleap-docman', 'Invalid property');
                }

                if (! $valid) {
                    if ($logmsg != '') {
                        $this->feedback->log('error', $logmsg);
                    }
                    $this->view                              = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = ['action' => \Docman_View_Admin_Metadata::IDENTIFIER];
                } else {
                    $this->action              = $view;
                    $this->_actionParams['md'] = $md;
                }

                break;
            case 'admin_create_love':
                \Docman_View_Admin_Metadata::getCSRFToken($this->getGroupId())->check();
                $mdFactory = $this->_getMetadataFactory($this->_viewParams['group_id']);
                if ($mdFactory->isValidLabel($this->request->get('md'))) {
                    $this->action                            = $view;
                    $this->_viewParams['default_url_params'] = ['action'  => \Docman_View_Admin_MetadataDetails::IDENTIFIER,
                        'md' => $this->request->get('md'),
                    ];
                } else {
                    $this->_viewParams['default_url_params'] = ['action'  => \Docman_View_Admin_Metadata::IDENTIFIER];
                }
                $this->view = 'RedirectAfterCrud';
                break;
            case 'admin_delete_love':
                \Docman_View_Admin_Metadata::getCSRFToken($this->getGroupId())->check();
                $mdFactory = $this->_getMetadataFactory($this->_viewParams['group_id']);
                if ($mdFactory->isValidLabel($this->request->get('md'))) {
                    $this->action                            = $view;
                    $this->_viewParams['default_url_params'] = ['action'  => \Docman_View_Admin_MetadataDetails::IDENTIFIER,
                        'md' => $this->request->get('md'),
                    ];
                } else {
                    $this->_viewParams['default_url_params'] = ['action'  => \Docman_View_Admin_Metadata::IDENTIFIER];
                }
                $this->view = 'RedirectAfterCrud';
                break;
            case \Docman_View_Admin_MetadataDetailsUpdateLove::IDENTIFIER:
                $valid = false;
                // Required params:
                // md (string [a-z_]+)
                // loveid (int)

                // Sanitize
                $_mdLabel = $this->request->get('md');
                $_loveId  = (int) $this->request->get('loveid');

                // Valid
                $md   = null;
                $love = null;
                $this->validateMetadata($_mdLabel, $md);
                if ($md !== null && $md->getLabel() !== 'status') {
                    $valid = $this->validateLove($_loveId, $md, $love);
                }

                if (! $valid) {
                    $this->view                              = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = ['action' => \Docman_View_Admin_Metadata::IDENTIFIER];
                } else {
                    $mdFactory = new Docman_MetadataFactory($this->groupId);
                    $mdFactory->appendMetadataValueList($md, false);

                    $this->view                = 'Admin_MetadataDetailsUpdateLove';
                    $this->_viewParams['md']   = $md;
                    $this->_viewParams['love'] = $love;
                }
                break;
            case 'admin_update_love':
                \Docman_View_Admin_Metadata::getCSRFToken($this->getGroupId())->check();
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
                $_loveId  = (int) $this->request->get('loveid');
                $_rank    = $this->request->get('rank');
                $_name    = $this->request->get('name');
                $_descr   = $this->request->get('descr');

                // Valid
                $md   = null;
                $love = null;
                $this->validateMetadata($_mdLabel, $md);
                if ($md !== null && $md->getLabel() !== 'status') {
                    $valid = $this->validateLove($_loveId, $md, $love);
                }

                if (! $valid) {
                    $this->feedback->log('error', dgettext('tuleap-docman', 'There is an error in parameters. Back to previous screen.'));
                    $this->view                              = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = ['action' => \Docman_View_Admin_Metadata::IDENTIFIER];
                } else {
                    // Set parameters
                    $love->setRank($_rank);
                    $love->setName($_name);
                    $love->setDescription($_descr);

                    // define action
                    $this->action                = $view;
                    $this->_actionParams['md']   = $md;
                    $this->_actionParams['love'] = $love;
                }
                break;

            case \Docman_View_Admin_MetadataImport::IDENTIFIER:
                $ok = false;
                if ($this->request->existAndNonEmpty('plugin_docman_metadata_import_group')) {
                    $pm       = ProjectManager::instance();
                    $srcGroup = $pm->getProjectFromAutocompleter($this->request->get('plugin_docman_metadata_import_group'));
                    if ($srcGroup && ! $srcGroup->isError()) {
                        $this->_viewParams['sSrcGroupId'] = $srcGroup->getGroupId();
                        $this->view                       = 'Admin_MetadataImport';
                        $ok                               = true;
                    }
                }
                if (! $ok) {
                    $this->view                              = 'RedirectAfterCrud';
                    $this->_viewParams['default_url_params'] = ['action' => \Docman_View_Admin_Metadata::IDENTIFIER];
                }
                break;

            case 'admin_import_metadata':
                if ($this->request->existAndNonEmpty('confirm')) {
                    if ($this->request->existAndNonEmpty('plugin_docman_metadata_import_group')) {
                        $pm                                 = ProjectManager::instance();
                        $srcGroup                           = $pm->getProjectFromAutocompleter($this->request->get('plugin_docman_metadata_import_group'));
                        $srcGroupId                         = $srcGroup->getGroupId();
                        $this->_actionParams['sSrcGroupId'] = $srcGroupId;
                        $this->_actionParams['sGroupId']    = $this->_viewParams['group_id'];

                        $this->action = $view;
                    } else {
                        $this->feedback->log('error', dgettext('tuleap-docman', 'Parameter is missing'));
                        $this->feedback->log('info', dgettext('tuleap-docman', 'Operation Canceled'));
                    }
                } else {
                    $this->feedback->log('info', dgettext('tuleap-docman', 'Operation Canceled'));
                }
                $this->view                              = 'RedirectAfterCrud';
                $this->_viewParams['default_url_params'] = ['action' => \Docman_View_Admin_Metadata::IDENTIFIER];
                break;

            case \Docman_View_Admin_Obsolete::IDENTIFIER:
                $this->view = 'Admin_Obsolete';
                break;

            case \Docman_View_Admin_LockInfos::IDENTIFIER:
                $this->view = 'Admin_LockInfos';
                break;
            case 'monitor':
                $redirect_to = '/plugins/document/' . urlencode($this->getProject()->getUnixNameLowerCase()). '/notifications/' . urlencode($item->getId());
                new CSRFSynchronizerToken('plugin-document')->check($redirect_to, $this->request);
                if ($this->request->exist('monitor')) {
                    $this->_actionParams['monitor'] =  $this->request->get('monitor');
                    if ($this->request->exist('cascade')) {
                        $this->_actionParams['cascade'] = $this->request->get('cascade');
                    }
                    $this->_actionParams['item'] = $item;
                    $this->action                = 'monitor';
                }
                $this->view                       = 'RedirectAfterCrud';
                $this->_viewParams['redirect_to'] = $redirect_to;
                break;
            case 'update_monitoring':
                $redirect_to = '/plugins/document/' . urlencode($this->getProject()->getUnixNameLowerCase()). '/notifications/' . urlencode($item->getId());
                new CSRFSynchronizerToken('plugin-document')->check($redirect_to, $this->request);
                $users_to_delete   = $this->request->get('listeners_users_to_delete');
                $ugroups_to_delete = $this->request->get('listeners_ugroups_to_delete');
                $ugroups_to_add  = $this->request->get('listeners_ugroups_to_add');
                $users_to_add  = $this->request->get('listeners_users_to_add');

                if (! $users_to_delete && ! $ugroups_to_delete && ! $ugroups_to_add && !$users_to_add) {
                    $this->feedback->log(
                        Feedback::ERROR,
                        dgettext('tuleap-docman', 'No element selected')
                    );
                } else {
                    if ($users_to_delete || $ugroups_to_delete) {
                        $this->removeMonitoring($item, $users_to_delete, $ugroups_to_delete);
                    }
                    if ($ugroups_to_add || $users_to_add) {
                        $this->addMonitoring($item, $ugroups_to_add, $users_to_add);
                    }
                    $this->_actionParams['item'] = $item;
                    $this->action                = 'update_monitoring';
                }
                $this->view                       = 'RedirectAfterCrud';
                $this->_viewParams['redirect_to'] = $redirect_to;
                break;
            case 'ajax_reference_tooltip':
                $this->view = 'AjaxReferenceTooltip';
                break;

            default:
                $purifier = Codendi_HTMLPurifier::instance();
                die($purifier->purify($view) . ' is not supported');
        }
    }

    public $item_factory;
    public function getItemFactory()
    {
        if (! $this->item_factory) {
            $this->item_factory = new Docman_ItemFactory();
        }
        return $this->item_factory;
    }

    public $metadataFactory;
    private function _getMetadataFactory($groupId)
    {
        if (! isset($metadataFactory[$groupId])) {
            $metadataFactory[$groupId] = new Docman_MetadataFactory($groupId);
        }
        return $metadataFactory[$groupId];
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
                $md    = $_md;
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
        $_love       = $loveFactory->getByElementId($loveId, $md->getLabel());
        if ($_love !== null) {
            // Still Need to verify that $love belong to $md
            $valid = true;
            $love  = $_love;
        }

        return $valid;
    }

    #[\Override]
    public function actionsManagement()
    {
        // Redefine actions classes names building.
        $className = static::class;
        $class     = substr($className, 0, -(strlen('Controller'))) . 'Actions';
        require_once($class . '.php');
        if (! class_exists($class)) {
            throw new LogicException("$class does not exist");
        }
        $wa = new $class($this, $this->gid);
        $wa->process($this->action, $this->_actionParams);
    }

    #[\Override]
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

    /**
     * @return Project
     */
    private function getProject()
    {
        return ProjectManager::instance()->getProject($this->getGroupId());
    }

    /**
     * @param $item
     */
    private function removeMonitoring($item, $users_to_delete_ids, $ugroups_to_delete_ids)
    {
        $this->_actionParams['listeners_users_to_delete']   = [];
        $this->_actionParams['listeners_ugroups_to_delete'] = [];
        if ($this->userCanManage($item->getId())) {
            $user_manager  = UserManager::instance();
            $valid_user_id = new Valid_UInt('listeners_users_to_delete');
            if ($this->request->validArray($valid_user_id)) {
                $users = [];
                foreach ($users_to_delete_ids as $user_id) {
                    $users[] = $user_manager->getUserById($user_id);
                }
                $this->_actionParams['listeners_users_to_delete'] = $users;
                $this->_actionParams['item']                      = $item;
            }
            $ugroup_manager  = new UGroupManager();
            $valid_ugroup_id = new Valid_UInt('listeners_ugroups_to_delete');
            if ($this->request->validArray($valid_ugroup_id)) {
                $ugroups = [];
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
     * @param list<int> $ugroup_ids_to_add
     * @param list<int> $user_ids_to_add
     */
    private function addMonitoring($item, $ugroup_ids_to_add, $user_ids_to_add)
    {
        $this->_actionParams['listeners_ugroups_to_add'] = [];
        $this->_actionParams['listeners_users_to_add'] = [];
        if ($this->userCanManage($item->getId())) {
            $users   = [];
            foreach ($user_ids_to_add as $user_id) {
                $users[] = UserManager::instance()->getUserById($user_id);
            }

            $ugroups = [];
            foreach ($ugroup_ids_to_add as $ugroup_id) {
                $ugroups[] = new UGroupManager()->getById($ugroup_id);
            }

            if (! empty($users)) {
                $this->notificationAddUsers($users);
            }

            if (! empty($ugroups)) {
                $this->notificationAddUgroups($ugroups);
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
}
