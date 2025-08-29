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
use Tuleap\BotMattermost\Bot\BotEditor;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\BotAlreadyExistException;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\CannotUpdateBotException;
use Tuleap\BotMattermost\Exception\EmptyUpdateException;
use Tuleap\BotMattermost\Exception\ProvidedBotParameterIsNotValidException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class EditBotController implements DispatchableWithRequest
{
    public function __construct(private BotFactory $bot_factory, private BotEditor $bot_editor)
    {
    }

    #[Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        try {
            $bot_id = (int) $variables['bot_id'];
            $bot    = $this->bot_factory->getBotById($bot_id);
        } catch (BotNotFoundException $exception) {
            throw new NotFoundException(
                $exception->getMessage()
            );
        }

        $project_id = $bot->getProjectId();
        if ($project_id === null) {
            $layout->addFeedback(Feedback::ERROR, 'System bots cannot be deleted this way.');
            $layout->redirect('/');
        }

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
            $this->bot_editor->editBotById(
                $bot_id,
                trim($bot_name),
                trim($webhook_url),
                trim($avatar_url)
            );
            $layout->addFeedback(Feedback::INFO, dgettext('tuleap-botmattermost', 'Bot successfully updated'));
        } catch (CannotUpdateBotException | EmptyUpdateException | BotAlreadyExistException | ProvidedBotParameterIsNotValidException $exception) {
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
