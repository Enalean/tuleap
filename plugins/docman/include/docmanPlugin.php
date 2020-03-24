<?php
/**
 * Copyright (c) Enalean, 2015 - Present.
 * Copyright (c) STMicroelectronics, 2006. All Rights Reserved.
 *
 * Originally written by Manuel Vacelet, 2006
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
 * along with Tuleap; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 *
 *
 */

use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\CLI\Events\GetWhitelistedKeys;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\Docman\ApprovalTable\ApprovalTableRetriever;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdateActionChecker;
use Tuleap\Docman\ApprovalTable\ApprovalTableUpdater;
use Tuleap\Docman\DocmanLegacyController;
use Tuleap\Docman\DocmanSettingsSiteAdmin\FilesUploadLimits\DocmanFilesUploadLimitsAdminController;
use Tuleap\Docman\DocmanSettingsSiteAdmin\FilesUploadLimits\DocmanFilesUploadLimitsAdminSaveController;
use Tuleap\Docman\DocmanSettingsSiteAdmin\FilesUploadLimits\DocumentFilesUploadLimitsSaver;
use Tuleap\Docman\DocmanWikisReferencingSameWikiPageRetriever;
use Tuleap\Docman\Download\DocmanFileDownloadController;
use Tuleap\Docman\Download\DocmanFileDownloadCORS;
use Tuleap\Docman\Download\DocmanFileDownloadResponseGenerator;
use Tuleap\Docman\ExternalLinks\DocmanHTTPControllerProxy;
use Tuleap\Docman\ExternalLinks\DocmanLinkProvider;
use Tuleap\Docman\ExternalLinks\ExternalLinkParametersExtractor;
use Tuleap\Docman\ExternalLinks\ILinkUrlProvider;
use Tuleap\Docman\ExternalLinks\LegacyLinkProvider;
use Tuleap\Docman\LegacyRestoreDocumentsController;
use Tuleap\Docman\LegacySendMessageController;
use Tuleap\Docman\Notifications\NotificationsForProjectMemberCleaner;
use Tuleap\Docman\Notifications\NotifiedPeopleRetriever;
use Tuleap\Docman\Notifications\UGroupsRetriever;
use Tuleap\Docman\Notifications\UgroupsToNotifyDao;
use Tuleap\Docman\Notifications\UgroupsToNotifyUpdater;
use Tuleap\Docman\Notifications\UgroupsUpdater;
use Tuleap\Docman\Notifications\UsersRetriever;
use Tuleap\Docman\Notifications\UsersToNotifyDao;
use Tuleap\Docman\Notifications\UsersUpdater;
use Tuleap\Docman\PermissionsPerGroup\PermissionPerGroupDocmanServicePaneBuilder;
use Tuleap\Docman\REST\ResourcesInjector;
use Tuleap\Docman\REST\v1\DocmanItemsEventAdder;
use Tuleap\Docman\Upload\UploadPathAllocatorBuilder;
use Tuleap\Docman\Upload\Version\DocumentOnGoingVersionToUploadDAO;
use Tuleap\Docman\Upload\Version\VersionBeingUploadedInformationProvider;
use Tuleap\Docman\Upload\Version\VersionDataStore;
use Tuleap\Docman\Upload\Version\VersionUploadCanceler;
use Tuleap\Docman\Upload\Version\VersionUploadCleaner;
use Tuleap\Docman\Upload\Version\VersionUploadFinisher;
use Tuleap\Docman\XML\Export\PermissionsExporterDao;
use Tuleap\Docman\XML\Import\ImportPropertiesExtractor;
use Tuleap\Docman\XML\Import\ItemImporter;
use Tuleap\Docman\XML\Import\NodeImporter;
use Tuleap\Docman\XML\Import\PermissionsImporter;
use Tuleap\Docman\XML\Import\PostDoNothingImporter;
use Tuleap\Docman\XML\Import\PostFileImporter;
use Tuleap\Docman\XML\Import\PostFolderImporter;
use Tuleap\Docman\XML\Import\VersionImporter;
use Tuleap\Docman\XML\XMLExporter;
use Tuleap\Docman\XML\XMLImporter;
use Tuleap\Event\Events\ExportXmlProject;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\SessionWriteCloseMiddleware;
use Tuleap\layout\HomePage\StatisticsCollectionCollector;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\PaginationPresenter;
use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\Mail\MailFilter;
use Tuleap\Mail\MailLogger;
use Tuleap\Project\Admin\Navigation\NavigationDropdownItemPresenter;
use Tuleap\Project\Admin\Navigation\NavigationDropdownQuickLinksCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupPaneCollector;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupFormatter;
use Tuleap\Project\Admin\PermissionsPerGroup\PermissionPerGroupUGroupRetriever;
use Tuleap\Project\ProjectAccessChecker;
use Tuleap\Project\RestrictedUserCanAccessProjectVerifier;
use Tuleap\Project\UGroupRetrieverWithLegacy;
use Tuleap\Project\XML\ServiceEnableForXmlImportRetriever;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\REST\BasicAuthentication;
use Tuleap\REST\RESTCurrentUserMiddleware;
use Tuleap\REST\TuleapRESTCORSMiddleware;
use Tuleap\Upload\FileBeingUploadedLocker;
use Tuleap\Upload\FileBeingUploadedWriter;
use Tuleap\Upload\FileUploadController;
use Tuleap\Widget\Event\GetPublicAreas;
use Tuleap\wiki\Events\GetItemsReferencingWikiPageCollectionEvent;

require_once __DIR__ . '/../vendor/autoload.php';

class DocmanPlugin extends Plugin //phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace
{
    public const TRUNCATED_SERVICE_NAME = 'Documents';
    public const SYSTEM_NATURE_NAME     = 'document';
    public const SERVICE_SHORTNAME      = 'docman';

    public const ADMIN_BASE_URL = '/admin/document';

    /**
     * Store docman root items indexed by groupId
     *
     * @var array
     */
    private $rootItems = array();

    /**
     * Store controller instances
     *
     * @var Array
     */
    private $controller = array();

