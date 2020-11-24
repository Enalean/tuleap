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
use ProjectManager;
use TemplateRendererFactory;
use TrackerFactory;
use TrackerManager;
use Tuleap\Layout\BaseLayout;
use Tuleap\Layout\IncludeAssets;
use Tuleap\Layout\JavascriptAsset;
use Tuleap\Request\DispatchableWithBurningParrot;
use Tuleap\Request\DispatchableWithProject;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Tracker\NewDropdown\TrackerInNewDropdownDao;

class TrackersDisplayController implements DispatchableWithRequest, DispatchableWithBurningParrot, DispatchableWithProject
{
    /**
     * @var ProjectManager
     */
    private $project_manager;
    /**
     * @var TrackerManager
     */
    private $tracker_manager;
    /**
     * @var TemplateRendererFactory
     */
    private $renderer_factory;
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

    public function __construct(
        ProjectManager $project_manager,
        TrackerManager $tracker_manager,
        TrackerFactory $tracker_factory,
        TemplateRendererFactory $renderer_factory,
        TrackerInNewDropdownDao $in_new_dropdown_dao,
        CSRFSynchronizerTokenProvider $token_provider
    ) {
        $this->project_manager     = $project_manager;
        $this->tracker_manager     = $tracker_manager;
        $this->tracker_factory     = $tracker_factory;
        $this->renderer_factory    = $renderer_factory;
        $this->in_new_dropdown_dao = $in_new_dropdown_dao;
        $this->token_provider      = $token_provider;
    }

    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        $project = $this->getProject($variables);
        if (
            ! $this->tracker_manager->userCanCreateTracker($project->getID())
            && ! $this->tracker_manager->userCanAdminAllProjectTrackers()
        ) {
            throw new ForbiddenException();
        }

        $trackers = [];
        foreach ($this->tracker_factory->getTrackersByGroupId($project->getID()) as $tracker) {
            $used_in_other_services_infos = $tracker->getInformationsFromOtherServicesAboutUsage();

            $cannot_delete_message = $used_in_other_services_infos['can_be_deleted']
                ? ""
                : sprintf(
                    dgettext('tuleap-tracker', 'You can\'t delete this tracker because it is used in: %1$s'),
                    $used_in_other_services_infos['message']
                );

            $trackers[] = new TrackerPresenter(
                $tracker->getId(),
                $tracker->getItemName(),
                $tracker->getName(),
                $tracker->getDescription(),
                $this->in_new_dropdown_dao->isContaining($tracker->getId()),
                $tracker->getAdministrationUrl(),
                MarkTrackerAsDeletedController::getURL($tracker),
                (bool) $used_in_other_services_infos['can_be_deleted'],
                $cannot_delete_message,
            );
        }

        $layout->addJavascriptAsset(
            new JavascriptAsset(
                new IncludeAssets(__DIR__ . '/../../../../../../../src/www/assets/trackers', '/assets/trackers'),
                'global-admin-trackers.js'
            )
        );
        $this->tracker_manager->displayHeader(
            $project,
            dgettext('tuleap-tracker', 'Trackers'),
            [],
            [],
            []
        );
        $renderer = $this->renderer_factory->getRenderer(TRACKER_TEMPLATE_DIR);
        $renderer->renderToPage(
            'global-admin/trackers',
            new TrackersDisplayPresenter($project, $trackers, $this->token_provider->getCSRF($project)),
        );

        $this->tracker_manager->displayFooter($project);
    }

    public static function getURL(Project $project): string
    {
        return \Tracker::getTrackerGlobalAdministrationURL($project);
    }

    public function getProject(array $variables): Project
    {
        return $this->project_manager->getProject($variables['id']);
    }
}
