<?php
/**
 * Copyright (c) Enalean, 2014-2017. All Rights Reserved.
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

namespace Tuleap\TestManagement;

use PFUser;
use Tracker_FormElementFactory;

class IndexController extends TestManagementController
{

    public function index()
    {
        $current_user = $this->request->getCurrentUser();
        return $this->renderToString(
            'index',
            new IndexPresenter(
                $this->project->getId(),
                $this->config->getCampaignTrackerId($this->project),
                $this->config->getTestDefinitionTrackerId($this->project),
                $this->config->getTestExecutionTrackerId($this->project),
                $this->config->getIssueTrackerId($this->project),
                $this->getIssueTrackerConfig($current_user),
                $current_user,
                $this->current_milestone
            )
        );
    }

    private function getIssueTrackerConfig(PFUser $current_user)
    {
        $issue_tracker_id = $this->config->getIssueTrackerId($this->project);
        $issue_tracker    = $this->tracker_factory->getTrackerById($issue_tracker_id);
        if (! $issue_tracker) {
            return array(
                "permissions" => array(
                    "create" => false,
                    "link"   => false
                )
            );
        }

        $execution_tracker_id = $this->config->getTestExecutionTrackerId($this->project);
        $execution_tracker    = $this->tracker_factory->getTrackerById($execution_tracker_id);

        $form_element_factory = Tracker_FormElementFactory::instance();
        $link_field           = $form_element_factory->getAnArtifactLinkField($current_user, $execution_tracker);
        return array(
            "permissions" => array(
                "create" => $issue_tracker->userCanSubmitArtifact($current_user),
                "link"   => $link_field && $link_field->userCanUpdate($current_user)
            ),
            "xref_color" => $issue_tracker->getNormalizedColor()
        );
    }
}