    public function __construct($id)
    {
        parent::__construct($id);
        bindtextdomain('tuleap-docman', __DIR__ . '/../site-content');

        $this->addHook('cssfile', 'cssFile', false);
        $this->addHook('javascript_file');
        $this->addHook('logs_daily', 'logsDaily', false);
        $this->addHook('permission_get_name', 'permission_get_name', false);
        $this->addHook('permission_get_object_type', 'permission_get_object_type', false);
        $this->addHook('permission_get_object_name', 'permission_get_object_name', false);
        $this->addHook('permission_user_allowed_to_change', 'permission_user_allowed_to_change', false);
        $this->addHook(GetPublicAreas::NAME);
        $this->addHook(Event::REGISTER_PROJECT_CREATION, 'installNewDocman', false);
        $this->addHook(Event::SERVICE_IS_USED);
        $this->addHook('soap', 'soap', false);
        $this->addHook(\Tuleap\Widget\Event\GetWidget::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetUserWidgetList::NAME);
        $this->addHook(\Tuleap\Widget\Event\GetProjectWidgetList::NAME);
        $this->addHook('codendi_daily_start', 'codendiDaily', false);
        $this->addHook('wiki_page_updated', 'wiki_page_updated', false);
        $this->addHook('wiki_before_content', 'wiki_before_content', false);
        $this->addHook(Event::WIKI_DISPLAY_REMOVE_BUTTON, 'wiki_display_remove_button', false);
        $this->addHook('isWikiPageReferenced', 'isWikiPageReferenced', false);
        $this->addHook('isWikiPageEditable', 'isWikiPageEditable', false);
        $this->addHook('userCanAccessWikiDocument', 'userCanAccessWikiDocument', false);
        $this->addHook('getPermsLabelForWiki', 'getPermsLabelForWiki', false);
        $this->addHook(\Tuleap\Reference\ReferenceGetTooltipContentEvent::NAME);
        $this->addHook('project_export_entry', 'project_export_entry', false);
        $this->addHook('project_export', 'project_export', false);
        $this->addHook('SystemEvent_PROJECT_RENAME', 'renameProject', false);
        $this->addHook('file_exists_in_data_dir', 'file_exists_in_data_dir', false);
        $this->addHook('webdav_root_for_service', 'webdav_root_for_service', false);
        $this->addHook(Event::SERVICES_ALLOWED_FOR_PROJECT);
        // Stats plugin
        $this->addHook('plugin_statistics_disk_usage_collect_project', 'plugin_statistics_disk_usage_collect_project', false);
        $this->addHook('plugin_statistics_disk_usage_service_label', 'plugin_statistics_disk_usage_service_label', false);
        $this->addHook('plugin_statistics_color', 'plugin_statistics_color', false);

        $this->addHook('show_pending_documents', 'show_pending_documents', false);

        $this->addHook('backend_system_purge_files', 'purgeFiles', false);
        $this->addHook('project_admin_remove_user', 'projectRemoveUser', false);

        $this->addHook('permission_request_information', 'permissionRequestInformation', false);

        $this->addHook('fill_project_history_sub_events', 'fillProjectHistorySubEvents', false);
        $this->addHook('project_is_deleted', 'project_is_deleted', false);
        $this->addHook(Event::PROCCESS_SYSTEM_CHECK);
        $this->addHook(Event::SERVICES_TRUNCATED_EMAILS);

        $this->addHook(Event::GET_REFERENCE);
        $this->addHook('project_admin_ugroup_deletion');
        $this->addHook(Event::PROJECT_ACCESS_CHANGE);
        $this->addHook(Event::SITE_ACCESS_CHANGE);
        $this->addHook(Event::SERVICE_CLASSNAMES);
        $this->addHook(NavigationDropdownQuickLinksCollector::NAME);
        $this->addHook(PermissionPerGroupPaneCollector::NAME);
        $this->addHook('site_admin_option_hook');
    }

    public function getHooksAndCallbacks()
    {
        if (defined('STATISTICS_BASE_DIR')) {
            $this->addHook(Statistics_Event::FREQUENCE_STAT_ENTRIES);
            $this->addHook(Statistics_Event::FREQUENCE_STAT_SAMPLE);
        }

        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);

        $this->addHook(GetWhitelistedKeys::NAME);

        $this->addHook(CollectRoutesEvent::NAME);

        $this->addHook(ServiceUrlCollector::NAME);

        $this->addHook(GetItemsReferencingWikiPageCollectionEvent::NAME);

        $this->addHook(StatisticsCollectionCollector::NAME);
        $this->addHook(ServiceEnableForXmlImportRetriever::NAME);

        $this->addHook(ExportXmlProject::NAME);
        $this->addHook(Event::IMPORT_XML_PROJECT);

