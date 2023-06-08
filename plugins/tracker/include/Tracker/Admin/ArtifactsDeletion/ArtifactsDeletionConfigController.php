<?php
/**
 * Copyright Enalean (c) 2018 - Present. All rights reserved.
 *
 * Tuleap and Enalean names and logos are registrated trademarks owned by
 * Enalean SAS. All other trademarks or names are properties of their respective
 * owners.
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

namespace Tuleap\Tracker\Admin\ArtifactDeletion;

use Codendi_Request;
use CSRFSynchronizerToken;
use Feedback;
use http\Exception;
use PluginManager;
use Tuleap\Admin\AdminPageRenderer;
use Tuleap\Layout\BaseLayout;
use Tuleap\Tracker\Admin\ArtifactsDeletion\ConfigurationArtifactsDeletion;

class ArtifactsDeletionConfigController
{
    public function __construct(
        private readonly AdminPageRenderer $admin_page_renderer,
        private readonly ConfigurationArtifactsDeletion $config,
        private readonly ArtifactsDeletionConfigDAO $dao,
        private readonly PluginManager $plugin_manager,
    ) {
    }

    public function index(CSRFSynchronizerToken $csrf)
    {
        $title                            = dgettext('tuleap-tracker', 'Trackers');
        $artifacts_limit                  = $this->config->getArtifactsDeletionLimit();
        $archive_deleted_items_plugin     = $this->plugin_manager->getPluginByName('archivedeleteditems');
        $is_archive_deleted_items_enabled = ($archive_deleted_items_plugin)
            ? $this->plugin_manager->isPluginEnabled($archive_deleted_items_plugin)
            : false;

        $this->admin_page_renderer->renderANoFramedPresenter(
            $title,
            TRACKER_TEMPLATE_DIR,
            'siteadmin-config/artifacts-deletion',
            new ArtifactsDeletionConfigPresenter(
                $csrf,
                $artifacts_limit,
                $is_archive_deleted_items_enabled
            )
        );
    }

    public function update(Codendi_Request $request, BaseLayout $response)
    {
        $new_artifacts_limit = intval($request->get('artifacts_limit'));

        if ($new_artifacts_limit >= 0) {
            try {
                $this->dao->updateDeletableArtifactsLimit($new_artifacts_limit);
                $response->addFeedback(Feedback::INFO, dgettext("tuleap-tracker", "Limit successfully updated."));
            } catch (Exception $e) {
                $response->addFeedback(Feedback::ERROR, dgettext("tuleap-tracker", "Something went wrong."));
            }
        } else {
            $response->addFeedback(Feedback::ERROR, dgettext("tuleap-tracker", "Please provide a valid limit."));
        }

        $response->redirect($_SERVER['REQUEST_URI']);
    }
}
