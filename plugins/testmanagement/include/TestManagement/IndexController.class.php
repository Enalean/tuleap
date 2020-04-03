<?php
/**
 * Copyright (c) Enalean, 2014-Present. All Rights Reserved.
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

use Codendi_Request;
use EventManager;
use PFUser;
use Tracker_FormElementFactory;
use TrackerFactory;
use Tuleap\Tracker\Artifact\RecentlyVisited\VisitRecorder;

class IndexController extends TestManagementController
{
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var VisitRecorder
     */
    private $visit_recorder;

    public function __construct(
        Codendi_Request $request,
        Config $config,
        EventManager $event_manager,
        TrackerFactory $tracker_factory,
        VisitRecorder $visit_recorder
    ) {
        parent::__construct($request, $config, $event_manager);

        $this->tracker_factory = $tracker_factory;
        $this->visit_recorder  = $visit_recorder;
    }

    public function index(): string
    {
        $current_user = $this->request->getCurrentUser();

        $this->recordVisit($current_user);

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

    /**
     * @return (bool[]|string)[]
     *
     * @psalm-return array{permissions: array{create: bool, link: bool}, xref_color?: string}
     */
    private function getIssueTrackerConfig(PFUser $current_user): array
    {
        $issue_tracker_id = $this->config->getIssueTrackerId($this->project);
        $empty_config              = [
            "permissions" => [
                "create" => false,
                "link"   => false
            ]
        ];

        if (! $issue_tracker_id) {
            return $empty_config;
        }

        $issue_tracker = $this->tracker_factory->getTrackerById($issue_tracker_id);
        if (! $issue_tracker) {
            return $empty_config;
        }

        assert($issue_tracker instanceof \Tracker);

        $execution_tracker_id = $this->config->getTestExecutionTrackerId($this->project);

        if (!$execution_tracker_id) {
            return $empty_config;
        }
        $execution_tracker    = $this->tracker_factory->getTrackerById($execution_tracker_id);
        if (! $execution_tracker) {
            return $empty_config;
        }

        $form_element_factory = Tracker_FormElementFactory::instance();
        $link_field           = $form_element_factory->getAnArtifactLinkField($current_user, $execution_tracker);
        return array(
            "permissions" => array(
                "create" => $issue_tracker->userCanSubmitArtifact($current_user),
                "link"   => $link_field && $link_field->userCanUpdate($current_user)
            ),
            "xref_color" => $issue_tracker->getColor()->getName()
        );
    }

    private function recordVisit(PFUser $current_user): void
    {
        if ($this->current_milestone === null) {
            return;
        }

        $artifact = $this->current_milestone->getArtifact();
        if ($artifact === null) {
            return;
        }

        $this->visit_recorder->record($current_user, $artifact);
    }
}
