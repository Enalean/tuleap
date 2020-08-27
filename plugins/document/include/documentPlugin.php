<?php
/**
 * Copyright (c) Enalean, 2018 - present. All Rights Reserved.
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

use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Tuleap\Docman\DocmanSettingsSiteAdmin\DocmanSettingsTabsPresenterCollection;
use Tuleap\Docman\ExternalLinks\DocmanLinkProvider;
use Tuleap\Docman\ExternalLinks\ExternalLinkRedirector;
use Tuleap\Docman\ExternalLinks\ExternalLinksManager;
use Tuleap\Docman\ExternalLinks\Link;
use Tuleap\Document\Config\Admin\FilesDownloadLimitsAdminController;
use Tuleap\Document\Config\Admin\FilesDownloadLimitsAdminSaveController;
use Tuleap\Document\Config\Admin\HistoryEnforcementAdminController;
use Tuleap\Document\Config\Admin\HistoryEnforcementAdminSaveController;
use Tuleap\Document\Config\FileDownloadLimitsBuilder;
use Tuleap\Document\Config\HistoryEnforcementSettingsBuilder;
use Tuleap\Document\DocumentUsageRetriever;
use Tuleap\Document\DownloadFolderAsZip\DocumentFolderZipStreamer;
use Tuleap\Document\DownloadFolderAsZip\ZipStreamerLoggingHelper;
use Tuleap\Document\DownloadFolderAsZip\ZipStreamMailNotificationSender;
use Tuleap\Document\LinkProvider\DocumentLinkProvider;
use Tuleap\Document\PermissionDeniedDocumentMailSender;
use Tuleap\Document\Tree\DocumentTreeController;
use Tuleap\Document\Tree\DocumentTreeProjectExtractor;
use Tuleap\Error\PlaceHolderBuilder;
use Tuleap\Http\HTTPFactoryBuilder;
use Tuleap\Http\Response\BinaryFileResponseBuilder;
use Tuleap\Http\Server\ServiceInstrumentationMiddleware;
use Tuleap\Layout\ServiceUrlCollector;
use Tuleap\Project\Flags\ProjectFlagsBuilder;
use Tuleap\Project\Flags\ProjectFlagsDao;
use Tuleap\Request\CollectRoutesEvent;

require_once __DIR__ . '/../../docman/include/docmanPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';

class documentPlugin extends Plugin // phpcs:ignore
{
    /**
     * @var DocmanPluginInfo
     */
    private $old_docman_plugin_info;

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        bindtextdomain('tuleap-document', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook(CollectRoutesEvent::NAME);
        $this->addHook(ExternalLinksManager::NAME);
        $this->addHook(ExternalLinkRedirector::NAME);
        $this->addHook(ServiceUrlCollector::NAME);
        $this->addHook(DocmanLinkProvider::NAME);
        $this->addHook(DocmanSettingsTabsPresenterCollection::NAME);

        return parent::getHooksAndCallbacks();
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new \Tuleap\Document\Plugin\PluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function getOldPluginInfo(): DocmanPluginInfo
    {
        if (! $this->old_docman_plugin_info) {
            $this->old_docman_plugin_info = new DocmanPluginInfo($this->getDocmanPlugin());
        }
        return $this->old_docman_plugin_info;
    }

    public function getDependencies()
    {
        return ['docman'];
    }

    public function routeGet(): DocumentTreeController
    {
        return new DocumentTreeController(
            $this->getProjectExtractor(),
            $this->getOldPluginInfo(),
            new FileDownloadLimitsBuilder(),
            new HistoryEnforcementSettingsBuilder(),
            new ProjectFlagsBuilder(new ProjectFlagsDao()),
        );
    }

    public function routeDownloadFolderAsZip(): DocumentFolderZipStreamer
    {
        return new DocumentFolderZipStreamer(
            new BinaryFileResponseBuilder(
                HTTPFactoryBuilder::responseFactory(),
                HTTPFactoryBuilder::streamFactory()
            ),
            $this->getProjectExtractor(),
            UserManager::instance(),
            new ZipStreamerLoggingHelper(),
            new ZipStreamMailNotificationSender(),
            new \Tuleap\Document\DownloadFolderAsZip\FolderSizeIsAllowedChecker(
                new \Tuleap\Docman\REST\v1\Folders\ComputeFolderSizeVisitor(),
            ),
            new \Tuleap\Document\Config\FileDownloadLimitsBuilder(),
            new SapiEmitter(),
            new \Tuleap\Http\Server\SessionWriteCloseMiddleware(),
            new ServiceInstrumentationMiddleware('document')
        );
    }

    public function routeSendRequestMail(): PermissionDeniedDocumentMailSender
    {
        return new PermissionDeniedDocumentMailSender(
            new PlaceHolderBuilder(\ProjectManager::instance()),
            new CSRFSynchronizerToken('plugin-document')
        );
    }

    public function collectRoutesEvent(CollectRoutesEvent $event): void
    {
        $event->getRouteCollector()->addGroup('/plugins/document', function (FastRoute\RouteCollector $r) {
            $r->post(
                '/PermissionDeniedRequestMessage/{project_id:\d+}',
                $this->getRouteHandler('routeSendRequestMail')
            );
            $r->get(
                '/{project_name:[A-z0-9-]+}/folders/{folder_id:\d+}/download-folder-as-zip',
                $this->getRouteHandler('routeDownloadFolderAsZip')
            );
            $r->get('/{project_name:[A-z0-9-]+}/[{vue-routing:.*}]', $this->getRouteHandler('routeGet'));
        });

        $event->getRouteCollector()->addGroup(\DocmanPlugin::ADMIN_BASE_URL, function (FastRoute\RouteCollector $r) {
            $r->get('/files-download-limits', $this->getRouteHandler('routeGetDocumentSettings'));
            $r->post('/files-download-limits', $this->getRouteHandler('routePostDocumentSettings'));
            $r->get('/history-enforcement', $this->getRouteHandler('routeGetHistoryEnforcementSettings'));
            $r->post('/history-enforcement', $this->getRouteHandler('routePostHistoryEnforcementSettings'));
        });
    }

    public function routeGetDocumentSettings(): FilesDownloadLimitsAdminController
    {
        return FilesDownloadLimitsAdminController::buildSelf();
    }

    public function routePostDocumentSettings(): FilesDownloadLimitsAdminSaveController
    {
        return FilesDownloadLimitsAdminSaveController::buildSelf();
    }

    public function routeGetHistoryEnforcementSettings(): HistoryEnforcementAdminController
    {
        return HistoryEnforcementAdminController::buildSelf();
    }

    public function routePostHistoryEnforcementSettings(): HistoryEnforcementAdminSaveController
    {
        return HistoryEnforcementAdminSaveController::buildSelf();
    }

    public function externalLinksManager(ExternalLinksManager $collector)
    {
        if (! PluginManager::instance()->isPluginAllowedForProject($this, $collector->getProjectId())) {
            return;
        }

        $project = ProjectManager::instance()->getProject($collector->getProjectId());

        $collector->addExternalLink(new Link($project, $collector->getFolderId()));
    }

    public function externalLinkRedirector(ExternalLinkRedirector $external_link_redirector)
    {
        $project_id = $external_link_redirector->getProject()->getID();
        if (! PluginManager::instance()->isPluginAllowedForProject($this, $project_id)) {
            return;
        }

        $external_link_redirector->checkAndStoreIfUserHasToBeenRedirected(
            $this->shouldUseDocumentUrl($external_link_redirector->getProject())
        );
    }

    public function getProjectExtractor(): DocumentTreeProjectExtractor
    {
        return new DocumentTreeProjectExtractor(ProjectManager::instance());
    }

    public function serviceUrlCollector(ServiceUrlCollector $collector): void
    {
        if (! PluginManager::instance()->isPluginAllowedForProject($this, $collector->getProject()->getID())) {
            return;
        }

        if ($collector->getServiceShortname() !== $this->getDocmanPlugin()->getServiceShortname()) {
            return;
        }

        if (! $this->shouldUseDocumentUrl($collector->getProject())) {
            return;
        }

        $collector->setUrl("/plugins/document/" . urlencode($collector->getProject()->getUnixNameLowerCase()) . "/");
    }

    private function getDocmanPlugin(): DocmanPlugin
    {
        return PluginManager::instance()->getPluginByName('docman');
    }

    private function getCurrentUser(): PFUser
    {
        $user_manager = UserManager::instance();
        return $user_manager->getCurrentUser();
    }

    private function shouldUseDocumentUrl(Project $project): bool
    {
        $retriever = new DocumentUsageRetriever();
        $user      = $this->getCurrentUser();
        return $retriever->shouldUseDocument($user, $project);
    }

    public function docmanLinkProvider(DocmanLinkProvider $link_provider)
    {
        $project = $link_provider->getProject();
        $link_provider->replaceProvider(new DocumentLinkProvider(HTTPRequest::instance()->getServerUrl(), $project));
    }

    public function docmanSettingsTabsPresenterCollection(DocmanSettingsTabsPresenterCollection $collection): void
    {
        $collection->add(
            new \Tuleap\Document\Config\Admin\FileDownloadTabPresenter()
        );

        $collection->add(
            new \Tuleap\Document\Config\Admin\HistoryEnforcementTabPresenter()
        );
    }
}
