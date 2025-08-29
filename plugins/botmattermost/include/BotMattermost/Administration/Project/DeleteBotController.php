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
use Tuleap\BotMattermost\Bot\BotDeletor;
use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\Exception\CannotDeleteBotException;
use Tuleap\Layout\BaseLayout;
use Tuleap\Request\DispatchableWithRequest;
use Tuleap\Request\ForbiddenException;
use Tuleap\Request\NotFoundException;

class DeleteBotController implements DispatchableWithRequest
{
    public function __construct(private BotFactory $bot_factory, private BotDeletor $bot_deletor)
    {
    }

    #[Override]
    public function process(HTTPRequest $request, BaseLayout $layout, array $variables)
    {
        try {
            $bot_id = $variables['bot_id'];
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

        try {
            $this->bot_deletor->deleteBot($bot);
            $layout->addFeedback(Feedback::INFO, dgettext('tuleap-botmattermost', 'Bot successfully deleted'));
        } catch (CannotDeleteBotException $exception) {
            $layout->addFeedback(Feedback::ERROR, $exception->getMessage());
        } finally {
            $layout->redirect(
                "/plugins/botmattermost/project/$project_id/admin"
            );
        }
    }
}
