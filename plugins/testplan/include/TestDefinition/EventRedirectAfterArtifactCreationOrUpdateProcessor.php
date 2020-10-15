<?php
/**
 * Copyright (c) Enalean, 2020 - Present. All Rights Reserved.
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

declare(strict_types=1);

namespace Tuleap\TestPlan\TestDefinition;

use Codendi_Request;
use Tracker_Artifact_Redirect;
use Tracker_ArtifactFactory;
use Tuleap\TestPlan\TestPlanPaneInfo;
use Tuleap\Tracker\Artifact\Artifact;
use Tuleap\Tracker\FormElement\Field\ArtifactLink\ArtifactLinkUpdater;

class EventRedirectAfterArtifactCreationOrUpdateProcessor
{
    /**
     * @var Tracker_ArtifactFactory
     */
    private $artifact_factory;
    /**
     * @var ArtifactLinkUpdater
     */
    private $artifact_link_updater;
    /**
     * @var RedirectParameterInjector
     */
    private $injector;

    public function __construct(
        Tracker_ArtifactFactory $artifact_factory,
        ArtifactLinkUpdater $artifact_link_updater,
        RedirectParameterInjector $injector
    ) {
        $this->artifact_factory      = $artifact_factory;
        $this->artifact_link_updater = $artifact_link_updater;
        $this->injector              = $injector;
    }

    public function process(
        Codendi_Request $request,
        Tracker_Artifact_Redirect $redirect,
        Artifact $artifact
    ): void {
        $ttm_backlog_item_id = $request->get(RedirectParameterInjector::TTM_BACKLOG_ITEM_ID_KEY);
        $ttm_milestone_id    = $request->get(RedirectParameterInjector::TTM_MILESTONE_ID_KEY);
        if (! $ttm_backlog_item_id || ! $ttm_milestone_id) {
            return;
        }

        $backlog_item = $this->artifact_factory->getArtifactById($ttm_backlog_item_id);
        if (! $backlog_item) {
            return;
        }

        $is_editing_backlog_item = $artifact->getId() === $backlog_item->getId();

        if (! $is_editing_backlog_item) {
            try {
                $this->artifact_link_updater->updateArtifactLinks(
                    $request->getCurrentUser(),
                    $backlog_item,
                    [$artifact->getId()],
                    [],
                    \Tuleap\TestManagement\Nature\NatureCoveredByPresenter::NATURE_COVERED_BY
                );
            } catch (\Tracker_NoArtifactLinkFieldException | \Tracker_Exception $e) {
                $GLOBALS['Response']->addFeedback(
                    \Feedback::WARN,
                    dgettext('tuleap-testplan', 'Unable to link the backlog item to the new artifact')
                );
                return;
            }
        }

        if ($redirect->mode === Tracker_Artifact_Redirect::STATE_CONTINUE) {
            $this->injector->injectParameters($redirect, $ttm_backlog_item_id, $ttm_milestone_id);

            return;
        }

        if ($redirect->mode === Tracker_Artifact_Redirect::STATE_STAY) {
            return;
        }

        $project_unixname  = $artifact->getTracker()->getProject()->getUnixNameMixedCase();
        $redirect_base_url = TestPlanPaneInfo::URL
            . '/' . urlencode($project_unixname)
            . '/' . urlencode((string) $ttm_milestone_id)
            . '/backlog_item/' . urlencode((string) $ttm_backlog_item_id);
        if (! $is_editing_backlog_item) {
            $redirect_base_url .= '/test/' . urlencode((string) $artifact->getId());
        }
        $redirect->base_url = $redirect_base_url;

        $redirect->query_parameters = [];
    }
}
