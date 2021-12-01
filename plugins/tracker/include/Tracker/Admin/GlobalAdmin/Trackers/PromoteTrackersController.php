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
use Tuleap\Tracker\NewDropdown\TrackerInNewDropdownDao;

class PromoteTrackersController implements DispatchableWithRequest, DispatchableWithProject
{
    public const URL = 'promote-trackers';

    private const PROJECT_HISTORY_ENTRY = 'tracker_promotion';

    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var TrackerFactory
     */
    private $tracker_factory;
    /**
     * @var TrackerInNewDropdownDao
     */
    private $in_new_dropdown_dao;
    /**
     * @var CSRFSynchronizerTokenProvider
     */
    private $token_provider;
    /**
     * @var ProjectHistoryDao
     */
    private $history_dao;
    /**
     * @var GlobalAdminPermissionsChecker
     */
    private $permissions_checker;

    public function __construct(
        ProjectManager $project_manager,
        GlobalAdminPermissionsChecker $permissions_checker,
        TrackerFactory $tracker_factory,
        TrackerInNewDropdownDao $in_new_dropdown_dao,
        CSRFSynchronizerTokenProvider $token_provider,
        ProjectHistoryDao $history_dao,
    ) {
        $this->project_manager     = $project_manager;
        $this->tracker_factory     = $tracker_factory;
        $this->in_new_dropdown_dao = $in_new_dropdown_dao;
        $this->token_provider      = $token_provider;
        $this->history_dao         = $history_dao;
        $this->permissions_checker = $permissions_checker;
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
                    dgettext(
                        'tuleap-tracker',
                        'Tracker %s will now appear in the +New dropdown on every pages of the project'
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
                    dgettext(
                        'tuleap-tracker',
                        'Tracker %s won\'t appear anymore in the +New dropdown on every pages of the project'
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
