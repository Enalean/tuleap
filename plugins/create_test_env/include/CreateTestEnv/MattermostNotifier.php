<?php
/**
 * Copyright (c) Enalean, 2018-Present. All Rights Reserved.
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
use Tuleap\BotMattermost\BotMattermostLogger;
use Tuleap\BotMattermost\SenderServices\ClientBotMattermost;
use Tuleap\BotMattermost\SenderServices\EncoderMessage;
use Tuleap\BotMattermost\Bot\BotDao;

class MattermostNotifier
{
    /**
     * @var BotFactory
     */
    private $bot_factory;
    /**
     * @var Sender
     */
    private $sender;

    public function __construct()
    {
        require_once __DIR__ . '/../../../botmattermost/include/botmattermostPlugin.php';
        $this->bot_factory = new BotFactory(new BotDao());
        $this->sender      = new Sender(
            new EncoderMessage(),
            new ClientBotMattermost(),
            new BotMattermostLogger()
        );
    }

    /**
     * @param int $bot_id
     * @param string $text
     */
    public function notify($bot_id, $text)
    {
        try {
            $message = new Message();
            $message->setText($text);

            /** @psalm-suppress UndefinedDocblockClass $bot */
            $bot = $this->bot_factory->getBotById($bot_id);
            if ($bot) {
                $this->sender->pushNotification($bot, $message, []);
            }
        } catch (BotNotFoundException $exception) {
        }
    }
}
