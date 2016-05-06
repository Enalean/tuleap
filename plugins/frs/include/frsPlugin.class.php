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

use Tuleap\FRS\PluginInfo;
use Tuleap\FRS\AdditionalInformationPresenter;
use Tuleap\FRS\Link\Updater;
use Tuleap\FRS\Link\Retriever;
use Tuleap\FRS\Link\Dao;

require_once 'constants.php';

class frsPlugin extends \Plugin
{

    public function __construct($id)
    {
        parent::__construct($id);
        $this->setScope(self::SCOPE_PROJECT);

        $this->addHook('frs_edit_form_additional_info');
        $this->addHook('frs_process_edit_form');
    }

    /**
     * @see Plugin::getDependencies()
     */
    public function getDependencies() {
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
    }

    public function frs_process_edit_form($params)
    {
        $release_request = $params['release_request'];

        if ($this->doesRequestContainsAdditionalInformation($release_request)) {
            $release_id  = $params['release_id'];
            $artifact_id = $release_request['artifact-id'];

            if (! ctype_digit($artifact_id)) {
                $params['error'] = $GLOBALS['Language']->getText('plugin_frs', 'artifact_id_not_int');
                return;
            }

            $artifact = Tracker_ArtifactFactory::instance()->getArtifactById($artifact_id);
            if (! $artifact) {
                $params['error'] = $GLOBALS['Language']->getText('plugin_frs', 'artifact_does_not_exist', $artifact_id);
                return;
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
        return isset($release_request['artifact-id']) && $release_request['artifact-id'] !== '';
    }

    private function getLinkUpdater()
    {
        return new Updater(new Dao(), $this->getLinkRetriever());
    }

    private function getLinkRetriever()
    {
        return new Retriever(new Dao());
    }
}
