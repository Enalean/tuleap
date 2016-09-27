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
use Tuleap\ArtifactsFolders\Nature\NatureIsFolderPresenter;
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

        $folder_usage_retriever = $this->getFolderUsageRetriever();
        if ($folder_usage_retriever->projectUsesArtifactsFolders($project, $user)) {
            $collection->add(new ArtifactView($artifact, $request, $user));
        }
    }

    /**
     * @return FolderUsageRetriever
     */
    private function getFolderUsageRetriever()
    {
        $dao = new Dao();

        return new FolderUsageRetriever($dao, TrackerFactory::instance());
    }
}
