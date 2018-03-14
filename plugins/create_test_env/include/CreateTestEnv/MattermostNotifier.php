<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 *
 */

namespace Tuleap\CreateTestEnv;

use Tuleap\BotMattermost\Bot\BotFactory;
use Tuleap\BotMattermost\Exception\BotNotFoundException;
use Tuleap\BotMattermost\SenderServices\Message;
use Tuleap\BotMattermost\SenderServices\Sender;

class MattermostNotifier
{
    /**
     * @var BotFactory
     */
    private $bot_factory;
    /**
     * @var NotificationBotDao
     */
    private $notification_bot_dao;
    /**
     * @var Sender
     */
    private $sender;

    public function __construct(BotFactory $bot_factory, NotificationBotDao $notification_bot_dao, Sender $sender)
    {
        $this->bot_factory          = $bot_factory;
        $this->notification_bot_dao = $notification_bot_dao;
        $this->sender               = $sender;
    }

    /**
     * @param $text
     */
    public function notify($text)
    {
        try {
            $message = new Message();
            $message->setText($text);

            $bot = $this->getBot();
            if ($bot) {
                $this->sender->pushNotification($bot, $message, []);
            }
        } catch (BotNotFoundException $exception) {
        }
    }

    /**
     * @return \Tuleap\BotMattermost\Bot\Bot
     * @throws BotNotFoundException
     */
    private function getBot()
    {
        $bot_id = (int) $this->notification_bot_dao->get();
        if ($bot_id > 0) {
            return $this->bot_factory->getBotById($bot_id);
        }
    }
}
