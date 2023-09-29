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

namespace Tuleap\Kanban\Home;

use Feedback;
use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use PFUser;
use Project;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Tuleap\Http\Response\RedirectWithFeedbackFactory;
use Tuleap\Kanban\KanbanManager;
use Tuleap\Kanban\Service\KanbanServiceHomepageUrlBuilder;
use Tuleap\Layout\Feedback\NewFeedback;
use Tuleap\Request\DispatchablePSR15Compatible;
use Tuleap\Request\ForbiddenException;

final class CreateKanbanController extends DispatchablePSR15Compatible
{
    public function __construct(
        private RedirectWithFeedbackFactory $redirect_with_feedback_factory,
        private readonly KanbanManager $kanban_manager,
        private readonly \TrackerFactory $tracker_factory,
        EmitterInterface $emitter,
        MiddlewareInterface ...$middleware_stack,
    ) {
        parent::__construct($emitter, ...$middleware_stack);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute(PFUser::class);
        assert($user instanceof PFUser);

        $project = $request->getAttribute(Project::class);
        assert($project instanceof Project);

        $body = $request->getParsedBody();
        if (! is_array($body)) {
            throw new \LogicException("Expected body to be an associative array");
        }

        if (! isset($body['kanban-name']) || empty($body['kanban-name'])) {
            throw new ForbiddenException();
        }
        $kanban_name = $body['kanban-name'];

        if (! isset($body['tracker-kanban']) || empty($body['tracker-kanban'])) {
            throw new ForbiddenException(dgettext('tuleap-kanban', 'No tracker has been selected.'));
        }

        $tracker_id = (int) $body['tracker-kanban'];
        if (! $tracker_id) {
            throw new ForbiddenException(dgettext('tuleap-kanban', 'No tracker has been selected.'));
        }

        $tracker = $this->tracker_factory->getTrackerById($tracker_id);
        if (! $tracker || (int) $tracker->getProject()->getID() !== (int) $project->getID()) {
            throw new ForbiddenException();
        }

        if ($this->kanban_manager->doesKanbanExistForTracker($tracker)) {
            return $this->redirectToHome($user, $project, new NewFeedback(
                Feedback::ERROR,
                dgettext('tuleap-kanban', 'Tracker already used by another Kanban.')
            ));
        }

        $is_promoted = false;

        if ($this->kanban_manager->createKanban($kanban_name, $is_promoted, $tracker_id)) {
            $feedback = new NewFeedback(
                Feedback::SUCCESS,
                sprintf(dgettext('tuleap-kanban', 'Kanban %1$s successfully created.'), $kanban_name)
            );
        } else {
            $feedback = new NewFeedback(
                Feedback::ERROR,
                sprintf(dgettext('tuleap-kanban', 'Error while creating Kanban %1$s.'), $kanban_name)
            );
        }

        return $this->redirectToHome($user, $project, $feedback);
    }

    public static function getUrl(Project $project): string
    {
        return '/projects/' . urlencode($project->getUnixNameMixedCase()) . '/kanban/create';
    }

    private function redirectToHome(PFUser $user, Project $project, NewFeedback $feedback): ResponseInterface
    {
        $homeurl = KanbanServiceHomepageUrlBuilder::getUrl($project);

        return $this->redirect_with_feedback_factory->createResponseForUser($user, $homeurl, $feedback);
    }
}
