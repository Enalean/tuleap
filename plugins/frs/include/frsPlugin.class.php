<?php
/**
 * Copyright (c) Enalean, 2016 - 2018. All Rights Reserved.
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


require_once __DIR__ . '/../../tracker/include/trackerPlugin.class.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/constants.php';

use Tuleap\FRS\PluginInfo;
use Tuleap\FRS\AdditionalInformationPresenter;
use Tuleap\FRS\Link\Updater;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\Link\Dao;
use Tuleap\FRS\REST\ResourcesInjector;
use Tuleap\FRS\ReleasePresenter;
use Tuleap\FRS\REST\v1\ReleaseRepresentation;
use Tuleap\FRS\UploadedLinksDao;
use Tuleap\FRS\UploadedLinksRetriever;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Tracker\Artifact\ActionButtons\MoveArtifactActionAllowedByPluginRetriever;
use Tuleap\Tracker\REST\v1\Event\ArtifactPartialUpdate;

class frsPlugin extends \Plugin
{

    /**
     * Allow a plugin to display its own view instead of the release notes view
     *
     * Parameters:
     *   'release'    => (Input)  FRSRelease    FRS Release
     *   'user'       => (Input)  PFUser        Current user
     *   'view'       => (Output) String        Rendered template of the view
     */
    const FRS_RELEASE_VIEW = 'frs_release_view';

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
        $this->addHook('cssfile');
        $this->addHook('javascript_file');
        $this->addHook(self::FRS_RELEASE_VIEW);
        $this->addHook(Event::REST_RESOURCES);
        $this->addHook(Event::REST_PROJECT_RESOURCES);
        $this->addHook(Event::REST_PROJECT_FRS_ENDPOINTS);
        $this->addHook(Event::REST_GET_PROJECT_FRS_PACKAGES);
        $this->addHook(Event::IMPORT_XML_PROJECT_TRACKER_DONE);

        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION);
            $this->addHook(ArtifactPartialUpdate::NAME);
            $this->addHook(MoveArtifactActionAllowedByPluginRetriever::NAME);
        }

        if (defined('AGILEDASHBOARD_BASE_DIR')) {
            $this->addHook(AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE);
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

    public function frs_edit_form_additional_info($params)
    {
        $renderer  = TemplateRendererFactory::build()->getRenderer(FRS_BASE_DIR.'/templates');

        $release_id         = $params['release_id'];
        $linked_artifact_id = $this->getLinkRetriever()->getLinkedArtifactId($release_id);

        $presenter                 = new AdditionalInformationPresenter($linked_artifact_id);
        $params['additional_info'] = $renderer->renderToString('additional-information', $presenter);

        $params['notes_in_markdown'] = true;
    }

    public function frs_process_edit_form($params)
    {
        $release_request = $params['release_request'];

        if ($this->doesRequestContainsAdditionalInformation($release_request)) {
            $release_id  = $params['release_id'];
            $artifact_id = $release_request['artifact-id'];

            if ($artifact_id !== '') {
                if (! ctype_digit($artifact_id)) {
                    $params['error'] = $GLOBALS['Language']->getText('plugin_frs', 'artifact_id_not_int');
                    return;
                }

                $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
                if (! $artifact) {
                    $params['error'] = $GLOBALS['Language']->getText('plugin_frs', 'artifact_does_not_exist', $artifact_id);
                    return;
                }
            }

            $updater = $this->getLinkUpdater();
            $saved   = $updater->updateLink($release_id, $artifact_id);

            if (! $saved) {
                $params['error'] = $GLOBALS['Language']->getText('plugin_frs', 'db_error');
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

    public function cssfile($params)
    {
        if ($this->isAFRSrequest()) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getPluginPath() . '/assets/tuleap-frs.css" />';
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getThemePath() . '/css/style.css" />';
        }
    }

    public function javascript_file()
    {
        if ($this->isAFRSrequest()) {
            $include_assets = new IncludeAssets(
                FRS_BASE_DIR . '/www/assets',
                $this->getPluginPath() . '/assets'
            );

            echo $include_assets->getHTMLSnippet('tuleap-frs.js');
        }
    }

    /**
     * @see FRS_RELEASE_VIEW
     */
    public function frs_release_view($params)
    {
        $release = $params['release'];
        $user    = $params['user'];

        $renderer       = $this->getTemplateRenderer();
        $representation = new ReleaseRepresentation();
        $representation->build($release, $this->getLinkRetriever(), $user, $this->getUploadedLinkRetriever());
        $presenter = new ReleasePresenter(
            $representation,
            $user->getShortLocale()
        );

        $params['view'] = $renderer->renderToString($presenter->getTemplateName(), $presenter);
    }

    private function getTemplateRenderer()
    {
        return TemplateRendererFactory::build()->getRenderer(FRS_BASE_DIR . '/templates');
    }

    public function rest_resources($params)
    {
        $injector = new ResourcesInjector();
        $injector->populate($params['restler']);
    }

    /** @see \Event::REST_PROJECT_FRS_ENDPOINTS */
    public function rest_project_frs_endpoints(array $params)
    {
        $params['available'] = true;
    }

    /** @see \Event::REST_GET_PROJECT_FRS_PACKAGES */
    public function rest_get_project_frs_packages(array $params)
    {
        $project_resource = new \Tuleap\FRS\REST\v1\ProjectResource(FRSPackageFactory::instance());

        $paginated_packages = $project_resource->getPackages(
            $params['project'],
            $params['current_user'],
            $params['limit'],
            $params['offset']
        );
        $params['result']     = $paginated_packages->getPackageRepresentations();
        $params['total_size'] = $paginated_packages->getTotalSize();
    }

    /** @see \Event::REST_PROJECT_RESOURCES */
    public function rest_project_resources(array $params)
    {
        $injector = new ResourcesInjector();
        $injector->declareProjectResource($params['resources'], $params['project']);
    }

    public function import_xml_project_tracker_done($params)
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
    public function tracker_artifact_editrenderer_add_view_in_collection(array $params)
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

    /** @see AGILEDASHBOARD_EVENT_ADDITIONAL_PANES_ON_MILESTONE */
    public function agiledashboard_event_additional_panes_on_milestone($params)
    {
        $milestone  = $params['milestone'];
        $release_id = $this->getLinkRetriever()->getLinkedReleaseId($milestone->getArtifact());
        if ($release_id) {
            $params['panes'][] = new Tuleap\FRS\AgileDashboardPaneInfo($milestone, $release_id);
        }
    }

    private function getUploadedLinkRetriever()
    {
        return new UploadedLinksRetriever(new UploadedLinksDao(), UserManager::instance());
    }

    /**
     * @return bool
     */
    private function isAFRSrequest()
    {
        return strpos($_SERVER['REQUEST_URI'], FRS_BASE_URL . '/') === 0;
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
