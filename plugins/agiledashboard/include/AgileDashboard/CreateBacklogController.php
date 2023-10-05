<?php
/**
 * Copyright (c) Enalean, 2023 - Present. All Rights Reserved.
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

namespace Tuleap\AgileDashboard;

use AgileDashboard_FirstScrumCreator;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PFUser;
use Project;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\AgileDashboard\MonoMilestone\ScrumForMonoMilestoneChecker;
use Tuleap\AgileDashboard\Planning\PlanningAdministrationDelegation;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Plugin\IsProjectAllowedToUsePlugin;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

final class CreateBacklogController extends DispatchablePSR15Compatible
{
    public function __construct(
        private readonly RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly IsProjectAllowedToUsePlugin $plugin,
        private readonly AgileDashboard_FirstScrumCreator $first_scrum_creator,
        private readonly ScrumForMonoMilestoneChecker $scrum_mono_milestone_checker,
        private readonly EventDispatcherInterface $event_dispatcher,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute(PFUser::class);
        assert($user instanceof PFUser);

        $project = $request->getAttribute(\Project::class);
        assert($project instanceof \Project);

        if (! $this->plugin->isAllowed($project->getID())) {
            throw new ForbiddenException();
        }

        $service = $project->getService(\AgileDashboardPlugin::PLUGIN_SHORTNAME);
        if (! $service instanceof AgileDashboardService) {
            throw new ForbiddenException();
        }

        $is_scrum_mono_milestone_enabled = $this->scrum_mono_milestone_checker->isMonoMilestoneEnabled(
            $project->getID()
        );
        if ($is_scrum_mono_milestone_enabled) {
            return $this->redirectToBacklog($user, $project, NewFeedback::error(
                dgettext('tuleap-agiledashboard', 'Unable to initiate a default backlog when Scrum mono milestone is enabled.')
            ));
        }

        $planning_administration_delegation = new PlanningAdministrationDelegation($project);
        $this->event_dispatcher->dispatch($planning_administration_delegation);
        if ($planning_administration_delegation->isPlanningAdministrationDelegated()) {
            return $this->redirectToBacklog($user, $project, NewFeedback::error(
                dgettext('tuleap-agiledashboard', 'Unable to initiate a default backlog when backlog administration is delegated to another service.')
            ));
        }

        $feedback = $this->first_scrum_creator->createFirstScrum($project);

        return $this->redirectToBacklog($user, $project, $feedback);
    }

    private function redirectToBacklog(PFUser $user, Project $project, NewFeedback $feedback): ResponseInterface
    {
        $url = AgileDashboardServiceHomepageUrlBuilder::getTopBacklogUrl($project);

        return $this->redirect_with_feedback_factory->createResponseForUser($user, $url, $feedback);
    }
}
