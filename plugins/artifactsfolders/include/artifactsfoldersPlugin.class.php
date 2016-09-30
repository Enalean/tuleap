<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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
use Tuleap\ArtifactsFolders\Folder\PresenterBuilder;
use Tuleap\ArtifactsFolders\Nature\NatureIsFolderPresenter;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureDao;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NatureIsChildLinkRetriever;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\Nature\NaturePresenterFactory;
use Tuleap\ArtifactsFolders\Folder\FolderUsageRetriever;
use Tuleap\ArtifactsFolders\Folder\Dao;
use Tuleap\ArtifactsFolders\Folder\ArtifactView;

require_once 'autoload.php';
require_once 'constants.php';

class ArtifactsFoldersPlugin extends Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);
    }

    public function getHooksAndCallbacks()
    {
        if (defined('TRACKER_BASE_URL')) {
            $this->addHook(NaturePresenterFactory::EVENT_GET_ARTIFACTLINK_NATURES);
            $this->addHook(Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION);
            $this->addHook(Event::JAVASCRIPT_FOOTER);
            $this->addHook('cssfile');
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
            $this->pluginInfo = new ArtifactsFoldersPluginInfo($this);
        }

        return $this->pluginInfo;
    }

    public function cssfile()
    {
        if (strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL) === 0) {
            echo '<link rel="stylesheet" type="text/css" href="' . $this->getThemePath() . '/css/style.css" />';
        }
    }

    public function javascript_footer($params)
    {
        if (strpos($_SERVER['REQUEST_URI'], TRACKER_BASE_URL) === 0) {
            echo '</script>' . $this->getMinifiedAssetHTML() . '</script>';
        }
    }

    public function event_get_artifactlink_natures($params)
    {
        $params['natures'][] = new NatureIsFolderPresenter();
    }

    /** @see Tracker_Artifact_EditRenderer::EVENT_ADD_VIEW_IN_COLLECTION */
    public function tracker_artifact_editrenderer_add_view_in_collection(array $params)
    {
        $user       = $params['user'];
        $request    = $params['request'];
        $artifact   = $params['artifact'];
        $collection = $params['collection'];

        $project = $artifact->getTracker()->getProject();
        if (! $this->isAllowed($project->getId())) {
            return;
        }

        $dao = new NatureDao();
        if ($this->canAddOurView($dao, $artifact, $project, $user)) {
            $view = new ArtifactView($artifact, $request, $user, $this->getPresenterBuilder($dao));
            $collection->add($view);
        }
    }

    private function canAddOurView(NatureDao $dao, Tracker_Artifact $artifact, Project $project, PFUser $user)
    {
        $folder_usage_retriever = $this->getFolderUsageRetriever();

        return
            $folder_usage_retriever->projectUsesArtifactsFolders($project, $user)
            && $dao->hasReverseLinkedArtifacts($artifact->getId(), NatureIsFolderPresenter::NATURE_IS_FOLDER);
    }

    /**
     * @return FolderUsageRetriever
     */
    private function getFolderUsageRetriever()
    {
        $dao = new Dao();

        return new FolderUsageRetriever($dao, TrackerFactory::instance());
    }

    private function getPresenterBuilder($dao)
    {
        $artifact_factory = Tracker_ArtifactFactory::instance();
        return new PresenterBuilder(
            new Dao(),
            $dao,
            $artifact_factory,
            new NatureIsChildLinkRetriever(
                $artifact_factory,
                new Tracker_FormElement_Field_Value_ArtifactLinkDao()
            )
        );
    }
}
