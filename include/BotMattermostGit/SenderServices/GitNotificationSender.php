<?php
/**
 * Copyright (c) Enalean, 2016-2018. All Rights Reserved.
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
use Tuleap\BotMattermost\SenderServices\Message;
use Tuleap\BotMattermost\SenderServices\Sender;
use Tuleap\BotMattermostGit\BotMattermostGitNotification\Factory;

class GitNotificationSender
{

    private $sender;
    private $bot_git_factory;
    private $repository;
    private $notification_builder;

    public function __construct(
        Sender $sender,
        Factory $bot_git_factory,
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
        if ($params['is_technical_reference_update']) {
            return;
        }

        try {
            $message = new Message();

            $message->setText($this->notification_builder->buildNotificationText($params));
            if ($bot_assignment = $this->bot_git_factory->getBotNotification($this->repository->getId())) {
                $this->sender->pushNotification($bot_assignment->getBot(), $message, $bot_assignment->getChannels());
            }
        } catch (BotNotFoundException $e) {
            // Nothing to do
        }
    }
}