        return parent::getHooksAndCallbacks();
    }

    public function getServiceShortname()
    {
        return self::SERVICE_SHORTNAME;
    }

    /** @see Event::SERVICE_CLASSNAMES */
    public function service_classnames(&$params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['classnames'][self::SERVICE_SHORTNAME] = \Tuleap\Docman\ServiceDocman::class;
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_ENTRIES
     */
    public function plugin_statistics_frequence_stat_entries($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['entries'][$this->getServiceShortname()] = 'Documents viewed';
    }

    /**
     * @see Statistics_Event::FREQUENCE_STAT_SAMPLE
     */
    public function plugin_statistics_frequence_stat_sample($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['character'] === $this->getServiceShortname()) {
            $params['sample'] = new Docman_Sample();
        }
    }

    public function permission_get_name($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (!$params['name']) {
            switch ($params['permission_type']) {
                case 'PLUGIN_DOCMAN_READ':
                    $params['name'] = dgettext('tuleap-docman', 'Document Reader');
                    break;
                case 'PLUGIN_DOCMAN_WRITE':
                    $params['name'] = dgettext('tuleap-docman', 'Document Writer');
                    break;
                case 'PLUGIN_DOCMAN_MANAGE':
                    $params['name'] = dgettext('tuleap-docman', 'Document Manager');
                    break;
                case 'PLUGIN_DOCMAN_ADMIN':
                    $params['name'] = dgettext('tuleap-docman', 'Document Administrator');
                    break;
                default:
                    break;
            }
        }
    }
    public function permission_get_object_type($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (!$params['object_type']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if = new Docman_ItemFactory();
                $item = $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $params['object_type'] = is_a($item, 'Docman_Folder') ? 'folder' : 'document';
                }
            }
        }
    }
    public function permission_get_object_name($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (!$params['object_name']) {
            if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                require_once('Docman_ItemFactory.class.php');
                $if = new Docman_ItemFactory();
                $item = $if->getItemFromDb($params['object_id']);
                if ($item) {
                    $params['object_name'] = $item->getTitle();
                }
            }
        }
    }

    public $_cached_permission_user_allowed_to_change; //phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore
    public function permission_user_allowed_to_change($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (!$params['allowed']) {
            if (!$this->_cached_permission_user_allowed_to_change) {
                if (in_array($params['permission_type'], array('PLUGIN_DOCMAN_READ', 'PLUGIN_DOCMAN_WRITE', 'PLUGIN_DOCMAN_MANAGE', 'PLUGIN_DOCMAN_ADMIN'))) {
                    $docman = $this->getHTTPController();
                    switch ($params['permission_type']) {
                        case 'PLUGIN_DOCMAN_READ':
                        case 'PLUGIN_DOCMAN_WRITE':
                        case 'PLUGIN_DOCMAN_MANAGE':
                            $this->_cached_permission_user_allowed_to_change = $docman->userCanManage($params['object_id']);
                            break;
                        default:
                            $this->_cached_permission_user_allowed_to_change = $docman->userCanAdmin();
                            break;
                    }
                }
            }
            $params['allowed'] = $this->_cached_permission_user_allowed_to_change;
        }
    }
    public function &getPluginInfo()
    {
        if (!is_a($this->pluginInfo, 'DocmanPluginInfo')) {
            $this->pluginInfo = new DocmanPluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function cssFile($params): void
    {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if ($this->currentRequestIsForPlugin() ||
            strpos($_SERVER['REQUEST_URI'], '/widgets/') === 0
        ) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getAssets()->getFileURL('default-style.css') . '" />' . "\n";
        }
    }

    public function javascript_file($params): void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // Only show the stylesheet if we're actually in the Docman pages.
        // This stops styles inadvertently clashing with the main site.
        if ($this->currentRequestIsForPlugin()) {
            echo $this->getAssets()->getHTMLSnippet('docman.js');
        }
    }

    private function getAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/docman/',
            '/assets/docman'
        );
    }

    public function logsDaily($params)
    {
        $project = $this->getProject($params['group_id']);
        if ($project->usesService($this->getServiceShortname())) {
            $controler = $this->getHTTPController();
            $controler->logsDaily($params);
        }
    }

    public function service_public_areas(GetPublicAreas $event) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $event->getProject();
        $service = $project->getService($this->getServiceShortname());
        if ($service !== null) {
            $event->addArea(
                '<a href="/plugins/docman/?group_id=' . urlencode((string) $project->getId()) . '">' .
                '<i class="dashboard-widget-content-projectpublicareas ' . Codendi_HTMLPurifier::instance()->purify($service->getIcon()) . '"></i>' .
                dgettext('tuleap-docman', 'Document Manager') . ': ' .
                dgettext('tuleap-docman', 'Project Documentation') .
                '</a>'
            );
        }
    }
    public function installNewDocman($params)
    {
        $controler = $this->getHTTPController();
        $controler->installDocman($params['ugroupsMapping'], $params['group_id']);
    }
    public function service_is_used($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (isset($params['shortname']) && $params['shortname'] == $this->getServiceShortname()) {
            if (isset($params['is_used']) && $params['is_used']) {
                $this->installNewDocman(
                    array('ugroupsMapping' => false, 'group_id' => $params['group_id'])
                );
            }
        }
    }
    public function soap($arams)
    {
        require_once('soap.php');
    }

    public function widgetInstance(\Tuleap\Widget\Event\GetWidget $get_widget_event)
    {
        switch ($get_widget_event->getName()) {
            case 'plugin_docman_mydocman':
                require_once('Docman_Widget_MyDocman.class.php');
                $get_widget_event->setWidget(new Docman_Widget_MyDocman($this->getPluginPath()));
                break;
            case 'plugin_docman_my_embedded':
                require_once('Docman_Widget_MyEmbedded.class.php');
                $get_widget_event->setWidget(new Docman_Widget_MyEmbedded($this->getPluginPath()));
                break;
            case 'plugin_docman_project_embedded':
                require_once('Docman_Widget_ProjectEmbedded.class.php');
                $get_widget_event->setWidget(new Docman_Widget_ProjectEmbedded($this->getPluginPath()));
                break;
            case 'plugin_docman_mydocman_search':
                require_once('Docman_Widget_MyDocmanSearch.class.php');
                $get_widget_event->setWidget(new Docman_Widget_MyDocmanSearch($this->getPluginPath()));
                break;
            default:
                break;
        }
    }

    public function getUserWidgetList(\Tuleap\Widget\Event\GetUserWidgetList $event)
    {
        $event->addWidget('plugin_docman_mydocman');
        $event->addWidget('plugin_docman_mydocman_search');
        $event->addWidget('plugin_docman_my_embedded');
    }

    public function getProjectWidgetList(\Tuleap\Widget\Event\GetProjectWidgetList $event)
    {
        $event->addWidget('plugin_docman_project_embedded');
    }

    public function uninstall()
    {
        $this->removeOrphanWidgets(array(
            'plugin_docman_mydocman',
            'plugin_docman_mydocman_search',
            'plugin_docman_my_embedded',
            'plugin_docman_project_embedded'
        ));
    }

    /**
     * Hook: called by daily codendi script.
     */
    public function codendiDaily()
    {
        $controler = $this->getHTTPController();
        $controler->notifyFuturObsoleteDocuments();
        $reminder = new Docman_ApprovalTableReminder();
        $reminder->remindApprovers();

        $this->cleanUnusedResources();
    }

    public function process()
    {
        $request            = HTTPRequest::instance();
        $user               = $request->getCurrentUser();
        $proxy = new DocmanHTTPControllerProxy(
            EventManager::instance(),
            new ExternalLinkParametersExtractor(),
            $this->getHTTPController(),
            $this->getItemDao()
        );

        $proxy->process($request, $user);
    }


    public function processSOAP($request)
    {
        return $this->getSOAPController($request)->process();
    }

    public function wiki_page_updated($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        require_once('Docman_WikiRequest.class.php');
        $request = new Docman_WikiRequest(array('action' => 'wiki_page_updated',
                                                'wiki_page' => $params['wiki_page'],
                                                'diff_link' => $params['diff_link'],
                                                'group_id'  => $params['group_id'],
                                                'user'      => $params['user'],
                                                'version'   => $params['version']));
        $this->getWikiController($request)->process();
    }

    public function wiki_before_content($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'wiki_before_content';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function wiki_display_remove_button($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'wiki_display_remove_button';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function isWikiPageReferenced($params)
    {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'check_whether_wiki_page_is_referenced';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function isWikiPageEditable($params)
    {
        require_once('Docman_WikiRequest.class.php');
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function userCanAccessWikiDocument($params)
    {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'check_whether_user_can_access';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function getPermsLabelForWiki($params)
    {
        require_once('Docman_WikiRequest.class.php');
        $params['action'] = 'getPermsLabelForWiki';
        $request = new Docman_WikiRequest($params);
        $this->getWikiController($request)->process();
    }

    public function referenceGetTooltipContentEvent(Tuleap\Reference\ReferenceGetTooltipContentEvent $event)
    {
        if ($event->getReference()->getServiceShortName() === 'docman') {
            $request = new Codendi_Request(array(
                'id'       => $event->getValue(),
                'group_id' => $event->getProject()->getID(),
                'action'   => 'ajax_reference_tooltip'
            ));
            $controller = $this->getHTTPController($request);
            ob_start();
            $controller->process();
            $event->setOutput(ob_get_clean());
        }
    }

    /**
     *  hook to display the link to export project data
     *  @param void
     *  @return void
     */
    public function project_export_entry($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        // Docman perms
        $url  = '?group_id=' . $params['group_id'] . '&export=plugin_docman_perms';
        $params['labels']['plugin_eac_docman']                           = dgettext('tuleap-docman', 'Document Manager Permissions');
        $params['data_export_links']['plugin_eac_docman']                = $url . '&show=csv';
        $params['data_export_format_links']['plugin_eac_docman']         = $url . '&show=format';
        $params['history_export_links']['plugin_eac_docman']             = null;
        $params['history_export_format_links']['plugin_eac_docman']      = null;
        $params['dependencies_export_links']['plugin_eac_docman']        = null;
        $params['dependencies_export_format_links']['plugin_eac_docman'] = null;
    }

    /**
     *  hook to display the link to export project data
     *  @param void
     *  @return void
     */
    public function project_export($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['export'] == 'plugin_docman_perms') {
            include_once('Docman_PermissionsExport.class.php');
            $request = HTTPRequest::instance();
            $permExport = new Docman_PermissionsExport($params['project']);
            if ($request->get('show') == 'csv') {
                $permExport->toCSV();
            } else { // show = format
                $permExport->renderDefinitionFormat();
            }
            exit;
        }
    }

    /**
     * Hook called when a project is being renamed
     * @param Array $params
     * @return bool
     */
    public function renameProject($params)
    {
        $docmanPath = $this->getPluginInfo()->getPropertyValueForName('docman_root') . '/';
        //Is this project using docman
        if (is_dir($docmanPath . $params['project']->getUnixName())) {
            $version      = new Docman_VersionFactory();

            return $version->renameProject($docmanPath, $params['project'], $params['new_name']);
        }

        return true;
    }

    /**
     * Hook called before renaming project to check the name validity
     * @param Array $params
     */
    public function file_exists_in_data_dir($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $docmanPath = $this->getPluginInfo()->getPropertyValueForName('docman_root') . '/';
        $path = $docmanPath . $params['new_name'];

        if (Backend::fileExists($path)) {
            $params['result'] = false;
            $params['error'] = dgettext('tuleap-docman', 'A directory already exists with this name under docman');
        }
    }

    /**
     * Hook to know if docman is activated for the given project
     * it returns the root item of that project
     *
     * @param Array $params
     */
    public function webdav_root_for_service($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $groupId = $params['project']->getId();
        if ($params['project']->usesService('docman')) {
            if (!isset($this->rootItems[$groupId])) {
                include_once 'Docman_ItemFactory.class.php';
                $docmanItemFactory = new Docman_ItemFactory();
                $this->rootItems[$groupId] = $docmanItemFactory->getRoot($groupId);
            }
            $params['roots']['docman'] = $this->rootItems[$groupId];
        }
    }

    /**
     * Hook to collect docman disk size usage per project
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_collect_project($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $row             = $params['project_row'];
        $root            = $this->getPluginInfo()->getPropertyValueForName('docman_root');
        $path            = $root . '/' . strtolower($row['unix_group_name']);

        if (! isset($params['time_to_collect']['plugin_docman'])) {
            $params['time_to_collect']['plugin_docman'] = 0;
        }

        $params['DiskUsageManager']->storeForGroup(
            $params['collect_date'],
            $row['group_id'],
            'plugin_docman',
            $path,
            $params['time_to_collect']
        );
    }

    /**
     * Hook to list docman in the list of serices managed by disk stats
     *
     * @param array $params
     */
    public function plugin_statistics_disk_usage_service_label($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $params['services']['plugin_docman'] = 'Docman';
    }

    /**
     * Hook to choose the color of the plugin in the graph
     *
     * @param array $params
     */
    public function plugin_statistics_color($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if ($params['service'] == 'plugin_docman') {
            $params['color'] = 'royalblue';
        }
    }

    /**
     * Hook to list pending documents and/or versions of documents in site admin page
     *
     * @param array $params
     */
    public function show_pending_documents($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $request = HTTPRequest::instance();
        $limit = 25;

        //return all pending versions for given group id
        $offsetVers = $request->getValidated('offsetVers', 'uint', 0);
        if (!$offsetVers || $offsetVers < 0) {
            $offsetVers = 0;
        }

        require_once('Docman_VersionFactory.class.php');
        $version = new Docman_VersionFactory();
        $res = $version->listPendingVersions($params['group_id'], $offsetVers, $limit);
        $html = '';
        $html .= '<section class="tlp-pane">
            <div class="tlp-pane-container">
                <div class="tlp-pane-header">
                    <h1 class="tlp-pane-title">' . dgettext('tuleap-docman', 'Document Manager') . '</h1>
                </div>
                <section class="tlp-pane-section">
                    <h2 class="tlp-pane-subtitle">' . dgettext('tuleap-docman', 'Deleted versions') . '</h2>';
        if (isset($res) && $res) {
            $html .= $this->showPendingVersions($params['csrf_token'], $res['versions'], $params['group_id'], $res['nbVersions'], $offsetVers, $limit);
        } else {
            $html .= '<table class="tlp-table">
                <thead>
                    <tr>
                        <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Item Id') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Document title') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Version label') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Version number') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Delete date') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Forcast purge date') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="tlp-table-cell-empty" colspan="8">
                            ' . dgettext('tuleap-docman', 'There are no pending versions.') . '
                        </td>
                    </tr>
                </tbody>
            </table>';
        }
        $html .= '</section>';
        $params['html'][] = $html;

        //return all pending items for given group id
        $offsetItem = $request->getValidated('offsetItem', 'uint', 0);
        if (!$offsetItem || $offsetItem < 0) {
            $offsetItem = 0;
        }
        require_once('Docman_ItemFactory.class.php');
        $item = new Docman_ItemFactory($params['group_id']);
        $res = $item->listPendingItems($params['group_id'], $offsetItem, $limit);
        $html = '';
        $html .= '<section class="tlp-pane-section">
                <h2 class="tlp-pane-subtitle">' . dgettext('tuleap-docman', 'Deleted items') . '</h2>';
        if (isset($res) && $res) {
            $html .= $this->showPendingItems($params['csrf_token'], $res['items'], $params['group_id'], $res['nbItems'], $offsetItem, $limit);
        } else {
            $html .= '<table class="tlp-table">
                <thead>
                    <tr>
                        <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Item Id') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Item type') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Document title') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Location') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Owner') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Delete date') . '</th>
                        <th>' . dgettext('tuleap-docman', 'Forcast purge date') . '</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="tlp-table-cell-empty" colspan="8">
                            No restorable items found
                        </td>
                    </tr>
                </tbody>
            </table>';
        }
        $html .= '</section>
            </div>
        </section>';
        $params['html'][] = $html;
    }

    public function showPendingVersions(CSRFSynchronizerToken $csrf_token, $versions, $groupId, $nbVersions, $offset, $limit)
    {
        $hp = Codendi_HTMLPurifier::instance();

        $html = '';
        $html .= '<table class="tlp-table">
            <thead>
                <tr>
                    <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Item Id') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Document title') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Version label') . '</th>
                    <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Version number') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Delete date') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Forcast purge date') . '</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

        if ($nbVersions > 0) {
            foreach ($versions as $row) {
                $historyUrl = $this->getPluginPath() . '/index.php?group_id=' . $groupId . '&id=' . $row['item_id'] . '&action=details&section=history';
                $purgeDate = strtotime('+' . $GLOBALS['sys_file_deletion_delay'] . ' day', $row['date']);
                $html .= '<tr>' .
                '<td class="tlp-table-cell-numeric"><a href="' . $historyUrl . '">' . $row['item_id'] . '</a></td>' .
                '<td>' . $hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $groupId) . '</td>' .
                '<td>' . $hp->purify($row['label']) . '</td>' .
                '<td class="tlp-table-cell-numeric">' . $row['number'] . '</td>' .
                '<td>' . html_time_ago($row['date']) . '</td>' .
                '<td>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $purgeDate) . '</td>' .
                '<td class="tlp-table-cell-actions">
                        <form method="post" action="/plugins/docman/restore_documents.php" onsubmit="return confirm(\'Confirm restore of this version\')">
                            ' . $csrf_token->fetchHTMLInput() . '
                            <input type="hidden" name="id" value="' . $hp->purify($row['id']) . '">
                            <input type="hidden" name="item_id" value="' . $hp->purify($row['item_id']) . '">
                            <input type="hidden" name="group_id" value="' . $hp->purify($groupId) . '">
                            <input type="hidden" name="func" value="confirm_restore_version">
                            <button class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline">
                                <i class="fa fa-repeat tlp-button-icon"></i> Restore
                            </button>
                        </form>
                    </td>
                </tr>';
            }
            $html .= '</tbody>
                </table>';

            if ($offset > 0 || ($offset + $limit) < $nbVersions) {
                $pagination = new PaginationPresenter(
                    $limit,
                    $offset,
                    count($versions),
                    $nbVersions,
                    "/admin/show_pending_documents.php",
                    array(
                        'group_id'   => $groupId,
                        'offsetItem' => ($offset + $limit)
                    )
                );

                $html .= '<div class="siteadmin-projects-pending-doc-pagination">';
                $html .= TemplateRendererFactory::build()
                    ->getRenderer(__DIR__)
                    ->renderToString('pagination', $pagination);
                $html .= '</div>';
            }
        } else {
            $html .= '<tr>
                        <td class="tlp-table-cell-empty" colspan="8">
                            ' . dgettext('tuleap-docman', 'There are no pending versions.') . '
                        </td>
                    </tr>
                </tbody>
            </table>';
        }

        return $html;
    }

    public function showPendingItems(CSRFSynchronizerToken $csrf_token, $res, $groupId, $nbItems, $offset, $limit)
    {
        $hp = Codendi_HTMLPurifier::instance();
        require_once('Docman_ItemFactory.class.php');
        $itemFactory = new Docman_ItemFactory($groupId);
        $uh = UserHelper::instance();

        $html = '';
        $html .= '<table class="tlp-table">
            <thead>
                <tr>
                    <th class="tlp-table-cell-numeric">' . dgettext('tuleap-docman', 'Item Id') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Item type') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Document title') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Location') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Owner') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Delete date') . '</th>
                    <th>' . dgettext('tuleap-docman', 'Forcast purge date') . '</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>';

        if ($nbItems > 0) {
            foreach ($res as $row) {
                $purgeDate = strtotime('+' . $GLOBALS['sys_file_deletion_delay'] . ' day', $row['date']);
                $html .= '<tr>' .
                '<td class="tlp-table-cell-numeric">' . $row['id'] . '</td>' .
                '<td>' . $itemFactory->getItemTypeAsText($row['item_type']) . '</td>' .
                '<td>' . $hp->purify($row['title'], CODENDI_PURIFIER_BASIC, $groupId) . '</td>' .
                '<td>' . $hp->purify($row['location']) . '</td>' .
                '<td>' . $hp->purify($uh->getDisplayNameFromUserId($row['user'])) . '</td>' .
                '<td>' . html_time_ago($row['date']) . '</td>' .
                '<td>' . format_date($GLOBALS['Language']->getText('system', 'datefmt'), $purgeDate) . '</td>' .
                '<td class="tlp-table-cell-actions">
                    <form method="post" action="/plugins/docman/restore_documents.php" onsubmit="return confirm(\'Confirm restore of this item\')">
                        ' . $csrf_token->fetchHTMLInput() . '
                        <input type="hidden" name="id" value="' . $hp->purify($row['id']) . '">
                        <input type="hidden" name="group_id" value="' . $hp->purify($groupId) . '">
                        <input type="hidden" name="func" value="confirm_restore_item">
                        <button class="tlp-table-cell-actions-button tlp-button-small tlp-button-primary tlp-button-outline">
                            <i class="fa fa-repeat tlp-button-icon"></i> Restore
                        </button>
                    </form>
                    </td>
                </tr>';
            }
            $html .= '</tbody>
                </table>';

            if ($offset > 0 || ($offset + $limit) < $nbItems) {
                $pagination = new PaginationPresenter(
                    $limit,
                    $offset,
                    count($res),
                    $nbItems,
                    "/admin/show_pending_documents.php",
                    array(
                        'group_id'   => $groupId,
                        'offsetItem' => ($offset + $limit)
                    )
                );

                $html .= '<div class="siteadmin-projects-pending-doc-pagination">';
                $html .= TemplateRendererFactory::build()
                    ->getRenderer(__DIR__)
                    ->renderToString('pagination', $pagination);
                $html .= '</div>';
            }
        } else {
            $html .= '<tr>
                        <td class="tlp-table-cell-empty" colspan="8">
                            ' . dgettext('tuleap-docman', 'There are no pending items.') . '
                        </td>
                    </tr>
                </tbody>
            </table>';
        }

        return $html;
    }

    /**
     * Hook to purge deleted items if their agony ends today
     *
     * @param Array $params
     *
     * @return void
     */
    public function purgeFiles(array $params)
    {
        require_once('Docman_ItemFactory.class.php');
        $itemFactory = new Docman_ItemFactory();
        $itemFactory->purgeDeletedItems($params['time']);

        require_once('Docman_VersionFactory.class.php');
        $versionFactory = new Docman_VersionFactory();
        $versionFactory->purgeDeletedVersions($params['time']);

        $this->cleanUnusedResources();
    }

    /**
     * Function called when a project is deleted.
     * It Marks all project documents as deleted
     *
     * @param mixed $params ($param['group_id'] the ID of the deleted project)
     *
     * @return void
     */
    public function project_is_deleted($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $groupId = $params['group_id'];
        if ($groupId) {
            require_once('Docman_ItemFactory.class.php');
            $docmanItemFactory = new Docman_ItemFactory();
            $docmanItemFactory->deleteProjectTree($groupId);
        }
        $this->cleanUnusedResources();
    }

    /**
     * Function called when a user is removed from a project
     * If a user is removed from a private project, the
     * documents monitored by that user should be monitored no more.
     *
     * @param array $params
     *
     * @return void
     */
    public function projectRemoveUser($params)
    {
        $project_id = $params['group_id'];
        $user_id    = $params['user_id'];

        $project = $this->getProject($project_id);
        $user    = $this->getUserManager()->getUserById($user_id);
        $notifications_for_project_member_cleaner = $this->getNotificationsForProjectMemberCleaner($project);
        $notifications_for_project_member_cleaner->cleanNotificationsAfterUserRemoval($project, $user);
    }

    /**
     * Display information about admin delegation
     *
     * @return void
     */
    public function permissionRequestInformation($params)
    {
        $params['notices'][] = dgettext('tuleap-docman', 'The selected group will not affect people notified for permission requests on documents service.<br> Only the <b>document managers</b> will be notified in that case.');
    }

    /**
     * Fill the list of subEvents related to docman in the project history interface
     *
     */
    public function fillProjectHistorySubEvents($params)
    {
        array_push(
            $params['subEvents']['event_permission'],
            'perm_reset_for_document',
            'perm_granted_for_document',
            'perm_reset_for_folder',
            'perm_granted_for_folder'
        );
    }

    protected function getWikiController($request)
    {
        return $this->getController('Docman_WikiController', $request);
    }

    protected function getHTTPController($request = null)
    {
        if ($request == null) {
            $request = HTTPRequest::instance();
        }
        return $this->getController('Docman_HTTPController', $request);
    }

    protected function getSOAPController($request)
    {
        return $this->getController('Docman_SOAPController', $request);
    }

    protected function getController($controller, $request)
    {
        if (!isset($this->controller[$controller])) {
            include_once $controller . '.class.php';
            $this->controller[$controller] = new $controller($this, $this->getPluginPath(), $this->getThemePath(), $request);
        } else {
            $this->controller[$controller]->setRequest($request);
        }
        return $this->controller[$controller];
    }

    public function proccess_system_check($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $docman_system_check = new Docman_SystemCheck(
            $this,
            new Docman_SystemCheckProjectRetriever(new Docman_SystemCheckDao()),
            BackendSystem::instance(),
            new PluginConfigChecker($params['logger']),
            $params['logger']
        );

        $docman_system_check->process();
    }

    public function services_truncated_emails($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project = $params['project'];
        if ($project->usesService('docman')) {
            $params['services'][] = dgettext('tuleap-docman', 'Documents');
        }
    }

    /**
     * @return Project
     */
    private function getProject($group_id)
    {
        return ProjectManager::instance()->getProject($group_id);
    }

    /**
     * @return MailBuilder
     */
    private function getMailBuilder()
    {
        return new MailBuilder(
            TemplateRendererFactory::build(),
            new MailFilter(
                UserManager::instance(),
                new ProjectAccessChecker(
                    PermissionsOverrider_PermissionsOverriderManager::instance(),
                    new RestrictedUserCanAccessProjectVerifier(),
                    EventManager::instance()
                ),
                new MailLogger()
            )
        );
    }

    public function get_reference($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $keyword       = $params['keyword'];
        $reference_row = $this->getSystemDocmanReferenceByKeyword($keyword);

        if ($reference_row) {
            $docman_element_id   = $params['value'];
            $docman_item_factory = new Docman_ItemFactory();
            $reference_factory   = new Docman_ReferenceFactory();

            $docman_item = $docman_item_factory->getItemFromDb($docman_element_id);

            if ($docman_item) {
                $reference = $reference_factory->getInstanceFromRowAndProjectId(
                    $reference_row,
                    $docman_item->getGroupId()
                );

                $params['reference'] = $reference;
            }
        }
    }

    private function getSystemDocmanReferenceByKeyword($keyword)
    {
        $dao    = new ReferenceDao();
        $result = $dao->searchSystemReferenceByNatureAndKeyword($keyword, self::SYSTEM_NATURE_NAME);

        if (! $result || $result->rowCount() < 1) {
            return null;
        }

        return $result->getRow();
    }

    public function project_admin_ugroup_deletion($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $project_id = $params['group_id'];
        $ugroup     = $params['ugroup'];

        $ugroups_to_notify_dao = $this->getUGroupToNotifyDao();
        $ugroups_to_notify_dao->deleteByUgroupId($project_id, $ugroup->getId());
    }

    /** @see Event::PROJECT_ACCESS_CHANGE */
    public function projectAccessChange(array $params): void
    {
        $project_id = $params['project_id'];
        $old_access = $params['old_access'];
        $new_access = $params['access'];

        $updater = $this->getUgroupsToNotifyUpdater();
        $updater->updateProjectAccess($project_id, $old_access, $new_access);

        $project = $this->getProject($project_id);
        $notifications_for_project_member_cleaner = $this->getNotificationsForProjectMemberCleaner($project);
        $notifications_for_project_member_cleaner->cleanNotificationsAfterProjectVisibilityChange($project, $new_access);
    }

    public function site_access_change($params) //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        $old_access = $params['old_value'];

        $updater = $this->getUgroupsToNotifyUpdater();
        $updater->updateSiteAccess($old_access);
    }

    private function getUgroupsToNotifyUpdater()
    {
        return new UgroupsToNotifyUpdater(
            $this->getUGroupToNotifyDao()
        );
    }

    private function getNotificationsForProjectMemberCleaner(Project $project)
    {
        return new NotificationsForProjectMemberCleaner(
            $this->getItemFactory($project->getID()),
            new Docman_NotificationsManager(
                $project,
                $this->getProvider($project),
                null,
                $this->getMailBuilder(),
                $this->getUsersToNotifyDao(),
                $this->getUsersNotificationRetriever(),
                $this->getUGroupsRetriever(),
                $this->getNotifiedPeopleRetriever(),
                $this->getUsersUpdater(),
                $this->getUGroupsUpdater()
            ),
            $this->getUserManager()
        );
    }

    private function getUsersToNotifyDao()
    {
        return new UsersToNotifyDao();
    }

    private function getUGroupManager()
    {
        return new UGroupManager(
            new UGroupDao(),
            new EventManager(),
            new UGroupUserDao()
        );
    }

    private function getUGroupToNotifyDao()
    {
        return new UgroupsToNotifyDao();
    }

    private function getUsersNotificationRetriever()
    {
        return new UsersRetriever(
            $this->getUsersToNotifyDao(),
            $this->getItemFactory()
        );
    }
    private function getUGroupsRetriever()
    {
        return new UGroupsRetriever($this->getUGroupToNotifyDao(), $this->getItemFactory());
    }

    private function getNotifiedPeopleRetriever()
    {
        return new NotifiedPeopleRetriever(
            $this->getUsersToNotifyDao(),
            $this->getUGroupToNotifyDao(),
            $this->getItemFactory(),
            $this->getUGroupManager()
        );
    }

    private function getItemFactory($project_id = null)
    {
        return new Docman_ItemFactory($project_id);
    }

    private function getUserManager()
    {
        return UserManager::instance();
    }

    private function getUGroupsUpdater()
    {
        return new UgroupsUpdater($this->getUGroupToNotifyDao());
    }

    private function getUsersUpdater()
    {
        return new UsersUpdater($this->getUsersToNotifyDao());
    }

    public function collectProjectAdminNavigationPermissionDropdownQuickLinks(NavigationDropdownQuickLinksCollector $quick_links_collector)
    {
        $project = $quick_links_collector->getProject();

        if (! $project->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $quick_links_collector->addQuickLink(
            new NavigationDropdownItemPresenter(
                dgettext('tuleap-docman', 'Document manager'),
                $this->getPluginPath() . '/?' . http_build_query(
                    array(
                        'group_id' => $project->getID(),
                        'action'   => 'admin_permissions'
                    )
                )
            )
        );
    }

    public function permissionPerGroupPaneCollector(PermissionPerGroupPaneCollector $event)
    {
        if (! $event->getProject()->usesService(self::SERVICE_SHORTNAME)) {
            return;
        }

        $ugroup_manager = new UGroupManager();

        $service_pane_builder = new PermissionPerGroupDocmanServicePaneBuilder(
            new PermissionPerGroupUGroupRetriever(PermissionsManager::instance()),
            new PermissionPerGroupUGroupFormatter($ugroup_manager),
            $ugroup_manager
        );

        $template_factory      = TemplateRendererFactory::build();
        $admin_permission_pane = $template_factory
            ->getRenderer(dirname(PLUGIN_DOCMAN_BASE_DIR) . '/templates')
            ->renderToString(
                'project-admin-permission-per-group',
                $service_pane_builder->buildPresenter($event)
            );

        $project         = $event->getProject();
        $service         = $project->getService($this->getServiceShortname());
        if ($service !== null) {
            $rank_in_project = $service->getRank();
            $event->addPane($admin_permission_pane, $rank_in_project);
        }
    }

    /** @see Event::REST_RESOURCES */
    public function restResources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /** @see \Event::REST_PROJECT_RESOURCES */
    public function rest_project_resources(array $params) : void //phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        ResourcesInjector::declareProjectResources($params['resources'], $params['project']);
    }

    public function routeUploadsDocmanFile(): FileUploadController
    {
        $document_ongoing_upload_dao = new \Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO();
        $root_path                   = $this->getPluginInfo()->getPropertyValueForName('docman_root');
        $path_allocator              = (new UploadPathAllocatorBuilder())->getDocumentUploadPathAllocator();
        $user_manager                = UserManager::instance();
        $event_manager               = EventManager::instance();

        return FileUploadController::build(
            new \Tuleap\Docman\Upload\Document\DocumentDataStore(
                new \Tuleap\Docman\Upload\Document\DocumentBeingUploadedInformationProvider(
                    $path_allocator,
                    $document_ongoing_upload_dao,
                    $this->getItemFactory()
                ),
                new \Tuleap\Upload\FileBeingUploadedWriter(
                    $path_allocator,
                    DBFactory::getMainTuleapDBConnection()
                ),
                new FileBeingUploadedLocker(
                    $path_allocator
                ),
                new \Tuleap\Docman\Upload\Document\DocumentUploadFinisher(
                    BackendLogger::getDefaultLogger(),
                    $path_allocator,
                    $this->getItemFactory(),
                    new Docman_VersionFactory(),
                    $event_manager,
                    $document_ongoing_upload_dao,
                    $this->getItemDao(),
                    new Docman_FileStorage($root_path),
                    new Docman_MIMETypeDetector(),
                    $user_manager,
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                    new DocmanItemsEventAdder($event_manager),
                    ProjectManager::instance()
                ),
                new \Tuleap\Docman\Upload\Document\DocumentUploadCanceler(
                    $path_allocator,
                    $document_ongoing_upload_dao
                )
            )
        );
    }

    public function routeUploadsVersionFile(): FileUploadController
    {
        $root_path                = $this->getPluginInfo()->getPropertyValueForName('docman_root');
        $path_allocator           = (new UploadPathAllocatorBuilder())->getVersionUploadPathAllocator();
        $version_to_upload_dao    = new DocumentOnGoingVersionToUploadDAO();
        $event_manager            = EventManager::instance();
        $approval_table_retriever = new ApprovalTableRetriever(
            new Docman_ApprovalTableFactoriesFactory(),
            new Docman_VersionFactory()
        );
        return FileUploadController::build(
            new VersionDataStore(
                new VersionBeingUploadedInformationProvider(
                    $version_to_upload_dao,
                    $this->getItemFactory(),
                    $path_allocator
                ),
                new FileBeingUploadedWriter(
                    $path_allocator,
                    DBFactory::getMainTuleapDBConnection()
                ),
                new VersionUploadFinisher(
                    BackendLogger::getDefaultLogger(),
                    $path_allocator,
                    $this->getItemFactory(),
                    new Docman_VersionFactory(),
                    $event_manager,
                    $version_to_upload_dao,
                    new DBTransactionExecutorWithConnection(DBFactory::getMainTuleapDBConnection()),
                    new Docman_FileStorage($root_path),
                    new Docman_MIMETypeDetector(),
                    UserManager::instance(),
                    new DocmanItemsEventAdder($event_manager),
                    ProjectManager::instance(),
                    $this->getDocmanLockFactory(),
                    new ApprovalTableUpdater($approval_table_retriever, new Docman_ApprovalTableFactoriesFactory()),
                    $approval_table_retriever,
                    new ApprovalTableUpdateActionChecker($approval_table_retriever)
                ),
                new VersionUploadCanceler($path_allocator, $version_to_upload_dao),
                new FileBeingUploadedLocker($path_allocator)
            )
        );
    }

    public function routeLegacyRestoreDocumentsController() : LegacyRestoreDocumentsController
    {
        return new LegacyRestoreDocumentsController($this);
    }

    public function routeLegacySendMessageController() : LegacySendMessageController
    {
        return new LegacySendMessageController(ProjectManager::instance());
    }

    public function routeLegacyController() : DocmanLegacyController
    {
        return new DocmanLegacyController(
            $this,
            EventManager::instance(),
            new ExternalLinkParametersExtractor(),
            $this->getItemDao()
        );
    }

    public function routeFileDownload() : DocmanFileDownloadController
    {
        $response_factory = HTTPFactoryBuilder::responseFactory();
        return new DocmanFileDownloadController(
            new SapiStreamEmitter(),
            new Docman_ItemFactory(),
            new DocmanFileDownloadResponseGenerator(
                new Docman_VersionFactory(),
                new BinaryFileResponseBuilder($response_factory, HTTPFactoryBuilder::streamFactory())
            ),
            BackendLogger::getDefaultLogger(),
            new SessionWriteCloseMiddleware(),
            new RESTCurrentUserMiddleware(\Tuleap\REST\UserManager::build(), new BasicAuthentication()),
            new TuleapRESTCORSMiddleware(),
            new DocmanFileDownloadCORS($response_factory)
        );
    }


    public function collectRoutesEvent(CollectRoutesEvent $event) : void
    {
        $route_collector = $event->getRouteCollector();

        $route_collector->addRoute(['OPTIONS', 'HEAD', 'PATCH', 'DELETE', 'POST', 'PUT'], '/uploads/docman/file/{id:\d+}', $this->getRouteHandler('routeUploadsDocmanFile'));
        $route_collector->addRoute(['OPTIONS', 'HEAD', 'PATCH', 'DELETE', 'POST', 'PUT'], '/uploads/docman/version/{id:\d+}', $this->getRouteHandler('routeUploadsVersionFile'));

        $route_collector->addRoute(['GET'], self::ADMIN_BASE_URL . "/files-upload-limits", $this->getRouteHandler('routeGetDocumentSettings'));
        $route_collector->addRoute(['POST'], self::ADMIN_BASE_URL . "/files-upload-limits", $this->getRouteHandler('routePostDocumentSettings'));

        $route_collector->addGroup('/plugins/docman', function (FastRoute\RouteCollector $r) {
            $r->addRoute(['GET', 'POST'], '/restore_documents.php', $this->getRouteHandler('routeLegacyRestoreDocumentsController'));
            $r->post('/sendmessage.php', $this->getRouteHandler('routeLegacySendMessageController'));
            $r->addRoute(['GET', 'POST'], '[/[index.php]]', $this->getRouteHandler('routeLegacyController'));
            $r->get('/download/{file_id:\d+}[/{version_id:\d+}]', $this->getRouteHandler('routeFileDownload'));
        });
    }

    public function routeGetDocumentSettings(): DocmanFilesUploadLimitsAdminController
    {
        return new DocmanFilesUploadLimitsAdminController(
            new AdminPageRenderer()
        );
    }

    public function routePostDocumentSettings(): DocmanFilesUploadLimitsAdminSaveController
    {
        return new DocmanFilesUploadLimitsAdminSaveController(
            new DocumentFilesUploadLimitsSaver(
                new ConfigDao()
            )
        );
    }

    private function cleanUnusedResources()
    {
        $this->cleanUnusedDocumentResources();
        $this->cleanUnusedVersionResources();
    }

    private function cleanUnusedDocumentResources(): void
    {
        $cleaner = new \Tuleap\Docman\Upload\Document\DocumentUploadCleaner(
            (new UploadPathAllocatorBuilder())->getDocumentUploadPathAllocator(),
            new \Tuleap\Docman\Upload\Document\DocumentOngoingUploadDAO()
        );
        $cleaner->deleteDanglingDocumentToUpload(new \DateTimeImmutable());
    }

    // @codingStandardsIgnoreLine
    public function site_admin_option_hook(array &$params)
    {
        $params['plugins'][] = [
            'label' => dgettext('tuleap-docman', 'Document'),
            'href'  => self::ADMIN_BASE_URL . '/files-upload-limits'
        ];
    }

    private function cleanUnusedVersionResources(): void
    {
        $cleaner = new VersionUploadCleaner(
            (new UploadPathAllocatorBuilder())->getVersionUploadPathAllocator(),
            new DocumentOnGoingVersionToUploadDAO()
        );
        $cleaner->deleteDanglingVersionToUpload(new \DateTimeImmutable());
    }

    public function getWhitelistedKeys(GetWhitelistedKeys $event)
    {
        $event->addPluginsKeys(PLUGIN_DOCMAN_MAX_FILE_SIZE_SETTING);
        $event->addPluginsKeys(PLUGIN_DOCMAN_MAX_NB_FILE_UPLOADS_SETTING);
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector): void
    {
        if ($collector->getServiceShortname() !== $this->getServiceShortname()) {
            return;
        }

        $collector->setUrl(DOCMAN_BASE_URL . "?group_id=" . $collector->getProject()->getID());
    }

    public function getItemsReferencingWikiPageCollectionEvent(GetItemsReferencingWikiPageCollectionEvent $event) : void
    {
        $wiki_page = $event->getWikiPage();

        $wikis_retriever = new DocmanWikisReferencingSameWikiPageRetriever(
            $this->getItemFactory($wiki_page->getGid()),
            Docman_PermissionsManager::instance($wiki_page->getGid())
        );

        $event->addItemsReferencingWikiPage(
            $wikis_retriever->retrieveWikiDocuments($wiki_page, $event->getUser())
        );
    }

    private function getDocmanLockFactory(): Docman_LockFactory
    {
        return new \Docman_LockFactory(new \Docman_LockDao(), new Docman_Log());
    }

    private function getProvider(Project $project): ILinkUrlProvider
    {
        $provider = new LegacyLinkProvider(
            HTTPRequest::instance()->getServerUrl() . '/?group_id=' . urlencode((string) $project->getID())
        );
        $link_provider = new DocmanLinkProvider($project, $provider);
        EventManager::instance()->processEvent($link_provider);

        return $link_provider->getProvider();
    }

    public function statisticsCollectionCollector(StatisticsCollectionCollector $collector): void
    {
        $collector->addStatistics(
            dgettext('tuleap-document', 'Documents'),
            $this->getItemDao()->countDocument(),
            $this->getItemDao()->countDocumentAfter($collector->getTimestamp())
        );
    }

    private function getItemDao(): Docman_ItemDao
    {
        return new Docman_ItemDao();
    }

    public function serviceEnableForXmlImportRetriever(ServiceEnableForXmlImportRetriever $event) : void
    {
        $event->addServiceIfPluginIsNotRestricted($this, $this->getServiceShortname());
    }

    public function exportXmlProject(ExportXmlProject $event): void
    {
        $project = $event->getProject();
        if (! $project->usesService($this->getServiceShortname())) {
            return;
        }

        $ugroup_retriever                   = new UGroupRetrieverWithLegacy(new UGroupManager());
        $project_ugroups_id_indexed_by_name = $ugroup_retriever->getProjectUgroupIds($project);

        $archive = $event->getArchive();
        $archive->addEmptyDir('documents');

        $export = new XMLExporter(
            $event->getLogger(),
            Docman_ItemFactory::instance($project->getGroupId()),
            new Docman_VersionFactory(),
            UserManager::instance(),
            $event->getUserXMLExporter(),
            new \Tuleap\Docman\XML\Export\PermissionsExporter(
                new PermissionsExporterDao(),
                $project_ugroups_id_indexed_by_name
            )
        );
        $export->export($project, $event->getIntoXml(), $archive);
    }

    /** @see Event::IMPORT_XML_PROJECT */
    public function importXmlProject(array $params): void
    {
        if (empty($params['xml_content']->docman)) {
            return;
        }
        $root_path = $this->getPluginInfo()->getPropertyValueForName('docman_root');

        $logger = new WrapperLogger($params['logger'], 'docman');
        $logger->info('Start import');

        $user_finder = $params['user_finder'];
        assert($user_finder instanceof \User\XML\Import\IFindUserFromXMLReference);

        $current_user = UserManager::instance()->getCurrentUser();
        assert($current_user !== null);

        $project             = $params['project'];
        $docman_item_factory = Docman_ItemFactory::instance($project->getGroupId());
        $current_date        = new DateTimeImmutable();
        $xml_importer        = new XMLImporter(
            $docman_item_factory,
            $project,
            $logger,
            new NodeImporter(
                new ItemImporter(
                    new PermissionsImporter(
                        $logger,
                        PermissionsManager::instance(),
                        new UGroupRetrieverWithLegacy(new UGroupManager()),
                        $project
                    ),
                    $docman_item_factory
                ),
                new PostFileImporter(
                    new VersionImporter(
                        $user_finder,
                        new Docman_VersionFactory(),
                        new Docman_FileStorage($root_path),
                        $project,
                        $params['extraction_path'],
                        $current_date,
                        $current_user
                    ),
                    $logger
                ),
                new PostFolderImporter(),
                new PostDoNothingImporter(),
                $logger,
                new ImportPropertiesExtractor($current_date, $current_user, $user_finder)
            ),
            new XML_RNGValidator()
        );
        $xml_importer->import($params['xml_content']->docman);

        $logger->info('Import completed');
    }
}
