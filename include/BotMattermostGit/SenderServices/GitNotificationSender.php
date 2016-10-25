<?php
/**
 * Copyright (c) Enalean, 2016. All Rights Reserved.
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

namespace Tuleap\BotMattermostGit\SenderServices;

use GitRepository;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\SenderServices\Sender;
use Tuleap\BotMattermostGit\BotGit\BotGitFactory;

class GitNotificationSender
{

    private $sender;
    private $bot_git_factory;
    private $repository;
    private $notification_builder;

    public function __construct(
        Sender $sender,
        BotGitFactory $bot_git_factory,
        GitRepository $repository,
        GitNotificationBuilder $notification_builder
    ) {
        $this->sender               = $sender;
        $this->bot_git_factory      = $bot_git_factory;
        $this->repository           = $repository;
        $this->notification_builder = $notification_builder;
    }

    public function process(array $params)
    {
        try {
            $bots = $this->bot_git_factory->getBotsByRepositoryId($this->repository->getId());
            $text = $this->notification_builder->buildNotificationText($params);
            if (! empty($bots)) {
                $this->sender->pushNotifications($bots, $text);
            }
        } catch (BotNotFoundException $e) {
            // Nothing to do
        }
    }
}
