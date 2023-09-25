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

namespace Tuleap\Tracker\Admin\GlobalAdmin\Trackers;

use HTTPRequest;
use Project;
use ProjectHistoryDao;
use ProjectManager;
use TrackerFactory;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Tracker\Admin\GlobalAdmin\GlobalAdminPermissionsChecker;
use Tuleap\Tracker\PromotedTrackerDao;
use Tuleap\Tracker\Service\PromotedTrackerConfigurationChecker;

class PromoteTrackersController implements DispatchableWithRequest, DispatchableWithProject
{
    public const URL = 'promote-trackers';

    private const PROJECT_HISTORY_ENTRY = 'tracker_promotion';

    public function __construct(
        private readonly ProjectManager $project_manager,
        private readonly GlobalAdminPermissionsChecker $permissions_checker,
        private readonly TrackerFactory $tracker_factory,
        private readonly PromotedTrackerDao $in_new_dropdown_dao,
        private readonly CSRFSynchronizerTokenProvider $token_provider,
        private readonly ProjectHistoryDao $history_dao,
        private readonly PromotedTrackerConfigurationChecker $configuration_checker,
    ) {
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);
        if (! $this->permissions_checker->doesUserHaveTrackerGlobalAdminRightsOnProject($project, $request->getCurrentUser())) {
            throw new ForbiddenException();
        }

        $this->token_provider->getCSRF($project)->check();

        $tracker = $this->tracker_factory->getTrackerById((int) $request->get('tracker_id'));
        if (! $tracker || $tracker->isDeleted() || (int) $tracker->getGroupId() !== (int) $project->getID()) {
            throw new ForbiddenException();
        }

        if ($request->get('is_promoted')) {
            $this->in_new_dropdown_dao->insert($tracker->getId());
            $this->history_dao->groupAddHistory(
                self::PROJECT_HISTORY_ENTRY,
                "1",
                $project->getID(),
                [$tracker->getName()]
            );
            $layout->addFeedback(
                \Feedback::INFO,
                sprintf(
                    $this->configuration_checker->isProjectAllowedToPromoteTrackersInSidebar($project)
                        ? dgettext(
                            'tuleap-tracker',
                            'Tracker %s will now appear in the sidebar or the +New dropdown on every page of the project'
                        )
                        : dgettext(
                            'tuleap-tracker',
                            'Tracker %s will now appear in the +New dropdown on every page of the project'
                        ),
                    $tracker->getName(),
                ),
            );
        } else {
            $this->in_new_dropdown_dao->delete($tracker->getId());
            $this->history_dao->groupAddHistory(
                self::PROJECT_HISTORY_ENTRY,
                "0",
                $project->getID(),
                [$tracker->getName()]
            );
            $layout->addFeedback(
                \Feedback::INFO,
                sprintf(
                    $this->configuration_checker->isProjectAllowedToPromoteTrackersInSidebar($project)
                        ? dgettext(
                            'tuleap-tracker',
                            'Tracker %s won\'t appear anymore in the sidebar or the +New dropdown on every page of the project'
                        )
                        : dgettext(
                            'tuleap-tracker',
                            'Tracker %s won\'t appear anymore in the +New dropdown on every page of the project'
                        ),
                    $tracker->getName(),
                ),
            );
        }

        $layout->redirect(TrackersDisplayController::getURL($project));
    }

    public static function getURL(Project $project): string
    {
        return \Tracker::getTrackerGlobalAdministrationURL($project) . '/' . self::URL;
    }

    public function getProject(array $variables): Project
    {
        return $this->project_manager->getProject($variables['id']);
    }
}
