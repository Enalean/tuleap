<?php
/**
 * Copyright (c) Enalean, 2021-Present. All Rights Reserved.
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

namespace Tuleap\BotMattermost\Administration\Project;

use CSRFSynchronizerToken;
use Feedback;
use HTTPRequest;
use Override;
use Tuleap\BotMattermost\Bot\BotCreator;
use Tuleap\BotMattermost\Exception\BotAlreadyExistException;
use Tuleap\BotMattermost\Exception\CannotCreateBotException;
use Tuleap\BotMattermost\Exception\ProvidedBotParameterIsNotValidException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;

class CreateBotController implements DispatchableWithRequest
{
    public function __construct(private BotCreator $bot_creator)
    {
    }

    #[Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        if (! $request->existAndNonEmpty('project_id')) {
            $layout->addFeedback(Feedback::ERROR, 'System bots cannot be created this way.');
            $layout->redirect('/');
        }

        $project_id = (int) $request->get('project_id');

        $user = $request->getCurrentUser();
        if (! $user->isAdmin($project_id)) {
            throw new ForbiddenException(
                'User is not project administrator.'
            );
        }

        $csrf = new CSRFSynchronizerToken("/plugins/botmattermost/project/$project_id/admin");
        $csrf->check();

        $bot_name = $request->get('bot_name');
        if ($bot_name === false) {
            $layout->addFeedback(
                Feedback::ERROR,
                'Request is not well formed, missing bot_name'
            );
            $this->redirectToBotProjectAdmin($layout, $project_id);
        }

        $webhook_url = $request->get('webhook_url');
        if ($bot_name === false) {
            $layout->addFeedback(
                Feedback::ERROR,
                'Request is not well formed, missing webhook_url'
            );
            $this->redirectToBotProjectAdmin($layout, $project_id);
        }

        $avatar_url = $request->get('avatar_url');
        if ($bot_name === false) {
            $layout->addFeedback(
                Feedback::ERROR,
                'Request is not well formed, missing avatar_url'
            );
            $this->redirectToBotProjectAdmin($layout, $project_id);
        }

        try {
            $this->bot_creator->createProjectBot(
                trim($bot_name),
                trim($webhook_url),
                trim($avatar_url),
                $project_id
            );
            $layout->addFeedback(Feedback::INFO, dgettext('tuleap-botmattermost', 'Bot successfully created'));
        } catch (CannotCreateBotException | ProvidedBotParameterIsNotValidException | BotAlreadyExistException $exception) {
            $layout->addFeedback(Feedback::ERROR, $exception->getMessage());
        } finally {
            $this->redirectToBotProjectAdmin($layout, $project_id);
        }
    }

    private function redirectToBotProjectAdmin(BaseLayout $layout, int $project_id): void
    {
        $layout->redirect(
            "/plugins/botmattermost/project/$project_id/admin"
        );
    }
}
