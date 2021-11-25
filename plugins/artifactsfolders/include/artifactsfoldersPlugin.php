<?php
/**
 * Copyright (c) Enalean, 2016 - Present. All Rights Reserved.
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

use Tuleap\ArtifactsFolders\ArtifactsFoldersPluginInfo;
use Tuleap\ArtifactsFolders\Folder\ArtifactLinkInformationPrepender;
use Tuleap\ArtifactsFolders\Folder\ArtifactPresenterBuilder;
use Tuleap\ArtifactsFolders\Folder\ArtifactView;
use Tuleap\ArtifactsFolders\Folder\Controller;
use Tuleap\ArtifactsFolders\Folder\Dao;
use Tuleap\ArtifactsFolders\Folder\DataFromRequestAugmentor;
use Tuleap\ArtifactsFolders\Folder\FolderHierarchicalRepresentationCollectionBuilder;
use Tuleap\ArtifactsFolders\Folder\FolderUsageRetriever;
use Tuleap\ArtifactsFolders\Folder\HierarchyOfFolderBuilder;
use Tuleap\ArtifactsFolders\Folder\PostSaveNewChangesetCommand;
use Tuleap\ArtifactsFolders\Folder\Router;
use Tuleap\ArtifactsFolders\Type\TypeInFolderPresenter;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Plugin\PluginWithLegacyInternalRouting;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\Events\ArtifactLinkTypeCanBeUnused;
use Tuleap\Tracker\Events\GetEditableTypesInProject;
use Tuleap\Tracker\Events\XMLImportArtifactLinkTypeCanBeDisabled;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkFieldValueDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypeIsChildLinkRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Type\TypePresenterFactory;
use Tuleap\XML\PHPCast;

require_once __DIR__ . '/../../tracker/include/trackerPlugin.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'constants.php';

class ArtifactsFoldersPlugin extends PluginWithLegacyInternalRouting // phpcs:ignore
{
    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
        bindtextdomain('tuleap-artifactsfolders', __DIR__ . '/../site-content');
    }

    public function getHooksAndCallbacks()
    {
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(TypePresenterFactory::EVENT_GET_ARTIFACTLINK_TYPES);
            $this->addHook(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION);
            $this->addHook('javascript_file');
            $this->addHook(TrackerXmlImport::ADD_PROPERTY_TO_TRACKER);
            $this->addHook(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_ADD_SYSTEM_TYPES);
            $this->addHook(Tracker_Artifact_XMLImport_XMLImportFieldStrategyArtifactLink::TRACKER_IS_TYPE_VALID);
            $this->addHook('cssfile');
            $this->addHook(Tracker_Artifact_ChangesetValue_ArtifactLinkDiff::HIDE_ARTIFACT);
            $this->addHook(TypePresenterFactory::EVENT_GET_TYPE_PRESENTER);
            $this->addHook(Tracker_FormElement_Field_ArtifactLink::PREPEND_ARTIFACTLINK_INFORMATION);
            $this->addHook(Tracker_FormElement_Field_ArtifactLink::GET_POST_SAVE_NEW_CHANGESET_QUEUE);
            $this->addHook(Tracker_FormElement_Field_ArtifactLink::AFTER_AUGMENT_DATA_FROM_REQUEST);
            $this->addHook(Artifact::DISPLAY_COPY_OF_ARTIFACT);
            $this->addHook(GetEditableTypesInProject::NAME);
            $this->addHook(ArtifactLinkTypeCanBeUnused::NAME);
            $this->addHook(XMLImportArtifactLinkTypeCanBeDisabled::NAME);

            $this->listenToCollectRouteEventWithDefaultController();
        }

        return parent::getHooksAndCallbacks();
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies()
    {
        return ['tracker'];
    }

    /**
     * @return PluginInfo
     */
    public function getPluginInfo()
    {
        if (! $this->pluginInfo) {
            $this->pluginInfo = new ArtifactsFoldersPluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function cssfile()
    {
        if (strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL) === 0) {
            $asset = $this->getIncludeAssets();
            echo '<link rel="stylesheet" type="text/css" href="' . $asset->getFileURL('style.css') . '" />';
        }
    }

    public function javascript_file($params): void // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
    {
        if (strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL) === 0) {
            $layout = $params['layout'];
            assert($layout instanceof \Tuleap\Layout\BaseLayout);
            $layout->addJavascriptAsset(new \Tuleap\Layout\JavascriptAsset($this->getIncludeAssets(), 'rollup.js'));
        }
    }

    private function getIncludeAssets(): IncludeAssets
    {
        return new IncludeAssets(
            __DIR__ . '/../../../src/www/assets/artifactsfolders/',
            '/assets/artifactsfolders'
        );
    }

    public function event_get_artifactlink_types($params)// phpcs:ignore
    {
        $params['types'][] = new TypeInFolderPresenter();
    }

    /** @see Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION */
    public function tracker_artifact_editrenderer_add_view_in_collection(array $params) // phpcs:ignore
    {
        $user       = $params['user'];
        $request    = $params['request'];
        $artifact   = $params['artifact'];
        $collection = $params['collection'];

        $project = $artifact->getTracker()->getProject();
        if (! $this->isAllowed($project->getId())) {
            return;
        }

        $dao = new TypeDao();
        if ($this->canAddOurView($dao, $artifact, $project, $user)) {
            $view = new ArtifactView($artifact, $request, $user, $this->getPresenterBuilder());
            $collection->add($view);
        }
    }

    private function canAddOurView(TypeDao $dao, Artifact $artifact, Project $project, PFUser $user)
    {
        $folder_usage_retriever = $this->getFolderUsageRetriever();

        return $folder_usage_retriever->projectUsesArtifactsFolders($project, $user)
            && $this->isViewAddableForArtifact($dao, $artifact);
    }

    private function isViewAddableForArtifact(TypeDao $dao, Artifact $artifact)
    {
        $linked_artifacts_ids = $dao->getReverseLinkedArtifactIds(
            $artifact->getId(),
            TypeInFolderPresenter::TYPE_IN_FOLDER,
            PHP_INT_MAX,
            0
        );

        if (count($linked_artifacts_ids) > 0) {
            return true;
        }

        $children_folder = $this->getTypeIsChildLinkRetriever()->getChildren($artifact);
        foreach ($children_folder as $child_folder) {
            if ($this->isViewAddableForArtifact($dao, $child_folder)) {
                return true;
            }
        }

        return false;
    }

    /** @see TrackerXmlImport::ADD_PROPERTY_TO_TRACKER */
    public function add_property_to_tracker(array $params) // phpcs:ignore
    {
        $xml_element = $params['xml_element'];
        $is_folder   = isset($xml_element['is_folder']) ? PHPCast::toBoolean($xml_element['is_folder']) : false;

        if ($is_folder) {
            $this->setFolderProperty($params['project'], $params['tracker_id'], $params['logger']);
        }
    }

    private function setFolderProperty(Project $project, $tracker_id, \Psr\Log\LoggerInterface $logger)
    {
        if (! $this->getFolderUsageRetriever()->doesProjectHaveAFolderTracker($project)) {
            if (! $this->getDao()->create($tracker_id)) {
                $logger->warning("Error while setting Folder flag for tracker $tracker_id.");
            }
        } else {
            $logger->warning("Cannot set tracker $tracker_id as a Folder tracker because you already have one defined for this project");
        }
    }

    private function getDao()
    {
        return new Dao();
    }

    /**
     * @return FolderUsageRetriever
     */
    private function getFolderUsageRetriever()
    {
        return new FolderUsageRetriever($this->getDao(), TrackerFactory::instance());
    }

    private function getPresenterBuilder()
    {
        return new ArtifactPresenterBuilder(
            $this->getHierarchyOfFolderBuilder(),
            new TypeDao(),
            $this->getTypeIsChildLinkRetriever(),
            Tracker_ArtifactFactory::instance()
        );
    }

    private function getHierarchyOfFolderBuilder()
    {
        return new HierarchyOfFolderBuilder(
            new Dao(),
            $this->getTypeIsChildLinkRetriever(),
            Tracker_ArtifactFactory::instance()
        );
    }

    private function getTypeIsChildLinkRetriever()
    {
        return new TypeIsChildLinkRetriever(
            Tracker_ArtifactFactory::instance(),
            new ArtifactLinkFieldValueDao()
        );
    }

    public function hide_artifact($params) // phpcs:ignore
    {
        $params['hide_artifact'] = $params['type'] === TypeInFolderPresenter::TYPE_IN_FOLDER;
    }

    public function event_get_type_presenter($params) // phpcs:ignore
    {
        if ($params['shortname'] === TypeInFolderPresenter::TYPE_IN_FOLDER) {
            $params['presenter'] = new TypeInFolderPresenter();
        }
    }

    public function process(): void
    {
        if (! defined('TRACKER_BASE_URL')) {
            return;
        }

        $router = new Router(
            Tracker_ArtifactFactory::instance(),
            new Tracker_URLVerification(),
            new Controller($this->getPresenterBuilder())
        );

        $router->route(HTTPRequest::instance());
    }

    public function tracker_add_system_types($params) // phpcs:ignore
    {
        $params['types'][] = TypeInFolderPresenter::TYPE_IN_FOLDER;
    }

    public function tracker_is_type_valid($params) // phpcs:ignore
    {
        if (
            $this->getDao()->isTrackerConfiguredToContainFolders($params['tracker_id']) === false
            && $params['type'] === TypeInFolderPresenter::TYPE_IN_FOLDER
        ) {
            $params['error'] = "Link between " . $params['artifact']->getId() . " and " . $params['children_id'] . " is inconsistent because tracker " .
                $params['tracker_id'] . " is not defined as a Folder. Artifact " . $params['artifact']->getId() . " added without type.";
        }
    }

    /** @see Tracker_FormElement_Field_ArtifactLink::PREPEND_ARTIFACTLINK_INFORMATION */
    public function prepend_artifactlink_information($params) // phpcs:ignore
    {
        $prepender = new ArtifactLinkInformationPrepender(
            $this->getHierarchyOfFolderBuilder(),
            new FolderHierarchicalRepresentationCollectionBuilder(
                Tracker_ArtifactFactory::instance(),
                new Dao()
            )
        );

        $params['html'] .= $prepender->prependArtifactLinkInformation(
            $params['artifact'],
            $params['current_user'],
            $params['reverse_artifact_links'],
            $params['read_only'],
            $params['additional_classes']
        );
    }

    /** @see Tracker_FormELement_Field_ArtifactLink::GET_POST_SAVE_NEW_CHANGESET_QUEUE */
    public function get_post_save_new_changeset_queue(array $params) // phpcs:ignore
    {
        $params['queue']->add(
            new PostSaveNewChangesetCommand($params['field'], HTTPRequest::instance(), $this->getDao())
        );
    }

    /** @see Tracker_FormELement_Field_ArtifactLink::AFTER_AUGMENT_DATA_FROM_REQUEST */
    public function after_augment_data_from_request(array $params) // phpcs:ignore
    {
        $request = HTTPRequest::instance();
        if (! $this->checkRequestConcernsArtifactFoldersWithSetParameters($request)) {
            return;
        }

        $augmentor = new DataFromRequestAugmentor(
            $request,
            $this->getHierarchyOfFolderBuilder()
        );

        $augmentor->augmentDataFromRequest($params['fields_data'][$params['field']->getId()]);
    }

    private function checkRequestConcernsArtifactFoldersWithSetParameters(HTTPRequest $request)
    {
        if (! $request->exist('new-artifact-folder')) {
            return false;
        }

        $new_artifact_folder = $request->get('new-artifact-folder');

        if (! $new_artifact_folder) {
            return false;
        }

        $selected_artifact = Tracker_ArtifactFactory::instance()->getArtifactById($new_artifact_folder);

        if (! $selected_artifact) {
            return false;
        }

        $tracker_id = $selected_artifact->getTrackerId();

        return $this->getDao()->isTrackerConfiguredToContainFolders($tracker_id);
    }

    /** @see Artifact::DISPLAY_COPY_OF_ARTIFACT */
    public function display_copy_of_artifact($params) // phpcs:ignore
    {
        $folder_hierarchy = $this->getHierarchyOfFolderBuilder()->getHierarchyOfFolderForArtifact(
            $params['artifact']
        );
        if (! $folder_hierarchy) {
            return;
        }

        $folder   = end($folder_hierarchy);
        $purifier = Codendi_HTMLPurifier::instance();

        $GLOBALS['Response']->addFeedback(
            Feedback::WARN,
            sprintf(
                dgettext('tuleap-artifactsfolders', 'The link between the artifact and the folder <a href="%s">%s</a> won\'t be copied.'),
                $purifier->purify($folder->getUri()),
                $folder->getXRefAndTitle(),
            ),
            CODENDI_PURIFIER_FULL
        );
    }

    public function tracker_get_editable_type_in_project(GetEditableTypesInProject $event) // phpcs:ignore
    {
        $project = $event->getProject();

        if ($this->isAllowed($project->getId()) && $this->getFolderUsageRetriever()->doesProjectHaveAFolderTracker($project)) {
            $event->addType(new TypeInFolderPresenter());
        }
    }

    public function tracker_artifact_link_can_be_unused(ArtifactLinkTypeCanBeUnused $event) // phpcs:ignore
    {
        $type = $event->getType();

        if ($type->shortname === TypeInFolderPresenter::TYPE_IN_FOLDER) {
            $event->setTypeIsCheckedByPlugin();
        }
    }

    public function tracker_xml_import_artifact_link_can_be_disabled(XMLImportArtifactLinkTypeCanBeDisabled $event) // phpcs:ignore
    {
        if ($event->getTypeName() !== TypeInFolderPresenter::TYPE_IN_FOLDER) {
            return;
        }

        $event->setTypeIsCheckedByPlugin();

        if (! $this->getFolderUsageRetriever()->doesProjectHaveAFolderTracker($event->getProject())) {
            $event->setTypeIsUnusable();
        } else {
            $event->setMessage(TypeInFolderPresenter::TYPE_IN_FOLDER . " type is forced because a tracker folder is defined.");
        }
    }
}
