<?php
/**
 * Copyright (c) Enalean, 2016-Present. All Rights Reserved.
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


require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use Tuleap\AgileDashboard\Milestone\Pane\PaneInfoCollector;
use Tuleap\DB\DBFactory;
use Tuleap\DB\DBTransactionExecutorWithConnection;
use Tuleap\FRS\AdditionalInformationPresenter;
use Tuleap\FRS\Events\GetReleaseNotesLink;
use Tuleap\FRS\Link\Dao;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\Link\Updater;
use Tuleap\FRS\PluginInfo;
use Tuleap\FRS\REST\ResourcesInjector;
use Tuleap\FRS\Upload\FileOngoingUploadDao;
use Tuleap\FRS\Upload\FileUploadCleaner;
use Tuleap\FRS\Upload\Tus\FileBeingUploadedInformationProvider;
use Tuleap\FRS\Upload\Tus\FileDataStore;
use Tuleap\FRS\Upload\Tus\FileUploadCanceler;
use Tuleap\FRS\Upload\Tus\FileUploadFinisher;
use Tuleap\FRS\Upload\Tus\ToBeCreatedFRSFileBuilder;
use Tuleap\FRS\Upload\UploadPathAllocator;
use Tuleap\Request\CollectRoutesEvent;
use Tuleap\Tracker\Artifact\ActionButtons\MoveArtifactActionAllowedByPluginRetriever;
use Tuleap\Tracker\REST\v1\Event\ArtifactPartialUpdate;
use Tuleap\Upload\FileBeingUploadedLocker;
use Tuleap\Upload\FileBeingUploadedWriter;
use Tuleap\Upload\FileUploadController;

class frsPlugin extends \Plugin // phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace,Squiz.Classes.ValidClassName.NotCamelCaps
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindTextDomain('tuleap-frs', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        $this->addHook('frs_edit_form_additional_info');
        $this->addHook('frs_process_edit_form');
        $this->addHook('codendi_daily_start');
        $this->addHook(GetReleaseNotesLink::NAME);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(Event::IMPORT_XML_PROJECT_TRACKER_DONE);
        $this->addHook(FRSOngoingUploadChecker::NAME);
        $this->addHook(CollectRoutesEvent::NAME);

        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION);
            $this->addHook(ArtifactPartialUpdate::NAME);
            $this->addHook(MoveArtifactActionAllowedByPluginRetriever::NAME);
        }

        if (defined('AGILEDASHBOARD_BASE_DIR')) {
            $this->addHook(PaneInfoCollector::NAME);
        }

        return parent::getHooksAndCallbacks();
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return array('tracker');
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new PluginInfo($this);
        }
        return $this->pluginInfo;
    }

    public function codendiDailyStart()
    {
        $cleaner = new FileUploadCleaner(
            new UploadPathAllocator(),
            new FileOngoingUploadDao()
        );
        $cleaner->deleteDanglingFilesToUpload(new \DateTimeImmutable());
    }

    public function frsOngoingUploadChecker(FRSOngoingUploadChecker $event)
    {
        $file = $event->getFile();
        $current_time = new DateTimeImmutable();
        $dao = new FileOngoingUploadDao();
        if (
            ! empty($dao->searchFileOngoingUploadByReleaseIDNameAndExpirationDate(
                $file->getRelease()->getReleaseID(),
                $file->getFileName(),
                $current_time->getTimestamp()
            ))
        ) {
            $event->setIsFileBeingUploadedToTrue();
        }
    }

    public function collectRoutesEvent(CollectRoutesEvent $event)
    {
        $event->getRouteCollector()->addRoute(
            ['OPTIONS', 'HEAD', 'PATCH', 'DELETE', 'POST', 'PUT'],
            '/uploads/frs/file/{id:\d+}',
            $this->getRouteHandler('routeUploads')
        );
        $event->getRouteCollector()->addGroup(
            '/frs',
            function (FastRoute\RouteCollector $r) {
                $r->get('/release/{release_id:\d+}/release-notes', $this->getRouteHandler('routeGetReleaseNote'));
            }
        );
    }

    public function routeGetReleaseNote(): \Tuleap\FRS\ReleaseNotesController
    {
        return \Tuleap\FRS\ReleaseNotesController::buildSelf();
    }

    public function routeUploads(): FileUploadController
    {
        $file_ongoing_upload_dao = new FileOngoingUploadDao();
        $path_allocator          = new UploadPathAllocator();
        $logger                  = BackendLogger::getDefaultLogger();
        $db_connection           = DBFactory::getMainTuleapDBConnection();

        return FileUploadController::build(
            new FileDataStore(
                new FileBeingUploadedInformationProvider(
                    $path_allocator,
                    $file_ongoing_upload_dao
                ),
                new FileBeingUploadedWriter(
                    $path_allocator,
                    $db_connection
                ),
                new FileBeingUploadedLocker(
                    $path_allocator
                ),
                new FileUploadFinisher(
                    $logger,
                    $path_allocator,
                    new FRSFileFactory($logger),
                    new FRSReleaseFactory(),
                    $file_ongoing_upload_dao,
                    new DBTransactionExecutorWithConnection($db_connection),
                    new FRSFileDao(),
                    new FRSLogDao(),
                    new ToBeCreatedFRSFileBuilder()
                ),
                new FileUploadCanceler(
                    $path_allocator,
                    $file_ongoing_upload_dao
                )
            )
        );
    }

    public function frs_edit_form_additional_info($params) //phpcs:ignore
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(FRS_BASE_DIR . '/templates');

        $release_id         = $params['release_id'];
        $linked_artifact_id = $this->getLinkRetriever()->getLinkedArtifactId($release_id);

        $presenter                 = new AdditionalInformationPresenter($linked_artifact_id);
        $params['additional_info'] = $renderer->renderToString('additional-information', $presenter);

        $params['notes_in_markdown'] = true;
    }

    public function frs_process_edit_form($params) //phpcs:ignore
    {
        $release_request = $params['release_request'];

        if ($this->doesRequestContainsAdditionalInformation($release_request)) {
            $release_id  = $params['release_id'];
            $artifact_id = $release_request['artifact-id'];

            if ($artifact_id !== '') {
                if (! ctype_digit($artifact_id)) {
                    $params['error'] = dgettext('tuleap-frs', 'The provided artifact id is not an integer. Linked artifact not updated.');
                    return;
                }

                $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
                if (! $artifact) {
                    $params['error'] = sprintf(dgettext('tuleap-frs', 'Artifact #%1$s does not exist.'), $artifact_id);
                    return;
                }
            }

            $updater = $this->getLinkUpdater();
            $saved   = $updater->updateLink($release_id, $artifact_id);

            if (! $saved) {
                $params['error'] = dgettext('tuleap-frs', 'An error occured while saving new linked artifact id.');
            }
        }
    }

    private function doesRequestContainsAdditionalInformation(array $release_request)
    {
        return isset($release_request['artifact-id']);
    }

    /**
     * @return Updater
     */
    private function getLinkUpdater()
    {
        return new Updater(new Dao(), $this->getLinkRetriever());
    }

    /** @return Retriever */
    private function getLinkRetriever()
    {
        return new Retriever(new Dao());
    }

    public function getReleaseNotesLink(GetReleaseNotesLink $event): void
    {
        $release_id = urlencode((string) $event->getRelease()->getReleaseID());
        $event->setUrl("/frs/release/$release_id/release-notes");
    }

    public function rest_resources($params) //phpcs:ignore
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /** @see \Event::REST_PROJECT_RESOURCES */
    public function rest_project_resources(array $params) //phpcs:ignore
    {
        $injector = new ResourcesInjector();
        $injector->declareProjectResources($params['resources'], $params['project']);
    }

    public function import_xml_project_tracker_done($params) //phpcs:ignore
    {
        $mappings            = $params['mappings_registery'];
        $artifact_id_mapping = $params['artifact_id_mapping'];

        $frs_release_mapping = $mappings->get(FRSXMLImporter::MAPPING_KEY);

        foreach ($frs_release_mapping as $release_id => $xml_artifact_id) {
            $artifact_id = $artifact_id_mapping->get($xml_artifact_id);

            if ($artifact_id) {
                $this->getLinkUpdater()->updateLink($release_id, $artifact_id);
            }
        }
    }

    /** @see Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION */
    public function tracker_artifact_editrenderer_add_view_in_collection(array $params) //phpcs:ignore
    {
        $user       = $params['user'];
        $request    = $params['request'];
        $artifact   = $params['artifact'];
        $collection = $params['collection'];

        $release_id = $this->getLinkRetriever()->getLinkedReleaseId($artifact);
        if ($release_id) {
            $collection->add(new Tuleap\FRS\ArtifactView($release_id, $artifact, $request, $user));
        }
    }

    public function agiledashboardEventAdditionalPanesOnMilestone(PaneInfoCollector $collector): void
    {
        $milestone  = $collector->getMilestone();
        $release_id = $this->getLinkRetriever()->getLinkedReleaseId($milestone->getArtifact());
        if ($release_id) {
            $collector->addPane(new Tuleap\FRS\AgileDashboardPaneInfo($milestone, $release_id));
        }
    }

    public function artifactPartialUpdate(ArtifactPartialUpdate $event)
    {
        $artifact   = $event->getArtifact();
        $release_id = $this->getLinkRetriever()->getLinkedReleaseId($artifact);

        if ($release_id !== null) {
            $event->setNotUpdatable('Artifact linked to a FRS release cannot be moved');
        }
    }

    public function moveArtifactActionAllowedByPluginRetriever(MoveArtifactActionAllowedByPluginRetriever $event)
    {
        $release_id = $this->getLinkRetriever()->getLinkedReleaseId($event->getArtifact());

        if ($release_id !== null) {
            $event->setCanNotBeMoveDueToExternalPlugin(dgettext('tuleap-frs', 'Artifact linked to a Files release cannot be moved'));
        }
    }
}
